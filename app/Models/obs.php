<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class obs extends Model
{
    use HasFactory;
    protected $table = "obs";
    protected $primaryKey = "obID";
    public $timestamps = true;
    protected $fillable =
    [
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
