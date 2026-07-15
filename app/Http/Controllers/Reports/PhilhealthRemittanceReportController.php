<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\AggregatesPayrollRemittance;
use App\Support\SimpleXlsx;
use Illuminate\Http\Request;

/**
 * PhilHealth Contribution Report — the RF-1 Employer Remittance worksheet.
 * Per-employee PhilHealth number, EE share, ER share, and total for a month.
 */
class PhilhealthRemittanceReportController extends Controller
{
    use AggregatesPayrollRemittance;

    private const LETTERHEAD = 'DEMO';

    private const SUMS = [
        'ee' => 'COALESCE(p.philhealth_contribution,0)',
        'er' => 'COALESCE(p.philhealth_employer,0)',
    ];

    public function index()
    {
        return view('pages.reports.philhealth_remittance', $this->filterOptions());
    }

    private function compute(Request $request): array
    {
        $p    = $this->periodParams($request);
        $rows = $this->fetchRemittanceRows($request, self::SUMS, 'ed.empPhilhealth')
            ->map(function ($r) {
                $r->ee    = (float) $r->ee;
                $r->er    = (float) $r->er;
                $r->total = round($r->ee + $r->er, 2);
                return $r;
            })
            ->filter(fn ($r) => $r->total > 0)
            ->values();

        return [$rows, $p, $this->employerNumber($request, 'dep_philhealth_employer_no')];
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
            'total_all'   => round($rows->sum('total'), 2),
            'count'       => $rows->count(),
        ]);
    }

    public function export(Request $request)
    {
        [$rows, $p, $employerNo] = $this->compute($request);

        $x = new SimpleXlsx('PhilHealth RF-1');
        $x->setColumnWidths([6, 20, 34, 16, 16, 16]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', "PHILHEALTH REMITTANCE REPORT (RF-1) — {$p['label']}", SimpleXlsx::S_TITLE);
        $x->setString('A3', 'PhilHealth Employer No.: ' . ($employerNo ?: '—'), SimpleXlsx::S_TITLE);

        $hr = 5;
        $headers = ['NO.', 'PHILHEALTH NO.', 'EMPLOYEE NAME', 'EE SHARE', 'ER SHARE', 'TOTAL'];
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $i => $col) {
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
            $r++;
        }

        $x->setString("C{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("D{$r}", (float) $rows->sum('ee'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("E{$r}", (float) $rows->sum('er'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("F{$r}", (float) $rows->sum('total'), SimpleXlsx::S_SUBTOTAL);

        $path = $x->saveToTempFile();

        return response()->download($path, 'PhilHealth_RF1_' . str_replace(' ', '_', $p['label']) . '.xlsx', [
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
            'title'         => 'PhilHealth Remittance Report (RF-1)',
            'employerLabel' => 'PhilHealth Employer No.',
            'employerNo'    => $employerNo,
            'govLabel'      => 'PhilHealth No.',
            'columns'       => ['EE Share', 'ER Share', 'Total'],
            'valueKeys'     => ['ee', 'er', 'total'],
            'totals'        => [$rows->sum('ee'), $rows->sum('er'), $rows->sum('total')],
            'note'          => 'Premium contributions per RA 11223 (Universal Health Care). Employee and employer shares are equal halves of the monthly premium.',
        ]);
    }
}
