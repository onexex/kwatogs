<?php

namespace App\Mail;

use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;

/**
 * Not ShouldQueue — queuing for payslip sends happens one level up, at
 * App\Jobs\SendPayslipEmailJob, which is what actually gets dispatched to
 * the queue. This class just describes the message itself.
 */
class PayslipMailable extends Mailable
{
    public function __construct(
        public Payroll $payroll,
        public string $pdfBinary,
        public string $passwordHint
    ) {
    }

    public function build()
    {
        $employee     = $this->payroll->employee;
        $employeeName = trim(($employee->fname ?? '').' '.($employee->lname ?? ''));
        $payDate      = Carbon::parse($this->payroll->pay_date)->format('M d, Y');
        $filename     = 'Payslip_'.$this->payroll->employee_id.'_'.Carbon::parse($this->payroll->pay_date)->format('Ymd').'.pdf';

        return $this->subject('Your Payslip - '.$payDate)
            ->html(
                '<p>Hi '.e($employeeName ?: 'there').',</p>'.
                '<p>Your payslip for <strong>'.e($payDate).'</strong> is attached as a password-protected PDF.</p>'.
                '<p>To open it, use '.e($this->passwordHint).'.</p>'.
                '<p>This is an automated message. If you have questions about this payslip, please contact HR.</p>'
            )
            ->attachData($this->pdfBinary, $filename, ['mime' => 'application/pdf']);
    }
}
