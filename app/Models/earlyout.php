<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class earlyout extends Model
{
    use HasFactory;
    use HasFactory;
    protected $table = 'eo';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'empID',
        'isID',
        'liloID',
        'purpose',
        'isRem',
        'hrRem',
        'isUpdate',
        'hrUpdate',
        'status',
    ];
}
