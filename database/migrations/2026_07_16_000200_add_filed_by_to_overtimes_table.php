<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records who filed an overtime row (users.id), so the Apply Employee Overtime
 * screen can offer a "Mine only" filter over the department-wide list. Nullable
 * and unconstrained — rows created through other flows (employee self-filing,
 * import) simply leave it null. Mirrors the existing `approved_by` column, which
 * also stores a users.id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            if (!Schema::hasColumn('overtimes', 'filed_by')) {
                $table->unsignedBigInteger('filed_by')->nullable()->after('approved_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            if (Schema::hasColumn('overtimes', 'filed_by')) {
                $table->dropIndex(['filed_by']);
                $table->dropColumn('filed_by');
            }
        });
    }
};
