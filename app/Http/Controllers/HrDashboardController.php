<?php

namespace App\Http\Controllers;

use App\Models\empDetail;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\OB;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\PayrollApproval;
use App\Models\ScheduleRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();
        $d = [];

        // ── Workforce ──────────────────────────────────────────────────
        $d['total']    = empDetail::count();
        $d['active']   = empDetail::where('empStatus', '1')->count();
        $d['resigned'] = max(0, $d['total'] - $d['active']);
        $d['cash']     = empDetail::where('empPayrollType', 'CASH')->count();
        $d['card']     = empDetail::where('empPayrollType', 'CARD')->count();

        $d['byDept'] = DB::table('emp_details as e')
            ->leftJoin('departments as dp', 'dp.id', '=', 'e.empDepID')
            ->where('e.empStatus', '1')
            ->selectRaw("dp.id as id, COALESCE(dp.dep_name,'Unassigned') as name, COUNT(*) as c")
            ->groupBy('dp.id', 'name')->orderByDesc('c')->limit(8)->get();

        $d['byCompany'] = DB::table('emp_details as e')
            ->leftJoin('companies as c', 'c.comp_id', '=', 'e.empCompID')
            ->where('e.empStatus', '1')
            ->selectRaw("COALESCE(c.comp_name,'Unassigned') as name, COUNT(*) as c")
            ->groupBy('name')->orderByDesc('c')->limit(7)->get();

        // ── Pending approvals ──────────────────────────────────────────
        $d['pendLeave'] = Leave::whereIn('status', ['FORAPPROVAL', 'APPROVED'])->count();
        $d['pendOt']    = Overtime::whereIn('status', ['FORAPPROVAL', 'APPROVED'])->count();
        $d['pendSched'] = ScheduleRequest::where('status', 'FORAPPROVAL')->count();
        $d['pendTotal'] = $d['pendLeave'] + $d['pendOt'] + $d['pendSched'];

        // ── Today's attendance ─────────────────────────────────────────
        $d['present'] = DB::table('attendance_summaries')->whereDate('attendance_date', $today)->where('total_hours', '>', 0)->distinct('employee_id')->count('employee_id');
        $d['late']    = DB::table('attendance_summaries')->whereDate('attendance_date', $today)->where('mins_late', '>', 0)->distinct('employee_id')->count('employee_id');
        $d['onLeave'] = LeaveDetail::whereDate('date', $today)->where('status', 'APPROVEDBYCFO')->distinct('employee_id')->count('employee_id');
        $d['onOb']    = OB::where('status', 'APPROVEDBYCFO')->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->distinct('employee_id')->count('employee_id');

        $scheduledToday = DB::table('employee_schedules')
            ->whereDate('sched_start_date', '<=', $today)->whereDate('sched_end_date', '>=', $today)
            ->distinct('employee_id')->count('employee_id');
        $d['scheduled'] = $scheduledToday;
        $d['absent']    = max(0, $scheduledToday - $d['present'] - $d['onLeave'] - $d['onOb']);

        // ── Payroll snapshot ───────────────────────────────────────────
        $latest = Payroll::orderByDesc('pay_date')->first();
        $d['payDate']     = $latest->pay_date ?? null;
        $d['payStatus']   = $latest->status ?? null;
        $d['payHeadcount'] = $d['payDate'] ? Payroll::whereDate('pay_date', $d['payDate'])->count() : 0;
        $d['payNet']      = $d['payDate'] ? (float) Payroll::whereDate('pay_date', $d['payDate'])->sum('net_pay') : 0;
        $d['payLocked']   = $d['payDate'] ? PayrollApproval::isLocked($d['payDate']) : false;

        // ── People alerts ──────────────────────────────────────────────
        $weekDays = collect(range(0, 6))->map(fn ($i) => Carbon::today()->addDays($i)->format('m-d'))->all();
        $d['birthdays'] = DB::table('emp_infos as i')
            ->join('users as u', 'u.empID', '=', 'i.empID')
            ->whereNotNull('i.empBdate')
            ->whereIn(DB::raw("DATE_FORMAT(i.empBdate, '%m-%d')"), $weekDays)
            ->selectRaw("TRIM(CONCAT(u.lname,', ',u.fname)) as name, DATE_FORMAT(i.empBdate,'%b %d') as bday")
            ->orderByRaw("DATE_FORMAT(i.empBdate,'%m-%d')")->limit(10)->get();

        $d['regularize'] = DB::table('emp_details as e')
            ->join('users as u', 'u.empID', '=', 'e.empID')
            ->whereNotNull('e.empDateRegular')
            ->whereDate('e.empDateRegular', '>=', $today)
            ->whereDate('e.empDateRegular', '<=', Carbon::today()->addDays(14)->toDateString())
            ->selectRaw("TRIM(CONCAT(u.lname,', ',u.fname)) as name, e.empDateRegular as due")
            ->orderBy('e.empDateRegular')->limit(10)->get();

        $schedEmpIds = DB::table('employee_schedules')
            ->whereDate('sched_start_date', '<=', $today)->whereDate('sched_end_date', '>=', $today)
            ->pluck('employee_id')->unique();
        $d['noSchedule'] = empDetail::where('empStatus', '1')->whereNotIn('empID', $schedEmpIds)->count();

        // ── Attendance patterns ────────────────────────────────────────
        $start14 = Carbon::today()->subDays(13)->toDateString();
        $rows = DB::table('attendance_summaries')
            ->whereDate('attendance_date', '>=', $start14)
            ->selectRaw("attendance_date, COUNT(CASE WHEN total_hours>0 THEN 1 END) as present, COUNT(CASE WHEN mins_late>0 THEN 1 END) as late")
            ->groupBy('attendance_date')->get()->keyBy('attendance_date');

        $trend = [];
        for ($i = 0; $i < 14; $i++) {
            $day = Carbon::today()->subDays(13 - $i);
            $key = $day->toDateString();
            $trend[] = [
                'label'   => $day->format('M d'),
                'short'   => $day->format('D'),
                'present' => (int) ($rows[$key]->present ?? 0),
                'late'    => (int) ($rows[$key]->late ?? 0),
            ];
        }
        $d['trend'] = $trend;

        $start30 = Carbon::today()->subDays(29)->toDateString();
        $d['topLate'] = DB::table('attendance_summaries as a')
            ->join('users as u', 'u.empID', '=', 'a.employee_id')
            ->where('a.attendance_date', '>=', $start30)
            ->selectRaw("TRIM(CONCAT(u.lname,', ',u.fname)) as name, SUM(a.mins_late) as late, COUNT(CASE WHEN a.mins_late>0 THEN 1 END) as occ")
            ->groupBy('a.employee_id', 'u.lname', 'u.fname')
            ->havingRaw('SUM(a.mins_late) > 0')->orderByDesc('late')->limit(6)->get();

        $totalDays = DB::table('attendance_summaries')->whereDate('attendance_date', '>=', $start30)->count();
        $lateDays  = DB::table('attendance_summaries')->whereDate('attendance_date', '>=', $start30)->where('mins_late', '>', 0)->count();
        $d['onTimeRate'] = $totalDays > 0 ? round((($totalDays - $lateDays) / $totalDays) * 100) : 0;
        $d['lateRate']   = $totalDays > 0 ? round(($lateDays / $totalDays) * 100) : 0;

        // ── Who's in right now ─────────────────────────────────────────
        $sinceYday = Carbon::today()->subDay()->toDateString();
        $d['whoInCount'] = DB::table('home_attendances')->whereNull('time_out')
            ->whereDate('attendance_date', '>=', $sinceYday)->distinct('employee_id')->count('employee_id');
        $d['whoIn'] = DB::table('home_attendances as h')
            ->join('users as u', 'u.empID', '=', 'h.employee_id')
            ->whereNull('h.time_out')->whereDate('h.attendance_date', '>=', $sinceYday)
            ->selectRaw("TRIM(CONCAT(u.lname,', ',u.fname)) as name, h.time_in")
            ->orderByDesc('h.time_in')->limit(12)->get();

        // ── 30-day trend ───────────────────────────────────────────────
        $start30t = Carbon::today()->subDays(29)->toDateString();
        $rows30 = DB::table('attendance_summaries')->whereDate('attendance_date', '>=', $start30t)
            ->selectRaw("attendance_date, COUNT(CASE WHEN total_hours>0 THEN 1 END) present, COUNT(CASE WHEN mins_late>0 THEN 1 END) late")
            ->groupBy('attendance_date')->get()->keyBy('attendance_date');
        $trend30 = [];
        for ($i = 0; $i < 30; $i++) {
            $day = Carbon::today()->subDays(29 - $i); $k = $day->toDateString();
            $trend30[] = ['label' => $day->format('M d'), 'short' => $day->format('j'),
                'present' => (int) ($rows30[$k]->present ?? 0), 'late' => (int) ($rows30[$k]->late ?? 0)];
        }
        $d['trend30'] = $trend30;

        // ── Absenteeism rate by department (30 days) ────────────────────
        $d['absentByDept'] = DB::table('employee_schedules as s')
            ->join('emp_details as e', 'e.empID', '=', 's.employee_id')
            ->leftJoin('departments as dp', 'dp.id', '=', 'e.empDepID')
            ->leftJoin('attendance_summaries as a', function ($j) {
                $j->on('a.employee_id', '=', 's.employee_id')->on('a.attendance_date', '=', 's.sched_start_date');
            })
            ->whereDate('s.sched_start_date', '>=', $start30)->whereDate('s.sched_start_date', '<=', $today)
            ->selectRaw("COALESCE(dp.dep_name,'Unassigned') as name, COUNT(*) as scheduled, COUNT(CASE WHEN a.total_hours>0 THEN 1 END) as present")
            ->groupBy('name')->havingRaw('COUNT(*) > 0')->orderByDesc('scheduled')->limit(8)->get();

        return view('pages.management.hr_dashboard', ['d' => $d]);
    }

    /** AJAX: all live counters for whole-dashboard auto-refresh. */
    public function live()
    {
        $today = Carbon::today()->toDateString();
        $present   = DB::table('attendance_summaries')->whereDate('attendance_date', $today)->where('total_hours', '>', 0)->distinct('employee_id')->count('employee_id');
        $late      = DB::table('attendance_summaries')->whereDate('attendance_date', $today)->where('mins_late', '>', 0)->distinct('employee_id')->count('employee_id');
        $onLeave   = LeaveDetail::whereDate('date', $today)->where('status', 'APPROVEDBYCFO')->distinct('employee_id')->count('employee_id');
        $onOb      = OB::where('status', 'APPROVEDBYCFO')->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->distinct('employee_id')->count('employee_id');
        $scheduled = DB::table('employee_schedules')->whereDate('sched_start_date', '<=', $today)->whereDate('sched_end_date', '>=', $today)->distinct('employee_id')->count('employee_id');
        $absent    = max(0, $scheduled - $present - $onLeave - $onOb);
        $active    = empDetail::where('empStatus', '1')->count();
        $pendLeave = Leave::whereIn('status', ['FORAPPROVAL', 'APPROVED'])->count();
        $pendOt    = Overtime::whereIn('status', ['FORAPPROVAL', 'APPROVED'])->count();
        $pendSched = ScheduleRequest::where('status', 'FORAPPROVAL')->count();
        $start30   = Carbon::today()->subDays(29)->toDateString();
        $tot       = DB::table('attendance_summaries')->whereDate('attendance_date', '>=', $start30)->count();
        $lateD     = DB::table('attendance_summaries')->whereDate('attendance_date', '>=', $start30)->where('mins_late', '>', 0)->count();
        $onTime    = $tot > 0 ? round(($tot - $lateD) / $tot * 100) : 0;
        $who       = $this->whoIn()->getData(true);

        return response()->json([
            'kpi'     => ['active' => $active, 'present' => $present, 'absent' => $absent, 'leaveob' => $onLeave + $onOb, 'pendTotal' => $pendLeave + $pendOt + $pendSched, 'ontime' => $onTime . '%'],
            'today'   => ['present' => $present, 'absent' => $absent, 'onLeave' => $onLeave, 'onOb' => $onOb, 'late' => $late, 'scheduled' => $scheduled],
            'pending' => ['pendLeave' => $pendLeave, 'pendOt' => $pendOt, 'pendSched' => $pendSched],
            'whoIn'   => $who,
        ]);
    }

    /** AJAX: currently clocked-in employees (auto-refresh). */
    public function whoIn()
    {
        $since = Carbon::today()->subDay()->toDateString();
        $count = DB::table('home_attendances')->whereNull('time_out')
            ->whereDate('attendance_date', '>=', $since)->distinct('employee_id')->count('employee_id');
        $list = DB::table('home_attendances as h')->join('users as u', 'u.empID', '=', 'h.employee_id')
            ->whereNull('h.time_out')->whereDate('h.attendance_date', '>=', $since)
            ->selectRaw("TRIM(CONCAT(u.lname,', ',u.fname)) as name, h.time_in")
            ->orderByDesc('h.time_in')->limit(25)->get()
            ->map(fn ($w) => ['name' => $w->name, 'time_in' => Carbon::parse($w->time_in)->format('h:i A')]);
        return response()->json(['count' => $count, 'list' => $list]);
    }

    /** AJAX: employees in a department (drill-down). */
    public function deptEmployees(Request $request)
    {
        $id = $request->query('id');
        $q = DB::table('emp_details as e')->join('users as u', 'u.empID', '=', 'e.empID')
            ->where('e.empStatus', '1');
        ($id === null || $id === '') ? $q->whereNull('e.empDepID') : $q->where('e.empDepID', $id);
        $rows = $q->selectRaw("TRIM(CONCAT(u.lname,', ',u.fname)) as name, u.empID as empid")
            ->orderBy('u.lname')->limit(150)->get();
        return response()->json($rows);
    }
}
