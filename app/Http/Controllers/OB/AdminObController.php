<?php

namespace App\Http\Controllers\OB;

use App\Http\Controllers\Controller;
use App\Models\obs;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminObController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can('adminob')) {
            abort(403, 'Unauthorized action.');
        }

        $employees = User::whereHas('empDetail', fn($q) => $q->where('empStatus', '1'))
            ->orderBy('lname')
            ->get();

        $obs = obs::select('obs.*', 'users.fname', 'users.lname', 'departments.dep_name', 'positions.pos_desc')
            ->join('users', 'users.empID', '=', 'obs.employee_id')
            ->join('emp_details', 'emp_details.empID', '=', 'obs.employee_id')
            ->join('departments', 'departments.id', '=', 'emp_details.empDepID')
            ->join('positions', 'positions.id', '=', 'emp_details.empPos')
            ->where('emp_details.empCompID', Auth::user()->empDetail->empCompID)
            ->orderByDesc('obs.created_at')
            ->paginate(15);

        return view('pages.modules.admin_ob', compact('employees', 'obs'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('adminob')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'employee_id' => ['required', 'exists:users,empID'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'destination' => ['required', 'string', 'max:255'],
            'purpose'     => ['required', 'string', 'max:500'],
            'total_hrs'   => ['required', 'numeric', 'min:0.5'],
        ]);

        $employee = User::where('empID', $request->employee_id)->firstOrFail();
        $filer    = Auth::user();

        obs::create([
            'employee_id' => $employee->empID,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'destination' => $request->destination,
            'purpose'     => $request->purpose,
            'total_hrs'   => $request->total_hrs,
            'status'      => 'Approved',
            'approved_by' => ucwords(strtolower($filer->fname . ' ' . $filer->lname)),
            'approved_at' => Carbon::now(),
            'remarks'     => $request->remarks ?? null,
        ]);

        $name = ucwords(strtolower($employee->fname . ' ' . $employee->lname));
        return response()->json(['status' => 'success', 'message' => "OB filed and approved successfully for {$name}."]);
    }
}
