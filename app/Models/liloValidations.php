<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class liloValidations extends Model
{
    use HasFactory;

    protected $table = "lilo_validations";
    protected $primaryKey = "id";
    public $timestamps = true;
    protected $fillable = [
        'empCompID',
        'lilo_gracePrd',
        'managersOverride',
        'managersTime',
        'no_logout_has_deduction',
        'minute_deduction',
    ];
}
