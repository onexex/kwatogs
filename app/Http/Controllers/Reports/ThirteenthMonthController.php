<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\department;
use App\Support\SimpleXlsx;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ThirteenthMonthController extends Controller
{
    private const LETTERHEAD = 'KWATOGS LOMI HOUSE';

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

        foreach ($rows as $r) {
            $r->total_basic = (float) $r->total_basic;
            $r->thirteenth  = round($r->total_basic / 12, 2);
        }

        return [$rows, $year, $from, $to];
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
            'count'          => $rows->count(),
        ]);
    }

    public function export(Request $request)
    {
        [$rows, $year, $from, $to] = $this->compute($request);

        $x = new SimpleXlsx('13th Month Pay');
        $x->setColumnWidths([6, 14, 16, 34, 22, 22, 10, 18, 18]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', '13TH MONTH PAY — COVERAGE '.strtoupper($this->coverageLabel($from, $to)), SimpleXlsx::S_TITLE);
        $x->setString('A3', 'Total basic salary earned within the coverage ÷ 12', SimpleXlsx::S_TITLE);

        $hr = 5;
        $headers = ['NO.', 'EMP ID', 'CARD NO', 'EMPLOYEE NAME', 'DEPARTMENT', 'COMPANY', 'MONTHS', 'TOTAL BASIC EARNED', '13TH MONTH PAY'];
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $i => $col) {
            $x->setString("{$col}{$hr}", $headers[$i], SimpleXlsx::S_BOLD);
        }

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
            $x->setNumber("G{$r}", (float) $row->months, SimpleXlsx::S_NORMAL);
            $x->setNumber("H{$r}", (float) $row->total_basic, SimpleXlsx::S_MONEY);
            $x->setNumber("I{$r}", (float) $row->thirteenth, SimpleXlsx::S_MONEY);
            $r++;
        }

        $x->setString("D{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("H{$r}", (float) $rows->sum('total_basic'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("I{$r}", (float) $rows->sum('thirteenth'), SimpleXlsx::S_SUBTOTAL);

        $path = $x->saveToTempFile();

        return response()->download($path, "13th_Month_Pay_{$year}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        [$rows, $year, $from, $to] = $this->compute($request);

        return view('pages.reports.thirteenth_month_print', [
            'rows'       => $rows,
            'year'       => $year,
            'coverage'   => $this->coverageLabel($from, $to),
            'totalBasic' => $rows->sum('total_basic'),
            'total13th'  => $rows->sum('thirteenth'),
            'letterhead' => self::LETTERHEAD,
        ]);
    }

    /**
     * Single-employee 13th-month PAYSLIP (printable slip in the payroll-payslip
     * style). Reuses compute() so the 13th-month figure is identical to the
     * report/export, then adds slip-only facts: company/department header +
     * address, BASIC RATE (from emp_details), TOTAL DAYS worked and TOTAL
     * TARDINESS (hrs) over the coverage. `pay_date` is the payout date shown on
     * the slip (defaults to the coverage end; it may fall outside the coverage
     * because 13th month is often released before Dec 24).
     */
    public function payslip(Request $request)
    {
        [$rows, $year, $from, $to] = $this->compute($request);

        $empId = (string) $request->query('employee_id', '');
        abort_if($empId === '', 404);

        $row   = $rows->firstWhere('employee_id', $empId); // may be null (no basic in coverage)
        $start = $from->format('Y-m-d');
        $end   = $to->format('Y-m-d');

        try {
            $payDate = $request->filled('pay_date')
                ? Carbon::parse($request->input('pay_date'))
                : $to->copy();
        } catch (\Throwable) {
            $payDate = $to->copy();
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
