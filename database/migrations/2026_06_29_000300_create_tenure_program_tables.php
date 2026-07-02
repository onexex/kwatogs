<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Programs Management — tenure-milestone benefits.
 *
 * A "program" is a years-of-service milestone tier (e.g. 2 years) that carries
 * one or more benefits (e.g. a sack of rice / "bigas"). Eligibility is computed
 * live from emp_details.empDateHired; grants record which employee was actually
 * given the benefit for that milestone. Wired into the HR Dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Milestone tiers
        Schema::create('tenure_programs', function (Blueprint $table) {
            $table->id();
            $table->string('title');                      // e.g. "2 Years of Service"
            $table->decimal('years_required', 5, 2);      // years of tenure to qualify
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Benefits attached to a milestone (one-to-many)
        Schema::create('tenure_program_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenure_program_id')->constrained('tenure_programs')->cascadeOnDelete();
            $table->string('name');                       // e.g. "Bigas (Rice)"
            $table->string('description')->nullable();    // qty / unit / notes, e.g. "1 sack (25kg)"
            $table->timestamps();
        });

        // Per-employee grant tracking for a milestone (granted vs not-yet)
        Schema::create('tenure_program_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenure_program_id')->constrained('tenure_programs')->cascadeOnDelete();
            $table->string('employee_id');                // emp_details.empID / users.empID
            $table->string('status')->default('granted'); // granted | pending
            $table->date('granted_at')->nullable();
            $table->string('granted_by')->nullable();     // acting admin name
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['tenure_program_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenure_program_grants');
        Schema::dropIfExists('tenure_program_benefits');
        Schema::dropIfExists('tenure_programs');
    }
};
