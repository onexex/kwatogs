<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveReportController extends Controller
{
    public function index()
    {
        $departments = department::orderBy('dep_name')->get();
        $leavetypes  = \App\Models\leavetype::orderBy('type_leave')->get();
        return view('pages.reports.leave_report', compact('departments', 'leavetypes'));
    }

    private function baseQuery(Request $request)
    {
        $q = DB::table('leaves as l')
            ->join('emp_details as ed', 'ed.empID', '=', 'l.employee_id')
            ->join('users as u', 'u.empID', '=', 'l.employee_id')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->leftJoin('leavetypes as lt', 'lt.id', '=', 'l.leave_type')
            ->selectRaw("l.id, l.start_date, l.end_date, l.total_hrs, l.reason, l.status, l.leave_kind,
                ed.empDepID as department_id, d.dep_name as department_name, lt.type_leave as leave_type,
                u.empID as employee_id, TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as employee_name");

        if ($request->filled('date_from')) {
            $q->whereDate('l.start_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('l.end_date', '<=', $request->date_to);
        }
        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $q->where('ed.empDepID', $request->department_id);
        }
        if ($request->filled('leave_type') && $request->leave_type !== 'all') {
            $q->where('l.leave_type', $request->leave_type);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $q->where('l.status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('u.fname', 'like', "%{$s}%")
                  ->orWhere('u.lname', 'like', "%{$s}%")
                  ->orWhere('u.empID', 'like', "%{$s}%");
            });
        }

        return $q->orderByDesc('l.start_date')->orderBy('employee_name');
    }

    public function fetch(Request $request)
    {
        return response()->json($this->baseQuery($request)->paginate(20));
    }

    public function print(Request $request)
    {
        $rows = $this->baseQuery($request)->get();
        return view('pages.reports.leave_report_print', [
            'rows'      => $rows,
            'filters'   => $request->all(),
            'totalDays' => round($rows->sum('total_hrs') / 8, 2),
        ]);
    }
}
