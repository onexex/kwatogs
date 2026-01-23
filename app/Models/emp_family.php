<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class emp_family extends Model
{
    use HasFactory;
    protected $table = 'emp_families';
    protected $primaryKey = 'id ';
    public $timestamps = true;   

    protected $fillable = [
        'empID',
        'famName',
        'famRelationID',
        'famRelationDesc',
        'famContact',
        'famICE',
    ];

}
