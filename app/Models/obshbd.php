<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class obshbd extends Model
{
    use HasFactory;
    protected $table = "obshbds";
    protected $primaryKey = "obIDHBD";
    public $timestamps = true;
    protected $fillable =
    [
    'obID',
    'empID',
    'empISID',
    'obFD',
    'obDateFrom',
    'obDateTo',
    'obIFrom',
    'obITo',
    'obCAAmt',
    'obCAPurpose',
    'obPurpose',
    'obTFrom',
    'obTTo',
    'obDuration',
    'obStatus',
    'obISReason',
    'obHRReason',
    'obType'
    ];
}
