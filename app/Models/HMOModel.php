<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HMOModel extends Model
{
    use HasFactory;
    protected $table = 'hmo';
    protected $primaryKey = 'id ';
    public $timestamps = true;

    protected $fillable = [
        // 'id',
        'idNo',
        'hmoName',
    ];
}
