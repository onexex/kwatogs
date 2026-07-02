<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records that a milestone benefit was actually given to an employee.
 * Absence of a row (for an otherwise-eligible employee) = still pending.
 */
class TenureProgramGrant extends Model
{
    use Auditable;

    protected $fillable = ['tenure_program_id', 'employee_id', 'status', 'granted_at', 'granted_by', 'note'];

    protected $casts = [
        'granted_at' => 'date',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(TenureProgram::class, 'tenure_program_id');
    }
}
