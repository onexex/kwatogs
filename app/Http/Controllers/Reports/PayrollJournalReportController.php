<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Support\SimpleXlsx;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Payroll Journal — the GL posting for a pay run, plus a per-department
 * (cost-centre) breakdown for accounting. Debits (salaries expense + employer
 * contributions) always equal Credits (statutory payables + withholding + loans
 * + net pay), with a single balancing line for non-liability deductions
 * (tardiness / penalties / adjustments) so the entry ties out exactly.
 */
class PayrollJournalReportController extends Controller
{
    private const LETTERHEAD = 'KWATOGS LOMI HOUSE';

    public function index()
    {
        return view('pages.reports.payroll_journal', [
            'companies' => DB::table('companies')->orderBy('comp_name')->get(),
            'payDates'  => DB::table('payrolls')->distinct()->orderByDesc('pay_date')->pluck('pay_date'),
        ]);
    }

    private function compute(Request $request): array
    {
        $payDate = $request->input('pay_date') ?: DB::table('payrolls')->max('pay_date');

        $q = DB::table('payrolls as p')
            ->leftJoin('emp_details as ed', 'ed.empID', '=', 'p.employee_id')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->where('p.pay_date', $payDate);

        if ($request->filled('company_id') && $request->company_id !== 'all') {
            $q->where('ed.empCompID', $request->company_id);
        }

        $agg = "
            COUNT(*) as headcount,
            SUM(COALESCE(p.basic_salary,0)) as basic,
            SUM(COALESCE(p.allowances,0)) as allowances,
            SUM(COALESCE(p.overtime_pay,0)) as overtime,
            SUM(COALESCE(p.holiday_pay,0)) as holiday,
            SUM(COALESCE(p.night_diff_pay,0)) as night_diff,
            SUM(COALESCE(p.gross_pay,0)) as gross,
            SUM(COALESCE(p.sss_contribution,0)) as ee_sss,
            SUM(COALESCE(p.philhealth_contribution,0)) as ee_phic,
            SUM(COALESCE(p.pagibig_contribution,0)) as ee_hdmf,
            SUM(COALESCE(p.sss_employer,0)) as er_sss,
            SUM(COALESCE(p.philhealth_employer,0)) as er_phic,
            SUM(COALESCE(p.pagibig_employer,0)) as er_hdmf,
            SUM(COALESCE(p.withholding_tax,0)) as wtax,
            SUM(COALESCE(p.sss_loan,0)+COALESCE(p.pagibig_loan,0)+COALESCE(p.company_loan,0)+COALESCE(p.cash_advance,0)) as loans,
            SUM(COALESCE(p.late_deduction,0)+COALESCE(p.undertime_deduction,0)+COALESCE(p.abs_ut_deduction,0)) as tardiness,
            SUM(COALESCE(p.other_deduction,0)+COALESCE(p.penalty_amount,0)+COALESCE(p.outPassDeduction,0)+COALESCE(p.overBreakDeduction,0)) as other_ded
        ";

        $byDept = $q->clone()
            ->selectRaw("COALESCE(d.dep_name, '— Unassigned —') as department_name, {$agg}")
            ->groupBy('department_name')
            ->orderBy('department_name')
            ->get()
            ->map(fn ($r) => $this->castRow($r));

        $grand = $this->castRow($q->clone()->selectRaw("'ALL' as department_name, {$agg}")->first());

        // Journal entry (company-wide), guaranteed to balance.
        $debit = [
            'Salaries &amp; Wages Expense'   => $grand->gross,
            'Employer SSS Contribution'   => $grand->er_sss,
            'Employer PhilHealth'         => $grand->er_phic,
            'Employer Pag-IBIG'           => $grand->er_hdmf,
        ];
        $totalDebit = array_sum($debit);

        $credit = [
            'SSS Payable (EE + ER)'        => $grand->ee_sss + $grand->er_sss,
            'PhilHealth Payable (EE + ER)' => $grand->ee_phic + $grand->er_phic,
            'Pag-IBIG Payable (EE + ER)'   => $grand->ee_hdmf + $grand->er_hdmf,
            'Withholding Tax Payable'      => $grand->wtax,
            'Loans &amp; Advances'             => $grand->loans,
            'Net Pay / Cash in Bank'       => $grand->net,
        ];
        $balancing = round($totalDebit - array_sum($credit), 2);
        $credit['Tardiness / Penalties / Adjustments'] = $balancing;

        $journal = [
            'debit'       => array_map(fn ($v) => round($v, 2), $debit),
            'credit'      => array_map(fn ($v) => round($v, 2), $credit),
            'total_debit' => round($totalDebit, 2),
            'total_credit'=> round(array_sum($credit), 2),
        ];

        return [$byDept, $grand, $journal, $payDate];
    }

    private function castRow($r)
    {
        foreach ((array) $r as $k => $v) {
            if ($k !== 'department_name' && is_numeric($v)) { $r->$k = (float) $v; }
        }
        // Net is derived from components so the journal always balances even when the
        // stored net_pay/total_deductions columns have drifted on older payroll rows.
        $r->ee_total = round($r->ee_sss + $r->ee_phic + $r->ee_hdmf, 2);
        $r->net = round($r->gross - ($r->ee_total + $r->wtax + $r->loans + $r->tardiness + $r->other_ded), 2);
        return $r;
    }

    public function fetch(Request $request)
    {
        [$byDept, $grand, $journal, $payDate] = $this->compute($request);

        return response()->json([
            'departments' => $byDept,
            'grand'       => $grand,
            'journal'     => $journal,
            'pay_date'    => $payDate,
        ]);
    }

    public function export(Request $request)
    {
        [$byDept, $grand, $journal, $payDate] = $this->compute($request);

        $x = new SimpleXlsx('Payroll Journal');
        $x->setColumnWidths([28, 10, 14, 14, 14, 14, 14, 14, 14, 14]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', 'PAYROLL JOURNAL — GL SUMMARY BY DEPARTMENT', SimpleXlsx::S_TITLE);
        $x->setString('A3', 'Pay date: ' . Carbon::parse($payDate)->format('M d, Y'), SimpleXlsx::S_TITLE);

        // Department breakdown
        $hr = 5;
        $headers = ['DEPARTMENT', 'HEADS', 'GROSS PAY', 'EE SSS', 'EE PHIC', 'EE HDMF', 'W/TAX', 'LOANS', 'ER SHARE', 'NET PAY'];
        $cols = range('A', 'J');
        foreach ($headers as $i => $h) { $x->setString("{$cols[$i]}{$hr}", $h, SimpleXlsx::S_BOLD); }

        $r = $hr + 1;
        foreach ($byDept as $row) {
            $x->setString("A{$r}", (string) $row->department_name, SimpleXlsx::S_NORMAL);
            $x->setNumber("B{$r}", (float) $row->headcount, SimpleXlsx::S_NORMAL);
            $x->setNumber("C{$r}", (float) $row->gross, SimpleXlsx::S_MONEY);
            $x->setNumber("D{$r}", (float) $row->ee_sss, SimpleXlsx::S_MONEY);
            $x->setNumber("E{$r}", (float) $row->ee_phic, SimpleXlsx::S_MONEY);
            $x->setNumber("F{$r}", (float) $row->ee_hdmf, SimpleXlsx::S_MONEY);
            $x->setNumber("G{$r}", (float) $row->wtax, SimpleXlsx::S_MONEY);
            $x->setNumber("H{$r}", (float) $row->loans, SimpleXlsx::S_MONEY);
            $x->setNumber("I{$r}", (float) ($row->er_sss + $row->er_phic + $row->er_hdmf), SimpleXlsx::S_MONEY);
            $x->setNumber("J{$r}", (float) $row->net, SimpleXlsx::S_MONEY);
            $r++;
        }
        $x->setString("A{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("B{$r}", (float) $grand->headcount, SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("C{$r}", (float) $grand->gross, SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("D{$r}", (float) $grand->ee_sss, SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("E{$r}", (float) $grand->ee_phic, SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("F{$r}", (float) $grand->ee_hdmf, SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("G{$r}", (float) $grand->wtax, SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("H{$r}", (float) $grand->loans, SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("I{$r}", (float) ($grand->er_sss + $grand->er_phic + $grand->er_hdmf), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("J{$r}", (float) $grand->net, SimpleXlsx::S_SUBTOTAL);

        // Journal entry block
        $r += 3;
        $x->setString("A{$r}", 'JOURNAL ENTRY', SimpleXlsx::S_BOLD);
        $r++;
        $x->setString("A{$r}", 'ACCOUNT', SimpleXlsx::S_BOLD);
        $x->setString("B{$r}", 'DEBIT', SimpleXlsx::S_BOLD);
        $x->setString("C{$r}", 'CREDIT', SimpleXlsx::S_BOLD);
        $r++;
        foreach ($journal['debit'] as $acct => $amt) {
            $x->setString("A{$r}", html_entity_decode($acct), SimpleXlsx::S_NORMAL);
            $x->setNumber("B{$r}", (float) $amt, SimpleXlsx::S_MONEY);
            $r++;
        }
        foreach ($journal['credit'] as $acct => $amt) {
            $x->setString("A{$r}", '    ' . html_entity_decode($acct), SimpleXlsx::S_NORMAL);
            $x->setNumber("C{$r}", (float) $amt, SimpleXlsx::S_MONEY);
            $r++;
        }
        $x->setString("A{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("B{$r}", (float) $journal['total_debit'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("C{$r}", (float) $journal['total_credit'], SimpleXlsx::S_SUBTOTAL);

        $path = $x->saveToTempFile();
        return response()->download($path, 'Payroll_Journal_' . $payDate . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        [$byDept, $grand, $journal, $payDate] = $this->compute($request);

        return view('pages.reports.payroll_journal_print', [
            'byDept'     => $byDept,
            'grand'      => $grand,
            'journal'    => $journal,
            'label'      => 'Pay date: ' . Carbon::parse($payDate)->format('M d, Y'),
            'letterhead' => self::LETTERHEAD,
        ]);
    }
}
