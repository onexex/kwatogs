<?php

namespace App\Http\Services;

use App\Enums\OvertimeStatusEnum;
use App\Models\Overtime;
use Illuminate\Support\Facades\Auth;

class OvertimeService
{
    public function updateStatus(int $overtimeId, string $status, ?string $remarks = null): array
    {
        $overtime = Overtime::find($overtimeId);
        $user = Auth::user();

        if ($overtime) {
            
            if ($status == OvertimeStatusEnum::DISAPPROVED->name) {
                $overtime->disapproved_remarks = $remarks;
            }

            if ($status == OvertimeStatusEnum::APPROVED->name) {
                $overtime->approved_by = $user->id;
                $overtime->approved_at = now();
            }

            $overtime->status = $status;
            $overtime->save();

           return [
                'status' => 200,
                'message' => 'Overtime successfully ' . $status  
            ];
        }
        return [
            'status' => 201,
            'message' => 'Overtime not found' 
        ];
    }
}