<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollLog extends Model
{
    use HasFactory;

    protected $table = 'payroll_logs';

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'employee_name',
        'department_id',
        'department_name',
        'classification',
        'pay_date',
        'payroll_start_date',
        'payroll_end_date',
        'gross_pay',
        'net_pay',
        'pay_rec',
        'breakdown',
    ];

    protected $casts = [
        'pay_date' => 'date:Y-m-d',
        'payroll_start_date' => 'date:Y-m-d',
        'payroll_end_date' => 'date:Y-m-d',
        'breakdown'          => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id', 'empID');
    }
}
