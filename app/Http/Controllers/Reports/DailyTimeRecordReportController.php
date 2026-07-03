<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Support\SimpleXlsx;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Daily Time Record (DTR) — the formal per-employee monthly time record:
 * every day of the month with first time-in, last time-out, hours rendered,
 * late/undertime/night-diff minutes, and status. Reads the computed
 * `attendance_summaries` for the figures and `home_attendances` for the actual
 * punch times (MIN time-in / MAX time-out per day).
 */
class DailyTimeRecordReportController extends Controller
{
    private const LETTERHEAD = 'KWATOGS LOMI HOUSE';

    public function index()
    {
        $employees = DB::table('users as u')
            ->leftJoin('emp_details as ed', 'ed.empID', '=', 'u.empID')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->whereNotNull('u.empID')
            ->selectRaw("u.empID, TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as name, COALESCE(d.dep_name,'') as dept")
            ->orderBy('name')
            ->get();

        return view('pages.reports.dtr', [
            'employees' => $employees,
            'years'     => range((int) now()->year, (int) now()->year - 5),
        ]);
    }

    private function compute(Request $request): array
    {
        $empId = $request->input('employee_id');
        $year  = (int) ($request->input('year') ?: now()->year);
        $month = (int) ($request->input('month') ?: now()->month);
        $month = max(1, min(12, $month));

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $employee = null;
        $days = [];
        $totals = ['present' => 0, 'absent' => 0, 'hours' => 0.0, 'late' => 0, 'undertime' => 0, 'night_diff' => 0];

        if ($empId) {
            $employee = DB::table('users as u')
                ->leftJoin('emp_details as ed', 'ed.empID', '=', 'u.empID')
                ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
                ->leftJoin('positions as p', 'p.id', '=', 'ed.empPos')
                ->where('u.empID', $empId)
                ->selectRaw("u.empID, TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as name,
                    COALESCE(d.dep_name,'—') as department, COALESCE(p.pos_desc,'—') as position")
                ->first();

            $summaries = DB::table('attendance_summaries')
                ->where('employee_id', $empId)
                ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->keyBy(fn ($r) => Carbon::parse($r->attendance_date)->toDateString());

            $punches = DB::table('home_attendances')
                ->where('employee_id', $empId)
                ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                ->selectRaw('attendance_date, MIN(time_in) as first_in, MAX(time_out) as last_out')
                ->groupBy('attendance_date')
                ->get()
                ->keyBy(fn ($r) => Carbon::parse($r->attendance_date)->toDateString());

            foreach (CarbonPeriod::create($start, $end) as $day) {
                $key = $day->toDateString();
                $s   = $summaries->get($key);
                $p   = $punches->get($key);

                $row = [
                    'date'       => $key,
                    'day'        => $day->format('D'),
                    'is_weekend' => $day->isWeekend(),
                    'time_in'    => $p && $p->first_in ? Carbon::parse($p->first_in)->format('g:i A') : '',
                    'time_out'   => $p && $p->last_out ? Carbon::parse($p->last_out)->format('g:i A') : '',
                    'hours'      => $s ? (float) $s->total_hours : 0.0,
                    'late'       => $s ? (int) $s->mins_late : 0,
                    'undertime'  => $s ? (int) $s->mins_undertime : 0,
                    'night_diff' => $s ? (int) $s->mins_night_diff : 0,
                    'status'     => $s ? $s->status : '',
                    'remarks'    => $s->remarks ?? '',
                ];
                $days[] = $row;

                if ($s) {
                    if (strtolower((string) $s->status) === 'present') { $totals['present']++; }
                    elseif (strtolower((string) $s->status) === 'absent') { $totals['absent']++; }
                    $totals['hours']      += $row['hours'];
                    $totals['late']       += $row['late'];
                    $totals['undertime']  += $row['undertime'];
                    $totals['night_diff'] += $row['night_diff'];
                }
            }
            $totals['hours'] = round($totals['hours'], 2);
        }

        $label = $start->format('F Y');

        return [$employee, $days, $totals, $label];
    }

    public function fetch(Request $request)
    {
        [$employee, $days, $totals, $label] = $this->compute($request);

        return response()->json([
            'employee' => $employee,
            'days'     => $days,
            'totals'   => $totals,
            'label'    => $label,
        ]);
    }

    public function export(Request $request)
    {
        [$employee, $days, $totals, $label] = $this->compute($request);

        $x = new SimpleXlsx('DTR');
        $x->setColumnWidths([12, 6, 13, 13, 9, 9, 9, 11, 11, 24]);

        $x->setString('A1', self::LETTERHEAD, SimpleXlsx::S_TITLE);
        $x->setString('A2', 'DAILY TIME RECORD — ' . strtoupper($label), SimpleXlsx::S_TITLE);
        $x->setString('A3', $employee ? strtoupper($employee->name) . '  (' . $employee->empID . ')  •  ' . $employee->department : 'No employee selected', SimpleXlsx::S_TITLE);

        $hr = 5;
        $headers = ['DATE', 'DAY', 'TIME IN', 'TIME OUT', 'HOURS', 'LATE', 'UT', 'NIGHT DIFF', 'STATUS', 'REMARKS'];
        $cols = range('A', 'J');
        foreach ($headers as $i => $h) { $x->setString("{$cols[$i]}{$hr}", $h, SimpleXlsx::S_BOLD); }

        $r = $hr + 1;
        foreach ($days as $d) {
            $x->setString("A{$r}", Carbon::parse($d['date'])->format('M d, Y'), SimpleXlsx::S_TEXT);
            $x->setString("B{$r}", $d['day'], SimpleXlsx::S_NORMAL);
            $x->setString("C{$r}", $d['time_in'], SimpleXlsx::S_TEXT);
            $x->setString("D{$r}", $d['time_out'], SimpleXlsx::S_TEXT);
            $x->setNumber("E{$r}", (float) $d['hours'], SimpleXlsx::S_NORMAL);
            $x->setNumber("F{$r}", (float) $d['late'], SimpleXlsx::S_NORMAL);
            $x->setNumber("G{$r}", (float) $d['undertime'], SimpleXlsx::S_NORMAL);
            $x->setNumber("H{$r}", (float) $d['night_diff'], SimpleXlsx::S_NORMAL);
            $x->setString("I{$r}", ucfirst((string) $d['status']), SimpleXlsx::S_NORMAL);
            $x->setString("J{$r}", (string) $d['remarks'], SimpleXlsx::S_NORMAL);
            $r++;
        }

        $x->setString("A{$r}", 'TOTAL', SimpleXlsx::S_BOLD);
        $x->setNumber("E{$r}", (float) $totals['hours'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("F{$r}", (float) $totals['late'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("G{$r}", (float) $totals['undertime'], SimpleXlsx::S_SUBTOTAL);
        $x->setNumber("H{$r}", (float) $totals['night_diff'], SimpleXlsx::S_SUBTOTAL);
        $x->setString("I{$r}", $totals['present'] . 'P / ' . $totals['absent'] . 'A', SimpleXlsx::S_BOLD);

        $fname = 'DTR_' . ($employee->empID ?? 'none') . '_' . str_replace(' ', '_', $label);
        $path  = $x->saveToTempFile();
        return response()->download($path, $fname . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        [$employee, $days, $totals, $label] = $this->compute($request);

        return view('pages.reports.dtr_print', [
            'employee'   => $employee,
            'days'       => $days,
            'totals'     => $totals,
            'label'      => $label,
            'letterhead' => self::LETTERHEAD,
        ]);
    }
}
