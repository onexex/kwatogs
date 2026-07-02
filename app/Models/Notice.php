<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * A notice or memo issued by HR to an employee.
 * type='disciplinary' rows (status='active') count toward the suspension
 * escalation; type='memo' rows are informational only.
 */
class Notice extends Model
{
    use Auditable;

    protected $fillable = [
        'employee_id', 'type', 'category', 'title', 'body',
        'issued_by', 'issued_at', 'status', 'is_read', 'read_at',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'read_at'   => 'datetime',
        'is_read'   => 'boolean',
    ];

    // Read-receipt churn isn't worth auditing.
    protected static function auditIgnore(): array
    {
        return ['updated_at', 'created_at', 'remember_token', 'password', 'is_read', 'read_at'];
    }
}
