<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Single-row settings model holding the app-wide maintenance state.
 * Mirrors the PayslipEmailSetting singleton pattern (::current()).
 *
 * Maintenance can be GLOBAL (locks every non-exempt user out) or scoped
 * to one or more DEPARTMENTS (only employees in those departments are
 * locked out). Exemption is permission-driven — see CheckMaintenanceMode.
 */
class MaintenanceSetting extends Model
{
    use HasFactory;
    use \App\Traits\Auditable;

    protected $table = 'maintenance_settings';
    protected $primaryKey = 'id';
    public $timestamps = true;

    public const SCOPE_GLOBAL     = 'global';
    public const SCOPE_DEPARTMENT = 'department';

    protected $fillable = [
        'is_active',
        'scope',
        'department_ids',
        'message',
        'starts_at',
        'ends_at',
        'updated_by',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'department_ids' => 'array',
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
    ];

    /**
     * Fetch the single settings row, creating sensible defaults if missing.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'is_active'      => false,
            'scope'          => self::SCOPE_GLOBAL,
            'department_ids' => [],
            'message'        => 'The system is temporarily unavailable while we perform scheduled maintenance. Please check back shortly.',
        ]);
    }

    /**
     * Is maintenance switched on AND (if scheduled) inside its time window?
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Given an employee's department id, does the active maintenance window
     * apply to them? Global maintenance applies to everyone; department-scoped
     * maintenance applies only to the listed department ids.
     */
    public function appliesToDepartment($departmentId): bool
    {
        if ($this->scope === self::SCOPE_GLOBAL) {
            return true;
        }

        if ($departmentId === null) {
            return false;
        }

        $ids = array_map('strval', (array) $this->department_ids);

        return in_array((string) $departmentId, $ids, true);
    }
}
