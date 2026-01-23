<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class schedules extends Model
{
    use HasFactory;
    protected $table = 'schedules';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'empID',
        'days',
        'wtID',
        'edID'
    ];
}
