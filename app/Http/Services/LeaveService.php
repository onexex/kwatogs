<?php

namespace App\Http\Services;

use App\Enums\LeaveStatusEnum;
use App\Models\EmpDetail;
use App\Models\Leave;
use App\Models\LeaveCreditAllocation;
use App\Models\leavevalidationModel;

class LeaveService
{
    public function updateStatus(int $leaveId, string $status): array
    {
        $leave = Leave::find($leaveId);

        if ($leave) {

            $leave->leaveDetails()->update([
                'status' => $status
            ]);

            if ($status == LeaveStatusEnum::APPROVEDBYCFO->name) {
                $leaveType = $leave->leaveType;

                if ($leaveType) {
                    $employeeDetail = EmpDetail::where('empID', $leave->employee_id)
                        ->first();
                    
                    if ($employeeDetail) {
                        $leaveValidation = leavevalidationModel::where('leave_type', $leaveType->id)
                            ->where('compID', $employeeDetail->empCompID)
                            ->first();
                        if ($leaveValidation->pre_allocated == 1) {
                            
                            $preAllocatedLeave = LeaveCreditAllocation::where('employee_id', $employeeDetail->empID)
                                ->where('leavetype_id', $leaveType->id)
                                ->where('year', now()->year)
                                ->first();

                            if ($preAllocatedLeave) {
                                $balance = $preAllocatedLeave->balance;

                                $leaveDuration = $leave->total_hrs / 8;

                                $preAllocatedLeave->balance = $balance - $leaveDuration;
                                $preAllocatedLeave->save();
                            }
                        }
                    }

                }
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