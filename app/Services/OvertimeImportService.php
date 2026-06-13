<?php

namespace App\Services;

use App\Models\empDetail;
use App\Models\Overtime;
use Carbon\Carbon;

class OvertimeImportService
{
    // 0-based column positions (must match the OT import template order)
    private const C = [
        'employee_id' => 0, 'name' => 1, 'date_from' => 2, 'date_to' => 3, 'time_in' => 4,
        'time_out' => 5, 'day_type' => 6, 'purpose' => 7, 'status' => 8,
        'total_hrs' => 9, 'total_pay' => 10, 'hourly_rate' => 11,
    ];

    // day_type => OT multiplier (mirrors OvertimeController)
    private const RATES = [
        'regular' => 1.25,
        'rest_day' => 1.69,
        'special_holiday' => 1.69,
        'regular_holiday' => 2.60,
        'rest_day_regular_holiday' => 3.38,
        'rest_day_special_holiday' => 1.95,
        'rest_day_double_regular_holiday' => 3.90,
        'double_holiday' => 3.38,
    ];

    private const STATUSES = ['FORAPPROVAL', 'CANCELED', 'APPROVED', 'APPROVEDBYCFO', 'DISAPPROVED'];

    public function __construct(private ?int $approverEmpDetailId = null) {}

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

        $emp = empDetail::where('empID', $empID)->first();
        if (!$emp) { throw new \Exception("Employee ID '{$empID}' not found."); }

        $dateFrom = $this->parseDate($this->cell($row, 'date_from'));
        if (!$dateFrom) { throw new \Exception('Invalid or missing Date From (YYYY-MM-DD).'); }
        $dateTo = $this->parseDate($this->cell($row, 'date_to')) ?: $dateFrom;

        $timeIn = $this->normTime($this->cell($row, 'time_in'));
        $timeOut = $this->normTime($this->cell($row, 'time_out'));
        if (!$timeIn || !$timeOut) { throw new \Exception('Time In and Time Out are required (HH:MM).'); }

        $dayType = strtolower(trim($this->cell($row, 'day_type'))) ?: 'regular';
        if (!isset(self::RATES[$dayType])) {
            throw new \Exception("Day Type '{$dayType}' is invalid. Allowed: " . implode(', ', array_keys(self::RATES)) . '.');
        }

        $status = strtoupper(trim($this->cell($row, 'status'))) ?: 'APPROVEDBYCFO';
        if (!in_array($status, self::STATUSES, true)) {
            throw new \Exception("Status '{$status}' is invalid. Allowed: " . implode(', ', self::STATUSES) . '.');
        }

        $purpose = trim($this->cell($row, 'purpose')) ?: 'Imported overtime';

        // ── Compute (mirrors OvertimeController) ──
        $basic = (float) ($emp->empBasic ?? 0);
        $hourlyRate = ($basic / 26) / 8;
        $rate = self::RATES[$dayType];

        $in  = Carbon::parse($dateFrom . ' ' . $timeIn);
        $out = Carbon::parse($dateTo . ' ' . $timeOut);
        if ($out->lessThanOrEqualTo($in)) { $out->addDay(); } // crossed midnight
        $totalHours = $out->floatDiffInHours($in);

        if ($totalHours >= 8) {
            $payable = $totalHours - 1;            // 1-hour meal break
            $premium = min($payable, 8);
            $excess  = max($payable - 8, 0);
            $pay = ($hourlyRate * $rate * $premium) + ($hourlyRate * 1.25 * $excess);
            $totalHours = $payable;
        } else {
            $pay = $hourlyRate * $rate * $totalHours;
        }

        // explicit overrides from the file (if provided)
        $totalHrs = $this->numOr($this->cell($row, 'total_hrs'), round($totalHours, 2));
        $totalPay = $this->numOr($this->cell($row, 'total_pay'), round($pay, 2));

        $approvedAt = in_array($status, ['APPROVED', 'APPROVEDBYCFO'], true) ? now() : null;

        $ot = Overtime::updateOrCreate(
            ['emp_detail_id' => $emp->id, 'date_from' => $dateFrom, 'time_in' => $timeIn],
            [
                'date_to' => $dateTo,
                'time_out' => $timeOut,
                'status' => $status,
                'approved_by' => $approvedAt ? $this->approverEmpDetailId : null,
                'approved_at' => $approvedAt,
                'purpose' => $purpose,
                'day_type' => $dayType,
                'day_type_computation' => $rate,
                'hourly_rate' => round($hourlyRate, 6),
                'total_hrs' => $totalHrs,
                'total_pay' => $totalPay,
            ]
        );

        return $ot->wasRecentlyCreated;
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

    private function normTime(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') { return null; }
        if (is_numeric($v) && (float) $v < 1) {
            $mins = (int) round(((float) $v) * 1440);
            return sprintf('%02d:%02d', intdiv($mins, 60), $mins % 60);
        }
        try { return Carbon::parse($v)->format('H:i'); } catch (\Throwable $e) { return null; }
    }

    private function numOr(string $v, $fallback)
    {
        $v = trim($v);
        return ($v === '' || !is_numeric($v)) ? $fallback : (float) $v;
    }
}
