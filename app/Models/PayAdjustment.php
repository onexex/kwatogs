<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayAdjustment extends Model
{
    protected $table = 'pay_adjustments';

    protected $fillable = [
        'employee_id',
        'pay_date',
        'label',
        'kind',       // addition | deduction
        'apply_to',   // gross | net
        'amount',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'pay_date' => 'date',
        'amount'   => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id', 'empID');
    }
}
