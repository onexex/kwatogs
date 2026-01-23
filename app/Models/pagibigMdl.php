<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pagibigMdl extends Model
{
    use HasFactory;
    protected $table = 'pagibig';
    protected $primaryKey = 'id ';
    public $timestamps = true;

    protected $fillable = [
        'EE',
        'ER'
    ];
}
