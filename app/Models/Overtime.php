<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Overtime extends Model
{
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
        'total_pay'   
    ];
}
