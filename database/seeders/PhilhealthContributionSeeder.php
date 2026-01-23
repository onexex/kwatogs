<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PhilhealthContribution;

class PhilhealthContributionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // PhilHealth Contribution Table 2025
        // Premium Rate: 5%
        // Employee Share: 2.5%
        // Employer Share: 2.5%

        // 1️⃣ Tier 1 — ₱10,000 and below (Minimum Premium ₱500)
        PhilhealthContribution::create([
            'range_from' => 0,
            'range_to' => 10000,
            'premium_rate' => 5.00,
            'employee_share' => 2.50,
            'employer_share' => 2.50,
            'min_salary' => 10000,
            'max_salary' => 10000,
            'effective_year' => 2025,
        ]);

        // 2️⃣ Tier 2 — ₱10,000.01 to ₱99,999.99 (Pro-rated 5%)
        PhilhealthContribution::create([
            'range_from' => 10000.01,
            'range_to' => 99999.99,
            'premium_rate' => 5.00,
            'employee_share' => 2.50,
            'employer_share' => 2.50,
            'min_salary' => 10000,
            'max_salary' => 100000,
            'effective_year' => 2025,
        ]);

        // 3️⃣ Tier 3 — ₱100,000 and above (Capped at ₱5,000)
        PhilhealthContribution::create([
            'range_from' => 100000,
            'range_to' => 999999,
            'premium_rate' => 5.00,
            'employee_share' => 2.50,
            'employer_share' => 2.50,
            'min_salary' => 100000,
            'max_salary' => 100000,
            'effective_year' => 2025,
        ]);
    }
}
