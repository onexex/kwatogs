<?php

namespace App\Helpers;

use App\Models\SssContribution;
use App\Models\PhilhealthContribution;
use App\Models\PagibigContribution;
use App\Models\BirWithholdingTax;
use App\Models\Loan;

class ContributionHelper
{
    /**
     * @param array $duesFlags Per-employee government-dues toggles, e.g.
     *        ['sss' => true, 'philhealth' => false, 'pagibig' => true].
     *        A missing/true flag means the employee IS subject to that due
     *        (default), so existing callers that omit it are unaffected.
     *        A false flag zeroes that contribution (both employee & employer
     *        share) and removes it from the taxable-income deduction.
     */
    public static function computeAll($monthlyGross, $employeeClass, $isEndOfMonth = false, $employeeId = null, array $duesFlags = [], $payDate = null)
    {
        $isTrainee = $employeeClass === 'TRN';
        $zeroShare = ['employee_share' => 0, 'employer_share' => 0, 'total' => 0];

        // ── Government contributions & withholding tax ──────────────────
        // These are MONTHLY dues, so they deduct only on the end-of-month cutoff,
        // and never for trainees (TRN), who aren't enrolled. (Loans/charges are
        // handled separately below and deduct on EVERY cutoff.)
        if ($isEndOfMonth && !$isTrainee) {
            $sss = SssContribution::compute($monthlyGross, $employeeClass);
            $philhealth = PhilhealthContribution::compute($monthlyGross, $employeeClass);
            $pagibig = PagibigContribution::compute($monthlyGross, $employeeClass);

            // Per-employee enrolment toggles: if an employee is NOT subject to a
            // government due, zero it out entirely (employee + employer share).
            // Omitted flag => enrolled.
            if (array_key_exists('sss', $duesFlags) && !$duesFlags['sss']) {
                $sss = $zeroShare;
            }
            if (array_key_exists('philhealth', $duesFlags) && !$duesFlags['philhealth']) {
                $philhealth = $zeroShare;
            }
            if (array_key_exists('pagibig', $duesFlags) && !$duesFlags['pagibig']) {
                $pagibig = $zeroShare;
            }

            $taxableIncome = $monthlyGross
                - ($sss['employee_share'] ?? 0)
                - ($philhealth['employee_share'] ?? 0)
                - ($pagibig['employee_share'] ?? 0);
            $withholdingTax = BirWithholdingTax::compute($taxableIncome, $employeeClass);
        } else {
            $sss = $philhealth = $pagibig = $zeroShare;
            $taxableIncome = 0;
            $withholdingTax = 0;
        }

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

        // Loans/charges deduct on EVERY cutoff (client policy), not just at end of
        // month — each cutoff pays whatever amortization is due against the remaining
        // balance until the loan is settled. Applies to all classifications,
        // trainees included (money owed to the company).
        if ($employeeId) {
            // Get active loans. Finite loans only count while they still have a
            // balance; recurring charges (rent etc.) are picked up regardless of
            // balance and keep deducting each cutoff until switched off.
            // A charge only deducts on/after its start date — a future-dated charge
            // is skipped until its start cutoff (gated only when a pay date is given).
            $loans = Loan::where('employee_id', $employeeId)
                ->where('status', 'active')
                ->where(fn($q) => $q->where('is_recurring', true)->orWhere('balance', '>', 0))
                ->when($payDate, fn($q) => $q->where('start_date', '<=', $payDate))
                ->get();

            foreach ($loans as $loan) {
                if ($loan->is_recurring) {
                    // Continuous monthly charge: deduct the full amount, no balance tracking.
                    $amount = $loan->monthly_amortization;
                    $newBalance = null;
                } else {
                    $amount = min($loan->monthly_amortization, $loan->balance);
                    $newBalance = $loan->balance - $amount;
                }

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
                    'new_balance' => $newBalance,           // null for recurring
                    'is_recurring' => (bool) $loan->is_recurring,
                ];
            }
        }

        // ($taxableIncome and $withholdingTax were computed above with the
        // end-of-month government contributions.)

        return [
            'sss' => $sss ,
            'philhealth' => $philhealth ,
            'pagibig' => $pagibig ,
            'withholding_tax' => $withholdingTax ,
            'loan_deduction' => $loanDeduction ,      // SSS/PhilHealth/Pag-IBIG loans
            'loan_breakdown' => $loanBreakdown,      // categorized
            'loan_details' => $loanDetails,          // for updating balances
            'taxable_income'=>  $taxableIncome,
        ];
    }
}
