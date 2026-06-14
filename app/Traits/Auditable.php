<?php

namespace App\Traits;

use App\Models\AuditLog;

/**
 * Add `use Auditable;` to a model to auto-record create/update/delete
 * into audit_logs with the acting user and before→after diffs.
 */
trait Auditable
{
    /** Fields never worth auditing. */
    protected static array $auditIgnore = ['updated_at', 'created_at', 'remember_token', 'password'];

    public static function bootAuditable(): void
    {
        static::created(fn ($m) => $m->writeAudit('created'));
        static::updated(fn ($m) => $m->writeAudit('updated'));
        static::deleted(fn ($m) => $m->writeAudit('deleted'));
    }

    public function writeAudit(string $action): void
    {
        try {
            $changes = null;

            if ($action === 'updated') {
                $changes = [];
                foreach ($this->getChanges() as $k => $new) {
                    if (in_array($k, static::$auditIgnore, true)) {
                        continue;
                    }
                    $changes[$k] = ['from' => $this->getOriginal($k), 'to' => $new];
                }
                if (empty($changes)) {
                    return; // nothing meaningful changed
                }
            } elseif ($action === 'created') {
                $attrs = $this->getAttributes();
                foreach (static::$auditIgnore as $ig) {
                    unset($attrs[$ig]);
                }
                $changes = $attrs;
            }

            $user = auth()->user();

            AuditLog::create([
                'user_id'   => optional($user)->id,
                'user_name' => $user ? trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: ($user->name ?? 'User') : 'system',
                'action'    => $action,
                'model'     => class_basename($this),
                'model_id'  => (string) $this->getKey(),
                'changes'   => $changes,
                'ip'        => request()->ip(),
                'url'       => request()->path(),
            ]);
        } catch (\Throwable $e) {
            // never let auditing break the actual operation
        }
    }
}
