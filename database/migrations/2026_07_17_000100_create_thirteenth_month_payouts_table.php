<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ledger of RELEASED 13th-month pay so the report becomes a system of record,
 * not just a calculator: it lets HR mark a batch as paid out (e.g. the June
 * mid-year half and the December half), see what's already been disbursed, and
 * avoid a double release. One row per employee per coverage YEAR (unique), so a
 * re-release for the same year updates the existing row rather than stacking.
 * `employee_id` is emp_details.empID / users.empID (a char key, so a plain
 * string column — the same convention as tenure_program_grants / loan_payments,
 * NOT a numeric FK).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thirteenth_month_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->index();     // empID
            $table->unsignedSmallInteger('coverage_year')->index();
            $table->date('coverage_from');
            $table->date('coverage_to');
            $table->decimal('amount', 12, 2)->default(0);          // 13th month released
            $table->decimal('taxable_excess', 12, 2)->default(0);  // portion above the BIR cap
            $table->date('released_at')->nullable();     // payout / release date
            $table->string('released_by')->nullable();   // acting admin name
            $table->string('batch')->nullable();         // free-text release batch label
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'coverage_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thirteenth_month_payouts');
    }
};
