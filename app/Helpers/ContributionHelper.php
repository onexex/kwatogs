<?php

namespace App\Helpers;

use App\Models\SSSContribution;
use App\Models\PhilhealthContribution;
use App\Models\PagibigContribution;
use App\Models\BirWithholdingTax;
use App\Models\Loan;

class ContributionHelper
{
    public static function computeAll($monthlyGross, $employeeClass, $isEndOfMonth = false, $employeeId = null)
    {
        // If NOT end of month or employee is trainee → no deductions
        if (!$isEndOfMonth || $employeeClass === 'TRN') {
            return [
                'sss' => ['employee_share' => 0, 'employer_share' => 0, 'total' => 0],
                'philhealth' => ['employee_share' => 0, 'employer_share' => 0, 'total' => 0],
                'pagibig' => ['employee_share' => 0, 'employer_share' => 0, 'total' => 0],
                'withholding_tax' => 0,
                'loan_deduction' => 0,
                'loan_breakdown' => [
                    'pagibig' => 0,
                    'sss' => 0,
                    'philhealth' => 0,
                    'salary' => 0,
                ],
                'loan_details' => [],
            ];
        }

        // Compute contributions
        $sss = SSSContribution::compute($monthlyGross, $employeeClass);
        $philhealth = PhilhealthContribution::compute($monthlyGross, $employeeClass);
        $pagibig = PagibigContribution::compute($monthlyGross, $employeeClass);

        // Default loan values
        $loanDeduction = 0;
        $loanBreakdown = [
            'pagibig' => 0,
            'sss' => 0,
            'philhealth' => 0,
            'salary' => 0,
            'other' => 0,
            'charges/penalty' => 0,
            'cash_adv' => 0,
        ];
        $loanDetails = [];
        $loans = [];

        if ($isEndOfMonth && $employeeClass !== 'TRN' && $employeeId) {
            // Get active loans
            $loans = Loan::where('employee_id', $employeeId)
                ->where('status', 'active')
                ->where('balance', '>', 0)
                ->get();

            foreach ($loans as $loan) {
                $amount = min($loan->monthly_amortization, $loan->balance);

                // Categorize loans
                switch ($loan->loan_type) {
                    // ✅ Deducted immediately (gov-type loans)
                    case 'pagibig':
                    case 'sss':
                    case 'philhealth':
                        $loanDeduction += $amount;
                        $loanBreakdown[$loan->loan_type] += $amount;
                        break;

                    // ✅ Deducted later from net pay
                    case 'salary':
                    case 'other':
                    case 'charges/penalty':
                    case 'cash_adv':
                        $loanBreakdown[$loan->loan_type] += $amount;
                        break;
                }

                // Store for balance update
                $loanDetails[] = [
                    'loan_id' => $loan->id,
                    'deducted_amount' => $amount,
                    'new_balance' => $loan->balance - $amount,
                ];
            }
        }

        // Compute taxable income (include contributions + gov loans only)
        $loanDeductibleForTax = 0;
        foreach ($loans as $loan) {
            if (in_array($loan->loan_type, ['pagibig', 'sss', 'philhealth'])) {
                $loanDeductibleForTax += min($loan->monthly_amortization, $loan->balance);
            }
        }


        $taxableIncome = $monthlyGross
            - ($sss['employee_share'] ?? 0)
            - ($philhealth['employee_share'] ?? 0)
            - ($pagibig['employee_share'] ?? 0)
            - $loanDeductibleForTax;

        // Compute withholding tax
        $withholdingTax = BirWithholdingTax::compute($taxableIncome, $employeeClass);

        return [
            'sss' => $sss,
            'philhealth' => $philhealth,
            'pagibig' => $pagibig,
            'withholding_tax' => $withholdingTax,
            'loan_deduction' => $loanDeduction,      // SSS/PhilHealth/Pag-IBIG loans
            'loan_breakdown' => $loanBreakdown,      // categorized
            'loan_details' => $loanDetails,          // for updating balances
            'taxable_income'=>  $taxableIncome,
        ];
    }
}
