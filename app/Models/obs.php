<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class obs extends Model
{
    use HasFactory;
    protected $table = "obs";
    protected $primaryKey = "id";
    public $timestamps = true;
    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'destination',
        'purpose',
        'total_hrs',
        'status',
        'approved_by',
        'approved_at',
        'remarks',
    ];
}
