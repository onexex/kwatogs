<?php

namespace App\Http\Services;

use App\Enums\LeaveStatusEnum;
use App\Models\Leave;

class LeaveService
{
    public function updateStatus(int $leaveId, string $status): array
    {
        $leave = Leave::find($leaveId);

        if ($leave) {

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