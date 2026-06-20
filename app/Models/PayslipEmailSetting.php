<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single-row settings controlling automated payslip email behavior.
 * Use PayslipEmailSetting::current() rather than querying directly.
 */
class PayslipEmailSetting extends Model
{
    use \App\Traits\Auditable;

    protected $table = 'payslip_email_settings';

    protected $fillable = [
        'password_source',
        'auto_send_on_approval',
        'updated_by',
    ];

    protected $casts = [
        'auto_send_on_approval' => 'boolean',
    ];

    public const PASSWORD_SOURCES = [
        'birthdate'   => "Employee's birthdate (DDMMYYYY)",
        'employee_id' => 'Employee ID number',
        'none'        => 'No password (not recommended — salary/PII left unprotected if forwarded)',
    ];

    /**
     * Fetch the single settings row, creating sensible defaults if it
     * doesn't exist yet (password-protected by birthdate, manual send only).
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'password_source'       => 'birthdate',
            'auto_send_on_approval' => false,
        ]);
    }
}
