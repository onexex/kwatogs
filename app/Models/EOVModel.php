<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EOVModel extends Model
{
    use HasFactory;
    protected $table = 'EO_Validation';
    protected $primaryKey = 'id ';
    public $timestamps = true;

    protected $fillable = [
        'before',
        'after',
        'tardy'
    ];
}
