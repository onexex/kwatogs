<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An employee's acknowledgement that they have read a handbook section.
 * The row itself (acknowledged_at + acked_version + ip) is the compliance
 * evidence, so — like notice read-receipts — it intentionally does NOT use
 * the Auditable trait (it would otherwise flood the audit trail).
 */
class HandbookAcknowledgement extends Model
{
    protected $fillable = [
        'employee_id', 'section_id', 'acked_version', 'ip', 'acknowledged_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'acked_version'   => 'integer',
    ];

    public function section()
    {
        return $this->belongsTo(HandbookSection::class, 'section_id');
    }
}
