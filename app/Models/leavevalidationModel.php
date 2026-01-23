<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class leavevalidationModel extends Model
{
    use HasFactory;
    protected $table = 'leavevalidation_models';
    protected $primaryKey = 'id ';
    public $timestamps = true;

    protected $fillable = [
        'compID',
        'leave_type',
        'credits',
        'min_leave',
        'no_before_file',
        'no_after_file',
        'file_before',
        'file_after',
        'file_halfday',
    ];
}
