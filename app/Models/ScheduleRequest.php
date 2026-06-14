<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class ScheduleRequest extends Model
{
    use Auditable;
    protected $table = 'schedule_requests';

    protected $fillable = [
        'employee_id', 'request_date',
        'old_sched_in', 'old_sched_out',
        'new_sched_in', 'new_sched_out',
        'reason', 'status', 'approved_by', 'approved_at',
        'disapproved_remarks', 'applied',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at'  => 'datetime',
        'applied'      => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id', 'empID');
    }
}
