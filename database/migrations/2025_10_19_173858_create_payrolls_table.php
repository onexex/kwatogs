<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();

            // 🧍 Employee reference
            $table->string('employee_id'); // Example: "WeDo-0001"

            // 📅 Payroll coverage
            $table->date('payroll_start_date');
            $table->date('payroll_end_date');
            $table->date('pay_date')->nullable();

            // 💰 Salary breakdown
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->decimal('holiday_pay', 12, 2)->default(0);
            $table->decimal('other_earnings', 12, 2)->default(0);

            // 🕒 Attendance-related fields
            $table->integer('late_minutes')->default(0);
            $table->integer('undertime_minutes')->default(0);
            $table->decimal('late_deduction', 12, 2)->default(0);
            $table->decimal('undertime_deduction', 12, 2)->default(0);
            $table->decimal('night_diff_hours', 8, 2)->default(0);
            $table->decimal('night_diff_pay', 12, 2)->default(0);
            $table->decimal('penalty_amount', 12, 2)->default(0);

            // 🏦 Government contributions
            $table->decimal('sss_contribution', 12, 2)->default(0);
            $table->decimal('philhealth_contribution', 12, 2)->default(0);
            $table->decimal('pagibig_contribution', 12, 2)->default(0);

            // 💸 Loans
            $table->decimal('sss_loan', 12, 2)->default(0);
            $table->decimal('pagibig_loan', 12, 2)->default(0);
            $table->decimal('company_loan', 12, 2)->default(0);

            // 🧾 Tax
            $table->decimal('withholding_tax', 12, 2)->default(0);

            // 🧮 Totals
            $table->decimal('gross_pay', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);

            // ⚙️ Status
            $table->enum('status', ['pending', 'processed', 'paid'])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
