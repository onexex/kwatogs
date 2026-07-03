<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\AggregatesPayrollRemittance;
use App\Support\SimpleXlsx;
use Illuminate\Http\Request;

/**
 * SSS Contribution Report — the R-3 Contribution Collection List worksheet.
 * Per-employee SSS number, EE share, ER share, EC, and total for a month.
 *
 * NOTE: the payroll engine stores a single ER figure (`sss_employer`); the EC
 * portion is not persisted separately, so EC is shown as 0.00 and TOTAL = EE+ER.
 */
class SssRemittanceReportController extends Controller
{
    use AggregatesPayrollRemittance;

    private const LETTERHEAD = 'KWATOGS LOMI HOUSE';

    private const SUMS = [
        'ee' => 'COALESCE(p.sss_contribution,0)',
        'er' => 'COALESCE(p.sss_employer,0)',
    ];

    public function index()
    {
        return view('pages.reports.sss_remittance', $this->filterOptions());
    }

    private function compute(Request $request): array
    {
        $p    = $this->periodParams($request);
        $rows = $this->fetchRemittanceRows($request, self::SUMS, 'ed.empSSS')
            ->map(function ($r) {
                $r->ee    = (float) $r->ee;
                $r->er    = (float) $r->er;
                $r->ec    = 0.0;
                $r->total = round($r->ee + $r->er + $r->ec, 2);
                return $r;
            })
            ->filter(fn ($r) => $r->total > 0)
            ->values();

        return [$rows, $p, $this->employerNumber($request, 'dep_sss_employer_no')];
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

        $x = new SimpleXlsx('SSS R-3');
        $x->setColumnWidths([6, 20, 34, 14, 14, 12, 14]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', "SSS CONTRIBUTION COLLECTION LIST (R-3) — {$p['label']}", SimpleXlsx::S_TITLE);
        $x->setString('A3', 'SSS Employer No.: ' . ($employerNo ?: '—'), SimpleXlsx::S_TITLE);

        $hr = 5;
        $headers = ['NO.', 'SSS NO.', 'EMPLOYEE NAME', 'EE SHARE', 'ER SHARE', 'EC', 'TOTAL'];
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
            $x->setNumber("F{$r}", (float) $row->ec, SimpleXlsx::S_MONEY);
            $x->setNumber("G{$r}", (float) $row->total, SimpleXlsx::S_MONEY);
            $r++;
        }

        $x->setString("C{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("D{$r}", (float) $rows->sum('ee'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("E{$r}", (float) $rows->sum('er'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("F{$r}", (float) $rows->sum('ec'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("G{$r}", (float) $rows->sum('total'), SimpleXlsx::S_SUBTOTAL);

        $path = $x->saveToTempFile();

        return response()->download($path, 'SSS_R3_' . str_replace(' ', '_', $p['label']) . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        [$rows, $p, $employerNo] = $this->compute($request);

        return view('pages.reports.remittance_print', [
            'rows'       => $rows,
            'label'      => $p['label'],
            'letterhead' => self::LETTERHEAD,
            'title'      => 'SSS Contribution Collection List (R-3)',
            'employerLabel' => 'SSS Employer No.',
            'employerNo' => $employerNo,
            'govLabel'   => 'SSS No.',
            'columns'    => ['EE Share', 'ER Share', 'EC', 'Total'],
            'valueKeys'  => ['ee', 'er', 'ec', 'total'],
            'totals'     => [$rows->sum('ee'), $rows->sum('er'), $rows->sum('ec'), $rows->sum('total')],
            'note'       => 'EC (Employees\' Compensation) is remitted by the employer and is not separated in the payroll record; TOTAL reflects EE + ER shares.',
        ]);
    }
}
