<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class employeeStatusModel extends Model
{
    use HasFactory;
    protected $table = 'emp_status';
    protected $primaryKey = 'id ';
    public $timestamps = true;

    protected $fillable = [
        'empStatName'
    ];
}
