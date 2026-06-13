<?php

namespace App\Http\Controllers;

use App\Models\PayrollLog;
use App\Models\department;
use Illuminate\Http\Request;

class PayrollLogController extends Controller
{
    /**
     * Payroll Logs module page.
     */
    public function index()
    {
        $departments = department::orderBy('dep_name')->get();
        $payDates = PayrollLog::select('pay_date')
            ->distinct()
            ->orderByDesc('pay_date')
            ->pluck('pay_date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'));

        return view('pages.modules.payroll_logs', compact('departments', 'payDates'));
    }

    /**
     * AJAX: filtered + paginated list of payroll logs.
     */
    public function fetch(Request $request)
    {
        $q = PayrollLog::query();

        if ($request->filled('pay_date')) {
            $q->whereDate('pay_date', $request->pay_date);
        }
        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $q->where('department_id', $request->department_id);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('employee_name', 'like', "%{$s}%")
                  ->orWhere('employee_id', 'like', "%{$s}%");
            });
        }

        $logs = $q->orderByDesc('pay_date')
            ->orderBy('employee_name')
            ->paginate(15);

        return response()->json($logs);
    }

    /**
     * Printable view (single employee, a whole pay date, or filtered set).
     */
    public function print(Request $request)
    {
        $request->validate([
            'pay_date'    => 'nullable|date',
            'employee_id' => 'nullable',
        ]);

        $q = PayrollLog::query();
        if ($request->filled('id')) {
            $q->where('id', $request->id);
        }
        if ($request->filled('pay_date')) {
            $q->whereDate('pay_date', $request->pay_date);
        }
        if ($request->filled('employee_id')) {
            $q->where('employee_id', $request->employee_id);
        }
        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $q->where('department_id', $request->department_id);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('employee_name', 'like', "%{$s}%")
                  ->orWhere('employee_id', 'like', "%{$s}%");
            });
        }

        $logs = $q->orderByDesc('pay_date')->orderBy('employee_name')->get();

        return view('pages.modules.payroll_logs_print', compact('logs'));
    }
}
