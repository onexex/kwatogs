<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\leavetype;
use App\Models\User;
use App\Services\Concerns\CreatesImportBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveImportService
{
    use CreatesImportBatch;

    // 0-based column positions (must match the leave import template order)
    private const C = [
        'employee_id' => 0, 'name' => 1, 'leave_type' => 2, 'start_date' => 3, 'end_date' => 4,
        'leave_kind' => 5, 'half_day' => 6, 'reason' => 7, 'status' => 8, 'hours_per_day' => 9,
    ];

    private const STATUSES = ['FORAPPROVAL', 'CANCELED', 'APPROVED', 'APPROVEDBYCFO', 'DISAPPROVED'];

    // Reference data preloaded once per import to avoid per-row queries.
    private array $empIdSet = [];
    private array $leaveTypeById = [];
    private array $leaveTypeByName = [];

    public function __construct(private ?int $approverUserId = null) {}

    public function import(array $rows, ?string $filename = null): array
    {
        $result = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [], 'aborted' => false, 'batch_id' => null];
        $this->preload();
        $start = (!empty($rows) && stripos($this->cell($rows[0], 'employee_id'), 'employee') !== false) ? 1 : 0;

        // ── Phase 1: validate every row up-front (no DB writes) and catch in-file duplicates. ──
        $prepared = [];
        $seen = [];
        for ($i = $start; $i < count($rows); $i++) {
            $row = $rows[$i];
            $lineNo = $i + 1;
            if ($this->isBlankRow($row)) { continue; }
            try {
                $data = $this->validateRow($row);
                $key = $data['employee_id'] . '|' . $data['start_date'] . '|' . $data['end_date'] . '|' . $data['leave_type'];
                if (isset($seen[$key])) {
                    throw new \Exception("Duplicate of row {$seen[$key]} (same employee, dates and leave type).");
                }
                $seen[$key] = $lineNo;
                $data['_line'] = $lineNo;
                $data['_key'] = $key;
                $prepared[] = $data;
            } catch (\Throwable $e) {
                $result['errors'][] = "Row {$lineNo}: " . $e->getMessage();
            }
        }

        // Reject rows that already exist in the DB (single bulk query, not one per row).
        $this->flagExisting($prepared, $result);

        // ── All-or-nothing: a single bad row aborts the whole import so data stays accurate. ──
        if (!empty($result['errors'])) {
            $result['aborted'] = true;
            $result['skipped'] = count($prepared) + count($result['errors']);
            return $result;
        }

        // ── Phase 2: persist everything in one transaction (rolls back if anything fails). ──
        // Tag every row with a batch id so the import can be rolled back as a unit later.
        DB::transaction(function () use (&$result, $prepared, $filename) {
            $starts = array_column($prepared, 'start_date');
            $ends   = array_column($prepared, 'end_date');
            $batch = $this->createImportBatch(
                'leave', $filename, count($prepared),
                $starts ? min($starts) : null, $ends ? max($ends) : null
            );

            foreach ($prepared as $data) {
                $this->persist($data, $batch->id) ? $result['inserted']++ : $result['updated']++;
            }

            $batch->update(['inserted' => $result['inserted'], 'updated' => $result['updated']]);
            $result['batch_id'] = $batch->id;
        });

        return $result;
    }

    /** Load employees and leave types once so row validation never hits the DB. */
    private function preload(): void
    {
        $this->empIdSet = User::whereNotNull('empID')->pluck('empID')->flip()->all();
        foreach (leavetype::all(['id', 'type_leave']) as $t) {
            $this->leaveTypeById[(int) $t->id] = (int) $t->id;
            $this->leaveTypeByName[strtolower(trim($t->type_leave))] = (int) $t->id;
        }
    }

    /** Bulk-check the prepared payloads against existing Leave rows; move collisions to errors. */
    private function flagExisting(array &$prepared, array &$result): void
    {
        if (empty($prepared)) { return; }
        $empIds = array_values(array_unique(array_column($prepared, 'employee_id')));
        $starts = array_values(array_unique(array_column($prepared, 'start_date')));

        $existing = [];
        foreach (Leave::whereIn('employee_id', $empIds)->whereIn('start_date', $starts)
                     ->get(['employee_id', 'start_date', 'end_date', 'leave_type']) as $l) {
            $k = $l->employee_id . '|' . Carbon::parse($l->start_date)->toDateString() . '|'
                . Carbon::parse($l->end_date)->toDateString() . '|' . $l->leave_type;
            $existing[$k] = true;
        }

        $kept = [];
        foreach ($prepared as $d) {
            if (isset($existing[$d['_key']])) {
                $result['errors'][] = "Row {$d['_line']}: A leave already exists for this employee, dates and leave type.";
            } else {
                $kept[] = $d;
            }
        }
        $prepared = $kept;
    }

    /** Validate + normalise one row into a payload (throws on any problem, writes nothing). */
    private function validateRow(array $row): array
    {
        $empID = trim($this->cell($row, 'employee_id'));
        if ($empID === '') { throw new \Exception('Employee ID is required.'); }
        if (!isset($this->empIdSet[$empID])) {
            throw new \Exception("Employee ID '{$empID}' not found.");
        }

        // resolve leave type (accepts the type name or a numeric id)
        $typeRaw = trim($this->cell($row, 'leave_type'));
        if ($typeRaw === '') { throw new \Exception('Leave Type is required.'); }
        $leaveTypeId = is_numeric($typeRaw)
            ? ($this->leaveTypeById[(int) $typeRaw] ?? null)
            : ($this->leaveTypeByName[strtolower($typeRaw)] ?? null);
        if (!$leaveTypeId) { throw new \Exception("Leave Type '{$typeRaw}' not found."); }

        $startStr = $this->parseDate($this->cell($row, 'start_date'));
        $endStr   = $this->parseDate($this->cell($row, 'end_date'));
        if (!$startStr) { throw new \Exception('Invalid or missing Start Date (YYYY-MM-DD).'); }
        if (!$endStr) { $endStr = $startStr; }
        if (Carbon::parse($endStr)->lt(Carbon::parse($startStr))) {
            throw new \Exception('End Date is before Start Date.');
        }

        $leaveKind = $this->parseKind($this->cell($row, 'leave_kind')); // 0 = Paid, 1 = UnPaid
        $halfDay   = $this->parseBool($this->cell($row, 'half_day'));
        $reason    = trim($this->cell($row, 'reason')) ?: 'Imported leave';

        $status = strtoupper(trim($this->cell($row, 'status'))) ?: 'APPROVEDBYCFO';
        if (!in_array($status, self::STATUSES, true)) {
            throw new \Exception("Status '{$status}' is invalid. Allowed: " . implode(', ', self::STATUSES) . '.');
        }

        $singleDay = ($startStr === $endStr);
        $defHours = ($halfDay && $singleDay) ? 4 : 8;
        $hoursPerDay = (float) $this->numOr($this->cell($row, 'hours_per_day'), $defHours);

        $days = Carbon::parse($startStr)->diffInDays(Carbon::parse($endStr)) + 1;
        $totalHrs = $hoursPerDay * $days;

        $approvedAt = in_array($status, ['APPROVED', 'APPROVEDBYCFO'], true) ? now() : null;

        return [
            'employee_id' => $empID, 'start_date' => $startStr, 'end_date' => $endStr,
            'leave_type' => $leaveTypeId, 'total_hrs' => $totalHrs, 'reason' => $reason,
            'status' => $status, 'is_half_day' => $halfDay, 'leave_kind' => $leaveKind,
            'hours_per_day' => $hoursPerDay, 'approved_at' => $approvedAt,
        ];
    }

    /** Persist one validated payload. Runs inside the caller's transaction. */
    private function persist(array $d, ?int $batchId = null): bool
    {
        $leave = Leave::updateOrCreate(
            ['employee_id' => $d['employee_id'], 'start_date' => $d['start_date'], 'end_date' => $d['end_date'], 'leave_type' => $d['leave_type']],
            [
                'total_hrs' => $d['total_hrs'], 'reason' => $d['reason'], 'status' => $d['status'],
                'is_half_day' => $d['is_half_day'], 'leave_kind' => $d['leave_kind'],
                'approved_by' => $d['approved_at'] ? $this->approverUserId : null, 'approved_at' => $d['approved_at'],
                'import_batch_id' => $batchId,
            ]
        );
        $created = $leave->wasRecentlyCreated;

        // rebuild per-day details
        LeaveDetail::where('leave_id', $leave->id)->delete();
        $day = Carbon::parse($d['start_date']);
        $end = Carbon::parse($d['end_date']);
        while ($day->lte($end)) {
            LeaveDetail::create([
                'employee_id' => $d['employee_id'],
                'leave_id' => $leave->id,
                'leavetype_id' => $d['leave_type'],
                'date' => $day->format('Y-m-d'),
                'leave_kind' => $d['leave_kind'],
                'total_hours' => $d['hours_per_day'],
                'status' => $d['status'],
                'import_batch_id' => $batchId,
            ]);
            $day->addDay();
        }

        return $created;
    }

    // ── helpers ──
    private function cell(array $row, string $key): string
    {
        $idx = self::C[$key];
        return isset($row[$idx]) ? (string) $row[$idx] : '';
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $v) { if (trim((string) $v) !== '') { return false; } }
        return true;
    }

    private function parseDate(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') { return null; }
        try { return Carbon::parse($v)->toDateString(); } catch (\Throwable $e) { return null; }
    }

    /** Paid -> 0, UnPaid -> 1. */
    private function parseKind(string $v): int
    {
        $v = strtolower(trim($v));
        if ($v === '' ) { return 0; }
        if (in_array($v, ['1', 'unpaid', 'un-paid', 'no'], true)) { return 1; }
        return 0; // paid / 0 / default
    }

    private function parseBool(string $v): bool
    {
        return in_array(strtolower(trim($v)), ['1', 'true', 'yes', 'y', 'half'], true);
    }

    private function numOr(string $v, $fallback)
    {
        $v = trim($v);
        return ($v === '' || !is_numeric($v)) ? $fallback : (float) $v;
    }
}
