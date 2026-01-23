<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class obValidations extends Model
{
    use HasFactory;

    protected $table = "ob_validations";
    protected $primaryKey = "id";
    public $timestamps = true;
    protected $fillable = ['ob_fBefore', 'ob_dBefore','ob_fAfter','ob_dAfter'];
}
