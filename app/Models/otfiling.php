<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class otfiling extends Model
{
    use HasFactory;
    protected $table = 'otfilings';
    protected $primaryKey = 'id ';
    public $timestamps = true;

    protected $fillable = [
        'comp_id',

        'filebefore',
        'fileafter',
        'no_days_before',
        'no_days_after',
        'holiday',
        'tardy',
    ];
}
