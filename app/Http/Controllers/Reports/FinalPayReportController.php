<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\department;
use App\Support\SimpleXlsx;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Final Pay / Last Pay computation worksheet for separated employees.
 *
 * Estimates the derivable components of a final pay:
 *   - Pro-rated 13th month = basic earned in the separation year ÷ 12
 *   - Unused leave conversion = remaining leave balance × daily rate
 * Last unpaid salary, tax refund and deductions are policy-specific and left for
 * HR to add manually — this is a computation AID, not the official final pay.
 */
class FinalPayReportController extends Controller
{
    private const LETTERHEAD = 'KWATOGS LOMI HOUSE';
    private const WORKING_DAYS = 26; // monthly basic ÷ working days = daily rate

    public function index()
    {
        return view('pages.reports.final_pay', [
            'departments' => department::orderBy('dep_name')->get(),
            'companies'   => DB::table('companies')->orderBy('comp_name')->get(),
            'years'       => range((int) now()->year, (int) now()->year - 5),
        ]);
    }

    private function compute(Request $request): array
    {
        $year = $request->input('year', (string) now()->year);

        $q = DB::table('emp_details as ed')
            ->join('users as u', 'u.empID', '=', 'ed.empID')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->whereNotNull('ed.separation_date');

        if ($year !== 'all') {
            $q->whereYear('ed.separation_date', (int) $year);
        }
        if ($request->filled('company_id') && $request->company_id !== 'all') {
            $q->where('ed.empCompID', $request->company_id);
        }
        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $q->where('ed.empDepID', $request->department_id);
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
                ed.separation_date, ed.separation_reason, ed.years_rendered,
                COALESCE(ed.empBasic,0) as monthly_basic, COALESCE(ed.empHrate,0) as hourly_rate,
                COALESCE((SELECT SUM(GREATEST(COALESCE(p.gross_pay,0) - COALESCE(p.overtime_pay,0) - COALESCE(p.holiday_pay,0) - COALESCE(p.night_diff_pay,0), 0)) FROM payrolls p
                    WHERE p.employee_id = ed.empID AND YEAR(p.pay_date) = YEAR(ed.separation_date)), 0) as basic_earned,
                COALESCE((SELECT SUM(lca.balance) FROM leave_credit_allocations lca
                    WHERE lca.employee_id = ed.empID AND lca.year = YEAR(ed.separation_date)), 0) as leave_balance
            ")
            ->orderBy('ed.separation_date')
            ->get()
            ->map(function ($r) {
                $r->monthly_basic = (float) $r->monthly_basic;
                $r->hourly_rate   = (float) $r->hourly_rate;
                $r->basic_earned  = (float) $r->basic_earned;
                $r->leave_balance = (float) $r->leave_balance;
                $r->years_rendered = $r->years_rendered !== null ? (float) $r->years_rendered : null;

                $r->daily_rate       = $r->monthly_basic > 0
                    ? round($r->monthly_basic / self::WORKING_DAYS, 2)
                    : round($r->hourly_rate * 8, 2);
                $r->prorated_13th    = round($r->basic_earned / 12, 2);
                $r->leave_conversion = round($r->leave_balance * $r->daily_rate, 2);
                $r->estimated_final  = round($r->prorated_13th + $r->leave_conversion, 2);
                return $r;
            });

        return [$rows, $year];
    }

    private function stats(Collection $rows): array
    {
        return [
            'count'      => $rows->count(),
            'th13'       => round($rows->sum('prorated_13th'), 2),
            'leave_conv' => round($rows->sum('leave_conversion'), 2),
            'estimated'  => round($rows->sum('estimated_final'), 2),
        ];
    }

    public function fetch(Request $request)
    {
        [$rows, $year] = $this->compute($request);
        return response()->json(['data' => $rows, 'year' => $year, 'stats' => $this->stats($rows)]);
    }

    public function export(Request $request)
    {
        [$rows, $year] = $this->compute($request);
        $st = $this->stats($rows);

        $x = new SimpleXlsx('Final Pay');
        $x->setColumnWidths([5, 12, 28, 20, 14, 12, 14, 14, 12, 16, 14, 18]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', 'FINAL PAY / LAST PAY COMPUTATION (ESTIMATE) — ' . ($year === 'all' ? 'ALL YEARS' : $year), SimpleXlsx::S_TITLE);
        $x->setString('A3', 'Estimate of derivable components only; last unpaid salary, tax refund & deductions are added manually per policy.', SimpleXlsx::S_TITLE);

        $hr = 5;
        $headers = ['NO.', 'EMP ID', 'EMPLOYEE NAME', 'DEPARTMENT', 'SEPARATED', 'YEARS', 'DAILY RATE',
            'BASIC EARNED', '13TH (PRO-RATED)', 'LEAVE BAL (DAYS)', 'LEAVE CONV.', 'EST. FINAL PAY'];
        $cols = range('A', 'L');
        foreach ($headers as $i => $h) { $x->setString("{$cols[$i]}{$hr}", $h, SimpleXlsx::S_BOLD); }

        $r = $hr + 1;
        $n = 0;
        foreach ($rows as $row) {
            $n++;
            $x->setNumber("A{$r}", $n, SimpleXlsx::S_NORMAL);
            $x->setString("B{$r}", (string) $row->employee_id, SimpleXlsx::S_TEXT);
            $x->setString("C{$r}", strtoupper((string) $row->employee_name), SimpleXlsx::S_NORMAL);
            $x->setString("D{$r}", (string) $row->department_name, SimpleXlsx::S_NORMAL);
            $x->setString("E{$r}", $row->separation_date ? Carbon::parse($row->separation_date)->format('M d, Y') : '', SimpleXlsx::S_TEXT);
            $x->setNumber("F{$r}", (float) ($row->years_rendered ?? 0), SimpleXlsx::S_NORMAL);
            $x->setNumber("G{$r}", (float) $row->daily_rate, SimpleXlsx::S_MONEY);
            $x->setNumber("H{$r}", (float) $row->basic_earned, SimpleXlsx::S_MONEY);
            $x->setNumber("I{$r}", (float) $row->prorated_13th, SimpleXlsx::S_MONEY);
            $x->setNumber("J{$r}", (float) $row->leave_balance, SimpleXlsx::S_NORMAL);
            $x->setNumber("K{$r}", (float) $row->leave_conversion, SimpleXlsx::S_MONEY);
            $x->setNumber("L{$r}", (float) $row->estimated_final, SimpleXlsx::S_MONEY);
            $r++;
        }

        $x->setString("C{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("I{$r}", (float) $st['th13'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("K{$r}", (float) $st['leave_conv'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("L{$r}", (float) $st['estimated'], SimpleXlsx::S_SUBTOTAL);

        $path = $x->saveToTempFile();
        return response()->download($path, 'Final_Pay_' . ($year === 'all' ? 'all' : $year) . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        [$rows, $year] = $this->compute($request);
        return view('pages.reports.final_pay_print', [
            'rows'       => $rows,
            'year'       => $year,
            'stats'      => $this->stats($rows),
            'letterhead' => self::LETTERHEAD,
        ]);
    }
}
