<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\AggregatesPayrollRemittance;
use App\Support\SimpleXlsx;
use Illuminate\Http\Request;

/**
 * BIR Withholding Tax on Compensation report.
 *
 *   mode=monthly → the 1601-C monthly remittance worksheet
 *   mode=annual  → the 1604-C Alphalist of Employees (also the data behind
 *                  each employee's Form 2316)
 *
 * Columns are identical in both modes — only the period differs. Figures come
 * straight from the payroll engine, which already computed statutory taxable
 * income and withholding tax per cutoff (end-of-month, non-trainee).
 *   Gross         = gross_pay
 *   Non-Taxable   = mandatory EE contributions (SSS + PhilHealth + Pag-IBIG)
 *   Taxable       = taxable_income
 *   Tax Withheld  = withholding_tax
 */
class BirWithholdingReportController extends Controller
{
    use AggregatesPayrollRemittance;

    private const LETTERHEAD = 'DEMO';

    private const SUMS = [
        'gross'        => 'COALESCE(p.gross_pay,0)',
        'non_taxable'  => 'COALESCE(p.sss_contribution,0)+COALESCE(p.philhealth_contribution,0)+COALESCE(p.pagibig_contribution,0)',
        'taxable'      => 'COALESCE(p.taxable_income,0)',
        'tax'          => 'COALESCE(p.withholding_tax,0)',
    ];

    public function index()
    {
        return view('pages.reports.bir_withholding', $this->filterOptions());
    }

    private function compute(Request $request): array
    {
        $p    = $this->periodParams($request);
        $rows = $this->fetchRemittanceRows($request, self::SUMS, 'ed.empTIN')
            ->map(function ($r) {
                $r->gross       = (float) $r->gross;
                $r->non_taxable = (float) $r->non_taxable;
                $r->taxable     = (float) $r->taxable;
                $r->tax         = round((float) $r->tax, 2);
                return $r;
            })
            ->filter(fn ($r) => $r->gross > 0)
            ->values();

        return [$rows, $p, $this->employerNumber($request, 'dep_tin')];
    }

    public function fetch(Request $request)
    {
        [$rows, $p, $employerTin] = $this->compute($request);

        return response()->json([
            'data'         => $rows,
            'label'        => $p['label'],
            'mode'         => $p['mode'],
            'employer_tin' => $employerTin,
            'total_gross'  => round($rows->sum('gross'), 2),
            'total_nontax' => round($rows->sum('non_taxable'), 2),
            'total_tax'    => round($rows->sum('taxable'), 2),
            'total_wtax'   => round($rows->sum('tax'), 2),
            'count'        => $rows->count(),
        ]);
    }

    public function export(Request $request)
    {
        [$rows, $p, $employerTin] = $this->compute($request);

        $form = $p['mode'] === 'annual' ? 'ALPHALIST (1604-C)' : 'MONTHLY REMITTANCE (1601-C)';

        $x = new SimpleXlsx('BIR Withholding');
        $x->setColumnWidths([6, 18, 34, 16, 16, 16, 16]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', "BIR WITHHOLDING TAX ON COMPENSATION — {$form}", SimpleXlsx::S_TITLE);
        $x->setString('A3', "Period: {$p['label']}   •   Employer TIN: " . ($employerTin ?: '—'), SimpleXlsx::S_TITLE);

        $hr = 5;
        $headers = ['NO.', 'TIN', 'EMPLOYEE NAME', 'GROSS COMP', 'NON-TAXABLE', 'TAXABLE', 'TAX WITHHELD'];
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
            $x->setNumber("D{$r}", (float) $row->gross, SimpleXlsx::S_MONEY);
            $x->setNumber("E{$r}", (float) $row->non_taxable, SimpleXlsx::S_MONEY);
            $x->setNumber("F{$r}", (float) $row->taxable, SimpleXlsx::S_MONEY);
            $x->setNumber("G{$r}", (float) $row->tax, SimpleXlsx::S_MONEY);
            $r++;
        }

        $x->setString("C{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("D{$r}", (float) $rows->sum('gross'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("E{$r}", (float) $rows->sum('non_taxable'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("F{$r}", (float) $rows->sum('taxable'), SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("G{$r}", (float) $rows->sum('tax'), SimpleXlsx::S_SUBTOTAL);

        $fname = ($p['mode'] === 'annual' ? 'BIR_Alphalist_' : 'BIR_1601C_') . str_replace(' ', '_', $p['label']);
        $path  = $x->saveToTempFile();

        return response()->download($path, $fname . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        [$rows, $p, $employerTin] = $this->compute($request);

        $title = $p['mode'] === 'annual'
            ? 'BIR Alphalist of Employees (1604-C)'
            : 'BIR Monthly Remittance — Withholding Tax on Compensation (1601-C)';

        return view('pages.reports.remittance_print', [
            'rows'          => $rows,
            'label'         => $p['label'],
            'letterhead'    => self::LETTERHEAD,
            'title'         => $title,
            'employerLabel' => 'Employer TIN',
            'employerNo'    => $employerTin,
            'govLabel'      => 'TIN',
            'columns'       => ['Gross Comp', 'Non-Taxable', 'Taxable', 'Tax Withheld'],
            'valueKeys'     => ['gross', 'non_taxable', 'taxable', 'tax'],
            'totals'        => [$rows->sum('gross'), $rows->sum('non_taxable'), $rows->sum('taxable'), $rows->sum('tax')],
            'note'          => 'Non-Taxable = mandatory SSS, PhilHealth and Pag-IBIG employee contributions. Taxable income and withholding tax are computed per the TRAIN law schedule (BIR withholding tax table). The Annual mode is the basis for each employee\'s Form 2316.',
        ]);
    }
}
