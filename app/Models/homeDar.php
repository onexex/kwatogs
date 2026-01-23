<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class homeDar extends Model
{
    use HasFactory;
    protected $table = 'home_dar';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'empID',
        'empActivity',
        'DarDateTime'
    ];
}
