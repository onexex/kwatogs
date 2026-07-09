<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A job applicant. Holds only the initial data captured at application time and
 * lives outside the employee tables. On hire it is converted into a real
 * employee via the registration/onboarding form (see registerCtrl::create),
 * which stamps `status = hired` + `hired_empID`.
 */
class Applicant extends Model
{
    use Auditable;

    protected $fillable = [
        'first_name', 'last_name', 'middle_name', 'email', 'mobile',
        'desired_position', 'highest_education', 'years_experience', 'qualifications',
        'department_id', 'source', 'resume_path',
        'rating', 'notes', 'applied_at', 'status', 'hired_empID',
        'hired_at', 'rejection_reason', 'reviewed_by',
    ];

    protected $casts = [
        'applied_at'       => 'date',
        'hired_at'         => 'datetime',
        'rating'           => 'integer',
        'years_experience' => 'float',
    ];

    /** Allowed highest-educational-attainment values (kept in sync with the Blade <select>). */
    public const EDUCATION_LEVELS = [
        'Elementary',
        'High School',
        'Senior High School',
        'Vocational / Technical',
        'College Undergraduate',
        'College Graduate',
        'Post-graduate',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(department::class, 'department_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} " . ($this->middle_name ? $this->middle_name . ' ' : '') . $this->last_name);
    }
}
