<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagibigContribution extends Model
{
    use HasFactory;

    protected $table = 'pagibig_contributions';

    protected $fillable = [
        'range_from',
        'range_to',
        'employee_rate',
        'employer_rate',
        'employee_share',
        'employer_share',
        'total_contribution',
        'max_salary_credit',
        'effective_year',
    ];

    /**
     * 🔍 Scope to find the record applicable to a salary range.
     */
    public function scopeForSalary($query, $salary)
    {
        return $query->where('range_from', '<=', $salary)
                     ->where('range_to', '>=', $salary);
    }

     public static function compute($salary, $employeeClass = null)
    {
        // ❗ No Pag-IBIG deduction for "TRN" employees
        if ($employeeClass === 'TRN') {
            return [
                'employee_share' => 0,
                'employer_share' => 0,
                'total' => 0,
            ];
        }

        // Get the latest applicable contribution table row
        $record = self::forSalary($salary)
            ->orderByDesc('effective_year')
            ->first();

        // If not found, fallback to standard rule
        if (!$record) {
            $max_salary = 5000;    // Government-mandated max salary credit
            $employee_rate = 0.02; // 2%
            $employer_rate = 0.02; // 2%

            $base = min($salary, $max_salary);

            $employee = $base * $employee_rate;
            $employer = $base * $employer_rate;

            return [
                'employee_share' => $employee,
                'employer_share' => $employer,
                'total' => $employee + $employer,
            ];
        }

        // Compute using record values
        $base = min($salary, $record->max_salary_credit);

        $employee = $base * ($record->employee_rate / 100);
        $employer = $base * ($record->employer_rate / 100);
        $total = $employee + $employer;

        return [
            'employee_share' => $employee,
            'employer_share' => $employer,
            'total' => $total,
        ];
    }

}
