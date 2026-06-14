<?php

namespace App\Services;

use App\Models\EmployeeSchedule;
use App\Models\homeAttendance;
use App\Models\ScheduleRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleRequestService
{
    /** Current schedule covering a given date (or null). */
    public function currentSchedule(string $empID, string $date): ?EmployeeSchedule
    {
        return EmployeeSchedule::where('employee_id', $empID)
            ->whereDate('sched_start_date', '<=', $date)
            ->whereDate('sched_end_date', '>=', $date)
            ->orderBy('sched_start_date', 'desc')
            ->first();
    }

    /**
     * Employee files a schedule change for a date. EMERGENCY MODE: the new
     * schedule is applied immediately so the employee can punch in right away,
     * even before HR is around. HR then reviews (approve = confirm, disapprove
     * = revert).
     */
    public function store(string $empID, string $date, string $newIn, string $newOut, ?string $reason): array
    {
        if ($date < Carbon::today()->toDateString()) {
            return ['ok' => false, 'message' => 'You can only request a change for today or a future date.'];
        }

        $req = ScheduleRequest::updateOrCreate(
            ['employee_id' => $empID, 'request_date' => $date],
            [
                'new_sched_in'        => $newIn,
                'new_sched_out'       => $newOut,
                'reason'              => $reason,
                'status'              => 'FORAPPROVAL',
                'approved_by'         => null,
                'approved_at'         => null,
                'disapproved_remarks' => null,
            ]
        );

        $this->applySchedule($req); // take effect now

        return [
            'ok' => true,
            'message' => "Submitted. Your schedule for {$date} is now active so you can time in — HR will review it.",
        ];
    }

    /** HR review: APPROVED = confirm, DISAPPROVED = revert to the old schedule. */
    public function updateStatus(int $id, string $status, ?string $remarks, ?int $approverUserId): array
    {
        $req = ScheduleRequest::find($id);
        if (!$req) { return ['ok' => false, 'message' => 'Request not found.']; }

        if ($status === 'DISAPPROVED') {
            if ($req->applied) { $this->revertSchedule($req); }
            $req->status = 'DISAPPROVED';
            $req->disapproved_remarks = $remarks;
            $req->save();
            return ['ok' => true, 'message' => 'Disapproved. The schedule was reverted and the day recomputed.'];
        }

        if ($status === 'APPROVED') {
            if (!$req->applied) {
                $this->applySchedule($req);
            } else {
                $this->recompute($req->employee_id, Carbon::parse($req->request_date)->toDateString());
            }
            $req->status      = 'APPROVED';
            $req->approved_by = $approverUserId;
            $req->approved_at = now();
            $req->save();
            return ['ok' => true, 'message' => 'Approved and confirmed.'];
        }

        return ['ok' => false, 'message' => 'Unknown status.'];
    }

    // ── helpers ──────────────────────────────────────────────────────────

    /** Write the requested schedule for that day (snapshotting the original). */
    private function applySchedule(ScheduleRequest $req): void
    {
        $date = Carbon::parse($req->request_date)->toDateString();
        $existing = $this->currentSchedule($req->employee_id, $date);

        // keep the ORIGINAL old times (don't overwrite if a request was already applied)
        if (is_null($req->old_sched_in)) {
            $req->old_sched_in  = $existing->sched_in ?? null;
            $req->old_sched_out = $existing->sched_out ?? null;
        }

        $endDate = strtotime($req->new_sched_out) <= strtotime($req->new_sched_in)
            ? Carbon::parse($date)->addDay()->toDateString()
            : $date;

        // critical writes — schedule + applied flag
        EmployeeSchedule::updateOrCreate(
            ['employee_id' => $req->employee_id, 'sched_start_date' => $date],
            [
                'sched_in'       => $req->new_sched_in,
                'sched_out'      => $req->new_sched_out,
                'sched_end_date' => $endDate,
                'break_start'    => $existing->break_start ?? null,
                'break_end'      => $existing->break_end ?? null,
                'shift_type'     => $existing->shift_type ?? null,
            ]
        );
        $req->applied = true;
        $req->save();

        // best-effort recompute (must not roll back the schedule write)
        try { $this->recompute($req->employee_id, $date); } catch (\Throwable $e) {}
    }

    /** Undo the applied schedule for that day, restoring the original. */
    private function revertSchedule(ScheduleRequest $req): void
    {
        $date = Carbon::parse($req->request_date)->toDateString();

        EmployeeSchedule::where('employee_id', $req->employee_id)
            ->whereDate('sched_start_date', $date)->delete();

        if (!is_null($req->old_sched_in)) {
            $endDate = strtotime($req->old_sched_out) <= strtotime($req->old_sched_in)
                ? Carbon::parse($date)->addDay()->toDateString()
                : $date;
            EmployeeSchedule::create([
                'employee_id'     => $req->employee_id,
                'sched_start_date' => $date,
                'sched_in'        => $req->old_sched_in,
                'sched_out'       => $req->old_sched_out,
                'sched_end_date'  => $endDate,
            ]);
        }

        $req->applied = false;
        $req->save();

        try { $this->recompute($req->employee_id, $date); } catch (\Throwable $e) {}
    }

    /** Recompute that day's attendance summary against the current schedule. */
    private function recompute(string $empID, string $date): void
    {
        $log = homeAttendance::where('employee_id', $empID)
            ->whereDate('attendance_date', $date)->first();
        if ($log) {
            $log->updateDailySummary();
        }
    }
}
