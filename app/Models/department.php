<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class department extends Model
{
    use HasFactory;
    use \App\Traits\Auditable;
    protected $table = 'departments';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'dep_name',
        'dep_address',
        'dep_contact_phone',
        'dep_email',
        'dep_tin',
        'dep_sss_employer_no',
        'dep_philhealth_employer_no',
        'dep_pagibig_employer_no',
        'dep_logo_path',
        'dep_description',
    ];

    public function documents()
    {
        return $this->hasMany(\App\Models\DepartmentDocument::class, 'department_id', 'id');
    }
}
