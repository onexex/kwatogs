<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class worktime extends Model
{
    use HasFactory;
    protected $table = 'worktimes';
    protected $primaryKey = 'id ';
    public $timestamps = true;   

    protected $fillable = [
        'wt_timefrom',
        'wt_timeto',
        'wt_timecross'
    ];

}
