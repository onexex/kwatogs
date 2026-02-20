<?php
namespace App\Http\Controllers\Leave;

use Carbon\Carbon;
use App\Models\Leave;
use App\Models\leavetype;
use Illuminate\Http\Request;
use App\Enums\LeaveStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\leavevalidationModel;
use Illuminate\Support\Facades\Auth;
use App\Models\LeaveCreditAllocation;
use App\Models\LeaveDetail;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{
    public function getAllLeaves()
    {
        $user = Auth::user();
        $leaves = $user->leaves()->with(['leaveType'])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'leaves' => $leaves
        ]); 
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(),[
            'date_from' => 'required',
            'date_to' => 'required',
            'purpose' => 'required',
        ]);

        if(!$validator->passes()){
            return response()->json([
                'status'=>201, 
                'error'=>$validator->errors()->toArray()
            ]);
        } else {
            if (isset($request->halfday)) {
                if ($request->date_from != $request->date_to) {
                    return response()->json([
                        'status' => 201,
                        'error' => ['date_to' => ['For half-day leave, Start Date and End Date must be the same.']]
                    ]); 
                }
            }

            $leavel = Leave::where('employee_id', $user->empID)
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_date', [$request->date_from, $request->date_to])
                          ->orWhereBetween('end_date', [$request->date_from, $request->date_to])
                          ->orWhere(function ($query) use ($request) {
                              $query->where('start_date', '<=', $request->date_from)
                                    ->where('end_date', '>=', $request->date_to);
                          });
                })
                ->where('status', '!=', LeaveStatusEnum::CANCELED->name)
                ->first();

            if ($leavel) {
                return response()->json([
                    'status' => 201,
                    'error' => ['date_from' => ['You already have a leave application that overlaps with the selected dates.']]
                ]); 
            }

            $leaveCredit = 0;
            if ($request->leavekind == 0) {

                $leave = leavetype::where('id', $request->leavetype)
                    ->first();

                $leaveValidation = leavevalidationModel::where('leave_type', $leave->id)
                            ->first();
                $empDetail = $user->empDetail;

                if ($leaveValidation) {
                    if (!$empDetail->empDateRegular) {
                        return response()->json([
                            'status' => 201,
                            'error' => ['leavetype' => ['Missing Regularization Date. Contact supervisor.']]
                        ]);
                    }

                    if ($leaveValidation->pre_allocated == 1) {
                        $preAllocatedLeave = LeaveCreditAllocation::where('employee_id', $user->empID)
                            ->where('leavetype_id', $leave->id)
                            ->where('year', now()->year)
                            ->first();

                        if ($preAllocatedLeave) {
                            // check balance
                            $balance = $preAllocatedLeave->balance;
                            $leaveDuration = Carbon::parse($request->date_to)->diffInDays(Carbon::parse($request->date_from)) + 1;
                            if (isset($request->halfday) && $request->halfday == 1) {
                                $leaveDuration = 0.5; 
                            }

                            if ($balance < $leaveDuration) {
                                return response()->json([
                                    'status' => 201,
                                    'error' => ['leavetype' => ['Insufficient leave balance. Contact supervisor.']]
                                ]);
                            } else {

                                $leaveApplied = Leave::where('employee_id', $user->empID)
                                    ->where('leave_type', $request->leavetype)
                                    ->where('leave_kind', $request->leavekind)
                                    ->where('status', LeaveStatusEnum::FORAPPROVAL->name)
                                    ->sum('total_hrs');

                                if ($leaveApplied + ($leaveDuration * 8) > $balance * 8) {
                                    return response()->json([
                                        'status' => 201,
                                        'error' => ['leavetype' => ['Applying for this leave will exceed your available balance. Contact supervisor.']]
                                    ]);
                                }

                                $start = $request->date_from;
                                $end = $request->date_to;
                                $halfday = isset($request->halfday) ? $request->halfday : 0;

                                $durationHours = 0;

                                if ($halfday) {
                                    $durationHours = 4;
                                } else {
                                    $startDate = \Carbon\Carbon::parse($start);
                                    $endDate = \Carbon\Carbon::parse($end);

                                    $diffDays = $startDate->diffInDays($endDate) + 1;

                                    if ($diffDays == 1) {
                                        $durationHours = 8;  
                                    } else {
                                        $durationHours = $diffDays * 8; 
                                    }
                                }

                                $leave = Leave::create([
                                    'employee_id' => $user->empID,
                                    'start_date' => $start,
                                    'end_date' => $end,
                                    'leave_type' => $request->leavetype,
                                    'total_hrs' => $durationHours,
                                    'reason' => $request->purpose,
                                    'status' => LeaveStatusEnum::FORAPPROVAL->name,
                                    'is_half_day' => $halfday,
                                    'leave_kind' => $request->leavekind,
                                ]);

                                $dateFrom = Carbon::parse($request->date_from);
                                $dateTo   = Carbon::parse($request->date_to);
                                    
                                while ($dateFrom->lte($dateTo)) {
                                    $durationHours = 8; 

                                    if ($halfday && $request->date_from == $request->date_to) {
                                        $durationHours = 4;
                                    }

                                    LeaveDetail::create([
                                        'employee_id' => $user->empID,
                                        'leave_id' => $leave->id,
                                        'leavetype_id' => $request->leavetype,
                                        'date' => $dateFrom->format('Y-m-d'),
                                        'leave_kind' => $request->leavekind,
                                        'total_hours' => $durationHours,
                                        'status' => LeaveStatusEnum::FORAPPROVAL->name,
                                    ]);

                                    $dateFrom->addDay();
                                }

                            }

                        } else {
                            return response()->json([
                                'status' => 201,
                                'error' => ['leavetype' => ['Leave credit missing. Contact supervisor.']]
                            ]);
                        }
                    } else {
                        // leave credit is based on validation credits
                        dd(1);
                    }
                } else {
                    return response()->json([
                        'status' => 201,
                        'error' => ['leavetype' => ['No Leave Validation yet. Contact supervisor.']]
                    ]);
                }
            } else {
                $start = $request->date_from;
                $end = $request->date_to;
                $halfday = isset($request->halfday) ? $request->halfday : 0;

                $durationHours = 0;

                if ($halfday) {
                    $durationHours = 4;
                } else {
                    $startDate = \Carbon\Carbon::parse($start);
                    $endDate = \Carbon\Carbon::parse($end);

                    $diffDays = $startDate->diffInDays($endDate) + 1;

                    if ($diffDays == 1) {
                        $durationHours = 8;  
                    } else {
                        $durationHours = $diffDays * 8; 
                    }
                }

                $leave = Leave::create([
                    'employee_id' => $user->empID,
                    'start_date' => $start,
                    'end_date' => $end,
                    'leave_type' => $request->leavetype,
                    'total_hrs' => $durationHours,
                    'reason' => $request->purpose,
                    'status' => LeaveStatusEnum::FORAPPROVAL->name,
                    'is_half_day' => $halfday,
                    'leave_kind' => $request->leavekind,
                ]);

                $dateFrom = Carbon::parse($request->date_from);
                $dateTo   = Carbon::parse($request->date_to);
                
                while ($dateFrom->lte($dateTo)) {
                    $durationHours = 8; 

                    if ($halfday && $request->date_from == $request->date_to) {
                        $durationHours = 4;
                    }

                    LeaveDetail::create([
                        'employee_id' => $user->empID,
                        'leave_id' => $leave->id,
                        'leavetype_id' => $request->leavetype,
                        'date' => $dateFrom->format('Y-m-d'),
                        'leave_kind' => $request->leavekind,
                        'total_hours' => $durationHours,
                        'status' => LeaveStatusEnum::FORAPPROVAL->name,
                    ]);

                    $dateFrom->addDay();
                }
            }

        }
    }

    public function checkLeaveCredit(Request $request)
    {
        $user = Auth::user();
        $leave = leavetype::where('id', $request->leave_id)
            ->first();

        if ($leave) {
            $leaveValidation = leavevalidationModel::where('leave_type', $leave->id)
                ->where('compID', $user->empDetail->empCompID)
                ->first();
            $empDetail = $user->empDetail;
            if ($leaveValidation) {
                if (!$empDetail->empDateRegular) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Missing Regularization Date'
                    ]);
                }
                if ($leaveValidation->pre_allocated == 1) {
                    $preAllocatedLeave = LeaveCreditAllocation::where('employee_id', $user->empID)
                        ->where('leavetype_id', $leave->id)
                        ->where('year', now()->year)
                        ->first();

                    if ($preAllocatedLeave) {
                         return response()->json([
                            'leave_credit' =>  $preAllocatedLeave->credits_allocated
                        ]);
                    } else {
                        return response()->json([
                            'status' => 404,
                            'message' => 'Leave credit missing. Contact supervisor.'
                        ]);
                    }
                } else {
                    return response()->json([
                        'leave_credit' =>  $leaveValidation->credits
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => 'No Leave Validation yet'
                ]);
            }
        }
    }

    public function destroy(Leave $leave)
    {
        if ($leave->status != LeaveStatusEnum::FORAPPROVAL->name) {
            return response()->json([
                'status' => 400,
                'message' => 'Only leaves with status "FOR APPROVAL" can be deleted.'
            ]);
        }

        $leaveDetails = LeaveDetail::where('leave_id', $leave->id)->get();
        foreach ($leaveDetails as $detail) {
            $detail->delete();
        }
        $leave->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Leave deleted successfully.'
        ]);
    }
}
