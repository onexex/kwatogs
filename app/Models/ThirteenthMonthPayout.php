<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A RELEASED 13th-month payout (one per employee per coverage year). Presence
 * of a row = already disbursed; absence = still pending. Saved through the model
 * instance so Auditable records the release/revert on the audit trail.
 *
 * @see database/migrations/2026_07_17_000100_create_thirteenth_month_payouts_table.php
 */
class ThirteenthMonthPayout extends Model
{
    use Auditable;

    /** Mid-year 50% advance. */
    public const PORTION_HALF = 'half';
    /** The whole / remaining balance (December settlement). */
    public const PORTION_FULL = 'full';

    protected $fillable = [
        'employee_id', 'coverage_year', 'portion', 'coverage_from', 'coverage_to',
        'amount', 'taxable_excess', 'released_at', 'released_by', 'batch', 'note',
    ];

    protected $casts = [
        'coverage_year'  => 'integer',
        'coverage_from'  => 'date',
        'coverage_to'    => 'date',
        'amount'         => 'float',
        'taxable_excess' => 'float',
        'released_at'    => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id', 'empID');
    }
}
