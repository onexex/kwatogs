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
        'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size',
        'issued_by', 'issued_at', 'status', 'is_read', 'read_at',
        // Explicit acknowledgement of receipt (disciplinary notices) — HR opts in per notice.
        'requires_ack', 'acknowledged_at', 'acknowledged_ip',
        // Notice to Explain (NTE)
        'requires_response', 'respond_by',
        'response_body', 'response_doc_path', 'response_doc_name', 'response_at',
        'response_decision', 'response_review_note', 'response_reviewed_by', 'response_reviewed_at',
    ];

    protected $casts = [
        'issued_at'          => 'date',
        'read_at'            => 'datetime',
        'is_read'            => 'boolean',
        'requires_ack'       => 'boolean',
        'acknowledged_at'    => 'datetime',
        'requires_response'  => 'boolean',
        'respond_by'         => 'date',
        'response_at'        => 'datetime',
        'response_reviewed_at' => 'datetime',
    ];

    // Read-receipt churn isn't worth auditing.
    protected static function auditIgnore(): array
    {
        return ['updated_at', 'created_at', 'remember_token', 'password', 'is_read', 'read_at'];
    }
}
