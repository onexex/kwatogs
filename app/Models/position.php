<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class position extends Model
{
    use HasFactory;
    protected $table = 'positions';
    protected $primaryKey = 'id ';
    public $timestamps = true;

    protected $fillable = [
        'pos_desc',
        'pos_jl',
        'pos_jl_desc'
    ];
    
}
