<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDeduction;
use App\Models\AttendanceSummary;
use App\Models\AuditLog;
use App\Models\EmployeeSchedule;
use App\Models\homeAttendance;
use App\Models\ImportBatch;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\LeaveHistory;
use App\Models\Overtime;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Shared import-history screen for the attendance / overtime / leave imports.
 * The module is fixed per route (->defaults('module', ...)); this one controller
 * lists past imports, shows the rows a batch created, and rolls a batch back so a
 * corrected file can be re-uploaded.
 */
class ImportHistoryController extends Controller
{
    /** Per-module display + URL config. */
    private function cfg(string $module): array
    {
        $map = [
            'attendance' => ['label' => 'Attendance', 'routePrefix' => 'attendance-import.history', 'import' => 'attendance-import.index'],
            'overtime'   => ['label' => 'Overtime',   'routePrefix' => 'overtime-import.history',   'import' => 'overtime-import.index'],
            'leave'      => ['label' => 'Leave',       'routePrefix' => 'leave-import.history',      'import' => 'leave-import.index'],
            'schedule'   => ['label' => 'Schedule',    'routePrefix' => 'schedule-import.history',   'import' => 'schedule-import.index'],
        ];
        return $map[$module] ?? abort(404);
    }

    public function index(Request $request)
    {
        // module comes from the route's ->defaults('module', ...), read explicitly so it
        // never collides with the {id} URL segment during positional argument binding.
        $module = $request->route()->parameter('module');
        $c = $this->cfg($module);
        $batches = ImportBatch::module($module)->orderByDesc('id')->get();

        return view('pages.modules.import_history_index', [
            'batches'     => $batches,
            'module'      => $module,
            'moduleLabel' => $c['label'],
            'routePrefix' => $c['routePrefix'],
            'importRoute' => $c['import'],
        ]);
    }

    public function show(Request $request, $id)
    {
        $module = $request->route()->parameter('module');
        $c = $this->cfg($module);
        $batch = ImportBatch::module($module)->findOrFail($id);
        [$columns, $rows] = $this->rowsFor($module, $batch);

        return view('pages.modules.import_history_show', [
            'batch'       => $batch,
            'module'      => $module,
            'moduleLabel' => $c['label'],
            'routePrefix' => $c['routePrefix'],
            'importRoute' => $c['import'],
            'columns'     => $columns,
            'rows'        => $rows,
        ]);
    }

    /**
     * Roll back a whole import: delete the records it created, then drop the batch.
     * Blocked if any of the batch's dates already fall inside a computed payroll,
     * since removing that data would desync payroll figures.
     */
    public function destroy(Request $request, $id)
    {
        $module = $request->route()->parameter('module');
        $batch = ImportBatch::module($module)->findOrFail($id);

        if ($batch->date_from && $batch->date_to) {
            $overlaps = Payroll::whereDate('payroll_start_date', '<=', $batch->date_to)
                ->whereDate('payroll_end_date', '>=', $batch->date_from)
                ->exists();
            if ($overlaps) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete: these dates are already part of a computed payroll. Delete/recompute that payroll first.',
                ], 422);
            }
        }

        DB::transaction(function () use ($module, $batch) {
            $this->rollback($module, $batch);
            $batch->delete();
        });

        AuditLog::record('deleted', 'ImportBatch:' . $module, $batch->id, [
            'file'      => $batch->filename,
            'date_from' => optional($batch->date_from)->toDateString(),
            'date_to'   => optional($batch->date_to)->toDateString(),
            'rows'      => $batch->row_count,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Import rolled back. You can now re-upload the corrected file.',
        ]);
    }

    /** Delete the records a batch created, per module. */
    private function rollback(string $module, ImportBatch $batch): void
    {
        if ($module === 'attendance') {
            $summaryIds = AttendanceSummary::where('import_batch_id', $batch->id)->pluck('id');
            AttendanceDeduction::whereIn('attendance_summary_id', $summaryIds)->delete();
            AttendanceSummary::where('import_batch_id', $batch->id)->delete();
            homeAttendance::where('import_batch_id', $batch->id)->delete();
            // Schedules are tagged ONLY when the import created them (not when it overwrote a
            // pre-existing one), so deleting batch-tagged schedules removes exactly what this
            // import added — pre-existing shifts it merely updated keep a null tag and survive.
            EmployeeSchedule::where('import_batch_id', $batch->id)->delete();
        } elseif ($module === 'overtime') {
            Overtime::where('import_batch_id', $batch->id)->delete();
        } elseif ($module === 'leave') {
            $leaveIds = Leave::where('import_batch_id', $batch->id)->pluck('id');
            LeaveHistory::whereIn('leave_id', $leaveIds)->delete(); // status-trail rows written on create
            LeaveDetail::where('import_batch_id', $batch->id)->delete();
            Leave::where('import_batch_id', $batch->id)->delete();
        } elseif ($module === 'schedule') {
            // The schedule import only ever creates rows (it rejects overlaps), so every
            // batch-tagged schedule was created by this import — safe to delete outright.
            EmployeeSchedule::where('import_batch_id', $batch->id)->delete();
        }
    }

    /**
     * Build [columns, rows] for the detail view, per module.
     * Each row is ['cells' => [string...], 'flag' => ?string] — `flag` is a non-null
     * validation reason when the row's figures are internally inconsistent (only
     * attendance computes flags today; the other modules always pass null).
     */
    private function rowsFor(string $module, ImportBatch $batch): array
    {
        if ($module === 'attendance') {
            $columns = ['Employee', 'Date', 'Status', 'Total Hrs', 'Late', 'Undertime', 'Night Diff', 'Remarks', 'Validation'];
            $rows = AttendanceSummary::with('employee')
                ->where('import_batch_id', $batch->id)
                ->orderBy('attendance_date')->orderBy('employee_id')->get()
                ->sortBy(fn ($r) => $this->sortName(optional($r->employee)))->values()
                ->map(function ($r) {
                    $flag = $this->attendanceAnomaly($r);
                    return [
                        'cells' => [
                            $this->nameOf(optional($r->employee), $r->employee_id),
                            Carbon::parse($r->attendance_date)->format('M d, Y'),
                            ucfirst($r->status),
                            number_format($r->total_hours, 2),
                            $r->mins_late . 'm',
                            $r->mins_undertime . 'm',
                            $r->mins_night_diff . 'm',
                            $r->remarks ?: '—',
                            $flag ? 'Needs Validation' : 'OK',
                        ],
                        'flag' => $flag,
                        // Raw values + id power the inline edit modal (attendance only).
                        'id'   => $r->id,
                        'raw'  => [
                            'employee'       => $this->nameOf(optional($r->employee), $r->employee_id),
                            'date'           => Carbon::parse($r->attendance_date)->format('M d, Y'),
                            'total_hours'    => (float) $r->total_hours,
                            'mins_late'      => (int) $r->mins_late,
                            'mins_undertime' => (int) $r->mins_undertime,
                            'status'         => strtolower((string) $r->status),
                            'remarks'        => (string) $r->remarks,
                        ],
                    ];
                })->all();
            return [$columns, $rows];
        }

        if ($module === 'overtime') {
            $columns = ['Employee', 'Date From', 'Date To', 'Time In', 'Time Out', 'Day Type', 'Status', 'Total Hrs', 'Total Pay'];
            $rows = Overtime::with('employee.user')
                ->where('import_batch_id', $batch->id)
                ->orderBy('date_from')->get()
                ->sortBy(fn ($r) => $this->sortName(optional(optional($r->employee)->user)))->values()
                ->map(fn ($r) => ['cells' => [
                    $this->nameOf(optional(optional($r->employee)->user), optional($r->employee)->empID),
                    Carbon::parse($r->date_from)->format('M d, Y'),
                    Carbon::parse($r->date_to)->format('M d, Y'),
                    substr((string) $r->time_in, 0, 5),
                    substr((string) $r->time_out, 0, 5),
                    $r->day_type,
                    $r->status,
                    number_format((float) $r->total_hrs, 2),
                    number_format((float) $r->total_pay, 2),
                ], 'flag' => null])->all();
            return [$columns, $rows];
        }

        if ($module === 'schedule') {
            $columns = ['Employee', 'Date', 'In', 'Out', 'Break', 'Shift'];
            $rows = EmployeeSchedule::with('users')
                ->where('import_batch_id', $batch->id)
                ->orderBy('employee_id')->orderBy('sched_start_date')->get()
                ->sortBy(fn ($r) => $this->sortName(optional($r->users)))->values()
                ->map(fn ($r) => ['cells' => [
                    $this->nameOf(optional($r->users), $r->employee_id),
                    Carbon::parse($r->sched_start_date)->format('M d, Y'),
                    substr((string) $r->sched_in, 0, 5),
                    substr((string) $r->sched_out, 0, 5),
                    ($r->break_start && $r->break_end)
                        ? substr((string) $r->break_start, 0, 5) . '–' . substr((string) $r->break_end, 0, 5)
                        : '—',
                    $r->shift_type ?: '—',
                ], 'flag' => null])->all();
            return [$columns, $rows];
        }

        // leave
        $columns = ['Employee', 'Leave Type', 'Start', 'End', 'Kind', 'Status', 'Total Hrs'];
        $rows = Leave::with(['employee.user', 'leaveType'])
            ->where('import_batch_id', $batch->id)
            ->orderBy('start_date')->get()
            ->sortBy(fn ($r) => $this->sortName(optional(optional($r->employee)->user)))->values()
            ->map(fn ($r) => ['cells' => [
                $this->nameOf(optional(optional($r->employee)->user), $r->employee_id),
                optional($r->leaveType)->type_leave ?: ('#' . $r->leave_type),
                Carbon::parse($r->start_date)->format('M d, Y'),
                Carbon::parse($r->end_date)->format('M d, Y'),
                ((int) $r->leave_kind === 1) ? 'Unpaid' : 'Paid',
                $r->status,
                number_format((float) $r->total_hrs, 2),
            ], 'flag' => null])->all();
        return [$columns, $rows];
    }

    /**
     * Return a human-readable validation reason when an attendance row's numbers
     * contradict each other, or null when the row is internally consistent.
     *
     * The rules catch figures that can't logically coexist on one day:
     *  - a full standard day (>= 8 hrs) that ALSO carries tardiness or undertime
     *    — you can't be paid a complete day and be short on the same day;
     *  - "present"/"ob" with no hours recorded at all;
     *  - "absent"/"leave" that still logs worked hours, lateness, or undertime.
     */
    private function attendanceAnomaly(AttendanceSummary $r): ?string
    {
        $status     = strtolower((string) $r->status);
        $hours      = (float) $r->total_hours;
        $late       = (int) $r->mins_late;
        $undertime  = (int) $r->mins_undertime;
        $worked     = in_array($status, ['present', 'ob'], true);
        $reasons    = [];

        if ($worked && $hours >= 8 && ($late > 0 || $undertime > 0)) {
            $parts = [];
            if ($late > 0)      $parts[] = "{$late}m late";
            if ($undertime > 0) $parts[] = "{$undertime}m undertime";
            $reasons[] = 'Full day (' . number_format($hours, 2) . ' hrs) but has ' . implode(' + ', $parts);
        }

        if ($worked && $hours <= 0) {
            $reasons[] = 'Marked ' . $status . ' but no hours recorded';
        }

        if (in_array($status, ['absent', 'leave'], true) && ($hours > 0 || $late > 0 || $undertime > 0)) {
            $reasons[] = 'Marked ' . $status . ' but logs hours/late/undertime';
        }

        return $reasons ? implode('; ', $reasons) : null;
    }

    /**
     * Inline-edit a single attendance row from the import-history detail screen.
     *
     * Source of truth is the per-day rollup (attendance_summaries — what payroll reads);
     * the overlapping fields (hours/status/remarks) are mirrored to the matching
     * home_attendances punch row so the punch log doesn't drift. Blocked when the row's
     * date already falls inside a computed payroll (same guard as a batch roll-back).
     */
    public function updateRow(Request $request, $id)
    {
        $summary = AttendanceSummary::findOrFail($id);

        if ($this->dateInComputedPayroll($summary->attendance_date)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit: this date is already part of a computed payroll. Delete/recompute that payroll first.',
            ], 422);
        }

        $data = $request->validate([
            'total_hours'    => ['required', 'numeric', 'min:0', 'max:24'],
            'mins_late'      => ['required', 'integer', 'min:0', 'max:1440'],
            'mins_undertime' => ['required', 'integer', 'min:0', 'max:1440'],
            'status'         => ['required', 'string', 'in:present,ob,leave,absent'],
            'remarks'        => ['nullable', 'string', 'max:255'],
        ]);

        // Guardrail: reject edits that would create a physically-impossible row. This is the
        // authoritative check (the frontend mirrors it for instant feedback, but never the
        // last word). Schedule-dependent "looks off" cases stay soft — only confirmed on the UI.
        if ($err = $this->consistencyError($data)) {
            return response()->json(['success' => false, 'message' => $err], 422);
        }

        $before = [
            'total_hours'    => (float) $summary->total_hours,
            'mins_late'      => (int) $summary->mins_late,
            'mins_undertime' => (int) $summary->mins_undertime,
            'status'         => $summary->status,
            'remarks'        => $summary->remarks,
        ];

        DB::transaction(function () use ($summary, $data) {
            // Source of truth: the rollup payroll computes from.
            $summary->forceFill([
                'total_hours'    => $data['total_hours'],
                'mins_late'      => $data['mins_late'],
                'mins_undertime' => $data['mins_undertime'],
                'status'         => $data['status'],
                'remarks'        => $data['remarks'] ?? null,
            ])->save();

            // Mirror overlapping fields to the punch log (one row per employee+date for
            // imported data). mins_late/undertime have no punch-log column, so they stay
            // on the summary only.
            $log = homeAttendance::where('employee_id', $summary->employee_id)
                ->whereDate('attendance_date', Carbon::parse($summary->attendance_date)->toDateString())
                ->first();
            if ($log) {
                $log->forceFill([
                    'duration_hours' => $data['total_hours'],
                    'status'         => $data['status'],
                    'remarks'        => $data['remarks'] ?? null,
                ])->save();
            }
        });

        AuditLog::record('updated', 'AttendanceSummary', $summary->id, [
            'employee_id' => $summary->employee_id,
            'date'        => Carbon::parse($summary->attendance_date)->toDateString(),
            'before'      => $before,
            'after'       => [
                'total_hours'    => (float) $data['total_hours'],
                'mins_late'      => (int) $data['mins_late'],
                'mins_undertime' => (int) $data['mins_undertime'],
                'status'         => $data['status'],
                'remarks'        => $data['remarks'] ?? null,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Row updated.',
            'flag'    => $this->attendanceAnomaly($summary->refresh()),
        ]);
    }

    /**
     * Hard validation for an attendance-row edit: return an error message for a
     * physically-impossible combination, or null if the row is acceptable.
     *
     * These are schedule-independent impossibilities (unlike attendanceAnomaly's
     * heuristics): you cannot be absent/on-leave yet log worked hours or tardiness,
     * and you cannot be present/OB with no hours at all.
     */
    private function consistencyError(array $d): ?string
    {
        $status    = $d['status'];
        $hours     = (float) $d['total_hours'];
        $late      = (int) $d['mins_late'];
        $undertime = (int) $d['mins_undertime'];

        if (in_array($status, ['absent', 'leave'], true)) {
            if ($hours > 0) {
                return ucfirst($status) . ' days cannot have worked hours. Set Total Hrs to 0, or change the status.';
            }
            if ($late > 0 || $undertime > 0) {
                return ucfirst($status) . ' days cannot have late/undertime. Clear those, or change the status.';
            }
        }

        if (in_array($status, ['present', 'ob'], true) && $hours <= 0) {
            return ucfirst($status) . ' days must have worked hours greater than 0, or change the status to Absent/Leave.';
        }

        if ($late + $undertime > 1440) {
            return 'Late + undertime cannot exceed 24 hours in a day.';
        }

        return null;
    }

    /** True when a date falls inside the range of an already-computed payroll. */
    private function dateInComputedPayroll($date): bool
    {
        $d = Carbon::parse($date)->toDateString();
        return Payroll::whereDate('payroll_start_date', '<=', $d)
            ->whereDate('payroll_end_date', '>=', $d)
            ->exists();
    }

    /** "LASTNAME, FIRSTNAME (EMPID)" (upper-cased) from a User-like model, falling back to just the id. */
    private function nameOf($user, $empId): string
    {
        $last  = $user ? strtoupper(trim((string) ($user->lname ?? ''))) : '';
        $first = $user ? strtoupper(trim((string) ($user->fname ?? ''))) : '';

        if ($last !== '' && $first !== '') {
            $name = "{$last}, {$first}";
        } else {
            $name = $last !== '' ? $last : $first; // whichever we have
        }

        return $name !== '' ? "{$name} ({$empId})" : (string) $empId;
    }

    /** Sort key so rows order alphabetically by last name, then first name. */
    private function sortName($user): string
    {
        $last  = $user ? strtoupper(trim((string) ($user->lname ?? ''))) : '';
        $first = $user ? strtoupper(trim((string) ($user->fname ?? ''))) : '';
        return $last . '|' . $first;
    }
}
