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

        
        $summaries->each(function ($summary) use ($logs) {
            $emp = $summary->employee_id;
            $date = $summary->attendance_date->format('Y-m-d');
            
           
            $summary->logs = $logs[$emp][$date] ?? collect([]);
            $summary->formatted_date = $date;
        });

        return response()->json([
            'status' => 'success',
            'data' => $summaries, 
        ]);
    }

}
