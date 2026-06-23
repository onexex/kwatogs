<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SSS Contribution bracket table backing App\Models\SSSModel
        // (SSS Contribution Table settings screen). No migration previously
        // existed for it, so the screen fatally errored on save/list.
        if (! Schema::hasTable('sss')) {
            Schema::create('sss', function (Blueprint $table) {
                $table->id();
                $table->integer('sssc');                 // bracket / contribution code
                $table->float('from', 20, 4);            // salary range from
                $table->float('to', 20, 4);              // salary range to
                $table->float('sser', 20, 4);            // employer share
                $table->float('ssee', 20, 4);            // employee share
                $table->float('ssec', 20, 4);            // EC contribution
                $table->timestamps();
            });
        }

        // Safety net: the legacy `philhealth` table (App\Models\philhealthModel,
        // migration create_philhealth_models_table) is absent from some databases
        // even though that migration is recorded as run. Recreate it here only if
        // missing so the PhilHealth settings screen works; guarded so a fresh
        // install (where the 2022 migration already created it) is untouched.
        if (! Schema::hasTable('philhealth')) {
            Schema::create('philhealth', function (Blueprint $table) {
                $table->id();
                $table->float('phsb', 20, 4);
                $table->float('salaryFrom', 20, 4);
                $table->float('salaryTo', 20, 4);
                $table->float('phee', 20, 4);
                $table->float('pher', 20, 4);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop `sss` — `philhealth` is owned by create_philhealth_models_table.
        Schema::dropIfExists('sss');
    }
};
