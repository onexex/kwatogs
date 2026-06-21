<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollDetail extends Model
{
    use HasFactory;

    protected $table = 'payroll_details';

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'logsType',
        'holiday_type',
        'holiday_pay',
        'totalHours',
        'late_minutes',
        'undertime_minutes',
        'late_deduction',
        'undertime_deduction',
        'night_diff_hours',
        'night_diff_pay',
        'penalty_amount',
        'adjustment_amount',
        'remarks',
        'date',
        'payroll_date'
    ];

    protected $casts = [
        'date' => 'date',
        'holiday_pay' => 'decimal:2',
    ];

    // 🔗 Relationships
    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id','empID');
    }

    public function empdetails()
    {
       return $this->belongsTo(empDetail::class, 'employee_id', 'empID');
    }


}
