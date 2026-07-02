<?php

namespace App\Services;

use App\Models\EmployeeSchedule;
use App\Models\homeAttendance;
use App\Models\User;
use App\Services\Concerns\CreatesImportBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-import employee schedules. One input row = an employee + a date range + optional
 * weekday filter + shift times, expanded into one employee_schedules row per matching day
 * (mirrors EmployeeScheduleController::store(), including overnight end-date handling and the
 * time-overlap rule). All-or-nothing: any bad/overlapping row aborts the whole import.
 */
class ScheduleImportService
{
    use CreatesImportBatch;

    // 0-based column positions (must match the import template order)
    // Col 1 = Employee Name (display-only, ignored during import)
    private const C = [
        'employee_id' => 0, 'sched_start_date' => 2, 'sched_end_date' => 3, 'sched_in' => 4,
        'sched_out' => 5, 'break_start' => 6, 'break_end' => 7, 'shift_type' => 8, 'days' => 9,
    ];

    // Carbon format('D') tokens, used for the optional weekday filter.
    private const DOW = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    private array $empIdSet = [];

    public function import(array $rows, ?string $filename = null): array
    {
        $result = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [], 'aborted' => false, 'batch_id' => null];
        $this->preload();
        $start = (!empty($rows) && stripos($this->cell($rows[0], 'employee_id'), 'employee') !== false) ? 1 : 0;

        // ── Phase 1: validate + expand every row to per-day payloads (no DB writes). ──
        $prepared = [];
        $seen = []; // "empID|date" => list of [in, out] windows already taken in this file
        for ($i = $start; $i < count($rows); $i++) {
            $row = $rows[$i];
            $lineNo = $i + 1;
            if ($this->isBlankRow($row)) { continue; }
            try {
                $spec = $this->validateRow($row);
                $days = $this->expand($spec, $lineNo, $seen); // throws on any problem
                foreach ($days as $p) { $prepared[] = $p; }
            } catch (\Throwable $e) {
                $result['errors'][] = "Row {$lineNo}: " . $e->getMessage();
            }
        }

        // Reject expanded days that overlap a schedule already in the DB (single bulk query).
        $this->flagExisting($prepared, $result);

        // Reject days the employee has already clocked attendance for — that schedule is
        // locked (same rule as the Scheduler UI); overwriting/adding a shift there would
        // diverge the recorded punch.
        $this->flagAttended($prepared, $result);

        // ── All-or-nothing: a single bad row aborts the whole import so data stays accurate. ──
        if (!empty($result['errors'])) {
            $result['aborted'] = true;
            $result['skipped'] = count($prepared) + count($result['errors']);
            return $result;
        }

        if (empty($prepared)) { return $result; }

        // ── Phase 2: persist everything in one transaction (rolls back if anything fails). ──
        DB::transaction(function () use (&$result, $prepared, $filename) {
            $starts = array_column($prepared, 'sched_start_date');
            $ends   = array_column($prepared, 'sched_end_date');
            $batch = $this->createImportBatch(
                'schedule', $filename, count($prepared),
                $starts ? min($starts) : null, $ends ? max($ends) : null
            );

            foreach ($prepared as $p) {
                EmployeeSchedule::create([
                    'employee_id'      => $p['employee_id'],
                    'sched_start_date' => $p['sched_start_date'],
                    'sched_end_date'   => $p['sched_end_date'],
                    'sched_in'         => $p['sched_in'],
                    'sched_out'        => $p['sched_out'],
                    'break_start'      => $p['break_start'],
                    'break_end'        => $p['break_end'],
                    'shift_type'       => $p['shift_type'],
                    'import_batch_id'  => $batch->id,
                ]);
                $result['inserted']++;
            }

            $batch->update(['inserted' => $result['inserted']]);
            $result['batch_id'] = $batch->id;
        });

        return $result;
    }

    /** Load all employee IDs once so row validation never hits the DB. */
    private function preload(): void
    {
        $this->empIdSet = User::whereNotNull('empID')->where('empID', '!=', '')->pluck('empID')->flip()->all();
    }

    /** Validate one row into a schedule spec (throws on any problem, writes nothing). */
    private function validateRow(array $row): array
    {
        $empID = trim($this->cell($row, 'employee_id'));
        if ($empID === '') { throw new \Exception('Employee ID is required.'); }
        if (!isset($this->empIdSet[$empID])) { throw new \Exception("Employee ID '{$empID}' not found."); }

        $startStr = $this->parseDate($this->cell($row, 'sched_start_date'));
        if (!$startStr) { throw new \Exception('Invalid or missing Start Date (YYYY-MM-DD).'); }
        $endStr = $this->parseDate($this->cell($row, 'sched_end_date')) ?: $startStr;
        if (Carbon::parse($endStr)->lt(Carbon::parse($startStr))) {
            throw new \Exception('End Date is before Start Date.');
        }

        $schedIn  = $this->normTime($this->cell($row, 'sched_in'));
        $schedOut = $this->normTime($this->cell($row, 'sched_out'));
        if (!$schedIn || !$schedOut) { throw new \Exception('Schedule In and Schedule Out are required (HH:MM).'); }

        $breakStart = $this->normTime($this->cell($row, 'break_start'));
        $breakEnd   = $this->normTime($this->cell($row, 'break_end'));
        // R1 (break required) + R2 (shift − break must net exactly 8h).
        if ($netError = \App\Models\EmployeeSchedule::netValidationError($schedIn, $schedOut, $breakStart, $breakEnd)) {
            throw new \Exception($netError);
        }

        $shiftType = trim($this->cell($row, 'shift_type')) ?: null;
        $days = $this->parseDays($this->cell($row, 'days')); // [] = every day in range

        return [
            'employee_id' => $empID, 'start' => $startStr, 'end' => $endStr,
            'sched_in' => $schedIn, 'sched_out' => $schedOut,
            'break_start' => $breakStart, 'break_end' => $breakEnd,
            'shift_type' => $shiftType, 'days' => $days,
        ];
    }

    /** Expand a spec's date range (honouring the weekday filter) into per-day payloads. */
    private function expand(array $spec, int $lineNo, array &$seen): array
    {
        $out = [];
        $cursor = Carbon::parse($spec['start']);
        $end    = Carbon::parse($spec['end']);
        $overnight = $this->minutes($spec['sched_out']) <= $this->minutes($spec['sched_in']);

        for ($date = $cursor->copy(); $date->lte($end); $date->addDay()) {
            if (!empty($spec['days']) && !in_array($date->format('D'), $spec['days'], true)) {
                continue;
            }
            $dateStr = $date->toDateString();
            $endDateStr = $overnight ? $date->copy()->addDay()->toDateString() : $dateStr;

            // In-file overlap: same employee + day with an intersecting time window.
            $key = $spec['employee_id'] . '|' . $dateStr;
            foreach ($seen[$key] ?? [] as [$exIn, $exOut]) {
                if ($this->timeOverlap($exIn, $exOut, $spec['sched_in'], $spec['sched_out'])) {
                    throw new \Exception("Schedule on {$dateStr} overlaps another row in this file (same employee).");
                }
            }
            $seen[$key][] = [$spec['sched_in'], $spec['sched_out']];

            $out[] = [
                'employee_id' => $spec['employee_id'], 'sched_start_date' => $dateStr,
                'sched_end_date' => $endDateStr, 'sched_in' => $spec['sched_in'], 'sched_out' => $spec['sched_out'],
                'break_start' => $spec['break_start'], 'break_end' => $spec['break_end'],
                'shift_type' => $spec['shift_type'], '_line' => $lineNo,
            ];
        }

        if (empty($out)) {
            throw new \Exception('No days matched the date range / weekday filter.');
        }
        return $out;
    }

    /** Bulk-check expanded days against existing schedules; move overlaps to errors. */
    private function flagExisting(array &$prepared, array &$result): void
    {
        if (empty($prepared)) { return; }
        $empIds  = array_values(array_unique(array_column($prepared, 'employee_id')));
        $starts  = array_column($prepared, 'sched_start_date');
        $ends    = array_column($prepared, 'sched_end_date');
        $minDate = min($starts);
        $maxDate = max($ends);

        // existing schedules for these employees that could cover any imported day
        $existing = [];
        foreach (EmployeeSchedule::whereIn('employee_id', $empIds)
                     ->whereDate('sched_start_date', '<=', $maxDate)
                     ->whereDate('sched_end_date', '>=', $minDate)
                     ->get(['employee_id', 'sched_start_date', 'sched_end_date', 'sched_in', 'sched_out']) as $e) {
            $existing[$e->employee_id][] = [
                'start' => Carbon::parse($e->sched_start_date)->toDateString(),
                'end'   => Carbon::parse($e->sched_end_date)->toDateString(),
                'in'    => substr((string) $e->sched_in, 0, 5),
                'out'   => substr((string) $e->sched_out, 0, 5),
            ];
        }

        $kept = [];
        foreach ($prepared as $p) {
            $clash = false;
            foreach ($existing[$p['employee_id']] ?? [] as $e) {
                if ($p['sched_start_date'] >= $e['start'] && $p['sched_start_date'] <= $e['end']
                    && $this->timeOverlap($e['in'], $e['out'], $p['sched_in'], $p['sched_out'])) {
                    $clash = true;
                    break;
                }
            }
            if ($clash) {
                $result['errors'][] = "Row {$p['_line']}: Schedule on {$p['sched_start_date']} overlaps an existing schedule for {$p['employee_id']}.";
            } else {
                $kept[] = $p;
            }
        }
        $prepared = $kept;
    }

    /** Bulk-reject days the employee already has attendance for (schedule is locked). */
    private function flagAttended(array &$prepared, array &$result): void
    {
        if (empty($prepared)) { return; }
        $empIds = array_values(array_unique(array_column($prepared, 'employee_id')));
        $dates  = array_values(array_unique(array_column($prepared, 'sched_start_date')));

        $attended = [];
        foreach (homeAttendance::whereIn('employee_id', $empIds)
                     ->whereIn('attendance_date', $dates)
                     ->get(['employee_id', 'attendance_date']) as $a) {
            $attended[$a->employee_id . '|' . Carbon::parse($a->attendance_date)->toDateString()] = true;
        }

        $kept = [];
        foreach ($prepared as $p) {
            if (isset($attended[$p['employee_id'] . '|' . $p['sched_start_date']])) {
                $result['errors'][] = "Row {$p['_line']}: {$p['employee_id']} already has attendance on {$p['sched_start_date']}; that schedule is locked and can't be imported.";
            } else {
                $kept[] = $p;
            }
        }
        $prepared = $kept;
    }

    /**
     * Time-of-day overlap, matching EmployeeScheduleController::store(): two windows clash when
     * the existing IN is before the new OUT and the existing OUT is after the new IN.
     * 'HH:MM' strings compare chronologically, so plain string comparison is correct.
     */
    private function timeOverlap(string $aIn, string $aOut, string $bIn, string $bOut): bool
    {
        return $aIn < $bOut && $aOut > $bIn;
    }

    /** Parse the optional comma-separated weekday filter into Carbon format('D') tokens. */
    private function parseDays(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') { return []; }
        $out = [];
        foreach (preg_split('/[,;\/]+/', $raw) as $tok) {
            $tok = trim($tok);
            if ($tok === '') { continue; }
            $norm = ucfirst(strtolower(substr($tok, 0, 3))); // "monday"/"MON" -> "Mon"
            if (!in_array($norm, self::DOW, true)) {
                throw new \Exception("Invalid day '{$tok}' (use Mon, Tue, Wed, Thu, Fri, Sat, Sun).");
            }
            $out[$norm] = $norm;
        }
        return array_values($out);
    }

    // ── helpers ──
    private function cell(array $row, string $key): string
    {
        $idx = self::C[$key];
        return isset($row[$idx]) ? (string) $row[$idx] : '';
    }

    private function isBlankRow(array $row): bool
    {
        // Skip rows where no schedule data is present (cols 2+), even if Employee ID/Name are filled
        $scheduleCells = array_slice($row, 2);
        foreach ($scheduleCells as $v) {
            if (trim((string) $v) !== '') { return false; }
        }
        return true;
    }

    private function parseDate(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') { return null; }
        try { return Carbon::parse($v)->toDateString(); } catch (\Throwable $e) { return null; }
    }

    /** Normalise a time value to "HH:MM" (24h), or null. */
    private function normTime(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') { return null; }
        if (is_numeric($v) && (float) $v < 1) { // Excel time as a fraction of a day
            $mins = (int) round(((float) $v) * 1440);
            return sprintf('%02d:%02d', intdiv($mins, 60), $mins % 60);
        }
        try { return Carbon::parse($v)->format('H:i'); } catch (\Throwable $e) { return null; }
    }

    private function minutes(?string $hhmm): int
    {
        if (!$hhmm) { return 0; }
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        return $h * 60 + $m;
    }
}
