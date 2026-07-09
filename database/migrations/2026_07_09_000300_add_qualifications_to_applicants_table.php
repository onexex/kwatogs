<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qualification fields for applicants so HR can scan/compare the talent pool at
 * a glance: highest educational attainment, years of experience, and a free
 * skills/qualifications note.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->string('highest_education')->nullable()->after('desired_position');
            $table->decimal('years_experience', 4, 1)->nullable()->after('highest_education');
            $table->text('qualifications')->nullable()->after('years_experience');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn(['highest_education', 'years_experience', 'qualifications']);
        });
    }
};
