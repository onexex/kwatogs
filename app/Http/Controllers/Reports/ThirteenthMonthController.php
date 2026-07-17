<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\department;
use App\Models\ThirteenthMonthPayout;
use App\Support\SimpleXlsx;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ThirteenthMonthController extends Controller
{
    private const LETTERHEAD = 'KWATOGS LOMI HOUSE';

    /**
     * BIR/TRAIN de-minimis cap: 13th-month pay + other benefits are income-tax
     * EXEMPT up to ₱90,000; only the excess is taxable. The report flags the
     * taxable excess per employee so HR can reconcile against withholding tax.
     * Not defined anywhere else in the codebase — this is the single home for it.
     */
    public const TAX_EXEMPT_CAP = 90000;

    public function index()
    {
        $departments = department::orderBy('dep_name')->get();
        $companies   = DB::table('companies')->orderBy('comp_name')->get();
        $years       = range((int) now()->year, (int) now()->year - 5);

        return view('pages.reports.thirteenth_month', compact('departments', 'companies', 'years'));
    }

    /**
     * 13th-month pay = total basic salary EARNED during the calendar year ÷ 12.
     *
     * `payrolls.basicPay` is NOT the earned basic: for RGLR it's the flat
     * semi-monthly basic BEFORE absence/late/undertime deductions, and for
     * daily-paid it's just the daily RATE (the real regular pay is folded into
     * gross_pay). The earned basic per cutoff is therefore derived as
     * gross_pay − overtime_pay − holiday_pay − night_diff_pay, which per the
     * DOLE 13th-month guidelines also correctly excludes OT, holiday pay and
     * night differential (allowances are never in gross_pay). Clamped ≥ 0 per
     * row because gross_pay itself is floor-clamped at 0 during computation.
     * Employees who only worked part of the year are pro-rated automatically
     * because their total earned basic is simply smaller.
     *
     * COVERAGE: pay dates inside `coverage_from`..`coverage_to`. Defaults to
     * the selected calendar year (Jan 1 – Dec 31) but is adjustable so payout
     * can run before Dec 24 (e.g. Dec 1 prev year – Nov 30). Parsing is
     * defensive (export/print are plain GET links): bad input falls back to
     * the calendar year, a reversed range is swapped, and the window is capped
     * at one year — every label downstream shows the EFFECTIVE window.
     *
     * @return array{0:\Illuminate\Support\Collection,1:int,2:Carbon,3:Carbon}
     */
    private function compute(Request $request): array
    {
        $year = (int) ($request->input('year') ?: now()->year);

        try {
            $from = $request->filled('coverage_from') ? Carbon::parse($request->input('coverage_from'))->startOfDay() : null;
            $to   = $request->filled('coverage_to') ? Carbon::parse($request->input('coverage_to'))->startOfDay() : null;
        } catch (\Throwable) {
            $from = $to = null;
        }
        if (!$from || !$to) {
            $from = Carbon::create($year, 1, 1);
            $to   = Carbon::create($year, 12, 31);
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        if ($from->diffInDays($to) > 366) {
            $to = $from->copy()->addYear()->subDay();
        }

        $year  = $to->year; // report/payout year = coverage end year
        $start = $from->format('Y-m-d');
        $end   = $to->format('Y-m-d');

        $q = DB::table('payrolls as p')
            ->join('users as u', 'u.empID', '=', 'p.employee_id')
            ->leftJoin('emp_details as ed', 'ed.empID', '=', 'p.employee_id')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->leftJoin('companies as c', 'c.comp_id', '=', 'ed.empCompID')
            ->whereBetween('p.pay_date', [$start, $end]);

        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $q->where('ed.empDepID', $request->department_id);
        }
        if ($request->filled('company_id') && $request->company_id !== 'all') {
            $q->where('ed.empCompID', $request->company_id);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('u.fname', 'like', "%{$s}%")
                  ->orWhere('u.lname', 'like', "%{$s}%")
                  ->orWhere('u.empID', 'like', "%{$s}%");
            });
        }
        // When the report screen ticks a subset of employees, Export/Print funnel the
        // chosen IDs through here so only those payees are emitted. fetch() never sends
        // this — the on-screen grid always shows the full candidate list to pick from.
        if ($request->filled('employee_ids')) {
            $q->whereIn('u.empID', (array) $request->input('employee_ids'));
        }

        $rows = $q->selectRaw("
                u.empID as employee_id,
                COALESCE(ed.empCardNo,'') as card_no,
                MAX(ed.empPayrollType) as payroll_type,
                MAX(ed.empStatus) as emp_status,
                MAX(ed.separation_date) as separation_date,
                MAX(ed.empDateHired) as date_hired,
                TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as employee_name,
                COALESCE(d.dep_name,'—') as department_name,
                COALESCE(c.comp_name,'—') as company_name,
                SUM(GREATEST(COALESCE(p.gross_pay,0) - COALESCE(p.overtime_pay,0) - COALESCE(p.holiday_pay,0) - COALESCE(p.night_diff_pay,0), 0)) as total_basic,
                COUNT(DISTINCT p.pay_date) as periods,
                COUNT(DISTINCT DATE_FORMAT(p.pay_date,'%Y-%m')) as months,
                MIN(p.pay_date) as first_pay,
                MAX(p.pay_date) as last_pay
            ")
            ->groupBy('u.empID', 'card_no', 'employee_name', 'department_name', 'company_name')
            ->havingRaw('SUM(GREATEST(COALESCE(p.gross_pay,0) - COALESCE(p.overtime_pay,0) - COALESCE(p.holiday_pay,0) - COALESCE(p.night_diff_pay,0), 0)) > 0')
            ->orderBy('employee_name')
            ->get();

        $this->enrichRows($rows, $from, $to, $year);

        return [$rows, $year, $from, $to];
    }

    /**
     * Decorate each computed row in-place with the derived facts the report
     * surfaces: 13th-month amount, BIR taxable excess (over ₱90k), employment
     * status label, a coverage-quality flag (new hire vs unexplained gap), and
     * the release/payout state from the payout ledger. Kept O(rows) — the
     * payout lookup is a single bulk query keyed by employee_id.
     */
    private function enrichRows(Collection $rows, Carbon $from, Carbon $to, int $year): void
    {
        // Number of calendar months the coverage window spans (>=1). Used to
        // tell an expected partial (short window / new hire) from a real gap.
        $spanMonths = ($from->year - $to->year) * 12 + ($from->month - $to->month);
        $spanMonths = abs($spanMonths) + 1;

        // Bulk-load claim records for this coverage year (one query), grouped
        // per employee — up to a 'half' (mid-year advance) and a 'full'
        // (remaining/whole) row each.
        $payouts = ThirteenthMonthPayout::where('coverage_year', $year)
            ->whereIn('employee_id', $rows->pluck('employee_id')->all())
            ->get()
            ->groupBy('employee_id');

        foreach ($rows as $r) {
            $r->total_basic = (float) $r->total_basic;
            $r->thirteenth  = round($r->total_basic / 12, 2);

            // (1) BIR tax-exemption split.
            $r->tax_exempt    = round(min($r->thirteenth, self::TAX_EXEMPT_CAP), 2);
            $r->taxable       = round(max(0, $r->thirteenth - self::TAX_EXEMPT_CAP), 2);
            $r->is_taxable    = $r->taxable > 0;

            // (2) Employment status.
            $code = (string) ($r->emp_status ?? '1');
            $r->status_code  = $code;
            $r->status_label = ['1' => 'Active', '0' => 'Resigned', '2' => 'End of Contract'][$code] ?? '—';
            $r->separated    = $code !== '1';

            // (3) Coverage quality: separated / new hire (expected partial, OK)
            //     vs full vs an unexplained gap that HR should review.
            $hired = null;
            try { $hired = $r->date_hired ? Carbon::parse($r->date_hired) : null; } catch (\Throwable) {}
            if ($r->separated) {
                $r->coverage_flag = 'separated';
            } elseif ($hired && $hired->gt($from)) {
                $r->coverage_flag = 'newhire';
            } elseif ((int) $r->months >= $spanMonths) {
                $r->coverage_flag = 'full';
            } else {
                $r->coverage_flag = 'partial'; // gap — needs review
            }

            // (4) Claim state: half (mid-year advance) vs full (remaining/whole),
            //     each with who/when, plus the running balance.
            $group = $payouts->get($r->employee_id) ?? collect();
            $half  = $group->firstWhere('portion', ThirteenthMonthPayout::PORTION_HALF);
            $full  = $group->firstWhere('portion', ThirteenthMonthPayout::PORTION_FULL);

            $claim = fn ($po) => $po ? [
                'amount' => (float) $po->amount,
                'at'     => $po->released_at?->format('Y-m-d'),
                'by'     => $po->released_by,
            ] : null;

            $r->claim_half     = $claim($half);
            $r->claim_full     = $claim($full);
            $r->released_total = round((float) $group->sum('amount'), 2);
            $r->balance        = round($r->thirteenth - $r->released_total, 2);

            if ($group->isEmpty()) {
                $r->claim_status = 'unclaimed';
            } elseif ($full || $r->balance <= 0.005) {
                $r->claim_status = 'full';   // fully settled
            } else {
                $r->claim_status = 'half';   // partial (advance only)
            }
            $r->released  = $r->claim_status === 'full';
            $r->partially = $r->claim_status === 'half';

            // (5) What is DUE now — the December view. Someone with no advance
            //     is owed the WHOLE; someone who took the mid-year half is owed
            //     the REMAINING; a settled row is PAID.
            if ($r->claim_status === 'full') {
                $r->due_type = 'paid';
            } elseif ($r->claim_status === 'half') {
                $r->due_type = 'remaining';
            } else {
                $r->due_type = 'whole';
            }
            $r->due_amount = $r->due_type === 'paid' ? 0.0 : $r->balance;
        }
    }

    private function coverageLabel(Carbon $from, Carbon $to): string
    {
        return $from->format('M j, Y').' – '.$to->format('M j, Y');
    }

    public function fetch(Request $request)
    {
        [$rows, $year, $from, $to] = $this->compute($request);

        return response()->json([
            'data'           => $rows,
            'year'           => $year,
            'coverage_from'  => $from->format('Y-m-d'),
            'coverage_to'    => $to->format('Y-m-d'),
            'coverage_label' => $this->coverageLabel($from, $to),
            'total_basic'    => $rows->sum('total_basic'),
            'total_13th'     => $rows->sum('thirteenth'),
            'total_taxable'  => $rows->sum('taxable'),
            'fully_count'    => $rows->where('claim_status', 'full')->count(),
            'half_count'     => $rows->where('claim_status', 'half')->count(),
            'unclaimed_count'=> $rows->where('claim_status', 'unclaimed')->count(),
            'count'          => $rows->count(),
        ]);
    }

    public function export(Request $request)
    {
        [$rows, $year, $from, $to] = $this->compute($request);

        $x = new SimpleXlsx('13th Month Pay');
        $x->setColumnWidths([6, 14, 16, 34, 22, 22, 16, 10, 20, 18, 18, 22, 22, 16]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', '13TH MONTH PAY — COVERAGE '.strtoupper($this->coverageLabel($from, $to)), SimpleXlsx::S_TITLE);
        $x->setString('A3', 'Total basic salary earned within the coverage ÷ 12  •  taxable excess = amount over ₱'.number_format(self::TAX_EXEMPT_CAP).'  •  half = mid-year advance, full = remaining', SimpleXlsx::S_TITLE);

        $hr = 5;
        $headers = ['NO.', 'EMP ID', 'CARD NO', 'EMPLOYEE NAME', 'DEPARTMENT', 'COMPANY', 'STATUS', 'MONTHS', 'TOTAL BASIC EARNED', '13TH MONTH PAY', 'TAXABLE EXCESS', 'HALF CLAIMED', 'FULL CLAIMED', 'BALANCE'];
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'] as $i => $col) {
            $x->setString("{$col}{$hr}", $headers[$i], SimpleXlsx::S_BOLD);
        }

        $claimCell = function ($c) {
            if (!$c) {
                return '—';
            }
            $parts = number_format($c['amount'], 2);
            if (!empty($c['at'])) { $parts .= ' • '.$c['at']; }
            if (!empty($c['by'])) { $parts .= ' • '.$c['by']; }
            return $parts;
        };

        $r = $hr + 1;
        $n = 0;
        foreach ($rows as $row) {
            $n++;
            $x->setNumber("A{$r}", $n, SimpleXlsx::S_NORMAL);
            $x->setString("B{$r}", (string) $row->employee_id, SimpleXlsx::S_TEXT);
            $x->setString("C{$r}", (string) $row->card_no, SimpleXlsx::S_TEXT);
            $x->setString("D{$r}", strtoupper((string) $row->employee_name), SimpleXlsx::S_NORMAL);
            $x->setString("E{$r}", (string) $row->department_name, SimpleXlsx::S_NORMAL);
            $x->setString("F{$r}", (string) $row->company_name, SimpleXlsx::S_NORMAL);
            $x->setString("G{$r}", (string) $row->status_label, SimpleXlsx::S_NORMAL);
            $x->setNumber("H{$r}", (float) $row->months, SimpleXlsx::S_NORMAL);
            $x->setNumber("I{$r}", (float) $row->total_basic, SimpleXlsx::S_MONEY);
            $x->setNumber("J{$r}", (float) $row->thirteenth, SimpleXlsx::S_MONEY);
            $x->setNumber("K{$r}", (float) $row->taxable, SimpleXlsx::S_MONEY);
            $x->setString("L{$r}", $claimCell($row->claim_half), SimpleXlsx::S_NORMAL);
            $x->setString("M{$r}", $claimCell($row->claim_full), SimpleXlsx::S_NORMAL);
            $x->setNumber("N{$r}", (float) $row->balance, SimpleXlsx::S_MONEY);
            $r++;
        }

        $x->setString("D{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("I{$r}", (float) $rows->sum('total_basic'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("J{$r}", (float) $rows->sum('thirteenth'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("K{$r}", (float) $rows->sum('taxable'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("N{$r}", (float) $rows->sum('balance'), SimpleXlsx::S_SUBTOTAL);

        $path = $x->saveToTempFile();

        $this->auditReportAction('exported', $from, $to, $rows, ['format' => 'register']);

        return response()->download($path, "13th_Month_Pay_{$year}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Bank/disbursement file: a lean second export for accounting/bank upload —
     * NO., EMP ID, CARD NO, EMPLOYEE, DEPARTMENT, MONTHS, TOTAL BASIC EARNED,
     * 13TH MONTH PAY (no COMPANY column). A separate SimpleXlsx instance because
     * the writer is single-sheet.
     */
    public function bankExport(Request $request)
    {
        [$rows, $year, $from, $to] = $this->compute($request);

        $x = new SimpleXlsx('13th Month Bank File');
        $x->setColumnWidths([6, 16, 16, 34, 22, 10, 20, 18]);
        $x->setString('A1', self::LETTERHEAD.' — 13TH MONTH DISBURSEMENT '.$year, SimpleXlsx::S_TITLE);

        $hr = 3;
        $headers = ['NO.', 'EMP ID', 'CARD NO', 'EMPLOYEE', 'DEPARTMENT', 'MONTHS', 'TOTAL BASIC EARNED', '13TH MONTH PAY'];
        foreach ($headers as $i => $h) {
            $x->setString(chr(65 + $i)."{$hr}", $h, SimpleXlsx::S_BOLD);
        }

        $r = $hr + 1;
        $n = 0;
        foreach ($rows as $row) {
            $n++;
            $x->setNumber("A{$r}", $n, SimpleXlsx::S_NORMAL);
            $x->setString("B{$r}", (string) $row->employee_id, SimpleXlsx::S_TEXT);
            $x->setString("C{$r}", (string) $row->card_no, SimpleXlsx::S_TEXT); // preserve leading zeros
            $x->setString("D{$r}", strtoupper((string) $row->employee_name), SimpleXlsx::S_NORMAL);
            $x->setString("E{$r}", (string) $row->department_name, SimpleXlsx::S_NORMAL);
            $x->setNumber("F{$r}", (float) $row->months, SimpleXlsx::S_NORMAL);
            $x->setNumber("G{$r}", (float) $row->total_basic, SimpleXlsx::S_MONEY);
            $x->setNumber("H{$r}", (float) $row->thirteenth, SimpleXlsx::S_MONEY);
            $r++;
        }
        $x->setString("D{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("G{$r}", (float) $rows->sum('total_basic'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("H{$r}", (float) $rows->sum('thirteenth'), SimpleXlsx::S_SUBTOTAL);

        $path = $x->saveToTempFile();

        $this->auditReportAction('exported', $from, $to, $rows, ['format' => 'bank_file']);

        return response()->download($path, "13th_Month_BankFile_{$year}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        [$rows, $year, $from, $to] = $this->compute($request);

        $this->auditReportAction('printed', $from, $to, $rows, ['format' => 'print']);

        return view('pages.reports.thirteenth_month_print', [
            'rows'        => $rows,
            'year'        => $year,
            'coverage'    => $this->coverageLabel($from, $to),
            'totalBasic'  => $rows->sum('total_basic'),
            'total13th'   => $rows->sum('thirteenth'),
            'totalTaxable'=> $rows->sum('taxable'),
            'totalBalance'=> $rows->sum('balance'),
            'letterhead'  => self::LETTERHEAD,
        ]);
    }

    /**
     * Record a CLAIM of the selected employees' 13th month for the coverage year:
     * `portion=half` = the mid-year 50% advance, `portion=full` = the remaining
     * (whole) balance. One ledger row per employee+year+portion (idempotent —
     * re-releasing a portion updates its row). The amount is always the freshly
     * re-computed figure (never a client-sent value); a `half` claim is skipped
     * for anyone already fully settled. Saved through the model instance so
     * Auditable logs it.
     */
    public function release(Request $request)
    {
        $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'string',
            'portion'        => 'nullable|in:half,full',
        ]);

        $portion = $request->input('portion', ThirteenthMonthPayout::PORTION_FULL);
        [$rows, $year, $from, $to] = $this->compute($request);
        $ids   = array_map('strval', $request->input('employee_ids'));
        $batch = $request->input('batch');
        $by    = $this->actorName();
        $today = Carbon::now()->startOfDay();

        $released = 0;
        $skipped  = 0;
        foreach ($rows as $r) {
            if (!in_array((string) $r->employee_id, $ids, true)) {
                continue;
            }

            // Already-claimed portions for this employee/year.
            $existing = ThirteenthMonthPayout::where('employee_id', $r->employee_id)
                ->where('coverage_year', $year)->get();
            $priorTotal = (float) $existing->where('portion', '!=', $portion)->sum('amount');

            if ($portion === ThirteenthMonthPayout::PORTION_HALF) {
                // A half advance makes no sense once the whole has been settled.
                if ($existing->firstWhere('portion', ThirteenthMonthPayout::PORTION_FULL)) {
                    $skipped++;
                    continue;
                }
                $amount = round($r->thirteenth / 2, 2);
            } else {
                // Full = the remaining balance to reach the computed total.
                $amount = round($r->thirteenth - $priorTotal, 2);
                if ($amount < 0) {
                    $amount = 0;
                }
            }

            $po = ThirteenthMonthPayout::firstOrNew([
                'employee_id'   => $r->employee_id,
                'coverage_year' => $year,
                'portion'       => $portion,
            ]);
            $po->forceFill([
                'coverage_from'  => $from->format('Y-m-d'),
                'coverage_to'    => $to->format('Y-m-d'),
                'amount'         => $amount,
                'taxable_excess' => $portion === ThirteenthMonthPayout::PORTION_FULL ? $r->taxable : 0,
                'released_at'    => $today->format('Y-m-d'),
                'released_by'    => $by,
                'batch'          => $batch,
            ])->save();
            $released++;
        }

        $label = $portion === ThirteenthMonthPayout::PORTION_HALF ? 'half advance' : 'full/remaining';
        $msg   = "{$released} employee(s) recorded ({$label}).";
        if ($skipped) {
            $msg .= " {$skipped} skipped (already fully claimed).";
        }

        return response()->json([
            'status'   => 'ok',
            'released' => $released,
            'skipped'  => $skipped,
            'message'  => $msg,
        ]);
    }

    /**
     * Revert claims for the selected employees in the coverage year — e.g. a
     * batch was recorded by mistake. Pass `portion` to revert only the half or
     * full row; omit it to clear both. Instance delete() so Auditable records it.
     */
    public function unrelease(Request $request)
    {
        $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'string',
            'portion'        => 'nullable|in:half,full',
        ]);

        $year = $this->resolveYear($request);
        $ids  = array_map('strval', $request->input('employee_ids'));

        $reverted = 0;
        ThirteenthMonthPayout::where('coverage_year', $year)
            ->whereIn('employee_id', $ids)
            ->when($request->filled('portion'), fn ($q) => $q->where('portion', $request->input('portion')))
            ->get()
            ->each(function ($po) use (&$reverted) {
                $po->delete();
                $reverted++;
            });

        return response()->json([
            'status'   => 'ok',
            'reverted' => $reverted,
            'message'  => "{$reverted} claim record(s) reverted.",
        ]);
    }

    /** Coverage-end year, mirroring compute()'s normalization. */
    private function resolveYear(Request $request): int
    {
        [, $year] = $this->compute($request);
        return $year;
    }

    /** Best-effort display name of the acting user for ledger/audit stamping. */
    private function actorName(): string
    {
        $u = Auth::user();
        if (!$u) {
            return 'system';
        }
        $name = trim(($u->fname ?? '').' '.($u->lname ?? ''));
        return $name !== '' ? $name : ($u->name ?? (string) $u->empID);
    }

    /**
     * Record a manual audit entry for export/print (salary data leaving the
     * system). AuditLog::record swallows its own failures, so this never breaks
     * the download.
     */
    private function auditReportAction(string $action, Carbon $from, Carbon $to, Collection $rows, array $extra = []): void
    {
        AuditLog::record($action, 'ThirteenthMonthReport', null, array_merge([
            'coverage' => $this->coverageLabel($from, $to),
            'count'    => $rows->count(),
            'total'    => round((float) $rows->sum('thirteenth'), 2),
        ], $extra));
    }

    /**
     * Single-employee 13th-month PAYSLIP (printable slip in the payroll-payslip
     * style). Reuses compute() so the 13th-month figure is identical to the
     * report/export, then adds slip-only facts: company/department header +
     * address, BASIC RATE (from emp_details), TOTAL DAYS worked and TOTAL
     * TARDINESS (hrs) over the coverage. Pay Date precedence: an explicit
     * `pay_date` field wins; otherwise the ACTUAL release date from the payout
     * ledger (the latest of the half/full claims); only if neither exists does
     * it fall back to the coverage end.
     */
    public function payslip(Request $request)
    {
        [$rows, $year, $from, $to] = $this->compute($request);

        $empId = (string) $request->query('employee_id', '');
        abort_if($empId === '', 404);

        $row   = $rows->firstWhere('employee_id', $empId); // may be null (no basic in coverage)
        $start = $from->format('Y-m-d');
        $end   = $to->format('Y-m-d');

        // Actual release date (latest claim) for this employee/coverage year.
        $releasedOn = ThirteenthMonthPayout::where('employee_id', $empId)
            ->where('coverage_year', $year)
            ->whereNotNull('released_at')
            ->max('released_at');

        try {
            if ($request->filled('pay_date')) {
                $payDate = Carbon::parse($request->input('pay_date'));
            } elseif ($releasedOn) {
                $payDate = Carbon::parse($releasedOn);
            } else {
                $payDate = $to->copy();
            }
        } catch (\Throwable) {
            $payDate = $releasedOn ? Carbon::parse($releasedOn) : $to->copy();
        }

        // Employee + company/department header facts (department carries the
        // company profile in this app — see CLAUDE.md).
        $emp = DB::table('users as u')
            ->leftJoin('emp_details as ed', 'ed.empID', '=', 'u.empID')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->leftJoin('companies as c', 'c.comp_id', '=', 'ed.empCompID')
            ->leftJoin('positions as pos', 'pos.id', '=', 'ed.empPos')
            ->where('u.empID', $empId)
            ->selectRaw("
                u.empID,
                TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as employee_name,
                ed.empBasic as basic_rate,
                ed.empClassification,
                d.dep_name, d.dep_address, c.comp_name, pos.pos_desc
            ")
            ->first();

        abort_if(!$emp, 404, 'Employee not found.');

        // Days worked and total tardiness both come from attendance_summaries
        // over the coverage (the payrolls table stores deduction amounts, not
        // minutes). Days = distinct attendance days with recorded hours.
        $att = DB::table('attendance_summaries')
            ->where('employee_id', $empId)
            ->whereBetween('attendance_date', [$start, $end])
            ->selectRaw('COUNT(DISTINCT CASE WHEN total_hours > 0 THEN attendance_date END) as days,
                         COALESCE(SUM(mins_late), 0) as tardy_mins')
            ->first();

        $totalDays = (int) ($att->days ?? 0);
        $tardyMins = (float) ($att->tardy_mins ?? 0);

        return view('pages.reports.thirteenth_month_payslip', [
            'emp'        => $emp,
            'coverage'   => $this->coverageLabel($from, $to),
            'payDate'    => $payDate,
            'totalDays'  => $totalDays,
            'tardyHours' => round($tardyMins / 60, 2),
            'totalBasic' => (float) ($row->total_basic ?? 0),
            'thirteenth' => (float) ($row->thirteenth ?? 0),
            'months'     => (int) ($row->months ?? 0),
            'letterhead' => self::LETTERHEAD,
        ]);
    }
}
