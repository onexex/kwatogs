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

    /** Build [columns, rows] for the detail view, per module. Rows are plain string cells. */
    private function rowsFor(string $module, ImportBatch $batch): array
    {
        if ($module === 'attendance') {
            $columns = ['Employee', 'Date', 'Status', 'Total Hrs', 'Late', 'Undertime', 'Night Diff', 'Remarks'];
            $rows = AttendanceSummary::with('employee')
                ->where('import_batch_id', $batch->id)
                ->orderBy('attendance_date')->orderBy('employee_id')->get()
                ->map(fn ($r) => [
                    $this->nameOf(optional($r->employee), $r->employee_id),
                    Carbon::parse($r->attendance_date)->format('M d, Y'),
                    ucfirst($r->status),
                    number_format($r->total_hours, 2),
                    $r->mins_late . 'm',
                    $r->mins_undertime . 'm',
                    $r->mins_night_diff . 'm',
                    $r->remarks ?: '—',
                ])->all();
            return [$columns, $rows];
        }

        if ($module === 'overtime') {
            $columns = ['Employee', 'Date From', 'Date To', 'Time In', 'Time Out', 'Day Type', 'Status', 'Total Hrs', 'Total Pay'];
            $rows = Overtime::with('employee.user')
                ->where('import_batch_id', $batch->id)
                ->orderBy('date_from')->get()
                ->map(fn ($r) => [
                    $this->nameOf(optional(optional($r->employee)->user), optional($r->employee)->empID),
                    Carbon::parse($r->date_from)->format('M d, Y'),
                    Carbon::parse($r->date_to)->format('M d, Y'),
                    substr((string) $r->time_in, 0, 5),
                    substr((string) $r->time_out, 0, 5),
                    $r->day_type,
                    $r->status,
                    number_format((float) $r->total_hrs, 2),
                    number_format((float) $r->total_pay, 2),
                ])->all();
            return [$columns, $rows];
        }

        if ($module === 'schedule') {
            $columns = ['Employee', 'Date', 'In', 'Out', 'Break', 'Shift'];
            $rows = EmployeeSchedule::with('users')
                ->where('import_batch_id', $batch->id)
                ->orderBy('employee_id')->orderBy('sched_start_date')->get()
                ->map(fn ($r) => [
                    $this->nameOf(optional($r->users), $r->employee_id),
                    Carbon::parse($r->sched_start_date)->format('M d, Y'),
                    substr((string) $r->sched_in, 0, 5),
                    substr((string) $r->sched_out, 0, 5),
                    ($r->break_start && $r->break_end)
                        ? substr((string) $r->break_start, 0, 5) . '–' . substr((string) $r->break_end, 0, 5)
                        : '—',
                    $r->shift_type ?: '—',
                ])->all();
            return [$columns, $rows];
        }

        // leave
        $columns = ['Employee', 'Leave Type', 'Start', 'End', 'Kind', 'Status', 'Total Hrs'];
        $rows = Leave::with(['employee.user', 'leaveType'])
            ->where('import_batch_id', $batch->id)
            ->orderBy('start_date')->get()
            ->map(fn ($r) => [
                $this->nameOf(optional(optional($r->employee)->user), $r->employee_id),
                optional($r->leaveType)->type_leave ?: ('#' . $r->leave_type),
                Carbon::parse($r->start_date)->format('M d, Y'),
                Carbon::parse($r->end_date)->format('M d, Y'),
                ((int) $r->leave_kind === 1) ? 'Unpaid' : 'Paid',
                $r->status,
                number_format((float) $r->total_hrs, 2),
            ])->all();
        return [$columns, $rows];
    }

    /** "First Last (EMPID)" from a User-like model, falling back to just the id. */
    private function nameOf($user, $empId): string
    {
        $name = $user ? trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) : '';
        return $name !== '' ? "{$name} ({$empId})" : (string) $empId;
    }
}
