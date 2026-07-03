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
 * Tardiness & Absences Summary — per-employee tally of late minutes, undertime,
 * absences, over-break and out-pass minutes for a month, ranked worst-first.
 * A management report built on the computed `attendance_summaries`.
 */
class TardinessAbsenceReportController extends Controller
{
    private const LETTERHEAD = 'KWATOGS LOMI HOUSE';

    public function index()
    {
        return view('pages.reports.tardiness', [
            'departments' => department::orderBy('dep_name')->get(),
            'companies'   => DB::table('companies')->orderBy('comp_name')->get(),
            'years'       => range((int) now()->year, (int) now()->year - 5),
        ]);
    }

    private function compute(Request $request): array
    {
        $year  = (int) ($request->input('year') ?: now()->year);
        $month = (int) ($request->input('month') ?: now()->month);
        $month = max(1, min(12, $month));
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $q = DB::table('attendance_summaries as a')
            ->join('users as u', 'u.empID', '=', 'a.employee_id')
            ->leftJoin('emp_details as ed', 'ed.empID', '=', 'a.employee_id')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->whereBetween('a.attendance_date', [$start->toDateString(), $end->toDateString()]);

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

        $rows = $q->selectRaw("
                u.empID as employee_id,
                TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as employee_name,
                COALESCE(d.dep_name,'—') as department_name,
                SUM(COALESCE(a.mins_late,0)) as late_mins,
                SUM(CASE WHEN COALESCE(a.mins_late,0) > 0 THEN 1 ELSE 0 END) as late_days,
                SUM(COALESCE(a.mins_undertime,0)) as ut_mins,
                SUM(CASE WHEN COALESCE(a.mins_undertime,0) > 0 THEN 1 ELSE 0 END) as ut_days,
                SUM(CASE WHEN LOWER(a.status) = 'absent' THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN LOWER(a.status) = 'present' THEN 1 ELSE 0 END) as present_days,
                SUM(COALESCE(a.over_break_minutes,0)) as over_break,
                SUM(COALESCE(a.outpass_minutes,0)) as outpass
            ")
            ->groupBy('u.empID', 'employee_name', 'department_name')
            ->get()
            ->map(function ($r) {
                foreach (['late_mins', 'late_days', 'ut_mins', 'ut_days', 'absent_days', 'present_days', 'over_break', 'outpass'] as $k) {
                    $r->$k = (int) $r->$k;
                }
                $r->infractions = $r->late_mins + $r->ut_mins + ($r->absent_days * 480) + $r->over_break + $r->outpass;
                return $r;
            })
            ->filter(fn ($r) => $r->late_mins > 0 || $r->ut_mins > 0 || $r->absent_days > 0 || $r->over_break > 0 || $r->outpass > 0)
            ->sortByDesc('infractions')
            ->values();

        return [$rows, $start->format('F Y')];
    }

    private function stats(Collection $rows): array
    {
        return [
            'employees'  => $rows->count(),
            'late_mins'  => $rows->sum('late_mins'),
            'ut_mins'    => $rows->sum('ut_mins'),
            'absences'   => $rows->sum('absent_days'),
            'over_break' => $rows->sum('over_break'),
            'outpass'    => $rows->sum('outpass'),
        ];
    }

    public function fetch(Request $request)
    {
        [$rows, $label] = $this->compute($request);
        return response()->json(['data' => $rows, 'label' => $label, 'stats' => $this->stats($rows)]);
    }

    public function export(Request $request)
    {
        [$rows, $label] = $this->compute($request);
        $st = $this->stats($rows);

        $x = new SimpleXlsx('Tardiness');
        $x->setColumnWidths([5, 12, 30, 22, 12, 10, 12, 10, 12, 12, 12]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', 'TARDINESS & ABSENCES SUMMARY — ' . strtoupper($label), SimpleXlsx::S_TITLE);

        $hr = 4;
        $headers = ['NO.', 'EMP ID', 'EMPLOYEE NAME', 'DEPARTMENT', 'LATE (MIN)', 'LATE DAYS', 'UT (MIN)', 'UT DAYS', 'ABSENCES', 'OVER-BREAK', 'OUT-PASS'];
        $cols = range('A', 'K');
        foreach ($headers as $i => $h) { $x->setString("{$cols[$i]}{$hr}", $h, SimpleXlsx::S_BOLD); }

        $r = $hr + 1;
        $n = 0;
        foreach ($rows as $row) {
            $n++;
            $x->setNumber("A{$r}", $n, SimpleXlsx::S_NORMAL);
            $x->setString("B{$r}", (string) $row->employee_id, SimpleXlsx::S_TEXT);
            $x->setString("C{$r}", strtoupper((string) $row->employee_name), SimpleXlsx::S_NORMAL);
            $x->setString("D{$r}", (string) $row->department_name, SimpleXlsx::S_NORMAL);
            $x->setNumber("E{$r}", (float) $row->late_mins, SimpleXlsx::S_NORMAL);
            $x->setNumber("F{$r}", (float) $row->late_days, SimpleXlsx::S_NORMAL);
            $x->setNumber("G{$r}", (float) $row->ut_mins, SimpleXlsx::S_NORMAL);
            $x->setNumber("H{$r}", (float) $row->ut_days, SimpleXlsx::S_NORMAL);
            $x->setNumber("I{$r}", (float) $row->absent_days, SimpleXlsx::S_NORMAL);
            $x->setNumber("J{$r}", (float) $row->over_break, SimpleXlsx::S_NORMAL);
            $x->setNumber("K{$r}", (float) $row->outpass, SimpleXlsx::S_NORMAL);
            $r++;
        }

        $x->setString("C{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("E{$r}", (float) $st['late_mins'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("G{$r}", (float) $st['ut_mins'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("I{$r}", (float) $st['absences'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("J{$r}", (float) $st['over_break'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("K{$r}", (float) $st['outpass'], SimpleXlsx::S_SUBTOTAL);

        $path = $x->saveToTempFile();
        return response()->download($path, 'Tardiness_' . str_replace(' ', '_', $label) . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        [$rows, $label] = $this->compute($request);
        return view('pages.reports.tardiness_print', [
            'rows'       => $rows,
            'label'      => $label,
            'stats'      => $this->stats($rows),
            'letterhead' => self::LETTERHEAD,
        ]);
    }
}
