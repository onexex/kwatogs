<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayslipEmailLog extends Model
{
    use \App\Traits\Auditable;

    protected $table = 'payslip_email_logs';

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'pay_date',
        'email_to',
        'status',
        'error_message',
        'mail_integration_setting_id',
        'sent_by',
        'sent_at',
    ];

    protected $casts = [
        'sent_at'  => 'datetime',
        'pay_date' => 'date',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id', 'empID');
    }
}
