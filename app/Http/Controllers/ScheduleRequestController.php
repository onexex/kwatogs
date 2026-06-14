<?php

namespace App\Http\Controllers;

use App\Models\ScheduleRequest;
use App\Services\ScheduleRequestService;
use Illuminate\Http\Request;

class ScheduleRequestController extends Controller
{
    public function __construct(private ScheduleRequestService $service) {}

    // Employee: own recent requests (JSON, for the embedded assistant in the clock-in area)
    public function mine(Request $request)
    {
        $empID = optional($request->user())->empID;
        $rows = ScheduleRequest::where('employee_id', $empID)
            ->orderByDesc('created_at')->limit(15)->get()
            ->map(fn ($r) => [
                'request_date'        => \Carbon\Carbon::parse($r->request_date)->format('M d, Y'),
                'new_sched_in'        => substr((string) $r->new_sched_in, 0, 5),
                'new_sched_out'       => substr((string) $r->new_sched_out, 0, 5),
                'status'              => $r->status,
                'reason'              => $r->reason,
                'disapproved_remarks' => $r->disapproved_remarks,
            ]);
        return response()->json($rows);
    }

    // AJAX: current schedule for a date (drives the assistant)
    public function currentSchedule(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $empID = optional($request->user())->empID;
        $sched = $this->service->currentSchedule($empID, $request->date);

        return response()->json([
            'has_schedule' => (bool) $sched,
            'sched_in'     => $sched->sched_in ?? null,
            'sched_out'    => $sched->sched_out ?? null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'request_date' => 'required|date',
            'new_sched_in' => 'required',
            'new_sched_out' => 'required',
            'reason' => 'nullable|string|max:255',
        ]);

        $res = $this->service->store(
            optional($request->user())->empID,
            $data['request_date'], $data['new_sched_in'], $data['new_sched_out'], $data['reason'] ?? null
        );

        return response()->json($res, $res['ok'] ? 200 : 422);
    }

    // HR: pending requests list
    public function pending()
    {
        $requests = ScheduleRequest::with('employee')
            ->where('status', 'FORAPPROVAL')
            ->orderBy('request_date')->get();

        return view('pages.modules.schedule_requests_pending', compact('requests'));
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:schedule_requests,id',
            'status' => 'required|in:APPROVED,DISAPPROVED',
            'remarks' => 'nullable|string|max:255',
        ]);

        $res = $this->service->updateStatus(
            (int) $request->id, $request->status, $request->remarks, optional($request->user())->id
        );

        return response()->json($res, $res['ok'] ? 200 : 422);
    }
}
