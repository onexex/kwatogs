<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSummary extends Model
{
    use HasFactory;

    protected $table = 'attendance_summaries';

    protected $fillable = [
        'employee_id',
        'attendance_date',
        'total_hours',
        'mins_late',
        'mins_undertime',
        'mins_night_diff',
        'status',
        'remarks',
        'over_break_minutes',
        'outpass_minutes'
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id','empID');
    }

   
    protected $casts = [
        'attendance_date' => 'date', // ← This makes it a Carbon instance
    ];

    // Inside class AttendanceSummary extends Model

    public function manualDeductions()
    {
        return $this->hasMany(AttendanceDeduction::class, 'attendance_summary_id');
    }

    /**
     * Accessor to get the Net Hours (Total - Deductions)
     * Access it using: $summary->net_hours
     */
    public function getNetHoursAttribute()
    {
        // Sum all minutes from the related deductions table
        $totalDeductedMins = $this->manualDeductions->sum('deduction_minutes');
        
        // Convert mins to hours (e.g. 30 mins = 0.5 hours)
        $deductedHours = $totalDeductedMins / 60;

        return $this->total_hours - $deductedHours;
    }
}
