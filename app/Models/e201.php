<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class e201 extends Model
{
    use HasFactory;
    protected $table = 'e201s';
    protected $primaryKey = 'id ';
    public $timestamps = true;

    protected $fillable = [
        'empID',
        'path',
    ];
}
