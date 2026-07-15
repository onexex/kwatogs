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
 * Payroll Register — the master per-period report: every employee on a pay run
 * with the full earnings → deductions → net breakdown. This is the accounting
 * artifact the disbursement exports (cash/card/gov-dues) are derived from.
 *
 * All figures are read straight from the stored `payrolls` row (gross_pay,
 * total_deductions, net_pay are authoritative); the register never recomputes.
 */
class PayrollRegisterReportController extends Controller
{
    private const LETTERHEAD = 'DEMO';

    public function index()
    {
        return view('pages.reports.payroll_register', [
            'departments'     => department::orderBy('dep_name')->get(),
            'companies'       => DB::table('companies')->orderBy('comp_name')->get(),
            'classifications' => DB::table('classifications')->orderBy('class_desc')->get(),
            'payDates'        => DB::table('payrolls')->distinct()->orderByDesc('pay_date')->pluck('pay_date'),
        ]);
    }

    private function compute(Request $request): array
    {
        $payDate = $request->input('pay_date')
            ?: DB::table('payrolls')->max('pay_date');

        $q = DB::table('payrolls as p')
            ->join('users as u', 'u.empID', '=', 'p.employee_id')
            ->leftJoin('emp_details as ed', 'ed.empID', '=', 'p.employee_id')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->leftJoin('companies as c', 'c.comp_id', '=', 'ed.empCompID')
            ->leftJoin('classifications as cl', 'cl.class_code', '=', 'ed.empClassification')
            ->where('p.pay_date', $payDate);

        if ($request->filled('company_id') && $request->company_id !== 'all') {
            $q->where('ed.empCompID', $request->company_id);
        }
        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $q->where('ed.empDepID', $request->department_id);
        }
        if ($request->filled('classification_id') && $request->classification_id !== 'all') {
            $q->where('ed.empClassification', $request->classification_id);
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
                COALESCE(cl.class_desc, ed.empClassification, '—') as classification,
                COALESCE(d.dep_name, '—') as department_name,
                p.basic_salary, p.allowances, p.overtime_pay, p.holiday_pay, p.night_diff_pay,
                p.gross_pay,
                p.late_deduction, p.undertime_deduction, p.abs_ut_deduction, p.penalty_amount,
                p.sss_contribution, p.philhealth_contribution, p.pagibig_contribution, p.withholding_tax,
                p.sss_loan, p.pagibig_loan, p.company_loan, p.cash_advance, p.other_deduction,
                p.outPassDeduction, p.overBreakDeduction, p.adjustment_amount,
                p.total_deductions, p.net_pay, p.pay_rec
            ")
            ->orderBy('department_name')
            ->orderBy('employee_name')
            ->get()
            ->map(function ($r) {
                $numeric = ['basic_salary', 'allowances', 'overtime_pay', 'holiday_pay', 'night_diff_pay', 'gross_pay',
                    'late_deduction', 'undertime_deduction', 'abs_ut_deduction', 'penalty_amount',
                    'sss_contribution', 'philhealth_contribution', 'pagibig_contribution', 'withholding_tax',
                    'sss_loan', 'pagibig_loan', 'company_loan', 'cash_advance', 'other_deduction',
                    'outPassDeduction', 'overBreakDeduction', 'adjustment_amount',
                    'total_deductions', 'net_pay', 'pay_rec'];
                foreach ($numeric as $k) { $r->$k = (float) ($r->$k ?? 0); }
                $r->tardiness = round($r->late_deduction + $r->undertime_deduction + $r->abs_ut_deduction, 2);
                $r->loans     = round($r->sss_loan + $r->pagibig_loan + $r->company_loan + $r->cash_advance, 2);
                $r->other_ded = round($r->other_deduction + $r->penalty_amount + $r->outPassDeduction + $r->overBreakDeduction, 2);
                // Register totals are derived from the component columns so the row always
                // ties out (stored total_deductions/net_pay can drift in older payroll rows).
                $r->total_ded = round($r->tardiness + $r->sss_contribution + $r->philhealth_contribution
                    + $r->pagibig_contribution + $r->withholding_tax + $r->loans + $r->other_ded, 2);
                $r->net       = round($r->gross_pay - $r->total_ded, 2);
                return $r;
            });

        return [$rows, $payDate];
    }

    public function fetch(Request $request)
    {
        [$rows, $payDate] = $this->compute($request);

        return response()->json([
            'data'     => $rows,
            'pay_date' => $payDate,
            'count'    => $rows->count(),
            'totals'   => $this->totals($rows),
        ]);
    }

    private function totals(Collection $rows): array
    {
        $keys = ['basic_salary', 'allowances', 'overtime_pay', 'holiday_pay', 'night_diff_pay',
            'gross_pay', 'tardiness', 'sss_contribution', 'philhealth_contribution', 'pagibig_contribution',
            'withholding_tax', 'sss_loan', 'pagibig_loan', 'company_loan', 'cash_advance',
            'loans', 'other_ded', 'total_ded', 'net'];
        $t = [];
        foreach ($keys as $k) { $t[$k] = round($rows->sum($k), 2); }
        return $t;
    }

    public function export(Request $request)
    {
        [$rows, $payDate] = $this->compute($request);
        $label = $this->periodLabel($rows, $payDate);

        // Money columns E..U mapped to row properties (no positional drift).
        $money = [
            'E' => 'basic_salary', 'F' => 'allowances', 'G' => 'overtime_pay', 'H' => 'holiday_pay', 'I' => 'night_diff_pay',
            'J' => 'gross_pay', 'K' => 'tardiness', 'L' => 'sss_contribution', 'M' => 'philhealth_contribution',
            'N' => 'pagibig_contribution', 'O' => 'withholding_tax', 'P' => 'sss_loan', 'Q' => 'pagibig_loan',
            'R' => 'company_loan', 'S' => 'cash_advance', 'T' => 'total_ded', 'U' => 'net',
        ];
        $headers = ['NO.', 'EMP ID', 'EMPLOYEE NAME', 'CLASSIFICATION',
            'BASIC', 'ALLOWANCE', 'OVERTIME', 'HOLIDAY', 'NIGHT DIFF', 'GROSS PAY',
            'LATE/UT', 'SSS', 'PHILHEALTH', 'PAG-IBIG', 'W/TAX', 'SSS LOAN', 'HDMF LOAN', 'CO. LOAN', 'CASH ADV', 'TOTAL DED', 'NET PAY'];

        $x = new SimpleXlsx('Payroll Register');
        $x->setColumnWidths([5, 12, 30, 16, 12, 12, 12, 12, 12, 13, 11, 11, 12, 11, 11, 11, 11, 11, 11, 13, 13]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', 'PAYROLL REGISTER', SimpleXlsx::S_TITLE);
        $x->setString('A3', $label, SimpleXlsx::S_TITLE);

        $hr = 5;
        $cols = range('A', 'U');
        foreach ($headers as $i => $h) { $x->setString("{$cols[$i]}{$hr}", $h, SimpleXlsx::S_BOLD); }

        $r = $hr + 1;
        $n = 0;
        foreach ($rows as $row) {
            $n++;
            $x->setNumber("A{$r}", $n, SimpleXlsx::S_NORMAL);
            $x->setString("B{$r}", (string) $row->employee_id, SimpleXlsx::S_TEXT);
            $x->setString("C{$r}", strtoupper((string) $row->employee_name), SimpleXlsx::S_NORMAL);
            $x->setString("D{$r}", (string) $row->classification, SimpleXlsx::S_NORMAL);
            foreach ($money as $col => $key) {
                $x->setNumber("{$col}{$r}", (float) $row->$key, SimpleXlsx::S_MONEY);
            }
            $r++;
        }

        $x->setString("C{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        foreach ($money as $col => $key) {
            $x->setNumber("{$col}{$r}", (float) $rows->sum($key), SimpleXlsx::S_SUBTOTAL);
        }

        $path = $x->saveToTempFile();
        return response()->download($path, 'Payroll_Register_' . $payDate . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        [$rows, $payDate] = $this->compute($request);

        return view('pages.reports.payroll_register_print', [
            'rows'       => $rows,
            'label'      => $this->periodLabel($rows, $payDate),
            'letterhead' => self::LETTERHEAD,
            'totals'     => $this->totals($rows),
        ]);
    }

    private function periodLabel(Collection $rows, $payDate): string
    {
        $first = $rows->first();
        $pay = 'Pay date: ' . Carbon::parse($payDate)->format('M d, Y');
        if ($first && isset($first->payroll_start_date)) {
            return $pay;
        }
        $period = DB::table('payrolls')->where('pay_date', $payDate)
            ->selectRaw('MIN(payroll_start_date) s, MAX(payroll_end_date) e')->first();
        if ($period && $period->s) {
            return 'Cut-off: ' . Carbon::parse($period->s)->format('M d') . ' – ' . Carbon::parse($period->e)->format('M d, Y')
                . '   •   ' . $pay;
        }
        return $pay;
    }
}
