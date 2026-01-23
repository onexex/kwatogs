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
        Schema::create('sss_contributions', function (Blueprint $table) {
            $table->id();

            // Salary range
            $table->decimal('range_from', 10, 2);
            $table->decimal('range_to', 10, 2);

            // Shares and other contributions
            $table->decimal('employee_share', 10, 2)->default(0);   // Employee’s portion
            $table->decimal('employer_share', 10, 2)->default(0);   // Employer’s portion
            $table->decimal('ec', 10, 2)->default(0);               // Employee’s Compensation (employer-paid)
            $table->decimal('mpf', 10, 2)->default(0);              // Mandatory Provident Fund (if applicable)
            $table->decimal('total_contribution', 10, 2)->default(0);

            // For version control (useful if SSS updates rates yearly)
            $table->year('effective_year')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sss_contributions');
    }
};
