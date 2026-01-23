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
        Schema::create('philhealth_contributions', function (Blueprint $table) {
            $table->id();

            // Salary range
            $table->decimal('range_from', 10, 2);
            $table->decimal('range_to', 10, 2);

            // Contribution rates and shares
            $table->decimal('premium_rate', 5, 2)->default(5.00);  // e.g. 5% total (split employer/employee)
            $table->decimal('employee_share', 5, 2)->default(2.50); // in percentage
            $table->decimal('employer_share', 5, 2)->default(2.50); // in percentage

            // Salary limits (ceiling/floor)
            $table->decimal('min_salary', 10, 2)->default(10000);
            $table->decimal('max_salary', 10, 2)->default(90000);

            // Version control
            $table->year('effective_year')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('philhealth_contributions');
    }
};
