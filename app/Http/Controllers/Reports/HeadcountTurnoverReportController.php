<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Support\SimpleXlsx;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Headcount / Manpower & Turnover — active-headcount snapshot by department and
 * classification, plus new hires and separations within a chosen year and an
 * approximate turnover rate. Reads emp_details (empStatus, empDateHired,
 * separation_date/reason, years_rendered).
 */
class HeadcountTurnoverReportController extends Controller
{
    private const LETTERHEAD = 'KWATOGS LOMI HOUSE';

    public function index()
    {
        return view('pages.reports.headcount', [
            'companies' => DB::table('companies')->orderBy('comp_name')->get(),
            'years'     => range((int) now()->year, (int) now()->year - 6),
        ]);
    }

    private function base(Request $request)
    {
        $q = DB::table('emp_details as ed')
            ->join('users as u', 'u.empID', '=', 'ed.empID');
        if ($request->filled('company_id') && $request->company_id !== 'all') {
            $q->where('ed.empCompID', $request->company_id);
        }
        return $q;
    }

    private function compute(Request $request): array
    {
        $year  = (int) ($request->input('year') ?: now()->year);
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end   = Carbon::create($year, 12, 31)->endOfDay();

        // Active headcount by department
        $byDept = $this->base($request)
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->where('ed.empStatus', '1')
            ->selectRaw("COALESCE(d.dep_name,'— Unassigned —') as department_name, COUNT(*) as headcount")
            ->groupBy('department_name')->orderBy('department_name')->get()
            ->map(fn ($r) => (object) ['department_name' => $r->department_name, 'headcount' => (int) $r->headcount]);

        // Active headcount by classification
        $byClass = $this->base($request)
            ->leftJoin('classifications as cl', 'cl.class_code', '=', 'ed.empClassification')
            ->where('ed.empStatus', '1')
            ->selectRaw("COALESCE(cl.class_desc, ed.empClassification, '— None —') as classification, COUNT(*) as headcount")
            ->groupBy('classification')->orderByDesc('headcount')->get()
            ->map(fn ($r) => (object) ['classification' => $r->classification, 'headcount' => (int) $r->headcount]);

        $active = $this->base($request)->where('ed.empStatus', '1')->count();

        // New hires within the year
        $newHires = $this->base($request)
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->leftJoin('positions as p', 'p.id', '=', 'ed.empPos')
            ->whereBetween('ed.empDateHired', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("u.empID as employee_id, TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as name,
                COALESCE(d.dep_name,'—') as department, COALESCE(p.pos_desc,'—') as position, ed.empDateHired as hired, ed.empStatus")
            ->orderBy('ed.empDateHired')->get();

        // Separations within the year (needs a separation_date)
        $separations = $this->base($request)
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->whereNotNull('ed.separation_date')
            ->whereBetween('ed.separation_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("u.empID as employee_id, TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as name,
                COALESCE(d.dep_name,'—') as department, ed.separation_date as sep_date, ed.separation_reason as reason,
                ed.years_rendered, ed.empStatus")
            ->orderBy('ed.separation_date')->get();

        $sepCount   = $separations->count();
        $hireCount  = $newHires->count();
        // Approximate turnover = separations / active headcount.
        $turnover   = $active > 0 ? round(($sepCount / $active) * 100, 2) : 0.0;

        $stats = [
            'active'    => $active,
            'new_hires' => $hireCount,
            'separations' => $sepCount,
            'turnover'  => $turnover,
        ];

        return [$byDept, $byClass, $newHires, $separations, $stats, $year];
    }

    private function statusLabel($s): string
    {
        return match ((string) $s) {
            '1' => 'Employed', '0' => 'Resigned', '2' => 'End of Contract', default => 'Inactive',
        };
    }

    public function fetch(Request $request)
    {
        [$byDept, $byClass, $newHires, $separations, $stats, $year] = $this->compute($request);

        $newHires = $newHires->map(function ($r) {
            $r->hired_fmt  = $r->hired ? Carbon::parse($r->hired)->format('M d, Y') : '—';
            $r->status_lbl = $this->statusLabel($r->empStatus);
            return $r;
        });
        $separations = $separations->map(function ($r) {
            $r->sep_fmt    = $r->sep_date ? Carbon::parse($r->sep_date)->format('M d, Y') : '—';
            $r->status_lbl = $this->statusLabel($r->empStatus);
            $r->years_rendered = $r->years_rendered !== null ? (float) $r->years_rendered : null;
            return $r;
        });

        return response()->json([
            'byDept' => $byDept, 'byClass' => $byClass,
            'newHires' => $newHires, 'separations' => $separations,
            'stats' => $stats, 'year' => $year,
        ]);
    }

    public function export(Request $request)
    {
        [$byDept, $byClass, $newHires, $separations, $stats, $year] = $this->compute($request);

        $x = new SimpleXlsx('Headcount');
        $x->setColumnWidths([28, 12, 30, 22, 16, 24, 12]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', "HEADCOUNT / MANPOWER & TURNOVER — {$year}", SimpleXlsx::S_TITLE);
        $x->setString('A3', "Active: {$stats['active']}   •   New hires: {$stats['new_hires']}   •   Separations: {$stats['separations']}   •   Turnover: {$stats['turnover']}%", SimpleXlsx::S_TITLE);

        $r = 5;
        $x->setString("A{$r}", 'HEADCOUNT BY DEPARTMENT', SimpleXlsx::S_BOLD); $r++;
        $x->setString("A{$r}", 'DEPARTMENT', SimpleXlsx::S_BOLD); $x->setString("B{$r}", 'HEADS', SimpleXlsx::S_BOLD); $r++;
        foreach ($byDept as $d) {
            $x->setString("A{$r}", $d->department_name, SimpleXlsx::S_NORMAL);
            $x->setNumber("B{$r}", (float) $d->headcount, SimpleXlsx::S_NORMAL); $r++;
        }
        $x->setString("A{$r}", 'TOTAL ACTIVE', SimpleXlsx::S_BOLD); $x->setNumber("B{$r}", (float) $stats['active'], SimpleXlsx::S_SUBTOTAL);

        $r += 2;
        $x->setString("A{$r}", 'NEW HIRES', SimpleXlsx::S_BOLD); $r++;
        $x->setString("A{$r}", 'EMP ID', SimpleXlsx::S_BOLD); $x->setString("B{$r}", 'NAME', SimpleXlsx::S_BOLD);
        $x->setString("C{$r}", 'DEPARTMENT', SimpleXlsx::S_BOLD); $x->setString("D{$r}", 'POSITION', SimpleXlsx::S_BOLD);
        $x->setString("E{$r}", 'DATE HIRED', SimpleXlsx::S_BOLD); $r++;
        foreach ($newHires as $h) {
            $x->setString("A{$r}", (string) $h->employee_id, SimpleXlsx::S_TEXT);
            $x->setString("B{$r}", strtoupper((string) $h->name), SimpleXlsx::S_NORMAL);
            $x->setString("C{$r}", (string) $h->department, SimpleXlsx::S_NORMAL);
            $x->setString("D{$r}", (string) $h->position, SimpleXlsx::S_NORMAL);
            $x->setString("E{$r}", $h->hired ? Carbon::parse($h->hired)->format('M d, Y') : '', SimpleXlsx::S_TEXT); $r++;
        }

        $r += 2;
        $x->setString("A{$r}", 'SEPARATIONS', SimpleXlsx::S_BOLD); $r++;
        $x->setString("A{$r}", 'EMP ID', SimpleXlsx::S_BOLD); $x->setString("B{$r}", 'NAME', SimpleXlsx::S_BOLD);
        $x->setString("C{$r}", 'DEPARTMENT', SimpleXlsx::S_BOLD); $x->setString("D{$r}", 'SEPARATION DATE', SimpleXlsx::S_BOLD);
        $x->setString("E{$r}", 'REASON', SimpleXlsx::S_BOLD); $x->setString("F{$r}", 'YEARS', SimpleXlsx::S_BOLD); $r++;
        foreach ($separations as $s) {
            $x->setString("A{$r}", (string) $s->employee_id, SimpleXlsx::S_TEXT);
            $x->setString("B{$r}", strtoupper((string) $s->name), SimpleXlsx::S_NORMAL);
            $x->setString("C{$r}", (string) $s->department, SimpleXlsx::S_NORMAL);
            $x->setString("D{$r}", $s->sep_date ? Carbon::parse($s->sep_date)->format('M d, Y') : '', SimpleXlsx::S_TEXT);
            $x->setString("E{$r}", (string) $s->reason, SimpleXlsx::S_NORMAL);
            $x->setNumber("F{$r}", (float) ($s->years_rendered ?? 0), SimpleXlsx::S_NORMAL); $r++;
        }

        $path = $x->saveToTempFile();
        return response()->download($path, "Headcount_Turnover_{$year}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        [$byDept, $byClass, $newHires, $separations, $stats, $year] = $this->compute($request);

        return view('pages.reports.headcount_print', [
            'byDept' => $byDept, 'byClass' => $byClass, 'newHires' => $newHires, 'separations' => $separations,
            'stats' => $stats, 'year' => $year, 'letterhead' => self::LETTERHEAD,
            'statusLabel' => fn ($s) => $this->statusLabel($s),
        ]);
    }
}
