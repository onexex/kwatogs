<?php

namespace App\Http\Controllers;

use App\Models\PayrollLog;
use App\Models\PayrollDetail;
use App\Models\Overtime;
use App\Models\empDetail;
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

        // Daily attendance breakdown (payroll_details) for the same employees + pay
        // dates, so the print view can list each day's status (Present / Absent /
        // Leave / OB / Holiday) below an employee's computation log. Keyed by
        // "employeeId|payDate" for O(1) lookup per log; one bulk query, no N+1.
        $dayDetails = collect();
        // Approved overtime per (employee, day) so each daily row can show an OT
        // badge with hours. Keyed by "empID|Y-m-d". One bounded bulk query.
        $otByEmpDate = collect();
        // Approved leave per (employee, day) so each daily row can show the leave
        // type + hours. Keyed by "empID|Y-m-d". One bounded bulk query, no N+1.
        $leaveByEmpDate = collect();
        if ($logs->isNotEmpty()) {
            $empIds   = $logs->pluck('employee_id')->unique()->all();
            $payDates = $logs->map(fn ($l) => \Carbon\Carbon::parse($l->pay_date)->format('Y-m-d'))
                ->unique()->all();

            $dayDetails = PayrollDetail::whereIn('employee_id', $empIds)
                ->whereIn('payroll_date', $payDates)
                ->orderBy('date')
                ->get()
                ->groupBy(fn ($d) => $d->employee_id.'|'.\Carbon\Carbon::parse($d->payroll_date)->format('Y-m-d'));

            // Bound the OT/leave queries to the cut-off span actually printed
            // (fallback to the pay dates if a log has no start/end), so we never
            // scan all OT/leave. Shared by both the OT and leave wiring below.
            $startBound = $logs->map(fn ($l) => optional($l->payroll_start_date)?->format('Y-m-d'))
                ->filter()->push(...$payDates)->min();
            $endBound   = $logs->map(fn ($l) => optional($l->payroll_end_date)?->format('Y-m-d'))
                ->filter()->push(...$payDates)->max();

            // ── Overtime wiring ──────────────────────────────────────────────
            // Overtime is keyed by emp_details.id, but our day rows are keyed by
            // empID (users.empID). Build the bridge map [emp_details.id => empID].
            $detailIdToEmpId = empDetail::whereIn('empID', $empIds)->pluck('empID', 'id');

            if ($detailIdToEmpId->isNotEmpty()) {
                Overtime::where('status', 'APPROVEDBYCFO')
                    ->whereIn('emp_detail_id', $detailIdToEmpId->keys())
                    ->whereBetween('date_from', [$startBound, $endBound])
                    ->get(['emp_detail_id', 'date_from', 'total_hrs', 'total_pay'])
                    ->each(function ($ot) use (&$otByEmpDate, $detailIdToEmpId) {
                        $empId = $detailIdToEmpId[$ot->emp_detail_id] ?? null;
                        if ($empId === null) {
                            return;
                        }
                        // OT is filed per shift/day; attribute to date_from only (do
                        // not spread across a range — that would fabricate hours/pay).
                        $key = $empId.'|'.\Carbon\Carbon::parse($ot->date_from)->format('Y-m-d');
                        $cur = $otByEmpDate->get($key, ['hrs' => 0.0, 'pay' => 0.0]);
                        $cur['hrs'] += (float) $ot->total_hrs;
                        $cur['pay'] += (float) $ot->total_pay;
                        $otByEmpDate->put($key, $cur);
                    });
            }

            // ── Leave wiring ─────────────────────────────────────────────────
            // LeaveDetail.employee_id already matches users.empID, so no id-bridge
            // is needed (unlike OT). Resolve leave type names once (id => name).
            $leaveTypeNames = \App\Models\leavetype::pluck('type_leave', 'id');

            \App\Models\LeaveDetail::where('status', 'APPROVEDBYCFO')
                ->whereIn('employee_id', $empIds)
                ->whereBetween('date', [$startBound, $endBound])
                ->get(['employee_id', 'date', 'leavetype_id', 'leave_kind', 'total_hours'])
                ->each(function ($lv) use (&$leaveByEmpDate, $leaveTypeNames) {
                    $key = $lv->employee_id.'|'.\Carbon\Carbon::parse($lv->date)->format('Y-m-d');
                    $leaveByEmpDate->put($key, [
                        'type' => $leaveTypeNames[$lv->leavetype_id] ?? 'Leave',
                        'hrs'  => (float) $lv->total_hours,
                        'paid' => (string) $lv->leave_kind === '0',   // '0' = paid, '1' = unpaid
                    ]);
                });
        }

        // Non-scheduled OT (rest-day / holiday) has no payroll_details row, so it
        // would be invisible in the Daily Attendance table (which is built from
        // scheduled days only). Inject a synthetic day-row for every OT date that
        // isn't already a scheduled row, attributed to the log whose pay period
        // contains it, then re-sort each group by date. The view reads OT hrs from
        // $otByEmpDate by date and renders the "Rest-day OT" badge.
        if ($otByEmpDate->isNotEmpty()) {
            foreach ($logs as $log) {
                $empId  = $log->employee_id;
                $payKey = $empId.'|'.\Carbon\Carbon::parse($log->pay_date)->format('Y-m-d');
                $group  = $dayDetails->get($payKey, collect());
                $shown  = $group->map(fn ($d) => \Carbon\Carbon::parse($d->date)->format('Y-m-d'))->all();

                $winStart = optional($log->payroll_start_date)?->format('Y-m-d') ?? (collect($shown)->min());
                $winEnd   = optional($log->payroll_end_date)?->format('Y-m-d')   ?? (collect($shown)->max());

                foreach ($otByEmpDate as $key => $val) {
                    [$kEmp, $kDate] = explode('|', $key);
                    if ($kEmp !== $empId || in_array($kDate, $shown, true)) {
                        continue; // wrong employee, or already a scheduled row
                    }
                    if ($winStart && $kDate < $winStart) { continue; }
                    if ($winEnd   && $kDate > $winEnd)   { continue; }

                    $group->push((object) [
                        'date' => $kDate, 'payroll_date' => \Carbon\Carbon::parse($log->pay_date)->format('Y-m-d'),
                        'logsType' => null, 'holiday_type' => null, 'holiday_pay' => 0, 'totalHours' => 0,
                        'late_minutes' => 0, 'undertime_minutes' => 0, 'night_diff_hours' => 0, 'night_diff_pay' => 0,
                        'late_deduction' => 0, 'undertime_deduction' => 0, 'penalty_amount' => 0,
                        'adjustment_amount' => 0, 'remarks' => null,
                    ]);
                }
                if ($group->isNotEmpty()) {
                    $dayDetails->put($payKey, $group->sortBy(fn ($d) =>
                        \Carbon\Carbon::parse($d->date)->format('Y-m-d'))->values());
                }
            }
        }

        return view('pages.modules.payroll_logs_print', compact('logs', 'dayDetails', 'otByEmpDate', 'leaveByEmpDate'));
    }
}
