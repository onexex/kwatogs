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
    //     $request->validate([
    //         'date_from' => 'required|date',
    //         'date_to'   => 'required|date',
    //         'employee_id' => 'nullable'
    //     ]);

    //     $query = AttendanceSummary::with(['employee', 'homeAttendances'])
    //                 ->whereBetween('attendance_date', [$request->date_from, $request->date_to])
    //                 ->orderBy('attendance_date', 'asc');

    //     if ($request->employee_id && $request->employee_id !== 'All') {
    //         $query->where('employee_id', $request->employee_id);
    //     }

    //     return response()->json($query->get());
    // }

    public function fetchAttendance(Request $request)
{
    $empId = $request->input('empID');
    $dateFrom = $request->input('dateFrom');
    $dateTo = $request->input('dateTo');

    $summaries = AttendanceSummary::with('employee')
        ->whereBetween('attendance_date', [$dateFrom, $dateTo])
        ->when($empId !== 'All', fn($q) => $q->where('employee_id', $empId))
        ->orderBy('attendance_date', 'asc')
        ->get();

    // Fetch HomeAttendance logs for the same employees and date range
    $logs = \App\Models\homeAttendance::whereBetween('attendance_date', [$dateFrom, $dateTo])
        ->when($empId !== 'All', fn($q) => $q->where('employee_id', $empId))
        ->orderBy('attendance_date')
        ->orderBy('time_in')
        ->get()
        ->groupBy(['employee_id', fn($log) => $log->attendance_date->format('Y-m-d')]);

    // Attach logs to each summary
    $summaries->each(function ($summary) use ($logs) {
        $emp = $summary->employee_id;
        $date = $summary->attendance_date->format('Y-m-d');
        $summary->logs = $logs[$emp][$date] ?? collect([]);
        // Clean date for frontend
        $summary->formatted_date = $summary->attendance_date->format('Y-m-d');
    });

    return response()->json([
        'status' => 'success',
        'data' => $summaries,
    ]);
}

}
