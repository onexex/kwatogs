<?php

namespace App\Http\Controllers;

use App\Models\empDetail;
use App\Models\Leave;
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
        // present/late are FACTUAL (who actually punched in) — kept unscoped so a person who
        // worked this morning and was separated later today still shows as present.
        $d['present'] = DB::table('home_attendances')->whereDate('attendance_date', $today)->whereNotNull('time_in')->distinct('employee_id')->count('employee_id');
        $d['late']    = $this->lateToday($today);
        // Excusals only count for still-active staff (an ex-employee isn't "on leave/OB").
        $d['onLeave'] = $this->onLeaveToday($today);
        $d['onOb']    = $this->onObToday($today);

        // "Scheduled today" anchors on the WORK DAY (sched_start_date = today) — the same
        // anchor punches use for attendance_date — so last night's 7 PM–7 AM shift belongs
        // to yesterday's count and tonight's belongs to today. Scoped to active staff so a
        // separated employee's stale schedule row can't inflate the count. The old range-cover
        // check (start <= today <= end) also matched yesterday's overnight row, whose punches
        // are stamped yesterday, so its worker looked absent every morning.
        $d['scheduled'] = $this->scheduledToday($today);
        $d['absent']    = $this->absentSoFarToday($today);
        // Scheduled today whose shift hasn't STARTED yet (night/later shifts) and who aren't
        // excused — explains the gap between "scheduled" and present+absent+leave+OB so a
        // pending 7 PM–7 AM shift reads as "not yet due", never as absent.
        $d['notYetDue'] = $this->notYetDueToday($today);

        // ── Attendance Rate ────────────────────────────────────────────
        $start30 = Carbon::today()->subDays(29)->toDateString();
        $d['attendanceRate'] = $this->attendanceRate30($start30, $today);

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

        // ── Uncorrected missed-logout attendance (needs validation, last 15 days = one cutoff) ──
        // Employee punched IN but never punched OUT → the day was auto-closed to the scheduled end
        // and earns 0 paid hours (homeAttendance stamps remarks='Auto-closed (Missed logout...)').
        // It's "corrected" once HR overwrites the day's gross in Summary Logs to a non-zero value,
        // so surface only days whose summary total_hours is still 0.
        $start15 = Carbon::today()->subDays(14)->toDateString();
        $missed = DB::table('home_attendances as h')
            ->join('users as u', 'u.empID', '=', 'h.employee_id')
            ->join('attendance_summaries as a', function ($j) {
                $j->on('a.employee_id', '=', 'h.employee_id')
                  ->on(DB::raw('DATE(a.attendance_date)'), '=', DB::raw('DATE(h.attendance_date)'));
            })
            ->leftJoin('employee_schedules as s', 's.id', '=', 'h.schedule_id')
            ->whereDate('h.attendance_date', '>=', $start15)
            ->where('h.remarks', 'like', '%Missed logout%')  // catches scheduled + "No Schedule" variants
            ->whereNotNull('h.time_in')
            ->where('a.total_hours', '=', 0)                  // still 0 ⇒ not yet corrected
            ->groupBy('h.employee_id', 'u.lname', 'u.fname', DB::raw('DATE(h.attendance_date)'), 's.sched_in', 's.sched_out')
            ->selectRaw("h.employee_id as empid, TRIM(CONCAT(u.lname,', ',u.fname)) as name,
                         DATE(h.attendance_date) as date, MIN(h.time_in) as time_in, s.sched_in, s.sched_out")
            ->orderByDesc(DB::raw('DATE(h.attendance_date)'))
            ->limit(15)->get();

        // Tag payroll-locked days (same rule as SummaryLogsController) — bulk fetch, no N+1.
        $mlIds = $missed->pluck('empid')->unique();
        $mlPay = $mlIds->isEmpty() ? collect()
            : Payroll::select('employee_id', 'payroll_start_date', 'payroll_end_date')
                ->whereIn('employee_id', $mlIds)
                ->whereDate('payroll_start_date', '<=', $today)
                ->whereDate('payroll_end_date', '>=', $start15)
                ->get()->groupBy('employee_id');
        $missed->each(function ($m) use ($mlPay) {
            $m->locked = (bool) optional($mlPay->get($m->empid))->first(fn ($p) =>
                $p->payroll_start_date->format('Y-m-d') <= $m->date
                && $m->date <= $p->payroll_end_date->format('Y-m-d'));
        });
        $d['missedLogouts'] = $missed;

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
        // Counts only judgeable scheduled days (shift already ENDED, not excused by
        // leave/OB/holiday — see judgeableScheduledDays). Before, today's schedules were
        // judged while still in progress: a 7 PM–7 AM shift showed absent all morning, and
        // even a day-shifter still on duty counted absent because total_hours is only
        // written on clock-out.
        $d['absentByDept'] = $this->judgeableScheduledDays($start30, $today)
            ->leftJoin('departments as dp', 'dp.id', '=', 'e.empDepID')
            ->leftJoin('attendance_summaries as a', function ($j) {
                $j->on('a.employee_id', '=', 's.employee_id')->on('a.attendance_date', '=', 's.sched_start_date');
            })
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
        // Same active-employee + work-day anchor + started-shift rules as index() — a night
        // shift that hasn't started yet reads as "not yet due", never absent all morning.
        $onLeave   = $this->onLeaveToday($today);
        $onOb      = $this->onObToday($today);
        $scheduled = $this->scheduledToday($today);
        $absent    = $this->absentSoFarToday($today);
        $notYetDue = $this->notYetDueToday($today);
        $active    = empDetail::where('empStatus', '1')->count();
        $pendLeave = Leave::whereIn('status', ['FORAPPROVAL', 'APPROVED'])->count();
        $pendOt    = Overtime::whereIn('status', ['FORAPPROVAL', 'APPROVED'])->count();
        $pendSched = ScheduleRequest::where('status', 'FORAPPROVAL')->count();
        $start30   = Carbon::today()->subDays(29)->toDateString();
        $tot       = DB::table('attendance_summaries')->whereDate('attendance_date', '>=', $start30)->count();
        $lateD     = DB::table('attendance_summaries')->whereDate('attendance_date', '>=', $start30)->where('mins_late', '>', 0)->count();
        $onTime    = $tot > 0 ? round(($tot - $lateD) / $tot * 100) : 0;

        // Live: attendance rate (same judgeable-day basis as index())
        $attRate = $this->attendanceRate30($start30, $today);

        // Live: OT summary
        $monthStart = Carbon::today()->startOfMonth()->toDateString();
        $otHours = round((float) Overtime::whereDate('date_from','>=',$monthStart)->sum('total_hrs'), 1);
        $otCost  = round((float) Overtime::whereDate('date_from','>=',$monthStart)->sum('total_pay'), 2);
        $otCount = Overtime::whereDate('date_from','>=',$monthStart)->count();

        $who = $this->whoIn()->getData(true);

        return response()->json([
            'kpi'      => ['active' => $active, 'present' => $present, 'absent' => $absent, 'leaveob' => $onLeave + $onOb, 'pendTotal' => $pendLeave + $pendOt + $pendSched, 'ontime' => $onTime . '%', 'attendanceRate' => $attRate . '%'],
            'today'    => ['present' => $present, 'absent' => $absent, 'onLeave' => $onLeave, 'onOb' => $onOb, 'late' => $late, 'scheduled' => $scheduled, 'notYetDue' => $notYetDue],
            'pending'  => ['pendLeave' => $pendLeave, 'pendOt' => $pendOt, 'pendSched' => $pendSched],
            'ot'       => ['hours' => $otHours, 'cost' => number_format($otCost, 2), 'count' => $otCount],
            'whoIn'    => $who,
        ]);
    }

    /**
     * Base query for "judgeable" scheduled work days in [$from, $to]. One employee_schedules
     * row = one work day, anchored on sched_start_date — the same anchor punches use for
     * attendance_date, so an overnight shift's summary lands on its start date. A day only
     * counts toward absenteeism when:
     *  - its shift window has fully ENDED (overnight-aware: sched_out <= sched_in means the
     *    shift spills past midnight, so the true end is sched_start_date + sched_out + 1 day).
     *    A 7 PM–7 AM shift scheduled today must not be judged this morning, and a day shift
     *    still on duty has no clock-out yet (total_hours is only written on logout);
     *  - it isn't excused: approved leave or OB that day, or a holiday for the employee's
     *    department (holiday_logger.department_id NULL = company-wide).
     * Aliases exposed to callers: s = employee_schedules, e = emp_details.
     */
    private function judgeableScheduledDays(string $from, string $to)
    {
        $q = DB::table('employee_schedules as s')
            ->join('emp_details as e', 'e.empID', '=', 's.employee_id')
            ->whereDate('s.sched_start_date', '>=', $from)
            ->whereDate('s.sched_start_date', '<=', $to)
            ->whereRaw('DATE_ADD(TIMESTAMP(s.sched_start_date, s.sched_out), INTERVAL (s.sched_out <= s.sched_in) DAY) <= NOW()');

        return $this->withoutExcusedDays($q);
    }

    /** NOT EXISTS filters for excused scheduled days (expects aliases s + e). */
    private function withoutExcusedDays($q)
    {
        return $q
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))->from('leave_details as ld')
                    ->whereColumn('ld.employee_id', 's.employee_id')
                    ->whereColumn('ld.date', 's.sched_start_date')
                    ->where('ld.status', 'APPROVEDBYCFO');
            })
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))->from('obs as ob')
                    ->whereColumn('ob.employee_id', 's.employee_id')
                    ->whereColumn('ob.start_date', '<=', 's.sched_start_date')
                    ->whereColumn('ob.end_date', '>=', 's.sched_start_date')
                    ->where('ob.status', 'APPROVEDBYCFO');
            })
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))->from('holiday_logger as hl')
                    ->whereColumn('hl.date', 's.sched_start_date')
                    ->where(function ($w) {
                        $w->whereNull('hl.department_id')->orWhereColumn('hl.department_id', 'e.empDepID');
                    });
            });
    }

    /**
     * Employees ALREADY absent today: still employed, today-anchored shift has STARTED
     * (sched_in has passed — a 7 PM–7 AM shift doesn't count this morning), no punch-in
     * today, and the day isn't excused by leave/OB/holiday. Precise NOT-EXISTS count
     * instead of the old scheduled − present − leave − OB arithmetic, whose terms didn't
     * cover the same set of people (overnight rows counted, unstarted shifts counted).
     */
    private function absentSoFarToday(string $today): int
    {
        $q = DB::table('employee_schedules as s')
            ->join('emp_details as e', 'e.empID', '=', 's.employee_id')
            ->where('e.empStatus', '1')
            ->whereDate('s.sched_start_date', $today)
            ->whereRaw('TIMESTAMP(s.sched_start_date, s.sched_in) <= NOW()')
            ->whereNotExists(function ($sub) use ($today) {
                $sub->select(DB::raw(1))->from('home_attendances as h')
                    ->whereColumn('h.employee_id', 's.employee_id')
                    ->whereDate('h.attendance_date', $today)
                    ->whereNotNull('h.time_in');
            });

        return $this->withoutExcusedDays($q)->distinct('s.employee_id')->count('s.employee_id');
    }

    /** Distinct ACTIVE employees with a work-day schedule anchored today. */
    private function scheduledToday(string $today): int
    {
        return DB::table('employee_schedules as s')
            ->join('emp_details as e', 'e.empID', '=', 's.employee_id')
            ->where('e.empStatus', '1')
            ->whereDate('s.sched_start_date', $today)
            ->distinct('s.employee_id')->count('s.employee_id');
    }

    /** Distinct ACTIVE employees on approved leave today. */
    private function onLeaveToday(string $today): int
    {
        return DB::table('leave_details as ld')
            ->join('emp_details as e', 'e.empID', '=', 'ld.employee_id')
            ->where('e.empStatus', '1')
            ->whereDate('ld.date', $today)
            ->where('ld.status', 'APPROVEDBYCFO')
            ->distinct('ld.employee_id')->count('ld.employee_id');
    }

    /** Distinct ACTIVE employees on approved OB covering today. */
    private function onObToday(string $today): int
    {
        return DB::table('obs as ob')
            ->join('emp_details as e', 'e.empID', '=', 'ob.employee_id')
            ->where('e.empStatus', '1')
            ->where('ob.status', 'APPROVEDBYCFO')
            ->whereDate('ob.start_date', '<=', $today)
            ->whereDate('ob.end_date', '>=', $today)
            ->distinct('ob.employee_id')->count('ob.employee_id');
    }

    /**
     * Active employees scheduled today whose shift has NOT started yet (sched_in still in the
     * future), who haven't punched in early, and who aren't excused by leave/OB/holiday. This
     * is the complement of absentSoFarToday within today's started/not-started split — a night
     * or later shift sits here until it begins instead of being miscounted as absent.
     */
    private function notYetDueToday(string $today): int
    {
        $q = DB::table('employee_schedules as s')
            ->join('emp_details as e', 'e.empID', '=', 's.employee_id')
            ->where('e.empStatus', '1')
            ->whereDate('s.sched_start_date', $today)
            ->whereRaw('TIMESTAMP(s.sched_start_date, s.sched_in) > NOW()')
            ->whereNotExists(function ($sub) use ($today) {
                $sub->select(DB::raw(1))->from('home_attendances as h')
                    ->whereColumn('h.employee_id', 's.employee_id')
                    ->whereDate('h.attendance_date', $today)
                    ->whereNotNull('h.time_in');
            });

        return $this->withoutExcusedDays($q)->distinct('s.employee_id')->count('s.employee_id');
    }

    /**
     * 30-day attendance rate over judgeable scheduled days only, with "present" measured
     * against the SAME set of days (a worked day only counts if it was a judgeable
     * scheduled day), so the rate is a true fraction and can't exceed 100%.
     */
    private function attendanceRate30(string $from, string $to): int
    {
        $sch = $this->judgeableScheduledDays($from, $to)->count();
        if ($sch === 0) {
            return 0;
        }
        $pres = $this->judgeableScheduledDays($from, $to)
            ->join('attendance_summaries as a', function ($j) {
                $j->on('a.employee_id', '=', 's.employee_id')->on('a.attendance_date', '=', 's.sched_start_date');
            })
            ->where('a.total_hours', '>', 0)->count();

        return (int) round($pres / $sch * 100);
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

    /**
     * AJAX: the HR Attention Center feed — everything waiting on this HR user, grouped by
     * urgency, for the floating topbar bell (rendered on every page). Route is gated by
     * can:hrdashboard, but each row also links to a screen with its OWN permission, so a row
     * is included ONLY when the viewer can act on it — no row ever points at a 403. Reuses the
     * same services + count queries the dashboard already runs; no new business logic.
     */
    public function attention(TenureProgramService $tenurePrograms, NoticeService $notices, CoeService $coe)
    {
        $u = auth()->user();
        $today = Carbon::today()->toDateString();

        // ── Counts (light queries + the three dashboard services) ──────
        $leave = Leave::whereIn('status', ['FORAPPROVAL', 'APPROVED'])->count();
        $ot    = Overtime::whereIn('status', ['FORAPPROVAL', 'APPROVED'])->count();
        $sched = ScheduleRequest::where('status', 'FORAPPROVAL')->count();

        // Uncorrected missed logouts (last 15 days) — same rule as index(): punched in, never
        // out, summary still 0. Counted as distinct employee-day.
        $missed = (int) DB::table('home_attendances as h')
            ->join('attendance_summaries as a', function ($j) {
                $j->on('a.employee_id', '=', 'h.employee_id')
                  ->on(DB::raw('DATE(a.attendance_date)'), '=', DB::raw('DATE(h.attendance_date)'));
            })
            ->whereDate('h.attendance_date', '>=', Carbon::today()->subDays(14)->toDateString())
            ->where('h.remarks', 'like', '%Missed logout%')
            ->whereNotNull('h.time_in')
            ->where('a.total_hours', '=', 0)
            ->selectRaw('COUNT(DISTINCT h.employee_id, DATE(h.attendance_date)) as c')
            ->value('c');

        $schedEmpIds = DB::table('employee_schedules')
            ->whereDate('sched_start_date', '<=', $today)->whereDate('sched_end_date', '>=', $today)
            ->pluck('employee_id')->unique();
        $noSchedule = empDetail::where('empStatus', '1')->whereNotIn('empID', $schedEmpIds)->count();

        $missingDocs = DB::table('emp_details as e')->where('e.empStatus', '1')
            ->where(function ($q) {
                $q->whereNull('e.empSSS')->orWhere('e.empSSS', '')
                  ->orWhereNull('e.empPhilhealth')->orWhere('e.empPhilhealth', '')
                  ->orWhereNull('e.empPagibig')->orWhere('e.empPagibig', '')
                  ->orWhereNull('e.empTIN')->orWhere('e.empTIN', '');
            })->count();

        $expiringPassport = DB::table('emp_details')
            ->whereNotNull('empPassportExpDate')
            ->whereDate('empPassportExpDate', '>=', $today)
            ->whereDate('empPassportExpDate', '<=', Carbon::today()->addDays(60)->toDateString())
            ->count();

        $regularize = DB::table('emp_details')->where('empStatus', '1')
            ->whereNotNull('empDateRegular')
            ->whereDate('empDateRegular', '>=', $today)
            ->whereDate('empDateRegular', '<=', Carbon::today()->addDays(14)->toDateString())
            ->count();

        $weekDays = collect(range(0, 6))->map(fn ($i) => Carbon::today()->addDays($i)->format('m-d'))->all();
        $birthdays = DB::table('emp_infos as i')->join('emp_details as e', 'e.empID', '=', 'i.empID')
            ->where('e.empStatus', '1')->whereNotNull('i.empBdate')
            ->whereIn(DB::raw("DATE_FORMAT(i.empBdate, '%m-%d')"), $weekDays)->count();

        $nd = $notices->dashboard();
        $over        = collect($nd['over'] ?? [])->count();
        $atRisk      = collect($nd['atRisk'] ?? [])->count();
        $pendingRecs = (int) ($nd['stats']['pendingRecs'] ?? 0);

        $coePending = (int) ($coe->dashboard()['stats']['pending'] ?? 0);

        $ms = $tenurePrograms->eligibility();
        $tenurePending = collect($ms['reached'] ?? [])->where('status', 'pending')->count();
        $anniversaries = collect($ms['upcoming'] ?? [])->count();

        // ── Assemble: [group, permission(s), count, label, sub, url, icon, severity] ──
        $canAny = fn ($perm) => collect((array) $perm)->some(fn ($p) => (bool) $u?->can($p));
        $e201   = ['e201', 'admine201'];
        $items = [
            ['urgent', 'noticemanagement', $over, 'Over suspension threshold', 'Disciplinary notices', '/pages/modules/notices', 'fa-triangle-exclamation', 'danger'],
            ['urgent', 'noticemanagement', $atRisk, 'At-risk employees', 'Approaching threshold', '/pages/modules/notices', 'fa-circle-exclamation', 'danger'],
            ['urgent', 'noticemanagement', $pendingRecs, 'Suspension recommendations', 'Pending your decision', '/pages/modules/notices', 'fa-gavel', 'danger'],
            ['approvals', 'pendingleaverequests', $leave, 'Leave requests to approve', 'Leave Requests', '/pages/modules/leaverequests', 'fa-calendar-xmark', 'info'],
            ['approvals', 'pendingovertimerequests', $ot, 'Overtime requests', 'Overtime', '/pages/modules/overtimerequests', 'fa-clock', 'info'],
            ['approvals', 'approveschedulechange', $sched, 'Schedule change requests', 'Schedule Requests', '/pages/modules/schedulerequests', 'fa-calendar-day', 'info'],
            ['attendance', 'summarylogs', $missed, 'Missed logouts to validate', 'Summary Logs', '/pages/modules/summary-logs', 'fa-right-from-bracket', 'warning'],
            ['attendance', 'hrdashboard', $noSchedule, 'Active staff with no schedule', 'Employee Schedules', '/employee-schedules', 'fa-calendar-plus', 'warning'],
            ['attendance', $e201, $missingDocs, 'Missing government docs', 'SSS, PhilHealth, TIN', '/pages/management/e201', 'fa-file-circle-exclamation', 'warning'],
            ['attendance', $e201, $expiringPassport, 'Passports expiring soon', 'Within 60 days', '/pages/management/e201', 'fa-id-card', 'warning'],
            ['hr', 'coemanagement', $coePending, 'COE requests to review', 'Certificate of Employment', '/pages/modules/coe', 'fa-file-signature', 'purple'],
            ['hr', 'programs', $tenurePending, 'Tenure milestones to grant', 'Programs', '/pages/modules/programs', 'fa-award', 'purple'],
            ['hr', $e201, $regularize, 'Upcoming regularizations', 'Within 14 days', '/pages/management/e201', 'fa-user-check', 'purple'],
            ['info', $e201, $birthdays, 'Birthdays this week', 'Say hello', '/pages/management/e201', 'fa-cake-candles', 'muted'],
            ['info', 'programs', $anniversaries, 'Work anniversaries', 'Within 60 days', '/pages/modules/programs', 'fa-champagne-glasses', 'muted'],
        ];

        $labels = [
            'urgent'     => 'Urgent',
            'approvals'  => 'Approvals waiting',
            'attendance' => 'Attendance and compliance',
            'hr'         => 'HR requests',
            'info'       => 'For your info',
        ];
        $groups = array_fill_keys(array_keys($labels), []);
        $total = 0;
        foreach ($items as [$g, $perm, $count, $label, $sub, $url, $icon, $sev]) {
            if ($count > 0 && $canAny($perm)) {
                $groups[$g][] = ['label' => $label, 'sub' => $sub, 'count' => (int) $count, 'url' => $url, 'icon' => $icon, 'severity' => $sev];
                // Badge/banner count = number of action AREAS waiting (one per row), not the sum
                // of every underlying record — a bell reading "6" is clearer than "99+". The
                // per-row pill still shows each area's real volume. FYI rows don't count.
                if ($g !== 'info') {
                    $total++;
                }
            }
        }

        $out = [];
        foreach ($groups as $key => $rows) {
            if ($rows) {
                $out[] = ['key' => $key, 'label' => $labels[$key], 'rows' => $rows];
            }
        }

        return response()->json(['total' => $total, 'groups' => $out]);
    }
}
