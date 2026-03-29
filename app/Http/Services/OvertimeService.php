<?php

namespace App\Http\Services;

use App\Models\Overtime;

class OvertimeService
{
    public function updateStatus(int $overtimeId, string $status)
    {
        $overtime = Overtime::find($overtimeId);

        if ($overtime) {
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