<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    use HasFactory;
    use \App\Traits\Auditable;

    protected $table = 'employee_documents';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'empID',
        'doc_type',
        'clearance_key',
        'label',
        'file_name',
        'file_path',
        'original_name',
        'size',
        'mime',
        'uploaded_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
