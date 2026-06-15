<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pag-IBIG (HDMF) raised the Maximum Fund Salary from ₱5,000 to ₱10,000
 * (effective 2024), which raises the maximum employee/employer share from
 * ₱100 to ₱200 (2% × ₱10,000).
 *
 * This corrects existing rows in pagibig_contributions on deployment.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('pagibig_contributions')
            ->where('max_salary_credit', 5000)
            ->update([
                'max_salary_credit' => 10000,
                'effective_year'    => 2026,
            ]);
    }

    public function down(): void
    {
        DB::table('pagibig_contributions')
            ->where('max_salary_credit', 10000)
            ->update([
                'max_salary_credit' => 5000,
                'effective_year'    => 2025,
            ]);
    }
};
