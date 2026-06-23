<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class effectivityDate extends Model
{
    use HasFactory;
    use \App\Traits\Auditable;
    protected $table = 'effectivity_dates';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'empID',
        'dFrom',
        'dTo'

    ];
}
