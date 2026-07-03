<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tags an employment document to a specific offboarding-clearance item
 * (resignation_letter, clearance_form, quitclaim, …). Set when HR attaches the
 * supporting file for a clearance requirement in the E-201 "Update Status" modal,
 * so the dossier clearance card and the COE issuance flow can link the proof.
 * NULL for ordinary documents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->string('clearance_key')->nullable()->after('doc_type');
            $table->index(['user_id', 'clearance_key']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'clearance_key']);
            $table->dropColumn('clearance_key');
        });
    }
};
