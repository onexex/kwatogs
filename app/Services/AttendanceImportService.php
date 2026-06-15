<?php

namespace App\Services;

use App\Models\AttendanceSummary;
use App\Models\EmployeeSchedule;
use App\Models\homeAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceImportService
{
    // 0-based column positions (must match the import template order)
    private const C = [
        'employee_id' => 0, 'name' => 1, 'date' => 2, 'sched_in' => 3, 'break_start' => 4,
        'break_end' => 5, 'sched_out' => 6, 'shift_type' => 7, 'time_in' => 8, 'time_out' => 9,
        'status' => 10, 'total_hours' => 11, 'mins_late' => 12, 'mins_undertime' => 13,
        'mins_night_diff' => 14, 'over_break' => 15, 'outpass' => 16, 'remarks' => 17,
    ];

    private const STATUSES = ['present', 'ob', 'leave', 'absent'];

    public function import(array $rows): array
    {
        $result = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        // Skip the header row if present
        $start = (!empty($rows) && stripos($this->cell($rows[0], 'employee_id'), 'employee') !== false) ? 1 : 0;

        for ($i = $start; $i < count($rows); $i++) {
            $row = $rows[$i];
            $lineNo = $i + 1;
            if ($this->isBlankRow($row)) { continue; }
            try {
                $created = $this->processRow($row);
                $created ? $result['inserted']++ : $result['updated']++;
            } catch (\Throwable $e) {
                $result['skipped']++;
                $result['errors'][] = "Row {$lineNo}: " . $e->getMessage();
            }
        }
        return $result;
    }

    private function processRow(array $row): bool
    {
        $empID = $this->resolveEmpID(trim($this->cell($row, 'employee_id')));

        $dateStr = $this->parseDate($this->cell($row, 'date'));
        if (!$dateStr) { throw new \Exception('Invalid or missing Date (use YYYY-MM-DD).'); }

        $schedIn  = $this->normTime($this->cell($row, 'sched_in'));
        $schedOut = $this->normTime($this->cell($row, 'sched_out'));
        if (!$schedIn || !$schedOut) { throw new \Exception('Schedule In and Schedule Out are required (HH:MM).'); }

        $breakStart = $this->normTime($this->cell($row, 'break_start'));
        $breakEnd   = $this->normTime($this->cell($row, 'break_end'));
        $shiftType  = trim($this->cell($row, 'shift_type')) ?: null;

        $status = strtolower(trim($this->cell($row, 'status'))) ?: 'present';
        if (!in_array($status, self::STATUSES, true)) {
            throw new \Exception("Status must be one of: " . implode(', ', self::STATUSES) . ".");
        }

        $timeIn  = $this->normTime($this->cell($row, 'time_in'));
        $timeOut = $this->normTime($this->cell($row, 'time_out'));
        $remarks = trim($this->cell($row, 'remarks')) ?: null;

        // overnight schedule -> end date next day
        $endDateStr = $this->minutes($schedOut) <= $this->minutes($schedIn)
            ? Carbon::parse($dateStr)->addDay()->toDateString()
            : $dateStr;

        // ── computed metrics (used when the Excel leaves them blank) ──
        $m = $this->computeMetrics($status, $schedIn, $schedOut, $timeIn, $timeOut, $breakStart, $breakEnd);

        $totalHours = $this->numOr($this->cell($row, 'total_hours'), $m['total_hours']);
        $minsLate   = (int) $this->numOr($this->cell($row, 'mins_late'), $m['mins_late']);
        $minsUt     = (int) $this->numOr($this->cell($row, 'mins_undertime'), $m['mins_undertime']);
        $minsNd     = (int) $this->numOr($this->cell($row, 'mins_night_diff'), $m['mins_night_diff']);
        $overBreak  = (int) $this->numOr($this->cell($row, 'over_break'), 0);
        $outpass    = (int) $this->numOr($this->cell($row, 'outpass'), 0);

        // timestamps for the home attendance log
        $timeInTs = $timeIn ? ($dateStr . ' ' . $timeIn . ':00') : null;
        $timeOutTs = null;
        if ($timeOut) {
            $outDate = ($timeIn && $this->minutes($timeOut) < $this->minutes($timeIn)) ? $endDateStr : $dateStr;
            $timeOutTs = $outDate . ' ' . $timeOut . ':00';
        }

        return DB::transaction(function () use (
            $empID, $dateStr, $endDateStr, $schedIn, $schedOut, $breakStart, $breakEnd, $shiftType,
            $timeInTs, $timeOutTs, $totalHours, $minsLate, $minsUt, $minsNd, $overBreak, $outpass, $status, $remarks
        ) {
            // 1) schedule (prerequisite)
            $sched = EmployeeSchedule::updateOrCreate(
                ['employee_id' => $empID, 'sched_start_date' => $dateStr],
                [
                    'sched_in' => $schedIn, 'sched_out' => $schedOut, 'sched_end_date' => $endDateStr,
                    'break_start' => $breakStart, 'break_end' => $breakEnd, 'shift_type' => $shiftType,
                ]
            );

            // 2) home attendance (actual log)
            homeAttendance::updateOrCreate(
                ['employee_id' => $empID, 'attendance_date' => $dateStr],
                [
                    'schedule_id' => $sched->id, 'time_in' => $timeInTs, 'time_out' => $timeOutTs,
                    'duration_hours' => $totalHours, 'night_diff_hours' => round($minsNd / 60, 2),
                    'status' => $status, 'remarks' => $remarks,
                ]
            );

            // 3) attendance summary (per-day rollup, unique employee+date)
            $summary = AttendanceSummary::updateOrCreate(
                ['employee_id' => $empID, 'attendance_date' => $dateStr],
                [
                    'total_hours' => $totalHours, 'mins_late' => $minsLate, 'mins_undertime' => $minsUt,
                    'mins_night_diff' => $minsNd, 'over_break_minutes' => $overBreak, 'outpass_minutes' => $outpass,
                    'status' => $status, 'remarks' => $remarks,
                ]
            );

            return $summary->wasRecentlyCreated;
        });
    }

    // ── metric computation ──────────────────────────────────────────────
    private function computeMetrics($status, $schedIn, $schedOut, $timeIn, $timeOut, $breakStart, $breakEnd): array
    {
        $zero = ['total_hours' => 0, 'mins_late' => 0, 'mins_undertime' => 0, 'mins_night_diff' => 0];
        if (in_array($status, ['absent', 'leave'], true) || !$timeIn || !$timeOut) {
            return $zero;
        }

        $sIn = $this->minutes($schedIn);
        $sOut = $this->minutes($schedOut);
        if ($sOut <= $sIn) { $sOut += 1440; } // overnight schedule

        $aIn = $this->minutes($timeIn);
        $aOut = $this->minutes($timeOut);
        if ($aOut < $aIn) { $aOut += 1440; } // crossed midnight

        $breakMins = 0;
        if ($breakStart && $breakEnd) {
            $bs = $this->minutes($breakStart);
            $be = $this->minutes($breakEnd);
            if ($be > $bs) { $breakMins = $be - $bs; }
        }

        // Clamp worked time to the schedule (don't credit early-in / late-out — that's OT)
        $inC = max($aIn, $sIn);
        $outC = min($aOut, $sOut);
        $worked = max(0, ($outC - $inC) - $breakMins);
        $late = max(0, $aIn - $sIn);
        $undertime = max(0, $sOut - $aOut);

        // Night differential window = 10 PM – 6 AM (00:00–06:00 plus 22:00–next 06:00).
        $night = $this->overlap($inC, $outC, 0, 360) + $this->overlap($inC, $outC, 1320, 1800);

        // Company rule: a break that falls inside the night window is NOT paid as ND,
        // so subtract the portion of the break that overlaps 10 PM – 6 AM.
        // e.g. 6pm–6am with a 3-hr night break = 8 − 3 = 5 hrs ND; a 1-hr break at 11pm = −1 hr.
        if ($breakStart && $breakEnd) {
            $bs = $this->minutes($breakStart);
            $be = $this->minutes($breakEnd);
            if ($be > $bs) {
                $night -= $this->overlap($bs, $be, 0, 360) + $this->overlap($bs, $be, 1320, 1800);
            }
        }
        $night = max(0, $night);

        return [
            'total_hours' => round($worked / 60, 2),
            'mins_late' => $late,
            'mins_undertime' => $undertime,
            'mins_night_diff' => $night,
        ];
    }

    private function overlap(int $aStart, int $aEnd, int $bStart, int $bEnd): int
    {
        return max(0, min($aEnd, $bEnd) - max($aStart, $bStart));
    }

    // ── employee resolution (empID, or by "LASTNAME, FIRSTNAME") ──────────
    private ?array $userIndex = null;

    private function resolveEmpID(string $value): string
    {
        if ($value === '') { throw new \Exception('Employee ID / name is required.'); }

        // exact empID match first
        if (User::where('empID', $value)->exists()) { return $value; }

        // fall back to name match
        $this->buildUserIndex();
        $parts = explode(',', $value, 2);
        if (count($parts) === 2) {
            $last = $this->nrm($parts[0]);
            $first = $this->nrm($parts[1]);
        } else {
            // "FIRST ... LAST" — assume last token is the surname
            $tok = preg_split('/\\s+/', $this->nrm($value));
            $last = array_pop($tok) ?: '';
            $first = implode(' ', $tok);
        }
        $firstTok = $first === '' ? '' : explode(' ', $first)[0];

        $full = $last . '||' . $first;
        if (!empty($this->userIndex[$full]) && count($this->userIndex[$full]) === 1) {
            return $this->userIndex[$full][0];
        }
        $key = $last . '|' . $firstTok;
        if (!empty($this->userIndex[$key])) {
            $ids = array_values(array_unique($this->userIndex[$key]));
            if (count($ids) === 1) { return $ids[0]; }
            throw new \Exception("Name '{$value}' matches multiple employees; use the Employee ID.");
        }
        throw new \Exception("Employee '{$value}' not found (no empID or name match).");
    }

    private function buildUserIndex(): void
    {
        if ($this->userIndex !== null) { return; }
        $this->userIndex = [];
        foreach (User::select('empID', 'fname', 'lname')->get() as $u) {
            if (!$u->empID) { continue; }
            $last = $this->nrm($u->lname);
            $first = $this->nrm($u->fname);
            $firstTok = $first === '' ? '' : explode(' ', $first)[0];
            $this->userIndex[$last . '|' . $firstTok][] = $u->empID;
            $this->userIndex[$last . '||' . $first][] = $u->empID;
        }
    }

    /** Uppercase, strip accents, drop punctuation, collapse spaces. */
    private function nrm($v): string
    {
        $v = (string) $v;
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $v);
        if ($t !== false && $t !== '') { $v = $t; }
        $v = strtoupper($v);
        $v = preg_replace('/[^A-Z0-9 ]/', ' ', $v);
        return trim(preg_replace('/\\s+/', ' ', $v));
    }

    // ── helpers ─────────────────────────────────────────────────────────
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

    /** Normalise a time value to "HH:MM" (24h), or null. */
    private function normTime(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') { return null; }
        // Excel may hand back a fraction of a day for a time cell
        if (is_numeric($v) && (float) $v < 1) {
            $mins = (int) round(((float) $v) * 1440);
            return sprintf('%02d:%02d', intdiv($mins, 60), $mins % 60);
        }
        try {
            return Carbon::parse($v)->format('H:i');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function minutes(?string $hhmm): int
    {
        if (!$hhmm) { return 0; }
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        return $h * 60 + $m;
    }

    /** Return the numeric value of $v if non-blank, else the fallback. */
    private function numOr(string $v, $fallback)
    {
        $v = trim($v);
        return ($v === '' || !is_numeric($v)) ? $fallback : (float) $v;
    }
}
