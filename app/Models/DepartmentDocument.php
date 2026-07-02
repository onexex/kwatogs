<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentDocument extends Model
{
    use HasFactory;
    use \App\Traits\Auditable;

    protected $table = 'department_documents';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'department_id',
        'label',
        'file_name',
        'file_path',
        'original_name',
        'size',
        'mime',
        'uploaded_by',
    ];

    public function department()
    {
        return $this->belongsTo(department::class, 'department_id', 'id');
    }
}
