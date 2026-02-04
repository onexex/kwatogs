<?php

namespace App\Observers;

use App\Models\Leave;
use App\Models\LeaveHistory;
use Illuminate\Support\Facades\Auth;

class LeaveStatusObserver
{
    /**
     * Handle the Leave "created" event.
     */
    public function created(Leave $leave): void
    {
        $user = Auth::user();
        
        if ($leave) {
            LeaveHistory::create([
                'leave_id' => $leave->id,
                'user_id' => $user->id,
                'status' => $leave->status,
            ]);
        }
    }

    /**
     * Handle the Leave "updated" event.
     */
    public function updated(Leave $leave): void
    {
        $user = Auth::user();
        
        if ($leave->isDirty('status')) {
            LeaveHistory::create([
                'leave_id' => $leave->id,
                'user_id' => $user->id,
                'status' => $leave->status,
            ]);
        }
    }

    /**
     * Handle the Leave "deleted" event.
     */
    public function deleted(Leave $leave): void
    {
        //
    }

    /**
     * Handle the Leave "restored" event.
     */
    public function restored(Leave $leave): void
    {
        //
    }

    /**
     * Handle the Leave "force deleted" event.
     */
    public function forceDeleted(Leave $leave): void
    {
        //
    }
}
