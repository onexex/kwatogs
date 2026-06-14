<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Overtime extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'emp_detail_id',
        'approved_by',
        'approved_at',
        'status',
        'date_from',
        'date_to',  
        'time_in',   
        'time_out',   
        'purpose',
        'total_hrs',
        'total_pay',
        'disapproved_remarks',   
        'day_type',
        'day_type_computation',
        'hourly_rate',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(empDetail::class, 'emp_detail_id', 'id');
    }
}
