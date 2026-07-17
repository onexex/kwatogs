<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ledger of RELEASED 13th-month pay so the report becomes a system of record,
 * not just a calculator: HR pays a mid-year HALF advance and the remaining WHOLE
 * in December, and at year-end reconciliation needs to see who already claimed
 * the half, who released it and when, and how much is still owed. Each claim is
 * its own row: one 'half' + one 'full' per employee per coverage YEAR (unique on
 * employee_id+coverage_year+portion), so re-releasing a portion updates its row
 * rather than stacking. `employee_id` is emp_details.empID / users.empID (a char
 * key, so a plain string column — the tenure_program_grants / loan_payments
 * convention, NOT a numeric FK).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thirteenth_month_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->index();     // empID
            $table->unsignedSmallInteger('coverage_year')->index();
            $table->string('portion', 10)->default('full'); // 'half' (mid-year advance) | 'full' (remaining/whole)
            $table->date('coverage_from');
            $table->date('coverage_to');
            $table->decimal('amount', 12, 2)->default(0);          // amount of THIS claim
            $table->decimal('taxable_excess', 12, 2)->default(0);  // portion above the BIR cap
            $table->date('released_at')->nullable();     // payout / release date
            $table->string('released_by')->nullable();   // acting admin name
            $table->string('batch')->nullable();         // free-text release batch label
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'coverage_year', 'portion'], 'tmp_emp_year_portion_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thirteenth_month_payouts');
    }
};
