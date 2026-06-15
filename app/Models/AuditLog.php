<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id', 'user_name', 'action', 'model', 'model_id', 'changes', 'ip', 'url',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    /**
     * Manually write an audit entry. Use this for actions that bypass Eloquent
     * model events — e.g. query-builder writes (DB::table()->update()),
     * Model::where()->update(), pivot changes (role assignment), or auth events.
     *
     * Auditing must never break the underlying operation, so all failures are swallowed.
     *
     * @param  string      $action   created | updated | deleted | login | logout | login-failed | role-assigned | role-removed | ...
     * @param  string      $model    A human-readable model/entity label (e.g. "empDetail", "User").
     * @param  mixed       $modelId  The record key, if any.
     * @param  array|null  $changes  {field: {from, to}} for updates, or a flat attribute map for creates.
     */
    public static function record(string $action, string $model, $modelId = null, ?array $changes = null): void
    {
        try {
            $user = auth()->user();
            $name = $user
                ? (trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: ($user->name ?? 'User'))
                : 'system';

            static::create([
                'user_id'   => optional($user)->id,
                'user_name' => $name,
                'action'    => $action,
                'model'     => $model,
                'model_id'  => $modelId !== null ? (string) $modelId : null,
                'changes'   => $changes,
                'ip'        => request()->ip(),
                'url'       => request()->path(),
            ]);
        } catch (\Throwable $e) {
            // never let auditing break the actual operation
        }
    }

    /**
     * Build a {field: {from, to}} diff between an old attribute map and the new
     * values being written. Values are compared loosely (as strings) to avoid
     * noise from int/string type differences. Returns [] when nothing changed.
     */
    public static function diff(array $old, array $new, array $ignore = ['updated_at', 'created_at', 'password', 'remember_token']): array
    {
        $changes = [];
        foreach ($new as $key => $value) {
            if (in_array($key, $ignore, true)) {
                continue;
            }
            $before = $old[$key] ?? null;
            if ((string) $before !== (string) $value) {
                $changes[$key] = ['from' => $before, 'to' => $value];
            }
        }
        return $changes;
    }
}
