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
        Schema::create('pagibig_contributions', function (Blueprint $table) {
            $table->id();

            // Salary range
            $table->decimal('range_from', 10, 2);
            $table->decimal('range_to', 10, 2);

            // Percentage rates (in percent)
            $table->decimal('employee_rate', 5, 2)->default(2.00);  // e.g. 2%
            $table->decimal('employer_rate', 5, 2)->default(2.00);  // e.g. 2%

            // Computed shares (optional cache)
            $table->decimal('employee_share', 10, 2)->default(0);
            $table->decimal('employer_share', 10, 2)->default(0);
            $table->decimal('total_contribution', 10, 2)->default(0);

            // Max salary covered by Pag-IBIG contribution (usually ₱5,000)
            $table->decimal('max_salary_credit', 10, 2)->default(5000);

            // Year for version control
            $table->year('effective_year')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagibig_contributions');
    }
};
