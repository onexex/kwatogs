<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class classification extends Model
{
    use HasFactory;
    protected $table = 'classifications';
    protected $primaryKey = 'id ';
    public $timestamps = true;   

    protected $fillable = [
        'class_code',
        'class_desc',
    ];
}
