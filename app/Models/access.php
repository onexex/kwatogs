<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class access extends Model
{
    use HasFactory;
      protected $table = 'access';
    protected $primaryKey = 'id ';
    public $timestamps = true;

    protected $fillable = [
        'home',
        'settings',
        'rpt_attend',
    ];
}
