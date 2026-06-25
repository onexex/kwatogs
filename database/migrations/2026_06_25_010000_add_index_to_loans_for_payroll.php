<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // The payroll engine looks up each employee's active loans once per run
            // (ContributionHelper::computeAll). Indexing employee_id + status turns
            // that per-employee scan into a seek as the table grows over the years.
            $table->index(['employee_id', 'status'], 'loans_employee_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex('loans_employee_status_idx');
        });
    }
};
