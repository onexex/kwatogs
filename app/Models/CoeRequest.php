<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * A Certificate of Employment request raised by an employee and reviewed by HR.
 * status: pending → approved | rejected. Approved requests carry a frozen
 * `snapshot` of the certified facts plus the approving HR's drawn e-signature,
 * and become downloadable as a PDF by the owning employee.
 */
class CoeRequest extends Model
{
    use Auditable;

    protected $fillable = [
        'employee_id', 'purpose', 'copies', 'date_needed', 'remarks',
        'status', 'include_salary', 'certificate_no', 'snapshot',
        'signatory_name', 'signatory_title', 'signature_data',
        'reviewed_by', 'reviewed_at', 'rejection_reason',
    ];

    protected $casts = [
        'date_needed'    => 'date',
        'reviewed_at'    => 'datetime',
        'include_salary' => 'boolean',
        'snapshot'       => 'array',
    ];

    // The base64 signature blob is large and noisy — keep it out of the audit diff.
    protected static function auditIgnore(): array
    {
        return ['updated_at', 'created_at', 'signature_data'];
    }
}
