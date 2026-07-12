<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDeduction;
use App\Models\AttendanceSummary;
use App\Models\AuditLog;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Summary Logs Management — admin "back door" over computed attendance summaries.
 *
 * Lists ONLY days that already have a computed AttendanceSummary row (unlike the
 * Attendance Viewer report, no partial-day rows are synthesized from raw punches)
 * and lets an authorized user overwrite the computed figures: gross duration,
 * manual-deduction total, late, undertime, night diff, passout and over-break.
 * Net duration is never stored — it is always derived (gross − deductions/60),
 * both here and in the Attendance Viewer / payroll.
 *
 * AttendanceSummary deliberately does NOT use the Auditable trait (every time-out
 * rewrites a summary — auditing those would flood the trail), so manual edits made
 * here are recorded explicitly via AuditLog::record() with a before→after diff.
 *
 * PAYROLL LOCK: a day already covered by a generated payroll row (employee +
 * payroll_start_date..payroll_end_date, any status) is read-only — the payslip was
 * computed from the old figures, so editing the summary afterwards would silently
 * desync them. Delete/regenerate that payroll run first, then edit. The lock is
 * enforced server-side in update() (423) and surfaced per-row by fetch() so the
 * UI swaps the Edit button for a lock badge.
 */
class SummaryLogsController extends Controller
{
    public function index()
    {
        // No status filter: the back door must also reach past records of
        // separated employees (mirrors reportAttendanceCtrl@index).
        $resultEmp = User::select('empID', 'fname', 'lname')->orderBy('lname')->orderBy('fname')->get();

        return view('pages.modules.summary_logs', compact('resultEmp'));
    }

    public function fetch(Request $request)
    {
        $empId = $request->input('empID');
        $dateFrom = $request->input('dateFrom');
        $dateTo = $request->input('dateTo');
        // "Missed logouts only" mode — reached from the HR Attention panel / dashboard card
        // so a click lands on exactly the days pending validation (punched in, never out,
        // summary still 0), not the whole range.
        $missedOnly = filter_var($request->input('missedOnly'), FILTER_VALIDATE_BOOLEAN);

        $summaries = AttendanceSummary::with(['employee', 'manualDeductions'])
            ->join('users', 'attendance_summaries.employee_id', '=', 'users.empID')
            ->whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->when($empId !== 'All', fn($q) => $q->where('attendance_summaries.employee_id', $empId))
            ->orderBy('users.lname', 'asc')
            ->orderBy('attendance_date', 'asc')
            ->select('attendance_summaries.*')
            ->get();

        // Raw punches per day — read-only context so the editor can judge the numbers.
        $logs = \App\Models\homeAttendance::whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->when($empId !== 'All', fn($q) => $q->where('employee_id', $empId))
            ->get()
            ->groupBy(['employee_id', fn($log) => $log->attendance_date->format('Y-m-d')]);

        // Assigned shift(s) covering the range — bulk-fetched once (no N+1),
        // matched per summary by date span, same as the Attendance Viewer.
        $schedules = \App\Models\EmployeeSchedule::when($empId !== 'All', fn($q) => $q->where('employee_id', $empId))
            ->whereDate('sched_start_date', '<=', $dateTo)
            ->whereDate('sched_end_date', '>=', $dateFrom)
            ->orderBy('sched_start_date', 'desc')
            ->get()
            ->groupBy('employee_id');

        // Generated payroll runs overlapping the range — bulk-fetched once, matched
        // per summary by date span. A covered day is locked against editing.
        $payrolls = Payroll::select('id', 'employee_id', 'payroll_start_date', 'payroll_end_date')
            ->when($empId !== 'All', fn($q) => $q->where('employee_id', $empId))
            ->whereDate('payroll_start_date', '<=', $dateTo)
            ->whereDate('payroll_end_date', '>=', $dateFrom)
            ->get()
            ->groupBy('employee_id');

        // Manual-adjustment audit trail — the exact "was this summary hand-edited?" signal.
        // update() writes one AuditLog row per real edit (and none on a no-op save), so the
        // presence of a row flags an adjusted day; its user_name/created_at/changes drive the
        // "Adjusted" pill tooltip. model_id is stored as a string in AuditLog::record().
        $summaryIds  = $summaries->pluck('id')->map(fn ($id) => (string) $id);
        $adjustments = AuditLog::where('model', 'AttendanceSummary')
            ->where('action', 'updated')
            ->whereIn('model_id', $summaryIds)
            ->orderByDesc('created_at')
            ->get(['model_id', 'user_name', 'changes', 'created_at'])
            ->groupBy('model_id');

        $summaries->each(function ($summary) use ($logs, $schedules, $payrolls, $adjustments) {
            $emp = $summary->employee_id;
            $date = $summary->attendance_date->format('Y-m-d');

            $summary->logs = $logs[$emp][$date] ?? collect([]);
            $summary->formatted_date = $date;
            $summary->deduction_minutes = (int) $summary->manualDeductions->sum('deduction_minutes');

            // Pending missed-logout day: punched in but never out (a log carries the
            // "Missed logout" remark) AND the computed gross is still 0 (not yet corrected).
            $summary->is_missed_logout = ((float) $summary->total_hours === 0.0)
                && collect($summary->logs)->contains(fn ($l) =>
                    stripos((string) ($l->remarks ?? ''), 'Missed logout') !== false);

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

            $covering = optional($payrolls->get($emp))->first(function ($p) use ($date) {
                return $p->payroll_start_date->format('Y-m-d') <= $date
                    && $date <= $p->payroll_end_date->format('Y-m-d');
            });
            $summary->payroll_locked = (bool) $covering;
            $summary->payroll_period = $covering
                ? $covering->payroll_start_date->format('M d') . ' – ' . $covering->payroll_end_date->format('M d, Y')
                : null;

            // Latest manual adjustment (if any) — display-only fields for the "Adjusted" pill.
            $adj = optional($adjustments->get((string) $summary->id))->first();
            $summary->is_adjusted = (bool) $adj;
            $summary->adjusted_by = $adj?->user_name;
            $summary->adjusted_at = optional($adj?->created_at)->format('M d, Y g:i A');
            $summary->adjusted_from_gross = $adj && isset($adj->changes['total_hours']['from'])
                ? $adj->changes['total_hours']['from']
                : null;
        });

        // Restrict to only the days pending missed-logout validation, if requested.
        if ($missedOnly) {
            $summaries = $summaries->filter(fn ($s) => $s->is_missed_logout)->values();
        }

        return response()->json([
            'status' => 'success',
            'data' => $summaries,
        ]);
    }

    public function update(Request $request, AttendanceSummary $summary)
    {
        // PAYROLL LOCK — a generated payroll already covers this employee+day, so
        // its payslip was computed from the current figures. Editing now would
        // silently desync attendance from pay. Regenerate/delete that run first.
        $dateStr = $summary->attendance_date->format('Y-m-d');
        $covering = Payroll::where('employee_id', $summary->employee_id)
            ->whereDate('payroll_start_date', '<=', $dateStr)
            ->whereDate('payroll_end_date', '>=', $dateStr)
            ->orderByDesc('payroll_end_date')
            ->first();

        if ($covering) {
            return response()->json([
                'status'  => 'locked',
                'message' => 'Locked — payroll for ' . $covering->payroll_start_date->format('M d')
                    . ' – ' . $covering->payroll_end_date->format('M d, Y')
                    . ' has already been generated for this employee. Delete/regenerate that payroll run first, then edit this summary.',
            ], 423);
        }

        $validated = $request->validate([
            'total_hours'        => 'required|numeric|min:0|max:24',
            'deduction_minutes'  => 'required|integer|min:0|max:1440',
            'mins_late'          => 'required|integer|min:0|max:1440',
            'mins_undertime'     => 'required|integer|min:0|max:1440',
            'mins_night_diff'    => 'required|integer|min:0|max:1440',
            'outpass_minutes'    => 'required|integer|min:0|max:1440',
            'over_break_minutes' => 'required|integer|min:0|max:1440',
            'edit_note'          => 'nullable|string|max:255',
        ]);

        $note = trim((string) $request->input('edit_note', ''));

        $summary->loadMissing('manualDeductions');
        $oldDeductions = (int) $summary->manualDeductions->sum('deduction_minutes');

        $old = [
            'total_hours'        => round((float) $summary->total_hours, 2),
            'deduction_minutes'  => $oldDeductions,
            'mins_late'          => (int) $summary->mins_late,
            'mins_undertime'     => (int) $summary->mins_undertime,
            'mins_night_diff'    => (int) $summary->mins_night_diff,
            'outpass_minutes'    => (int) $summary->outpass_minutes,
            'over_break_minutes' => (int) $summary->over_break_minutes,
        ];

        $new = [
            'total_hours'        => round((float) $validated['total_hours'], 2),
            'deduction_minutes'  => (int) $validated['deduction_minutes'],
            'mins_late'          => (int) $validated['mins_late'],
            'mins_undertime'     => (int) $validated['mins_undertime'],
            'mins_night_diff'    => (int) $validated['mins_night_diff'],
            'outpass_minutes'    => (int) $validated['outpass_minutes'],
            'over_break_minutes' => (int) $validated['over_break_minutes'],
        ];

        $changes = AuditLog::diff($old, $new);
        if (empty($changes)) {
            return response()->json(['status' => 'success', 'message' => 'No changes to save.']);
        }

        DB::transaction(function () use ($summary, $new, $oldDeductions, $note) {
            $summary->forceFill([
                'total_hours'        => $new['total_hours'],
                'mins_late'          => $new['mins_late'],
                'mins_undertime'     => $new['mins_undertime'],
                'mins_night_diff'    => $new['mins_night_diff'],
                'outpass_minutes'    => $new['outpass_minutes'],
                'over_break_minutes' => $new['over_break_minutes'],
            ])->save();

            // The visible "Deductions (Min)" total is the sum of attendance_deductions
            // rows. When the total is edited, consolidate: drop the old rows and write
            // a single row carrying the new total (0 ⇒ no row at all).
            if ($new['deduction_minutes'] !== $oldDeductions) {
                AttendanceDeduction::where('attendance_summary_id', $summary->id)->delete();
                if ($new['deduction_minutes'] > 0) {
                    AttendanceDeduction::create([
                        'attendance_summary_id' => $summary->id,
                        'deduction_minutes'     => $new['deduction_minutes'],
                        'reason'                => $note !== '' ? $note : 'Summary Logs manual adjustment',
                        'added_by'              => Auth::id(),
                    ]);
                }
            }
        });

        if ($note !== '') {
            $changes['note'] = $note;
        }
        AuditLog::record('updated', 'AttendanceSummary', $summary->id, $changes);

        $summary->refresh()->load(['employee', 'manualDeductions']);
        $summary->deduction_minutes = (int) $summary->manualDeductions->sum('deduction_minutes');
        $summary->formatted_date = $summary->attendance_date->format('Y-m-d');
        // We just verified no payroll covers this day (the lock check above passed).
        $summary->payroll_locked = false;
        $summary->payroll_period = null;
        // We just recorded an adjustment above — flag the just-saved row so the pill shows
        // immediately without a refetch. Author/time match the audit row we wrote.
        $summary->is_adjusted = true;
        $summary->adjusted_by = Auth::user()
            ? (trim((Auth::user()->fname ?? '') . ' ' . (Auth::user()->lname ?? '')) ?: (Auth::user()->name ?? 'User'))
            : 'system';
        $summary->adjusted_at = now()->format('M d, Y g:i A');
        $summary->adjusted_from_gross = $changes['total_hours']['from'] ?? null;

        return response()->json([
            'status' => 'success',
            'message' => 'Summary updated.',
            'data' => $summary,
        ]);
    }
}
