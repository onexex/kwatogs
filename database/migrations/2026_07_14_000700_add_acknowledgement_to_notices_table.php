<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Explicit acknowledgement of receipt for disciplinary notices.
 *
 * The passive `read_at` receipt (auto-stamped when the employee opens My
 * Notices) is weak proof for a labour dispute — it reads as "the system
 * recorded the page was opened". A deliberate "I acknowledge receipt" click
 * is stronger due-process evidence. These columns hold that one-time act
 * (timestamp + IP) for a disciplinary notice; it is worded as acknowledging
 * RECEIPT, not agreement, and is independent of the NTE response flow.
 *
 * One notice = one employee, so the acknowledgement lives on the notice row
 * itself — no separate table (unlike the versioned handbook acknowledgement,
 * which is per employee × section).
 *
 * HR opts in per notice via `requires_ack` (disciplinary-only, mirrors the NTE
 * `requires_response` flag) — the employee only sees the acknowledge button
 * when HR asks for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->boolean('requires_ack')->default(false)->after('respond_by');
            $table->timestamp('acknowledged_at')->nullable()->after('read_at');
            $table->string('acknowledged_ip')->nullable()->after('acknowledged_at');
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn(['requires_ack', 'acknowledged_at', 'acknowledged_ip']);
        });
    }
};
