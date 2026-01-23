<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSchedule extends Model
{
    use HasFactory;

    protected $table = 'employee_schedules';

    protected $fillable = [
        'employee_id',
        'sched_start_date',
        'sched_in',
        'sched_end_date',
        'sched_out',
        'shift_type',
        'break_start',   
        'break_end'     
    ];

  

    // Schedule belongs to an employee
    public function users()
    {
        return $this->belongsTo(User::class, 'employee_id','empID');
    }

    // Schedule has many home attendances
    public function homeAttendances()
    {
        return $this->hasMany(HomeAttendance::class, 'schedule_id');
    }

    // Optional: Get all attendance summaries for this schedule's employee (by date range)
    public function attendanceSummaries()
    {
        return $this->hasMany(AttendanceSummary::class, 'employee_id', 'employee_id')
                    ->whereBetween('attendance_date', [$this->sched_start_date, $this->sched_end_date]);
    }
}
