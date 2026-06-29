<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Certificate of Employment (COE) requests.
 *
 * An employee requests a COE (purpose, copies, date needed). The request is
 * gated: only active employees with a complete profile and no pending request
 * may submit. HR reviews and either approves or rejects. On approval HR draws
 * an e-signature (stored as a base64 PNG), optionally includes salary, and a
 * snapshot of the certified facts is frozen so the PDF never drifts if the
 * employee's record later changes. Once approved the employee can download the
 * certificate as a PDF.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coe_requests', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->index();          // emp_details.empID / users.empID
            $table->string('purpose');                       // why the employee needs it (loan, visa, …)
            $table->unsignedInteger('copies')->default(1);
            $table->date('date_needed')->nullable();
            $table->text('remarks')->nullable();             // employee note to HR

            $table->string('status')->default('pending');    // pending | approved | rejected
            $table->boolean('include_salary')->default(false); // HR chooses per request
            $table->string('certificate_no')->nullable();    // COE-YYYY-#### assigned on approval
            $table->json('snapshot')->nullable();            // certified facts frozen at approval

            // HR e-signature + signatory block (set on approval)
            $table->string('signatory_name')->nullable();
            $table->string('signatory_title')->nullable();
            $table->longText('signature_data')->nullable();  // data:image/png;base64,… of the drawn signature

            $table->string('reviewed_by')->nullable();       // HR user name
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coe_requests');
    }
};
