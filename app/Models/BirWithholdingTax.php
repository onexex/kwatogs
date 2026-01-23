<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BirWithholdingTax extends Model
{
    use HasFactory;

    protected $fillable = [
        'compensation_from',
        'compensation_to',
        'fixed_tax',
        'excess_over',
        'percent_over',
        'effective_year',
    ];

    // public static function compute($taxableIncome, $employeeClass = null)
    // {
    //     // ❗ Skip withholding tax if employee is Trainee
    //     if ($employeeClass === 'TRN') {
    //         return 0;
    //     }

    //     // Find the correct tax bracket
    //     $record = self::where('compensation_from', '<=', $taxableIncome)
    //         ->where('compensation_to', '>=', $taxableIncome)
    //         ->orderByDesc('effective_year')
    //         ->first();

    //     // If no bracket, no withholding tax applies
    //     if (!$record) {
    //         return 0;
    //     }

    //     // TRAIN tax computation:
    //     $tax = $record->fixed_tax 
    //         + (($taxableIncome - $record->excess_over) * ($record->percent_over / 100));

    //     // Ensure not negative and safe
    //     return max($tax, 0);
    // }

    public static function compute($taxableIncome, $employeeClass = null)
    {
        // ❗ Skip withholding tax if employee is Trainee / Non-taxable class
        if (strtoupper($employeeClass) === 'TRN') {
            return 0;
        }

        // Ensure numeric values and no negatives
        $taxableIncome = max(($taxableIncome ?? 0), 0);

        // Find matching TRAIN bracket for this taxable income
        $record = self::where('compensation_from', '<=', $taxableIncome)
            ->where('compensation_to', '>=', $taxableIncome)
            ->orderByDesc('effective_year')
            ->first();

        // If no matching bracket → no withholding tax
        if (!$record) {
            return 0;
        }

        // TRAIN Law Withholding computation:
        $tax = $record->fixed_tax
            + max(($taxableIncome - $record->excess_over), 0) * ($record->percent_over / 100);

        // ✅ Ensure result is non-negative and rounded
        return round(max($tax, 0), 2);
    }


}
