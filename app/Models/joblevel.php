<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class joblevel extends Model
{
    use HasFactory;
    use \App\Traits\Auditable;
    protected $table = 'joblevels';
    protected $primaryKey = 'id';
    public $timestamps = true;


    protected $fillable = [
        'job_desc',
    ];
    
}
