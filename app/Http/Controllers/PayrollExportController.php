<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Support\SimpleXlsx;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Payroll disbursement exports (dependency-free .xlsx via App\Support\SimpleXlsx).
 *   - exportCash : Name + Pay Receivable list for cash release
 *   - exportCard : exact ATM bank-upload layout (grouped per company, running
 *                  number, name, account number, pay-receivable amount, subtotals,
 *                  ATM/CASH/OVERALL totals, signatory block)
 *
 * Both honor the payroll register filters:
 *   pay_date, company_id, classification_id, department_id
 */
class PayrollExportController extends Controller
{
    /** Letterhead shown at the top of the bank file. */
    private const LETTERHEAD = 'KWATOGS LOMI HOUSE';

    // ── Data ─────────────────────────────────────────────────────────────
    private function getRows(Request $request)
    {
        $payDate          = $request->query('pay_date') ?: $request->query('payDate');
        $companyId        = $request->query('company_id', 'all') ?: 'all';
        $classificationId = $request->query('classification_id', 'all') ?: 'all';
        $departmentId     = $request->query('department_id', 'all') ?: 'all';

        $query = Payroll::with(['employee.empDetail.company'])
            ->join('users', 'payrolls.employee_id', '=', 'users.empID')
            ->select('payrolls.*');

        if (!empty($payDate)) {
            $query->where('payrolls.pay_date', $payDate);
        }

        if ($companyId !== 'all' || $classificationId !== 'all' || $departmentId !== 'all') {
            $query->whereHas('employee.empDetail', function ($q) use ($companyId, $classificationId, $departmentId) {
                if ($companyId !== 'all')        { $q->where('empCompID', $companyId); }
                if ($classificationId !== 'all') { $q->where('empClassification', $classificationId); }
                if ($departmentId !== 'all')     { $q->where('empDepID', $departmentId); }
            });
        }

        return $query->orderBy('users.lname')->orderBy('users.fname')->get();
    }

    private function isCard($row): bool
    {
        return strtoupper((string) (optional(optional($row->employee)->empDetail)->empPayrollType ?? 'CASH')) === 'CARD';
    }

    private function empName($row): string
    {
        $e = optional($row->employee);
        return strtoupper(trim(($e->lname ?? '') . ', ' . ($e->fname ?? '')));
    }

    private function accountNo($row): string
    {
        return (string) (optional(optional($row->employee)->empDetail)->empCardNo ?? '');
    }

    private function compName($row): string
    {
        return optional(optional(optional($row->employee)->empDetail)->company)->comp_name ?: 'UNASSIGNED';
    }

    private function payRec($row): float
    {
        return (float) ($row->pay_rec ?? $row->net_pay ?? 0);
    }

    private function periodText($rows): string
    {
        $first = $rows->first();
        if (!$first) { return ''; }
        try {
            $s = Carbon::parse($first->payroll_start_date);
            $e = Carbon::parse($first->payroll_end_date);
            return strtoupper($s->format('F j') . ' - ' . $e->format('F j, Y'));
        } catch (\Throwable $ex) {
            return '';
        }
    }

    private function download(SimpleXlsx $xlsx, string $filename)
    {
        $path = $xlsx->saveToTempFile();
        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── CARD / ATM bank-upload file ──────────────────────────────────────
    public function exportCard(Request $request)
    {
        $rows = $this->getRows($request);
        $card = $rows->filter(fn ($r) => $this->isCard($r))->values();
        $cashTotal = (float) $rows->reject(fn ($r) => $this->isCard($r))->sum(fn ($r) => $this->payRec($r));

        $x = new SimpleXlsx('ATM PAYROLL');
        $x->setColumnWidths(['A' => 5, 'B' => 38, 'C' => 27, 'D' => 21, 'E' => 3.3, 'F' => 8.3]);

        // Header block
        $x->setString('B2', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('B3', 'PAY RECEIVABLE FOR ATM', SimpleXlsx::S_TITLE);
        $x->setString('B4', $this->periodText($rows), SimpleXlsx::S_TITLE);
        $x->mergeCells('B2:D2');
        $x->mergeCells('B3:D3');
        $x->mergeCells('B4:D4');

        $groups = $card->groupBy(fn ($r) => $this->compName($r));

        $r = 5;
        $counter = 0;
        $atmTotal = 0.0;

        foreach ($groups as $company => $members) {
            $x->setString("B{$r}", strtoupper((string) $company), SimpleXlsx::S_BOLD);
            $r++;

            $sub = 0.0;
            foreach ($members as $m) {
                $counter++;
                $amt = $this->payRec($m);
                $sub += $amt;
                $atmTotal += $amt;

                $x->setNumber("A{$r}", $counter, SimpleXlsx::S_NORMAL);
                $x->setString("B{$r}", $this->empName($m), SimpleXlsx::S_NORMAL);
                $x->setString("C{$r}", $this->accountNo($m), SimpleXlsx::S_TEXT);
                $x->setNumber("D{$r}", $amt, SimpleXlsx::S_MONEY);
                $r++;
            }

            // Subtotal
            $x->setNumber("D{$r}", $sub, SimpleXlsx::S_SUBTOTAL);
            $x->setBool("F{$r}", true, SimpleXlsx::S_BOLD);
            $r += 2; // subtotal + blank separator
        }

        // Totals block
        $r++;
        $x->setString("C{$r}", 'ATM PAYROLL:', SimpleXlsx::S_BOLD);
        $x->setNumber("D{$r}", $atmTotal, SimpleXlsx::S_BOLDMONEY);
        $r++;
        $x->setString("C{$r}", 'CASH PAYROLL:', SimpleXlsx::S_BOLD);
        $x->setNumber("D{$r}", $cashTotal, SimpleXlsx::S_BOLDMONEY);
        $r++;
        $x->setString("C{$r}", 'OVERALL PAYROLL:', SimpleXlsx::S_BOLD);
        $x->setNumber("D{$r}", $atmTotal + $cashTotal, SimpleXlsx::S_BOLDMONEY);

        // Signatories
        $r += 2;
        $x->setString("A{$r}", 'Prepared by:', SimpleXlsx::S_BOLD);
        $x->setString("C{$r}", 'Checked & Verified by:', SimpleXlsx::S_BOLD);
        $x->setString("D{$r}", 'Approved by:', SimpleXlsx::S_BOLD);

        $payDate = $request->query('pay_date') ?: $request->query('payDate') ?: now()->format('Y-m-d');
        return $this->download($x, 'Payroll_ATM_' . $payDate . '.xlsx');
    }

    // ── GOVERNMENT DUES (EE / ER remittance summary) ─────────────────────
    public function exportGovDues(Request $request)
    {
        $rows = $this->getRows($request);

        $x = new SimpleXlsx('GOV DUES');
        $x->setColumnWidths([
            'A' => 5,   // No.
            'B' => 34,  // Employee Name
            'C' => 24,  // Company
            'D' => 11, 'E' => 11, 'F' => 12,   // SSS EE / ER / Total
            'G' => 11, 'H' => 11, 'I' => 12,   // PhilHealth EE / ER / Total
            'J' => 11, 'K' => 11, 'L' => 12,   // Pag-IBIG EE / ER / Total
        ]);

        // Header block
        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', 'GOVERNMENT DUES', SimpleXlsx::S_TITLE);
        $x->setString('A3', $this->periodText($rows), SimpleXlsx::S_TITLE);
        $x->mergeCells('A1:L1');
        $x->mergeCells('A2:L2');
        $x->mergeCells('A3:L3');

        // Column headings
        $r = 5;
        $headings = [
            'A' => 'NO.',          'B' => 'EMPLOYEE NAME', 'C' => 'COMPANY',
            'D' => 'SSS EE',       'E' => 'SSS ER',        'F' => 'SSS Total',
            'G' => 'PHIC EE',      'H' => 'PHIC ER',       'I' => 'PHIC Total',
            'J' => 'HDMF EE',      'K' => 'HDMF ER',       'L' => 'HDMF Total',
        ];
        foreach ($headings as $col => $label) {
            $x->setString("{$col}{$r}", $label, SimpleXlsx::S_BOLD);
        }
        $r++;

        // Running totals per numeric column
        $tot = [
            'sssEe' => 0.0, 'sssEr' => 0.0,
            'phicEe' => 0.0, 'phicEr' => 0.0,
            'hdmfEe' => 0.0, 'hdmfEr' => 0.0,
        ];

        $counter = 0;
        foreach ($rows as $row) {
            $counter++;

            $sssEe  = (float) ($row->sss_contribution ?? 0);
            $sssEr  = (float) ($row->sss_employer ?? 0);
            $phicEe = (float) ($row->philhealth_contribution ?? 0);
            $phicEr = (float) ($row->philhealth_employer ?? 0);
            $hdmfEe = (float) ($row->pagibig_contribution ?? 0);
            $hdmfEr = (float) ($row->pagibig_employer ?? 0);

            $x->setNumber("A{$r}", $counter, SimpleXlsx::S_NORMAL);
            $x->setString("B{$r}", $this->empName($row), SimpleXlsx::S_NORMAL);
            $x->setString("C{$r}", strtoupper((string) $this->compName($row)), SimpleXlsx::S_NORMAL);

            $x->setNumber("D{$r}", $sssEe, SimpleXlsx::S_MONEY);
            $x->setNumber("E{$r}", $sssEr, SimpleXlsx::S_MONEY);
            $x->setNumber("F{$r}", $sssEe + $sssEr, SimpleXlsx::S_MONEY);

            $x->setNumber("G{$r}", $phicEe, SimpleXlsx::S_MONEY);
            $x->setNumber("H{$r}", $phicEr, SimpleXlsx::S_MONEY);
            $x->setNumber("I{$r}", $phicEe + $phicEr, SimpleXlsx::S_MONEY);

            $x->setNumber("J{$r}", $hdmfEe, SimpleXlsx::S_MONEY);
            $x->setNumber("K{$r}", $hdmfEr, SimpleXlsx::S_MONEY);
            $x->setNumber("L{$r}", $hdmfEe + $hdmfEr, SimpleXlsx::S_MONEY);

            $tot['sssEe'] += $sssEe;   $tot['sssEr'] += $sssEr;
            $tot['phicEe'] += $phicEe; $tot['phicEr'] += $phicEr;
            $tot['hdmfEe'] += $hdmfEe; $tot['hdmfEr'] += $hdmfEr;

            $r++;
        }

        // TOTAL row
        $x->setString("B{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("D{$r}", $tot['sssEe'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("E{$r}", $tot['sssEr'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("F{$r}", $tot['sssEe'] + $tot['sssEr'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("G{$r}", $tot['phicEe'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("H{$r}", $tot['phicEr'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("I{$r}", $tot['phicEe'] + $tot['phicEr'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("J{$r}", $tot['hdmfEe'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("K{$r}", $tot['hdmfEr'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("L{$r}", $tot['hdmfEe'] + $tot['hdmfEr'], SimpleXlsx::S_SUBTOTAL);

        $payDate = $request->query('pay_date') ?: $request->query('payDate') ?: now()->format('Y-m-d');
        return $this->download($x, 'Payroll_GovDues_' . $payDate . '.xlsx');
    }

    // ── CASH list (name + net pay only) ──────────────────────────────────
    public function exportCash(Request $request)
    {
        $rows = $this->getRows($request);
        $cash = $rows->filter(fn ($r) => !$this->isCard($r))->values();

        $x = new SimpleXlsx('CASH PAYROLL');
        $x->setColumnWidths(['A' => 5, 'B' => 38, 'C' => 21]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', 'PAY RECEIVABLE FOR CASH', SimpleXlsx::S_TITLE);
        $x->setString('A3', $this->periodText($rows), SimpleXlsx::S_TITLE);
        $x->mergeCells('A1:C1');
        $x->mergeCells('A2:C2');
        $x->mergeCells('A3:C3');

        $r = 5;
        $x->setString("A{$r}", 'NO.', SimpleXlsx::S_BOLD);
        $x->setString("B{$r}", 'EMPLOYEE NAME', SimpleXlsx::S_BOLD);
        $x->setString("C{$r}", 'PAY RECEIVABLE', SimpleXlsx::S_BOLD);
        $r++;

        $counter = 0;
        $total = 0.0;
        foreach ($cash as $m) {
            $counter++;
            $amt = $this->payRec($m);
            $total += $amt;
            $x->setNumber("A{$r}", $counter, SimpleXlsx::S_NORMAL);
            $x->setString("B{$r}", $this->empName($m), SimpleXlsx::S_NORMAL);
            $x->setNumber("C{$r}", $amt, SimpleXlsx::S_MONEY);
            $r++;
        }

        $x->setString("B{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("C{$r}", $total, SimpleXlsx::S_SUBTOTAL);

        $payDate = $request->query('pay_date') ?: $request->query('payDate') ?: now()->format('Y-m-d');
        return $this->download($x, 'Payroll_Cash_' . $payDate . '.xlsx');
    }
}
