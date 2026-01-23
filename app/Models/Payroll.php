<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'payrolls';

    protected $fillable = [
        'employee_id',
        'payroll_start_date',
        'payroll_end_date',
        'pay_date',
        'basic_salary',
        'allowances',
        'overtime_pay',
        'holiday_pay',
        'other_earnings',
        'late_minutes',
        'undertime_minutes',
        'late_deduction',
        'undertime_deduction',
        'night_diff_hours',
        'night_diff_pay',
        'penalty_amount',
        'sss_contribution',
        'philhealth_contribution',
        'pagibig_contribution',
        'sss_loan',
        'pagibig_loan',
        'company_loan',
        'withholding_tax',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'status',
         'sss_employer',
        'philhealth_employer',
        'pagibig_employer',
        'basicPay',
        'taxable_income'

    ];

    protected $casts = [
        'payroll_start_date' => 'date',
        'payroll_end_date' => 'date',
        'pay_date' => 'date',
    ];

    public static function getPreviousGrossIfEndOfMonth($employeeId, $currentEndDate, $employeeClass = null)
    {
        $endDate = Carbon::parse($currentEndDate);

        if (!$endDate->isLastOfMonth() || $employeeClass === 'TRN') {
            return 0;
        }

        $previous = self::where('employee_id', $employeeId)
            ->where('payroll_end_date', '<', $endDate)
            ->orderByDesc('payroll_end_date')
            ->first();

        return $previous->gross_pay ?? 0;
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id','empID');
    }
}
