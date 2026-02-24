<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveDetail extends Model
{
    use HasFactory;

    protected $fillable = [
       'employee_id',
       'leave_id',
       'leavetype_id',
       'date',
       'leave_kind',
       'total_hours',
       'status',
    ];
}
