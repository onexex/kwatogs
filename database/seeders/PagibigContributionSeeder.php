<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PagibigContribution;

class PagibigContributionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1️⃣ Tier 1: ₱1,500 and below
        PagibigContribution::create([
            'range_from' => 0,
            'range_to' => 1500,
            'employee_rate' => 1.00,
            'employer_rate' => 2.00,
            'employee_share' => 0,
            'employer_share' => 0,
            'total_contribution' => 0,
            'max_salary_credit' => 5000,
            'effective_year' => 2025,
        ]);

        // 2️⃣ Tier 2: Over ₱1,500
        PagibigContribution::create([
            'range_from' => 1500.01,
            'range_to' => 999999,
            'employee_rate' => 2.00,
            'employer_rate' => 2.00,
            'employee_share' => 0,
            'employer_share' => 0,
            'total_contribution' => 0,
            'max_salary_credit' => 5000,
            'effective_year' => 2025,
        ]);
    }
}
