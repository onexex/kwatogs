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
 * Loan Ledger / Outstanding Balances — every company & government loan/advance
 * (`loans`) with principal, total paid (from `loan_payments`), current balance,
 * amortization, and status. This is the receivable/liability view the payroll
 * loan-deduction engine draws down each cutoff.
 */
class LoanLedgerReportController extends Controller
{
    private const LETTERHEAD = 'KWATOGS LOMI HOUSE';

    public function index()
    {
        return view('pages.reports.loan_ledger', [
            'departments' => department::orderBy('dep_name')->get(),
            'companies'   => DB::table('companies')->orderBy('comp_name')->get(),
            'loanTypes'   => DB::table('loans')->distinct()->orderBy('loan_type')->pluck('loan_type'),
        ]);
    }

    private function compute(Request $request): Collection
    {
        $status = $request->input('status', 'active');

        $q = DB::table('loans as l')
            ->join('users as u', 'u.empID', '=', 'l.employee_id')
            ->leftJoin('emp_details as ed', 'ed.empID', '=', 'l.employee_id')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->leftJoin('companies as c', 'c.comp_id', '=', 'ed.empCompID');

        if ($status !== 'all') {
            $q->where('l.status', $status);
        }
        if ($request->filled('loan_type') && $request->loan_type !== 'all') {
            $q->where('l.loan_type', $request->loan_type);
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

        return $q->selectRaw("
                l.id,
                u.empID as employee_id,
                TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as employee_name,
                COALESCE(d.dep_name,'—') as department_name,
                l.loan_type, l.other_description,
                COALESCE(l.loan_amount,0) as loan_amount,
                COALESCE(l.balance,0) as balance,
                COALESCE(l.monthly_amortization,0) as monthly_amortization,
                l.start_date, l.end_date, l.status, l.is_recurring,
                COALESCE((SELECT SUM(lp.amount_paid) FROM loan_payments lp WHERE lp.loan_id = l.id), 0) as total_paid,
                (SELECT MAX(lp.payment_date) FROM loan_payments lp WHERE lp.loan_id = l.id) as last_payment
            ")
            ->orderBy('employee_name')
            ->orderBy('l.loan_type')
            ->get()
            ->map(function ($r) {
                $r->loan_amount          = (float) $r->loan_amount;
                $r->balance              = (float) $r->balance;
                $r->monthly_amortization = (float) $r->monthly_amortization;
                $r->total_paid           = (float) $r->total_paid;
                $r->is_recurring         = (bool) $r->is_recurring;
                $r->type_label           = $this->typeLabel($r->loan_type, $r->other_description);
                return $r;
            });
    }

    private function typeLabel(?string $type, ?string $desc): string
    {
        $map = [
            'salary' => 'Salary Loan', 'other' => 'Other', 'charges/penalty' => 'Charges / Penalty',
            'cash_adv' => 'Cash Advance', 'sss' => 'SSS Loan', 'pagibig' => 'Pag-IBIG Loan', 'philhealth' => 'PhilHealth',
        ];
        $label = $map[$type] ?? ucfirst((string) $type);
        if (($type === 'other' || !isset($map[$type])) && $desc) {
            $label .= ' — ' . $desc;
        }
        return $label;
    }

    private function stats(Collection $rows): array
    {
        return [
            'count'        => $rows->count(),
            'principal'    => round($rows->sum('loan_amount'), 2),
            'paid'         => round($rows->sum('total_paid'), 2),
            'outstanding'  => round($rows->sum('balance'), 2),
            'monthly'      => round($rows->where('status', 'active')->sum('monthly_amortization'), 2),
        ];
    }

    public function fetch(Request $request)
    {
        $rows = $this->compute($request);
        return response()->json([
            'data'  => $rows,
            'stats' => $this->stats($rows),
        ]);
    }

    public function export(Request $request)
    {
        $rows  = $this->compute($request);
        $stats = $this->stats($rows);

        $x = new SimpleXlsx('Loan Ledger');
        $x->setColumnWidths([5, 12, 30, 20, 24, 14, 14, 14, 14, 12, 12, 12, 11]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', 'LOAN LEDGER / OUTSTANDING BALANCES', SimpleXlsx::S_TITLE);
        $x->setString('A3', 'As of ' . now()->format('M d, Y'), SimpleXlsx::S_TITLE);

        $hr = 5;
        $headers = ['NO.', 'EMP ID', 'EMPLOYEE NAME', 'DEPARTMENT', 'LOAN TYPE',
            'PRINCIPAL', 'TOTAL PAID', 'BALANCE', 'MONTHLY', 'RECURRING', 'START', 'END', 'STATUS'];
        $cols = range('A', 'M');
        foreach ($headers as $i => $h) { $x->setString("{$cols[$i]}{$hr}", $h, SimpleXlsx::S_BOLD); }

        $r = $hr + 1;
        $n = 0;
        foreach ($rows as $row) {
            $n++;
            $x->setNumber("A{$r}", $n, SimpleXlsx::S_NORMAL);
            $x->setString("B{$r}", (string) $row->employee_id, SimpleXlsx::S_TEXT);
            $x->setString("C{$r}", strtoupper((string) $row->employee_name), SimpleXlsx::S_NORMAL);
            $x->setString("D{$r}", (string) $row->department_name, SimpleXlsx::S_NORMAL);
            $x->setString("E{$r}", (string) $row->type_label, SimpleXlsx::S_NORMAL);
            $x->setNumber("F{$r}", (float) $row->loan_amount, SimpleXlsx::S_MONEY);
            $x->setNumber("G{$r}", (float) $row->total_paid, SimpleXlsx::S_MONEY);
            $x->setNumber("H{$r}", (float) $row->balance, SimpleXlsx::S_MONEY);
            $x->setNumber("I{$r}", (float) $row->monthly_amortization, SimpleXlsx::S_MONEY);
            $x->setString("J{$r}", $row->is_recurring ? 'Yes' : 'No', SimpleXlsx::S_NORMAL);
            $x->setString("K{$r}", $row->start_date ? Carbon::parse($row->start_date)->format('Y-m-d') : '', SimpleXlsx::S_TEXT);
            $x->setString("L{$r}", $row->end_date ? Carbon::parse($row->end_date)->format('Y-m-d') : '', SimpleXlsx::S_TEXT);
            $x->setString("M{$r}", ucfirst((string) $row->status), SimpleXlsx::S_NORMAL);
            $r++;
        }

        $x->setString("C{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("F{$r}", (float) $stats['principal'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("G{$r}", (float) $stats['paid'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("H{$r}", (float) $stats['outstanding'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("I{$r}", (float) $stats['monthly'], SimpleXlsx::S_SUBTOTAL);

        $path = $x->saveToTempFile();
        return response()->download($path, 'Loan_Ledger_' . now()->format('Y-m-d') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        $rows = $this->compute($request);

        return view('pages.reports.loan_ledger_print', [
            'rows'       => $rows,
            'stats'      => $this->stats($rows),
            'status'     => ucfirst($request->input('status', 'active')),
            'letterhead' => self::LETTERHEAD,
        ]);
    }
}
