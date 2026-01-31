<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceDeduction extends Model
{
    protected $fillable = [
        'attendance_summary_id',
        'deduction_minutes',
        'reason',
        'added_by'
    ];

    public function summary()
    {
        return $this->belongsTo(AttendanceSummary::class, 'attendance_summary_id');
    }
}