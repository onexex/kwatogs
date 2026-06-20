<?php

namespace App\Services;

use App\Models\Payroll;
use Carbon\Carbon;

/**
 * Derives the password used to lock an employee's payslip PDF, based on
 * whichever source is configured in Settings -> Mail Integration ->
 * Payslip Email Settings (see PayslipEmailSetting).
 */
class PayslipPasswordResolver
{
    /**
     * @return array{password: ?string, source_used: string, hint: string}
     *               password is null only when the setting is 'none' or
     *               when 'birthdate' was requested but the employee has no
     *               birthdate on file (we fall back to employee_id so the
     *               PDF is still protected, and report that in source_used).
     */
    public function resolve(Payroll $payroll, string $configuredSource): array
    {
        $employeeId = $payroll->employee_id;

        if ($configuredSource === 'none') {
            return ['password' => null, 'source_used' => 'none', 'hint' => 'This payslip is not password protected.'];
        }

        if ($configuredSource === 'birthdate') {
            $birthdate = optional(optional($payroll->employee)->employeeInformation)->empBdate;

            if (!empty($birthdate)) {
                $password = Carbon::parse($birthdate)->format('dmY');

                return [
                    'password'    => $password,
                    'source_used' => 'birthdate',
                    'hint'        => 'your birthdate in DDMMYYYY format (e.g. 5 Jan 1994 -> 05011994)',
                ];
            }

            // No birthdate on file — fall back so the PDF is still protected.
            return [
                'password'    => (string) $employeeId,
                'source_used' => 'employee_id_fallback',
                'hint'        => 'your employee ID number',
            ];
        }

        // 'employee_id' (or any unrecognized value defaults here defensively)
        return [
            'password'    => (string) $employeeId,
            'source_used' => 'employee_id',
            'hint'        => 'your employee ID number',
        ];
    }
}
