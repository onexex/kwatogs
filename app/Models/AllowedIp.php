<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllowedIp extends Model
{
    use HasFactory;

    protected $table = 'allowed_ips';

    protected $fillable = [
        'ip_address',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * The user who added this IP to the allowlist.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'empID');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Only active (enabled) IP entries.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Check if a given IP address is in the active allowlist.
     */
    public static function isAllowed(string $ip): bool
    {
        return static::where('ip_address', $ip)
            ->where('status', true)
            ->exists();
    }
}
