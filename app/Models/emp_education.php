<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class emp_education extends Model
{
    use HasFactory;
    protected $table = 'emp_educations';
    // protected $primaryKey = 'id ';
    public $timestamps = true;   

    protected $fillable = [
        'empID',
        'schoolLevel',
        'schoolName',
        'schoolYearStarted',
        'schoolYearEnded',
        'schoolAddress',
    ];
}
