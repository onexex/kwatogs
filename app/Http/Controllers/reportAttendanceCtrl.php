<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceSummary;
use App\Models\homeAttendance;
use App\Models\User;

class reportAttendanceCtrl extends Controller
{
    public function index()
    {
        $resultEmp = User::select('empID', 'fname', 'lname')->orderBy('lname')->get();
        return view('reports.attendance', compact('resultEmp'));
    }

    // public function fetchAttendance(Request $request)
    // {
    //     $empId = $request->input('empID');
    //     $dateFrom = $request->input('dateFrom');
    //     $dateTo = $request->input('dateTo');

    //     // Added manualDeductions to the with() call
    //     $summaries = AttendanceSummary::with(['employee', 'manualDeductions'])
    //         ->whereBetween('attendance_date', [$dateFrom, $dateTo])
    //         ->when($empId !== 'All', fn($q) => $q->where('employee_id', $empId))
    //         ->orderBy('attendance_date', 'asc')
    //         ->get();

    //     $logs = \App\Models\homeAttendance::join('users', 'home_attendances.employee_id', '=', 'users.empID') // I-join para sa sorting
    //         ->whereBetween('attendance_date', [$dateFrom, $dateTo])
    //         ->when($empId !== 'All', fn($q) => $q->where('employee_id', $empId))
    //          ->orderBy('users.lname', 'asc')
    //         ->orderBy('attendance_date', 'asc')
    //         // ->orderBy('time_in', 'asc')
           
    //         ->get()
    //         ->groupBy(['employee_id', fn($log) => $log->attendance_date->format('Y-m-d')])
    //         ;

    //     $summaries->each(function ($summary) use ($logs) {
    //         $emp = $summary->employee_id;
    //         $date = $summary->attendance_date->format('Y-m-d');
    //         $summary->logs = $logs[$emp][$date] ?? collect([]);
    //         $summary->formatted_date = $summary->attendance_date->format('Y-m-d');
    //     });

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $summaries,
    //     ]);
    // }

    public function fetchAttendance(Request $request)
    {
        $empId = $request->input('empID');
        $dateFrom = $request->input('dateFrom');
        $dateTo = $request->input('dateTo');

       
        $summaries = AttendanceSummary::with(['employee', 'manualDeductions'])
            ->join('users', 'attendance_summaries.employee_id', '=', 'users.empID') 
            ->whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->when($empId !== 'All', fn($q) => $q->where('attendance_summaries.employee_id', $empId))
            ->orderBy('users.lname', 'asc') 
            ->orderBy('attendance_date', 'asc') 
            ->select('attendance_summaries.*') 
            ->get();

        $logs = \App\Models\homeAttendance::whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->when($empId !== 'All', fn($q) => $q->where('employee_id', $empId))
            ->get()
            ->groupBy(['employee_id', fn($log) => $log->attendance_date->format('Y-m-d')]);

        // Assigned shift(s) covering the range — used to show each day's actual schedule.
        // Bulk-fetched once (no N+1), then matched per summary by date span.
        $schedules = \App\Models\EmployeeSchedule::when($empId !== 'All', fn($q) => $q->where('employee_id', $empId))
            ->whereDate('sched_start_date', '<=', $dateTo)
            ->whereDate('sched_end_date', '>=', $dateFrom)
            ->orderBy('sched_start_date', 'desc')
            ->get()
            ->groupBy('employee_id');

        $summaries->each(function ($summary) use ($logs, $schedules) {
            $emp = $summary->employee_id;
            $date = $summary->attendance_date->format('Y-m-d');

            $summary->logs = $logs[$emp][$date] ?? collect([]);
            $summary->formatted_date = $date;

            // Schedule valid for this attendance date (start <= date <= end)
            $sched = optional($schedules->get($emp))->first(function ($s) use ($date) {
                $start = \Carbon\Carbon::parse($s->sched_start_date)->format('Y-m-d');
                $end   = \Carbon\Carbon::parse($s->sched_end_date)->format('Y-m-d');
                return $start <= $date && $date <= $end;
            });

            $summary->schedule = $sched ? [
                'sched_in'    => $sched->sched_in,
                'sched_out'   => $sched->sched_out,
                'break_start' => $sched->break_start,
                'break_end'   => $sched->break_end,
                'shift_type'  => $sched->shift_type,
            ] : null;
        });

        return response()->json([
            'status' => 'success',
            'data' => $summaries, 
        ]);
    }

}
