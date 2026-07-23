<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-off cleanup: delete the historical `login` / `logout` rows that the old
 * EventServiceProvider auth listeners wrote into `audit_logs` on every sign-in
 * and sign-out. Those listeners were removed (see EventServiceProvider) because
 * they flooded the table with low-value entries; successful logins are already
 * tracked separately in IpAccessLog (Allowed IPs dashboard).
 *
 * `login-failed` rows are intentionally KEPT — they are low-volume and
 * security-relevant.
 *
 * This is destructive and irreversible (the pruned rows are not recoverable),
 * so down() is a deliberate no-op. Chunked deletes keep the transaction/lock
 * footprint small on a large table.
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            do {
                $deleted = DB::table('audit_logs')
                    ->whereIn('action', ['login', 'logout'])
                    ->limit(5000)
                    ->delete();
            } while ($deleted > 0);
        } catch (\Throwable $e) {
            // Never let a cleanup break the migration run.
        }
    }

    public function down(): void
    {
        // Irreversible: the pruned login/logout audit rows cannot be restored.
    }
};
