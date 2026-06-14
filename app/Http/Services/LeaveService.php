<?php

namespace App\Http\Services;

use App\Enums\LeaveStatusEnum;
use App\Models\empDetail;
use App\Models\Leave;
use App\Models\LeaveCreditAllocation;
use App\Models\leavevalidationModel;
use Illuminate\Support\Facades\Auth;

class LeaveService
{
    public function updateStatus(int $leaveId, string $status, ?string $remarks = null): array
    {
        $leave = Leave::find($leaveId);
        $user = Auth::user();

        if ($leave) {

            $leave->leaveDetails()->update([
                'status' => $status
            ]);

            if ($status == LeaveStatusEnum::APPROVEDBYCFO->name) {
                $leaveType = $leave->leaveType;

                if ($leaveType) {
                    $employeeDetail = empDetail::where('empID', $leave->employee_id)
                        ->first();

                    if ($employeeDetail) {
                        $leaveValidation = leavevalidationModel::where('leave_type', $leaveType->id)
                            ->where('compID', $employeeDetail->empCompID)
                            ->first();

                        // Null-safe: only deduct credits when this leave type is configured
                        // as pre-allocated. A missing validation record = no deduction.
                        if ($leaveValidation && $leaveValidation->pre_allocated == 1) {

                            $preAllocatedLeave = LeaveCreditAllocation::where('employee_id', $employeeDetail->empID)
                                ->where('leavetype_id', $leaveType->id)
                                ->where('year', now()->year)
                                ->first();

                            if ($preAllocatedLeave) {
                                $balance = (float) $preAllocatedLeave->balance;

                                // Itemized approval: walk the per-day leave_details in date
                                // order. Approve each day the remaining credits can cover;
                                // disapprove the excess days. Balance never goes negative.
                                $details   = $leave->leaveDetails()->orderBy('date')->get();
                                $remaining = $balance;
                                $covered   = 0.0;
                                $rejected  = 0.0;

                                foreach ($details as $det) {
                                    $dayVal = (float) $det->total_hours / 8; // 1.0 full day, 0.5 half day
                                    if ($remaining + 1e-9 >= $dayVal) {
                                        $det->status = LeaveStatusEnum::APPROVEDBYCFO->name;
                                        $remaining -= $dayVal;
                                        $covered   += $dayVal;
                                    } else {
                                        $det->status = LeaveStatusEnum::DISAPPROVED->name;
                                        $rejected  += $dayVal;
                                    }
                                    $det->save();
                                }

                                // deduct only the days actually approved
                                $preAllocatedLeave->balance = $balance - $covered;
                                $preAllocatedLeave->save();

                                if ($rejected > 0) {
                                    $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.');
                                    $reason = 'Insufficient leave credits: ' . $fmt($covered + $rejected)
                                        . ' day(s) requested but only ' . $fmt($balance) . ' credit(s) available — '
                                        . $fmt($covered) . ' day(s) approved, ' . $fmt($rejected) . ' day(s) disapproved.';
                                    $leave->disapproved_remarks = $reason;
                                    $leave->remarks = trim((string) $leave->remarks . ' ' . $reason);

                                    // if no day could be covered, the whole leave is disapproved
                                    if ($covered <= 0) {
                                        $status = LeaveStatusEnum::DISAPPROVED->name;
                                    }
                                }
                            }
                        }
                    }

                }
            }

            if ($status == LeaveStatusEnum::DISAPPROVED->name && !empty($remarks)) {
                $leave->disapproved_remarks = $remarks;
            }

            if ($status == LeaveStatusEnum::APPROVED->name) {
                $leave->approved_by = $user->id;
                $leave->approved_at = now();
            }

            $leave->status = $status;
            $leave->save();

            return [
                'status' => 200,
                'message' => 'Leave successfully ' . $status  
            ];
        }
        return [
            'status' => 201,
            'message' => 'Leave not found' 
        ];
    }
}