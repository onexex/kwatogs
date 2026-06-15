<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class department extends Model
{
    use HasFactory;
    use \App\Traits\Auditable;
    protected $table = 'departments';
    protected $primaryKey = 'id ';
    public $timestamps = true;

    protected $fillable = [
        'dep_name',
    ];
}
