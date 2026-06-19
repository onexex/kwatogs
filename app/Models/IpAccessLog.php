<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpAccessLog extends Model
{
    protected $table = 'ip_access_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'ip_address',
        'status',
        'action_type',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeAllowed($query)
    {
        return $query->where('status', 'allowed');
    }

    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeLogin($query)
    {
        return $query->where('action_type', 'login');
    }

    // ─── Static helper ────────────────────────────────────────────────────────

    /**
     * Record an IP access event. Never throws — audit logging must not
     * break the main request flow.
     *
     * @param  string       $status     'allowed' | 'blocked'
     * @param  string       $actionType 'login'   | 'access'
     * @param  string       $ip
     * @param  User|null    $user
     */
    public static function record(
        string $status,
        string $actionType,
        string $ip,
        ?User  $user = null,
    ): void {
        try {
            static::create([
                'user_id'     => $user?->id,
                'user_name'   => $user
                    ? trim(($user->fname ?? '') . ' ' . ($user->lname ?? ''))
                    : null,
                'ip_address'  => $ip,
                'status'      => $status,
                'action_type' => $actionType,
            ]);
        } catch (\Throwable) {
            // swallow — never interrupt the main flow
        }
    }
}
