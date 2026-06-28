<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SssContribution extends Model
{
    use HasFactory;
    use \App\Traits\Auditable;

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
     * ⚡ PERFORMANCE: payroll generation calls compute() once per employee.
     * Load the (small, static) rate table ONCE per request and resolve the
     * bracket in PHP, instead of one DB query per employee. Cache is per-request
     * so rate-table edits are picked up on the next payroll run.
     */
    protected static $bracketCache = null;

    protected static function brackets()
    {
        if (static::$bracketCache === null) {
            static::$bracketCache = static::orderByDesc('effective_year')->get();
        }
        return static::$bracketCache;
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
        $record = self::brackets()
            ->first(fn ($r) => $r->range_from <= $salary && $r->range_to >= $salary);

        return [
            'employee_share' => $record->employee_share ?? 0,
            'employer_share' => $record->employer_share ?? 0,
            'ec' => $record->ec ?? 0,
            'mpf' => $record->mpf ?? 0,
            'total' => $record->total_contribution ?? 0,
        ];
    }

}
