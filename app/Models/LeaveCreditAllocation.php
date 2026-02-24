<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveCreditAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leavetype_id',
        'year',
        'credits_allocated',
        'balance',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id', 'empID');
    }

    public function leaveType()
    {
        return $this->belongsTo(leavetype::class, 'leavetype_id', 'id');
    }
}
