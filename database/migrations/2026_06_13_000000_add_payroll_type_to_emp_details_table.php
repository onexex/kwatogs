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
        Schema::table('emp_details', function (Blueprint $table) {
            // Payroll disbursement type: CASH or CARD
            $table->string('empPayrollType', 10)->default('CASH')->after('empAllowance');
            // Card / account number (used when empPayrollType = CARD)
            $table->string('empCardNo', 50)->nullable()->after('empPayrollType');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emp_details', function (Blueprint $table) {
            $table->dropColumn(['empPayrollType', 'empCardNo']);
        });
    }
};
