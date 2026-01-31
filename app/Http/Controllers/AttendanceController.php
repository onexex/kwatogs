<?php

namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Models\homeAttendance;
use App\Models\EmployeeSchedule;
use App\Models\AttendanceSummary;
use App\Models\AttendanceDeduction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AttendanceController extends Controller
{
   

    public function getAttendanceList(Request $request)
    {
        $empID = Session::get('LoggedUserEmpID');
        if (!$empID) return response()->json([], 401);

        $from = $request->get('from', now()->subDays(10)->toDateString());
        $to   = $request->get('to', now()->toDateString());

<<<<<<< HEAD
        $punches = HomeAttendance::where('employee_id', $empID)
=======
        $punches = homeAttendance::where('employee_id', $empID)
>>>>>>> 01cd4020dea42afea2742bbba03a1adf1880e770
                    ->whereBetween('attendance_date', [$from, $to])
                    ->orderBy('attendance_date' , 'desc')
                    ->orderBy('time_in','desc')
                    ->get()
                    ->map(function($a){
                        return [
                            'attendance_date' => $a->attendance_date->format('Y-m-d'),
                            'day' => $a->attendance_date->format('l'),
                            'time_in' => $a->time_in ? $a->time_in->format('h:i A') : '-',
                            'time_out' => $a->time_out ? $a->time_out->format('h:i A') : '-',
                            'duration' => $a->duration_hours,
                            'night_diff' => $a->night_diff_hours,
                            'remarks' => $a->remarks ?? '',
                        ];
                    });

        $summary = AttendanceSummary::where('employee_id', $empID)
                    ->whereBetween('attendance_date', [$from, $to])
                    ->orderBy('attendance_date')
                    ->get()
                    ->map(function($s){
                        return [
                            'attendance_date' => Carbon::parse($s->attendance_date)->format('Y-m-d'),
                            'total_hours' => $s->total_hours,
                            'mins_late' => $s->mins_late ?? 0,
                            'mins_undertime' => $s->mins_undertime ?? 0,
                            'mins_night_diff' => $s->mins_night_diff ?? 0,
                            'status' => $s->status,
                        ];
                    });

        return response()->json([
            'punches' => $punches,
            'summary' => $summary
        ]);
    }

    public function timeIn(Request $request)
    {
        $empID = Session::get('LoggedUserEmpID');

        if (!$empID) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session expired'
            ]);
        }

        try {
            $attendance = HomeAttendance::logTimeIn($empID);

            $responseMessage = 'Time In recorded';
            if (!empty($attendance->remarks)) {
                $responseMessage .= ' (' . $attendance->remarks . ')';
            }

            return response()->json([
                'status' => 'success',
                'message' => $responseMessage,
                'data' => $attendance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function timeOut(Request $request)
    {
        $empID = Session::get('LoggedUserEmpID');
        $today = now()->toDateString();

        $attendance = HomeAttendance::where('employee_id', $empID)
            // ->whereDate('attendance_date', $today)
            ->whereNull('time_out')
            ->latest('time_in')
            ->first();

        if (!$attendance) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active punch found'
            ]);
        }

        try {
            $attendance->logTimeOut();

            $responseMessage = 'Time Out recorded';
            if (!empty($attendance->remarks)) {
                $responseMessage .= ' (' . $attendance->remarks . ')';
            }

            return response()->json([
                'status' => 'success',
                'message' => $responseMessage,
                'data' => $attendance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // public function index()
    // {
    //     // Fetch summaries with their related employee and deductions
    //     $summaries = AttendanceSummary::with(['employee', 'manualDeductions'])
    //         ->orderBy('attendance_date', 'desc')
    //         ->get();

    
    // }
    

    public function index(Request $request)
{
    $query = AttendanceSummary::with(['employee', 'manualDeductions'])
        ->when($request->from_date, fn($q) => $q->whereDate('attendance_date', '>=', $request->from_date))
        ->when($request->to_date, fn($q) => $q->whereDate('attendance_date', '<=', $request->to_date))
        ->when($request->search, function ($q) use ($request) {
            $q->whereHas('employee', function ($sub) use ($request) {
                $sub->where('lname', 'like', '%' . $request->search . '%')
                    ->orWhere('mname', 'like', '%' . $request->search . '%')
                    ->orWhere('fname', 'like', '%' . $request->search . '%')


                    ->orWhere('empID', 'like', '%' . $request->search . '%');
            });
        })
        ->orderBy('attendance_date', 'desc');

    if ($request->ajax()) {
        return response()->json($query->get());
    }

    // Standard load (for first time visit)
    $summaries = $query->paginate(50);
            return view('pages.modules.hradjustment', compact('summaries'));

}

    public function storeDeduction(Request $request)
    {
        $request->validate([
            'attendance_summary_id' => 'required|exists:attendance_summaries,id',
            'deduction_minutes' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
        ]);

        AttendanceDeduction::create([
            'attendance_summary_id' => $request->attendance_summary_id,
            'deduction_minutes' => $request->deduction_minutes,
            'reason' => $request->reason,
            'added_by' => Auth::id(), // Tracks which admin logged this
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Deduction logged successfully!'
        ]);
    }

    public function destroyDeduction($id)
    {
        $deduction = AttendanceDeduction::findOrFail($id);
        $deduction->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Deduction removed successfully!'
        ]);
    }





}
