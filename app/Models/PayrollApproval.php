<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollApproval extends Model
{
    protected $table = 'payroll_approvals';

    protected $fillable = [
        'pay_date',
        'approved_by',
        'approved_by_name',
        'approved_at',
        'remarks',
    ];

    protected $casts = [
        'pay_date'    => 'date',
        'approved_at' => 'datetime',
    ];

    /** Is the given pay date approved/locked? */
    public static function isLocked($payDate): bool
    {
        return static::whereDate('pay_date', $payDate)->exists();
    }
}
