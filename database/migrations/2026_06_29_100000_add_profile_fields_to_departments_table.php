<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * This client runs a single company but uses the Department entity to represent
 * that company. These columns turn a department row into a full company profile
 * (address, contacts, gov't employer numbers, logo, notes). dep_name is widened
 * from char(40) — too short for a real company name — to varchar(191).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('dep_name', 191)->nullable()->default('n/a')->change();

            $table->string('dep_address')->nullable()->after('dep_name');
            $table->string('dep_contact_phone', 50)->nullable()->after('dep_address');
            $table->string('dep_email', 191)->nullable()->after('dep_contact_phone');
            $table->string('dep_tin', 50)->nullable()->after('dep_email');
            $table->string('dep_sss_employer_no', 50)->nullable()->after('dep_tin');
            $table->string('dep_philhealth_employer_no', 50)->nullable()->after('dep_sss_employer_no');
            $table->string('dep_pagibig_employer_no', 50)->nullable()->after('dep_philhealth_employer_no');
            $table->string('dep_logo_path')->nullable()->after('dep_pagibig_employer_no');
            $table->text('dep_description')->nullable()->after('dep_logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn([
                'dep_address',
                'dep_contact_phone',
                'dep_email',
                'dep_tin',
                'dep_sss_employer_no',
                'dep_philhealth_employer_no',
                'dep_pagibig_employer_no',
                'dep_logo_path',
                'dep_description',
            ]);
            $table->char('dep_name', 40)->nullable()->default('n/a')->change();
        });
    }
};
