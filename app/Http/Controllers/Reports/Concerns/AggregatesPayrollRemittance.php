<?php

namespace App\Http\Controllers\Reports\Concerns;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Shared query helpers for the statutory-remittance reports
 * (BIR 1601-C/1604-C, SSS R-3, PhilHealth RF-1, Pag-IBIG MCRF).
 *
 * All of them aggregate `payrolls` rows for a period, per employee, join the
 * employee's government ID number, and honour the same company/department/
 * search filters used across the other reports (see ThirteenthMonthController).
 *
 * Government contributions + withholding tax are stored per payroll row and are
 * (by design) end-of-month + non-trainee only, so summing them across a month or
 * a year is safe and never double-counts.
 */
trait AggregatesPayrollRemittance
{
    /**
     * Resolve the reporting window from the request.
     *
     * mode=monthly (default) → year + month  → one calendar month
     * mode=annual            → year          → whole calendar year (alphalist)
     *
     * @return array{start:string,end:string,label:string,year:int,month:?int,mode:string}
     */
    protected function periodParams(Request $request): array
    {
        $year = (int) ($request->input('year') ?: now()->year);
        $mode = $request->input('mode') === 'annual' ? 'annual' : 'monthly';

        if ($mode === 'annual') {
            return [
                'start' => "{$year}-01-01",
                'end'   => "{$year}-12-31",
                'label' => "CALENDAR YEAR {$year}",
                'year'  => $year,
                'month' => null,
                'mode'  => 'annual',
            ];
        }

        $month = (int) ($request->input('month') ?: now()->month);
        $month = max(1, min(12, $month));
        $ref   = Carbon::create($year, $month, 1);

        return [
            'start' => $ref->copy()->startOfMonth()->format('Y-m-d'),
            'end'   => $ref->copy()->endOfMonth()->format('Y-m-d'),
            'label' => strtoupper($ref->format('F Y')),
            'year'  => $year,
            'month' => $month,
            'mode'  => 'monthly',
        ];
    }

    /**
     * Per-employee aggregation of payroll figures for the window.
     *
     * @param  array<string,string>  $sums    alias => raw SQL expression to SUM
     * @param  string                $govCol  qualified column for the gov ID (e.g. 'ed.empSSS')
     * @return Collection
     */
    protected function fetchRemittanceRows(Request $request, array $sums, string $govCol): Collection
    {
        $p = $this->periodParams($request);

        $q = DB::table('payrolls as p')
            ->join('users as u', 'u.empID', '=', 'p.employee_id')
            ->leftJoin('emp_details as ed', 'ed.empID', '=', 'p.employee_id')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->leftJoin('companies as c', 'c.comp_id', '=', 'ed.empCompID')
            ->whereBetween('p.pay_date', [$p['start'], $p['end']]);

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

        $select = "
            u.empID as employee_id,
            TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as employee_name,
            COALESCE({$govCol}, '') as gov_id,
            COALESCE(d.dep_name, '—') as department_name,
            COALESCE(c.comp_name, '—') as company_name
        ";
        foreach ($sums as $alias => $expr) {
            $select .= ", SUM({$expr}) as {$alias}";
        }

        return $q->selectRaw($select)
            ->groupBy('u.empID', 'employee_name', 'gov_id', 'department_name', 'company_name')
            ->orderBy('employee_name')
            ->get();
    }

    /**
     * The employer registration number to print on the remittance header.
     * When a single department is selected, use that department's number;
     * otherwise fall back to the first department that carries one.
     */
    protected function employerNumber(Request $request, string $column): string
    {
        $q = DB::table('departments');
        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $q->where('id', $request->department_id);
        }
        return (string) ($q->whereNotNull($column)->where($column, '!=', '')->value($column) ?? '');
    }

    /** Filter selects for the company/department dropdowns. */
    protected function filterOptions(): array
    {
        return [
            'departments' => \App\Models\department::orderBy('dep_name')->get(),
            'companies'   => DB::table('companies')->orderBy('comp_name')->get(),
            'years'       => range((int) now()->year, (int) now()->year - 5),
        ];
    }
}
