<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class leavetype extends Model
{
    use HasFactory;
    protected $table = 'leavetypes';
    protected $primaryKey = 'id ';
    public $timestamps = true;

    protected $fillable = [
        'type_leave',
    ];
}
