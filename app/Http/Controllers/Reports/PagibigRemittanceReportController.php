<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\AggregatesPayrollRemittance;
use App\Support\SimpleXlsx;
use Illuminate\Http\Request;

/**
 * Pag-IBIG Contribution Report — the MCRF (Membership Contribution Remittance
 * Form) worksheet. Per-employee Pag-IBIG MID, EE share, ER share, total, and
 * the STL/loan amortization collected for the month (from `pagibig_loan`).
 */
class PagibigRemittanceReportController extends Controller
{
    use AggregatesPayrollRemittance;

    private const LETTERHEAD = 'DEMO';

    private const SUMS = [
        'ee'   => 'COALESCE(p.pagibig_contribution,0)',
        'er'   => 'COALESCE(p.pagibig_employer,0)',
        'loan' => 'COALESCE(p.pagibig_loan,0)',
    ];

    public function index()
    {
        return view('pages.reports.pagibig_remittance', $this->filterOptions());
    }

    private function compute(Request $request): array
    {
        $p    = $this->periodParams($request);
        $rows = $this->fetchRemittanceRows($request, self::SUMS, 'ed.empPagibig')
            ->map(function ($r) {
                $r->ee    = (float) $r->ee;
                $r->er    = (float) $r->er;
                $r->loan  = (float) $r->loan;
                $r->total = round($r->ee + $r->er, 2);
                return $r;
            })
            ->filter(fn ($r) => $r->total > 0 || $r->loan > 0)
            ->values();

        return [$rows, $p, $this->employerNumber($request, 'dep_pagibig_employer_no')];
    }

    public function fetch(Request $request)
    {
        [$rows, $p, $employerNo] = $this->compute($request);

        return response()->json([
            'data'        => $rows,
            'label'       => $p['label'],
            'employer_no' => $employerNo,
            'total_ee'    => round($rows->sum('ee'), 2),
            'total_er'    => round($rows->sum('er'), 2),
            'total_loan'  => round($rows->sum('loan'), 2),
            'total_all'   => round($rows->sum('total'), 2),
            'count'       => $rows->count(),
        ]);
    }

    public function export(Request $request)
    {
        [$rows, $p, $employerNo] = $this->compute($request);

        $x = new SimpleXlsx('Pag-IBIG MCRF');
        $x->setColumnWidths([6, 22, 34, 14, 14, 14, 16]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', "PAG-IBIG CONTRIBUTION REMITTANCE (MCRF) — {$p['label']}", SimpleXlsx::S_TITLE);
        $x->setString('A3', 'Pag-IBIG Employer No.: ' . ($employerNo ?: '—'), SimpleXlsx::S_TITLE);

        $hr = 5;
        $headers = ['NO.', 'PAG-IBIG MID', 'EMPLOYEE NAME', 'EE SHARE', 'ER SHARE', 'TOTAL', 'STL / LOAN'];
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $i => $col) {
            $x->setString("{$col}{$hr}", $headers[$i], SimpleXlsx::S_BOLD);
        }

        $r = $hr + 1;
        $n = 0;
        foreach ($rows as $row) {
            $n++;
            $x->setNumber("A{$r}", $n, SimpleXlsx::S_NORMAL);
            $x->setString("B{$r}", (string) $row->gov_id, SimpleXlsx::S_TEXT);
            $x->setString("C{$r}", strtoupper((string) $row->employee_name), SimpleXlsx::S_NORMAL);
            $x->setNumber("D{$r}", (float) $row->ee, SimpleXlsx::S_MONEY);
            $x->setNumber("E{$r}", (float) $row->er, SimpleXlsx::S_MONEY);
            $x->setNumber("F{$r}", (float) $row->total, SimpleXlsx::S_MONEY);
            $x->setNumber("G{$r}", (float) $row->loan, SimpleXlsx::S_MONEY);
            $r++;
        }

        $x->setString("C{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("D{$r}", (float) $rows->sum('ee'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("E{$r}", (float) $rows->sum('er'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("F{$r}", (float) $rows->sum('total'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("G{$r}", (float) $rows->sum('loan'), SimpleXlsx::S_SUBTOTAL);

        $path = $x->saveToTempFile();

        return response()->download($path, 'PagIBIG_MCRF_' . str_replace(' ', '_', $p['label']) . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        [$rows, $p, $employerNo] = $this->compute($request);

        return view('pages.reports.remittance_print', [
            'rows'          => $rows,
            'label'         => $p['label'],
            'letterhead'    => self::LETTERHEAD,
            'title'         => 'Pag-IBIG Contribution Remittance (MCRF)',
            'employerLabel' => 'Pag-IBIG Employer No.',
            'employerNo'    => $employerNo,
            'govLabel'      => 'Pag-IBIG MID',
            'columns'       => ['EE Share', 'ER Share', 'Total', 'STL / Loan'],
            'valueKeys'     => ['ee', 'er', 'total', 'loan'],
            'totals'        => [$rows->sum('ee'), $rows->sum('er'), $rows->sum('total'), $rows->sum('loan')],
            'note'          => 'Membership contributions (MCRF). STL / Loan is the Short-Term Loan amortization collected for the period and is remitted separately from contributions.',
        ]);
    }
}
