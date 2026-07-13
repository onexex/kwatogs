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

        if (! $overtime) {
            return [
                'status' => 201,
                'message' => 'Overtime not found'
            ];
        }

        if (! $this->canTransition($user, $overtime->status, $status)) {
            return [
                'status' => 403,
                'message' => 'You are not allowed to perform this action.'
            ];
        }

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

    /**
     * Server-side guard for who may move an overtime request between statuses.
     *
     * First-level approvers (approveovertime) may APPROVE or DISAPPROVE a
     * FOR APPROVAL request. CFO approvers (approvecfoovertime) may CONFIRM
     * (APPROVED BY CFO) or DISAPPROVE an already-APPROVED request.
     */
    private function canTransition($user, string $currentStatus, string $newStatus): bool
    {
        switch ($newStatus) {
            case OvertimeStatusEnum::APPROVED->name:
                return $currentStatus === OvertimeStatusEnum::FORAPPROVAL->name
                    && $user->can('approveovertime');

            case OvertimeStatusEnum::APPROVEDBYCFO->name:
                return $currentStatus === OvertimeStatusEnum::APPROVED->name
                    && $user->can('approvecfoovertime');

            case OvertimeStatusEnum::DISAPPROVED->name:
                if ($currentStatus === OvertimeStatusEnum::FORAPPROVAL->name) {
                    return $user->can('approveovertime');
                }
                if ($currentStatus === OvertimeStatusEnum::APPROVED->name) {
                    return $user->can('approvecfoovertime');
                }
                return false;

            default:
                return false;
        }
    }
}