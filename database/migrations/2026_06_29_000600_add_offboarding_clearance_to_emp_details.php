<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offboarding clearance checklist (managed from the E-201 "Update Status" modal,
 * ticked when HR marks an employee Resigned / End of Contract).
 *
 * HR-attested checkboxes — NO file uploads. Each item is a boolean done-flag;
 * the legally meaningful sign-offs (quitclaim, clearance) get field-level audit
 * via the empDetail Auditable trait. An optional per-item reference note lives in
 * the `clearance_refs` JSON map, and `cleared_by`/`cleared_at` record who last
 * saved the checklist + when (mirrors the flag_status / flagged_by / flagged_at
 * attestation pattern in the migration that added the separation/flag layer).
 *
 * Applicability is by exit type (see App\Services\OffboardingClearanceService):
 *   - cl_resignation_letter : Resigned ('0') only
 *   - cl_office_notice       : End of Contract / Terminated ('2') only
 *   - cl_clearance_form, cl_company_items, cl_quitclaim : all separated
 *
 * A separated employee's COE is gated on these being complete. Re-activating the
 * employee (back to Employed) clears them alongside the other exit fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emp_details', function (Blueprint $table) {
            $table->boolean('cl_resignation_letter')->default(false)->after('flagged_by');
            $table->boolean('cl_office_notice')->default(false)->after('cl_resignation_letter');
            $table->boolean('cl_clearance_form')->default(false)->after('cl_office_notice');
            $table->boolean('cl_company_items')->default(false)->after('cl_clearance_form');
            $table->boolean('cl_quitclaim')->default(false)->after('cl_company_items');

            $table->json('clearance_refs')->nullable()->after('cl_quitclaim'); // { item_key: "optional note" }
            $table->string('cleared_by', 150)->nullable()->after('clearance_refs');
            $table->date('cleared_at')->nullable()->after('cleared_by');
        });
    }

    public function down(): void
    {
        Schema::table('emp_details', function (Blueprint $table) {
            $table->dropColumn([
                'cl_resignation_letter',
                'cl_office_notice',
                'cl_clearance_form',
                'cl_company_items',
                'cl_quitclaim',
                'clearance_refs',
                'cleared_by',
                'cleared_at',
            ]);
        });
    }
};
