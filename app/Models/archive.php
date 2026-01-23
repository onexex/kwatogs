<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class archive extends Model
{
    use HasFactory;
    protected $table = "archive";
    protected $primaryKey = "id";
    public $timestamps = true;
    protected $fillable =
    ['fname',
    'lname',
    'pos',
    'empDatesFrom',
    'empDatesTo',
    'empStatus',
    'clearance',
    'reasonForLeaving',
    'derogatoryRecords',
    'salary',
    'pendingResign',
    'addRemarks',
    'verifiedBy'
    ];
}
