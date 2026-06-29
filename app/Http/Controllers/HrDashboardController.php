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
use App\Services\CoeService;
use App\Services\NoticeService;
use App\Services\TenureProgramService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrDashboardController extends Controller
{
    public function index(TenureProgramService $tenurePrograms, NoticeService $notices, CoeService $coe)
    {
        $today = Carbon::today()->toDateString();
        $d = [];

        // ── Workforce ──────────────────────────────────────────────────
        $d['total']         = empDetail::count();
        $d['active']        = empDetail::where('empStatus', '1')->count();   // Employed
        $d['resigned']      = empDetail::where('empStatus', '0')->count();   // Resigned
        $d['endOfContract'] = empDetail::where('empStatus', '2')->count();   // End of Contract
        $d['cash']     = empDetail::where('empPayrollType', 'CASH')->count();
        $d['card']     = empDetail::where('empPayrollType', 'CARD')->count();

        $d['byDept'] = DB::table('emp_details as e')
            ->leftJoin('departments as dp', 'dp.id', '=', 'e.empDepID')
            ->where('e.empStatus', '1')
            ->whereNotNull('e.empDepID')
            ->where('e.empID', '!=', 'KWTGS-2026-0001')
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
        // "Present" must reflect anyone who actually punched in today — the live punch
        // log (home_attendances), NOT attendance_summaries. The summary's total_hours is
        // only written when an employee clocks OUT (updateDailySummary sums duration_hours,
        // which is computed in logTimeOut), so counting summaries misses everyone still on
        // shift. This is the same source the "Who's in right now" panel uses.
        $d['present'] = DB::table('home_attendances')->whereDate('attendance_date', $today)->whereNotNull('time_in')->distinct('employee_id')->count('employee_id');
        $d['late']    = $this->lateToday($today);
        $d['onLeave'] = LeaveDetail::whereDate('date', $today)->where('status', 'APPROVEDBYCFO')->distinct('employee_id')->count('employee_id');
        $d['onOb']    = OB::where('status', 'APPROVEDBYCFO')->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->distinct('employee_id')->count('employee_id');

        $scheduledToday = DB::table('employee_schedules')
            ->whereDate('sched_start_date', '<=', $today)->whereDate('sched_end_date', '>=', $today)
            ->distinct('employee_id')->count('employee_id');
        $d['scheduled'] = $scheduledToday;
        $d['absent']    = max(0, $scheduledToday - $d['present'] - $d['onLeave'] - $d['onOb']);

        // ── Attendance Rate ────────────────────────────────────────────
        $start30 = Carbon::today()->subDays(29)->toDateString();
        $sch30 = DB::table('employee_schedules')->whereDate('sched_start_date','>=',$start30)->whereDate('sched_start_date','<=',$today)->count();
        $pres30 = DB::table('attendance_summaries')->whereDate('attendance_date','>=',$start30)->where('total_hours','>',0)->distinct('employee_id','attendance_date')->count('employee_id');
        $d['attendanceRate'] = $sch30 > 0 ? round($pres30 / $sch30 * 100) : 0;

        // ── Overtime Summary (current month) ────────────────────────────
        $monthStart = Carbon::today()->startOfMonth()->toDateString();
        $d['otHours']  = round((float) Overtime::whereDate('date_from','>=',$monthStart)->sum('total_hrs'), 1);
        $d['otCost']   = round((float) Overtime::whereDate('date_from','>=',$monthStart)->sum('total_pay'), 2);
        $d['otCount']  = Overtime::whereDate('date_from','>=',$monthStart)->count();

        // ── Gender diversity ────────────────────────────────────────────
        $d['male']   = DB::table('emp_infos as i')
            ->join('emp_details as e', 'e.empID', '=', 'i.empID')
            ->where('e.empStatus', '1')->where('i.gender', 'Male')->count();
        $d['female'] = DB::table('emp_infos as i')
            ->join('emp_details as e', 'e.empID', '=', 'i.empID')
            ->where('e.empStatus', '1')->where('i.gender', 'Female')->count();
        $d['otherG'] = max(0, $d['active'] - $d['male'] - $d['female']);
        $d['malePct'] = $d['active'] > 0 ? round($d['male'] / $d['active'] * 100) : 0;

        // ── Employee turnover (last 6 months) ───────────────────────────
        $turnover = [];
        for ($m = 5; $m >= 0; $m--) {
            $month = Carbon::today()->subMonths($m);
            $moStart = $month->copy()->startOfMonth()->toDateString();
            $moEnd   = $month->copy()->endOfMonth()->toDateString();
            $hired   = empDetail::whereBetween('empDateHired', [$moStart, $moEnd])->count();
            $resigned = empDetail::whereBetween('empDateResigned', [$moStart, $moEnd])->count();
            $net = $hired - $resigned;
            $turnover[] = ['month' => $month->format('M'), 'hired' => $hired, 'resigned' => $resigned, 'net' => $net];
        }
        $d['turnover'] = $turnover;

        // ── Recent new hires (last 30 days) ─────────────────────────────
        $d['newHires'] = DB::table('emp_details as e')
            ->join('users as u', 'u.empID', '=', 'e.empID')
            ->whereDate('e.empDateHired', '>=', $start30)->whereDate('e.empDateHired', '<=', $today)
            ->selectRaw("TRIM(CONCAT(u.lname,', ',u.fname)) as name, e.empDateHired as hired, COALESCE((SELECT dep_name FROM departments WHERE id=e.empDepID),'—') as dept")
            ->orderByDesc('e.empDateHired')->limit(8)->get();

        // ── Document / compliance alerts ────────────────────────────────
        $d['expiringPassport'] = DB::table('emp_details as e')
            ->join('users as u', 'u.empID', '=', 'e.empID')
            ->whereNotNull('e.empPassportExpDate')->whereDate('e.empPassportExpDate', '>=', $today)
            ->whereDate('e.empPassportExpDate', '<=', Carbon::today()->addDays(60)->toDateString())
            ->selectRaw("TRIM(CONCAT(u.lname,', ',u.fname)) as name, e.empPassportExpDate as exp")
            ->orderBy('e.empPassportExpDate')->limit(6)->get();

        $d['missingDocs'] = DB::table('emp_details as e')
            ->join('users as u', 'u.empID', '=', 'e.empID')
            ->where('e.empStatus', '1')
            ->where(function ($q) {
                $q->whereNull('e.empSSS')->orWhere('e.empSSS','')
                  ->orWhereNull('e.empPhilhealth')->orWhere('e.empPhilhealth','')
                  ->orWhereNull('e.empPagibig')->orWhere('e.empPagibig','')
                  ->orWhereNull('e.empTIN')->orWhere('e.empTIN','');
            })
            ->selectRaw("TRIM(CONCAT(u.lname,', ',u.fname)) as name, 
                CASE WHEN e.empSSS IS NULL OR e.empSSS='' THEN 'SSS' 
                     WHEN e.empPhilhealth IS NULL OR e.empPhilhealth='' THEN 'PhilHealth'
                     WHEN e.empPagibig IS NULL OR e.empPagibig='' THEN 'Pag-IBIG'
                     ELSE 'TIN' END as missing")
            ->limit(10)->get();

        // ── Leave utilization (current month) ───────────────────────────
        $d['leaveThisMonth'] = DB::table('leaves as l')
            ->join('leavetypes as lt', 'lt.id', '=', 'l.leave_type')
            ->whereDate('l.start_date', '>=', $monthStart)
            ->selectRaw("lt.type_leave as type, COUNT(*) as cnt, SUM(l.total_hrs) as hrs")
            ->groupBy('lt.type_leave')->orderByDesc('cnt')->limit(6)->get();
        $d['leaveTotalDays'] = round(Leave::whereDate('start_date', '>=', $monthStart)->sum('total_hrs') / 8, 1);

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
            ->join('emp_details as e', 'e.empID', '=', 'i.empID')
            ->where('e.empStatus', '1')
            ->whereNotNull('i.empBdate')
            ->whereIn(DB::raw("DATE_FORMAT(i.empBdate, '%m-%d')"), $weekDays)
            ->selectRaw("TRIM(CONCAT(u.lname,', ',u.fname)) as name, DATE_FORMAT(i.empBdate,'%b %d') as bday")
            ->orderByRaw("DATE_FORMAT(i.empBdate,'%m-%d')")->limit(10)->get();

        $d['regularize'] = DB::table('emp_details as e')
            ->join('users as u', 'u.empID', '=', 'e.empID')
            ->where('e.empStatus', '1') // only active (Employed) staff can be regularized
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

        // ── Tenure-milestone programs (Programs Management) ─────────────
        // Reuses the same eligibility computation as the Programs screen so the
        // dashboard widget and the screen never drift. Trimmed to the few rows
        // the widget shows (pending grants + nearest anniversaries).
        $milestones = $tenurePrograms->eligibility();
        $d['msStats']    = $milestones['stats'];
        $d['msPending']  = collect($milestones['reached'])->where('status', 'pending')->take(6)->values();
        $d['msUpcoming'] = collect($milestones['upcoming'])->take(6)->values();

        // ── Disciplinary notices / suspension escalation ────────────────
        $noticeData = $notices->dashboard();
        $d['ntcStats']  = $noticeData['stats'];
        $d['ntcOver']   = $noticeData['over'];        // names shown in the alert flash (suspend threshold)
        $d['ntcAtRisk'] = $noticeData['atRisk'];      // approaching the limit

        // ── Certificate of Employment requests ──────────────────────────
        // Same service as the COE screen so the widget and the screen never drift.
        $coeData = $coe->dashboard();
        $d['coeStats']   = $coeData['stats'];
        $d['coePending'] = $coeData['pending'];

        return view('pages.management.hr_dashboard', ['d' => $d]);
    }

    /**
     * Count employees who are LATE today, computed live from the punch log instead of
     * attendance_summaries.mins_late (which is only written on clock-out, so it misses
     * anyone still on shift). Mirrors homeAttendance::updateDailySummary(): an employee is
     * late when their FIRST punch-in of the day is after the scheduled start
     * (sched_start_date + sched_in) of the schedule that punch is tied to.
     */
    private function lateToday(string $today): int
    {
        $rows = DB::table('home_attendances as h')
            ->join('employee_schedules as s', 's.id', '=', 'h.schedule_id')
            ->whereDate('h.attendance_date', $today)
            ->whereNotNull('h.time_in')
            ->selectRaw('h.employee_id, MIN(h.time_in) as first_in, s.sched_start_date, s.sched_in')
            ->groupBy('h.employee_id', 's.sched_start_date', 's.sched_in')
            ->get();

        $late = [];
        foreach ($rows as $r) {
            $schedIn = Carbon::parse($r->sched_start_date . ' ' . $r->sched_in);
            if (Carbon::parse($r->first_in)->gt($schedIn)) {
                $late[$r->employee_id] = true; // distinct employees only
            }
        }

        return count($late);
    }

    /** AJAX: all live counters for whole-dashboard auto-refresh. */
    public function live()
    {
        $today = Carbon::today()->toDateString();
        // Present = anyone who punched in today (home_attendances), matching index(). The
        // summary's total_hours is only set on clock-out, so it under-counts active staff.
        $present   = DB::table('home_attendances')->whereDate('attendance_date', $today)->whereNotNull('time_in')->distinct('employee_id')->count('employee_id');
        $late      = $this->lateToday($today);
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

        // Live: attendance rate
        $sch30 = DB::table('employee_schedules')->whereDate('sched_start_date','>=',$start30)->whereDate('sched_start_date','<=',$today)->count();
        $pres30 = DB::table('attendance_summaries')->whereDate('attendance_date','>=',$start30)->where('total_hours','>',0)->distinct('employee_id','attendance_date')->count('employee_id');
        $attRate = $sch30 > 0 ? round($pres30 / $sch30 * 100) : 0;

        // Live: OT summary
        $monthStart = Carbon::today()->startOfMonth()->toDateString();
        $otHours = round((float) Overtime::whereDate('date_from','>=',$monthStart)->sum('total_hrs'), 1);
        $otCost  = round((float) Overtime::whereDate('date_from','>=',$monthStart)->sum('total_pay'), 2);
        $otCount = Overtime::whereDate('date_from','>=',$monthStart)->count();

        $who = $this->whoIn()->getData(true);

        return response()->json([
            'kpi'      => ['active' => $active, 'present' => $present, 'absent' => $absent, 'leaveob' => $onLeave + $onOb, 'pendTotal' => $pendLeave + $pendOt + $pendSched, 'ontime' => $onTime . '%', 'attendanceRate' => $attRate . '%'],
            'today'    => ['present' => $present, 'absent' => $absent, 'onLeave' => $onLeave, 'onOb' => $onOb, 'late' => $late, 'scheduled' => $scheduled],
            'pending'  => ['pendLeave' => $pendLeave, 'pendOt' => $pendOt, 'pendSched' => $pendSched],
            'ot'       => ['hours' => $otHours, 'cost' => number_format($otCost, 2), 'count' => $otCount],
            'whoIn'    => $who,
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
