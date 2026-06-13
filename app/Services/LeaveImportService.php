<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\leavetype;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveImportService
{
    // 0-based column positions (must match the leave import template order)
    private const C = [
        'employee_id' => 0, 'name' => 1, 'leave_type' => 2, 'start_date' => 3, 'end_date' => 4,
        'leave_kind' => 5, 'half_day' => 6, 'reason' => 7, 'status' => 8, 'hours_per_day' => 9,
    ];

    private const STATUSES = ['FORAPPROVAL', 'CANCELED', 'APPROVED', 'APPROVEDBYCFO', 'DISAPPROVED'];

    public function __construct(private ?int $approverUserId = null) {}

    public function import(array $rows): array
    {
        $result = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
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
        $empID = trim($this->cell($row, 'employee_id'));
        if ($empID === '') { throw new \Exception('Employee ID is required.'); }
        if (!User::where('empID', $empID)->exists()) {
            throw new \Exception("Employee ID '{$empID}' not found.");
        }

        // resolve leave type (accepts the type name or a numeric id)
        $typeRaw = trim($this->cell($row, 'leave_type'));
        if ($typeRaw === '') { throw new \Exception('Leave Type is required.'); }
        $type = is_numeric($typeRaw)
            ? leavetype::find((int) $typeRaw)
            : leavetype::whereRaw('LOWER(type_leave) = ?', [strtolower($typeRaw)])->first();
        if (!$type) { throw new \Exception("Leave Type '{$typeRaw}' not found."); }
        $leaveTypeId = $type->id;

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

        return DB::transaction(function () use (
            $empID, $startStr, $endStr, $leaveTypeId, $totalHrs, $reason, $status, $halfDay,
            $leaveKind, $hoursPerDay, $approvedAt
        ) {
            $leave = Leave::updateOrCreate(
                ['employee_id' => $empID, 'start_date' => $startStr, 'end_date' => $endStr, 'leave_type' => $leaveTypeId],
                [
                    'total_hrs' => $totalHrs, 'reason' => $reason, 'status' => $status,
                    'is_half_day' => $halfDay, 'leave_kind' => $leaveKind,
                    'approved_by' => $approvedAt ? $this->approverUserId : null, 'approved_at' => $approvedAt,
                ]
            );
            $created = $leave->wasRecentlyCreated;

            // rebuild per-day details
            LeaveDetail::where('leave_id', $leave->id)->delete();
            $d = Carbon::parse($startStr);
            $end = Carbon::parse($endStr);
            while ($d->lte($end)) {
                LeaveDetail::create([
                    'employee_id' => $empID,
                    'leave_id' => $leave->id,
                    'leavetype_id' => $leaveTypeId,
                    'date' => $d->format('Y-m-d'),
                    'leave_kind' => $leaveKind,
                    'total_hours' => $hoursPerDay,
                    'status' => $status,
                ]);
                $d->addDay();
            }

            return $created;
        });
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
