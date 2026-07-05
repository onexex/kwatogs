<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\department;
use App\Support\SimpleXlsx;
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
     * @return array{0:\Illuminate\Support\Collection,1:int}
     */
    private function compute(Request $request): array
    {
        $year  = (int) ($request->input('year') ?: now()->year);
        $start = "{$year}-01-01";
        $end   = "{$year}-12-31";

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

        $rows = $q->selectRaw("
                u.empID as employee_id,
                TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as employee_name,
                COALESCE(d.dep_name,'—') as department_name,
                COALESCE(c.comp_name,'—') as company_name,
                SUM(GREATEST(COALESCE(p.gross_pay,0) - COALESCE(p.overtime_pay,0) - COALESCE(p.holiday_pay,0) - COALESCE(p.night_diff_pay,0), 0)) as total_basic,
                COUNT(DISTINCT p.pay_date) as periods,
                COUNT(DISTINCT DATE_FORMAT(p.pay_date,'%Y-%m')) as months,
                MIN(p.pay_date) as first_pay,
                MAX(p.pay_date) as last_pay
            ")
            ->groupBy('u.empID', 'employee_name', 'department_name', 'company_name')
            ->havingRaw('SUM(GREATEST(COALESCE(p.gross_pay,0) - COALESCE(p.overtime_pay,0) - COALESCE(p.holiday_pay,0) - COALESCE(p.night_diff_pay,0), 0)) > 0')
            ->orderBy('employee_name')
            ->get();

        foreach ($rows as $r) {
            $r->total_basic = (float) $r->total_basic;
            $r->thirteenth  = round($r->total_basic / 12, 2);
        }

        return [$rows, $year];
    }

    public function fetch(Request $request)
    {
        [$rows, $year] = $this->compute($request);

        return response()->json([
            'data'        => $rows,
            'year'        => $year,
            'total_basic' => $rows->sum('total_basic'),
            'total_13th'  => $rows->sum('thirteenth'),
            'count'       => $rows->count(),
        ]);
    }

    public function export(Request $request)
    {
        [$rows, $year] = $this->compute($request);

        $x = new SimpleXlsx('13th Month Pay');
        $x->setColumnWidths([6, 14, 34, 22, 22, 10, 18, 18]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', "13TH MONTH PAY — CALENDAR YEAR {$year}", SimpleXlsx::S_TITLE);
        $x->setString('A3', 'Total basic salary earned ÷ 12', SimpleXlsx::S_TITLE);

        $hr = 5;
        $headers = ['NO.', 'EMP ID', 'EMPLOYEE NAME', 'DEPARTMENT', 'COMPANY', 'MONTHS', 'TOTAL BASIC EARNED', '13TH MONTH PAY'];
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $i => $col) {
            $x->setString("{$col}{$hr}", $headers[$i], SimpleXlsx::S_BOLD);
        }

        $r = $hr + 1;
        $n = 0;
        foreach ($rows as $row) {
            $n++;
            $x->setNumber("A{$r}", $n, SimpleXlsx::S_NORMAL);
            $x->setString("B{$r}", (string) $row->employee_id, SimpleXlsx::S_TEXT);
            $x->setString("C{$r}", strtoupper((string) $row->employee_name), SimpleXlsx::S_NORMAL);
            $x->setString("D{$r}", (string) $row->department_name, SimpleXlsx::S_NORMAL);
            $x->setString("E{$r}", (string) $row->company_name, SimpleXlsx::S_NORMAL);
            $x->setNumber("F{$r}", (float) $row->months, SimpleXlsx::S_NORMAL);
            $x->setNumber("G{$r}", (float) $row->total_basic, SimpleXlsx::S_MONEY);
            $x->setNumber("H{$r}", (float) $row->thirteenth, SimpleXlsx::S_MONEY);
            $r++;
        }

        $x->setString("C{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("G{$r}", (float) $rows->sum('total_basic'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("H{$r}", (float) $rows->sum('thirteenth'), SimpleXlsx::S_SUBTOTAL);

        $path = $x->saveToTempFile();

        return response()->download($path, "13th_Month_Pay_{$year}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        [$rows, $year] = $this->compute($request);

        return view('pages.reports.thirteenth_month_print', [
            'rows'       => $rows,
            'year'       => $year,
            'totalBasic' => $rows->sum('total_basic'),
            'total13th'  => $rows->sum('thirteenth'),
            'letterhead' => self::LETTERHEAD,
        ]);
    }
}
