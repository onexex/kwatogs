<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class company extends Model
{
    use HasFactory;
    protected $table = 'companies';
    protected $primaryKey = 'id ';
    public $timestamps = true;   

    protected $fillable = [
        'comp_id',
        'comp_name',
        'comp_code',
        'comp_color',
        'comp_logo_path',
    ];
}
