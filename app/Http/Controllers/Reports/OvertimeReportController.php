<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OvertimeReportController extends Controller
{
    public function index()
    {
        $departments = department::orderBy('dep_name')->get();
        return view('pages.reports.overtime_report', compact('departments'));
    }

    private function baseQuery(Request $request)
    {
        $q = DB::table('overtimes as o')
            ->join('emp_details as ed', 'ed.id', '=', 'o.emp_detail_id')
            ->join('users as u', 'u.empID', '=', 'ed.empID')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.empDepID')
            ->selectRaw("o.id, o.date_from, o.date_to, o.time_in, o.time_out, o.total_hrs, o.total_pay, o.purpose, o.status,
                ed.empDepID as department_id, d.dep_name as department_name,
                u.empID as employee_id, TRIM(CONCAT(COALESCE(u.lname,''), ', ', COALESCE(u.fname,''))) as employee_name");

        if ($request->filled('date_from')) {
            $q->whereDate('o.date_from', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('o.date_to', '<=', $request->date_to);
        }
        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $q->where('ed.empDepID', $request->department_id);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $q->where('o.status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('u.fname', 'like', "%{$s}%")
                  ->orWhere('u.lname', 'like', "%{$s}%")
                  ->orWhere('u.empID', 'like', "%{$s}%");
            });
        }

        return $q->orderByDesc('o.date_from')->orderBy('employee_name');
    }

    public function fetch(Request $request)
    {
        return response()->json($this->baseQuery($request)->paginate(20));
    }

    public function print(Request $request)
    {
        $rows = $this->baseQuery($request)->get();
        return view('pages.reports.overtime_report_print', [
            'rows'     => $rows,
            'filters'  => $request->all(),
            'totalHrs' => $rows->sum('total_hrs'),
            'totalPay' => $rows->sum('total_pay'),
        ]);
    }
}
