<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * An auto-generated recommendation that an employee be considered for
 * suspension after accumulating disciplinary notices. Created by the system;
 * resolved (dismissed/actioned) manually by HR — never changes empStatus.
 */
class SuspensionRecommendation extends Model
{
    use Auditable;

    protected $fillable = [
        'employee_id', 'reason', 'notice_count', 'status',
        'recommended_by', 'recommended_at', 'resolved_by', 'resolved_at', 'resolution_note',
    ];

    protected $casts = [
        'notice_count'   => 'integer',
        'recommended_at' => 'datetime',
        'resolved_at'    => 'datetime',
    ];
}
