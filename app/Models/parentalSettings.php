<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class parentalSettings extends Model
{
    use HasFactory;

    protected $table = "parental_settings";
    protected $primaryKey = "id";
    public $timestamps = true;
    protected $fillable = ['prtl_nameFam', 'prtl_empID','prtl_birthday'];
}
