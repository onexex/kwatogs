<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SssContribution extends Model
{
    use HasFactory;

    protected $table = 'sss_contributions';

    protected $fillable = [
        'range_from',
        'range_to',
        'employee_share',
        'employer_share',
        'ec',
        'mpf',
        'total_contribution',
        'effective_year',
    ];

    /**
     * 🔍 Scope to get SSS contribution based on a given salary.
     */
    public function scopeForSalary($query, $salary)
    {
        return $query->where('range_from', '<=', $salary)
                     ->where('range_to', '>=', $salary);
    }

    /**
     * 🧮 Helper method to compute employee share based on salary.
     */
    public static function compute($salary, $employeeClass = null)
    {
        // If employee class is TR, return 0 contributions
        if ($employeeClass === 'TRN') {
            return [
                'employee_share' => 0,
                'employer_share' => 0,
                'ec' => 0,
                'mpf' => 0,
                'total' => 0,
            ];
        }

        // Otherwise, compute based on salary range
        $record = self::forSalary($salary)
            ->orderByDesc('effective_year')
            ->first();

        return [
            'employee_share' => $record->employee_share ?? 0,
            'employer_share' => $record->employer_share ?? 0,
            'ec' => $record->ec ?? 0,
            'mpf' => $record->mpf ?? 0,
            'total' => $record->total_contribution ?? 0,
        ];
    }

}
