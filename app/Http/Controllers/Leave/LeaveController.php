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

            $leaveCredit = 0;
            if ($request->leavekind == 0) {
                // paid leave

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
    }

    public function checkLeaveCredit(Request $request)
    {
        $user = Auth::user();
        $leave = leavetype::where('id', $request->leave_id)
            ->first();

        if ($leave) {
            $leaveValidation = leavevalidationModel::where('leave_type', $leave->id)
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
}
