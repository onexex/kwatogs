<?php

namespace App\Services;

use App\Models\empDetail;
use App\Models\Overtime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

    // empID => empDetail (id + empBasic), preloaded once per import.
    private array $empByEmpID = [];

    public function __construct(private ?int $approverEmpDetailId = null) {}

    public function import(array $rows): array
    {
        $result = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [], 'aborted' => false];
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
                $key = $data['emp_detail_id'] . '|' . $data['date_from'] . '|' . $data['time_in'];
                if (isset($seen[$key])) {
                    throw new \Exception("Duplicate of row {$seen[$key]} (same employee, date and time in).");
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
        DB::transaction(function () use (&$result, $prepared) {
            foreach ($prepared as $data) {
                $this->persist($data) ? $result['inserted']++ : $result['updated']++;
            }
        });

        return $result;
    }

    /** Load all employee detail rows once so row validation never hits the DB. */
    private function preload(): void
    {
        foreach (empDetail::whereNotNull('empID')->get(['id', 'empID', 'empBasic']) as $e) {
            $this->empByEmpID[$e->empID] = $e;
        }
    }

    /** Bulk-check the prepared payloads against existing Overtime rows; move collisions to errors. */
    private function flagExisting(array &$prepared, array &$result): void
    {
        if (empty($prepared)) { return; }
        $empIds = array_values(array_unique(array_column($prepared, 'emp_detail_id')));
        $dates  = array_values(array_unique(array_column($prepared, 'date_from')));

        $existing = [];
        foreach (Overtime::whereIn('emp_detail_id', $empIds)->whereIn('date_from', $dates)
                     ->get(['emp_detail_id', 'date_from', 'time_in']) as $o) {
            // Normalise time_in to HH:MM — the DB may return HH:MM:SS for a TIME column.
            $ti = substr((string) $o->time_in, 0, 5);
            $existing[$o->emp_detail_id . '|' . Carbon::parse($o->date_from)->toDateString() . '|' . $ti] = true;
        }

        $kept = [];
        foreach ($prepared as $d) {
            if (isset($existing[$d['_key']])) {
                $result['errors'][] = "Row {$d['_line']}: An overtime already exists for this employee, date and time in.";
            } else {
                $kept[] = $d;
            }
        }
        $prepared = $kept;
    }

    /** Validate + compute one row into a payload (throws on any problem, writes nothing). */
    private function validateRow(array $row): array
    {
        $empID = trim($this->cell($row, 'employee_id'));
        if ($empID === '') { throw new \Exception('Employee ID is required.'); }

        $emp = $this->empByEmpID[$empID] ?? null;
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

        return [
            'emp_detail_id' => $emp->id, 'date_from' => $dateFrom, 'date_to' => $dateTo,
            'time_in' => $timeIn, 'time_out' => $timeOut, 'status' => $status,
            'approved_at' => $approvedAt, 'purpose' => $purpose, 'day_type' => $dayType,
            'day_type_computation' => $rate, 'hourly_rate' => round($hourlyRate, 6),
            'total_hrs' => $totalHrs, 'total_pay' => $totalPay,
        ];
    }

    /** Persist one validated payload. Runs inside the caller's transaction. */
    private function persist(array $d): bool
    {
        $ot = Overtime::updateOrCreate(
            ['emp_detail_id' => $d['emp_detail_id'], 'date_from' => $d['date_from'], 'time_in' => $d['time_in']],
            [
                'date_to' => $d['date_to'],
                'time_out' => $d['time_out'],
                'status' => $d['status'],
                'approved_by' => $d['approved_at'] ? $this->approverEmpDetailId : null,
                'approved_at' => $d['approved_at'],
                'purpose' => $d['purpose'],
                'day_type' => $d['day_type'],
                'day_type_computation' => $d['day_type_computation'],
                'hourly_rate' => $d['hourly_rate'],
                'total_hrs' => $d['total_hrs'],
                'total_pay' => $d['total_pay'],
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
