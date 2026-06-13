<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $table = 'payroll_periods';

    protected $fillable = [
        'company_id',
        'label',
        'pay_day',
        'pay_end_of_month',
        'cutoff_from_day',
        'cutoff_from_prev_month',
        'cutoff_to_day',
        'sort',
    ];

    protected $casts = [
        'pay_end_of_month'       => 'boolean',
        'cutoff_from_prev_month' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(company::class, 'company_id', 'id');
    }
}
