<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Applicant Tracking / Recruitment module.
 *
 * Captures only the INITIAL important data when a person applies. The row lives
 * entirely outside the employee tables (users / emp_details) so applicants never
 * leak into payroll, attendance, sidebar counts, or login.
 *
 * Lifecycle via `status`:
 *   - pool     : applied, kept in the talent pool (searchable by desired position)
 *   - hired    : HR ticked "Hire" and completed the full onboarding form, which
 *                creates the real employee; `hired_empID` links to it
 *   - rejected : not moving forward (hidden from the default pool view)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();

            // Initial important data captured at application time.
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();

            // What they applied for — free-text so the pool is searchable even for
            // roles that aren't predefined positions (e.g. "Dishwasher"). Optional
            // link to a department for filtering.
            $table->string('desired_position')->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();

            $table->string('source')->nullable();          // Walk-in | Referral | Online | Agency | Other
            $table->string('resume_path')->nullable();      // public/docs/applicants/<id>/<file>
            $table->unsignedTinyInteger('rating')->nullable(); // optional 1..5 HR score
            $table->text('notes')->nullable();
            $table->date('applied_at')->nullable();

            $table->string('status')->default('pool')->index(); // pool | hired | rejected
            $table->string('hired_empID')->nullable();          // set when converted to an employee
            $table->timestamp('hired_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('reviewed_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
