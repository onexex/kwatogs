<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhilhealthContribution extends Model
{
    use HasFactory;
    use \App\Traits\Auditable;

    protected $table = 'philhealth_contributions';

    protected $fillable = [
        'range_from',
        'range_to',
        'premium_rate',
        'employee_share',
        'employer_share',
        'min_salary',
        'max_salary',
        'effective_year',
    ];

    /**
     * 🔍 Get the applicable record for the given salary.
     */
    public function scopeForSalary($query, $salary)
    {
        return $query->where('range_from', '<=', $salary)
                     ->where('range_to', '>=', $salary);
    }

    /**
     * ⚡ PERFORMANCE: load the (small, static) rate table ONCE per request and
     * resolve the bracket in PHP, instead of one DB query per employee during
     * payroll generation. Cache is per-request so edits apply on the next run.
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
     * 🧮 Compute PhilHealth contribution based on salary.
     */
    public static function compute($salary, $employeeClass = null)
    {
        // ❗ If class is TRN → no PhilHealth contribution
        if ($employeeClass === 'TRN') {
            return [
                'employee_share' => 0,
                'employer_share' => 0,
                'total' => 0,
            ];
        }

        $record = self::brackets()
            ->first(fn ($r) => $r->range_from <= $salary && $r->range_to >= $salary);

        if (!$record) {
            // Default PhilHealth rule if no table match
            $min = 10000;
            $max = 90000;
            $rate = 5 / 100;
            $salary = min(max($salary, $min), $max);
            $total = $salary * $rate;

            return [
                'employee_share' => $total / 2,
                'employer_share' => $total / 2,
                'total' => $total,
            ];
        }

        $base_salary = min(max($salary, $record->min_salary), $record->max_salary);
        $total = $base_salary * ($record->premium_rate / 100);
        $employee = $total * ($record->employee_share / $record->premium_rate);
        $employer = $total * ($record->employer_share / $record->premium_rate);

        return [
            'employee_share' => $employee,
            'employer_share' => $employer,
            'total' => $total,
        ];
    }

}
