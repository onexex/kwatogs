<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class holidayLoggerModel extends Model
{
    use HasFactory;
    protected $table = 'holiday_logger';
    protected $primaryKey = 'id ';
    public $timestamps = true;

    protected $fillable = [
        'date',
        'description',
        'type'
    ];
}
