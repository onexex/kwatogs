<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SssContribution;

/**
 * Official SSS Contribution Table — effective 2026 (RA 11199 final step).
 *
 *  • Total rate 15%  → Employer 10%, Employee 5%
 *  • MSC range ₱5,000 – ₱35,000
 *  • Regular SS contribution applies on MSC up to ₱20,000;
 *    the portion above ₱20,000 goes to the Mandatory Provident Fund (MPF/WISP).
 *  • EC (Employees' Compensation, employer-paid): ₱10 for MSC below ₱15,000,
 *    ₱30 for MSC ₱15,000 and above.
 *
 * Stored amounts (per the sss_contributions schema):
 *  • employee_share     = employee Regular SS + employee MPF (this is what payroll deducts)
 *  • employer_share     = employer Regular SS + employer MPF  (excludes EC)
 *  • ec                 = Employees' Compensation (employer-paid)
 *  • mpf                = total MPF (employer + employee)
 *  • total_contribution = employee_share + employer_share + ec  (grand total)
 *
 * The values were validated against the published 2026 SSS table
 * (e.g. MSC 16,500 → employee share ₱825; MSC 35,000 → employee share ₱1,750).
 */
class SssContributionSeeder extends Seeder
{
    public function run(): void
    {
        $year = 2026;

        // Idempotent: clear any existing rows for this year before inserting.
        SssContribution::where('effective_year', $year)->delete();

        // MSC steps: ₱5,000 → ₱35,000 in ₱500 increments.
        $mscList = array_merge(range(5000, 20000, 500), range(20500, 35000, 500));

        foreach ($mscList as $msc) {
            // Compensation range that maps to this MSC.
            if ($msc === 5000) {
                $from = 0;        $to = 5249.99;
            } elseif ($msc === 35000) {
                $from = 34750;    $to = 999999.99;
            } else {
                $from = $msc - 250; $to = $msc + 249.99;
            }

            if ($msc <= 20000) {
                // Pure Regular SS, no provident fund yet.
                $employerRegular = 0.10 * $msc;
                $employerMpf     = 0;
                $employeeRegular = 0.05 * $msc;
                $employeeMpf     = 0;
            } else {
                // Regular SS capped at MSC 20,000; the excess funds the MPF.
                $mpfBase         = $msc - 20000;
                $employerRegular = 2000;
                $employerMpf     = 0.10 * $mpfBase;
                $employeeRegular = 1000;
                $employeeMpf     = 0.05 * $mpfBase;
            }

            $ec = $msc < 15000 ? 10 : 30;

            $employeeShare = $employeeRegular + $employeeMpf;
            $employerShare = $employerRegular + $employerMpf;
            $mpf           = $employerMpf + $employeeMpf;
            $total         = $employeeShare + $employerShare + $ec;

            SssContribution::create([
                'range_from'         => $from,
                'range_to'           => $to,
                'employee_share'     => round($employeeShare, 2),
                'employer_share'     => round($employerShare, 2),
                'ec'                 => $ec,
                'mpf'                => round($mpf, 2),
                'total_contribution' => round($total, 2),
                'effective_year'     => $year,
            ]);
        }
    }
}
