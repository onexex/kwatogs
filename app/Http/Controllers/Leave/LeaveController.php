<?php
namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Models\LeaveCreditAllocation;
use App\Models\leavetype;
use App\Models\leavevalidationModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
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
