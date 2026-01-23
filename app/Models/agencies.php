<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class agencies extends Model
{
    use HasFactory;
    protected $table = "agencies";
    protected $primaryKey = "id";
    public $timestamps = true;
    protected $fillable = ['ag_name','ag_status'];
}
