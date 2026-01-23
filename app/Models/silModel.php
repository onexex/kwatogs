<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class silModel extends Model
{
    use HasFactory;
    protected $table = 'silloan';
    protected $primaryKey = 'id ';
    public $timestamps = true;

    protected $fillable = [
        'silEmpID',
        'silAmount',
        'silType',
        'silStatus',
        'silDate'
    ];
}
