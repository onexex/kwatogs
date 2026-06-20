<?php

namespace App\Jobs;

use App\Mail\PayslipMailable;
use App\Models\Payroll;
use App\Models\PayslipEmailLog;
use App\Models\PayslipEmailSetting;
use App\Services\DynamicMailManager;
use App\Services\PayslipPasswordResolver;
use App\Services\PayslipPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

/**
 * Generates and sends one employee's payslip PDF. Dispatched once per
 * employee from PayslipEmailController so a failure for one person (bad
 * email, provider hiccup) never blocks the rest of the batch.
 *
 * Under the default QUEUE_CONNECTION=sync this just runs immediately
 * in the same request; switch to a database/redis queue + a worker
 * process for large batches so the HTTP request doesn't have to wait
 * for every email to send one at a time.
 */
class SendPayslipEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public int $payrollId,
        public ?string $triggeredBy = null
    ) {
    }

    public function handle(
        DynamicMailManager $mailManager,
        PayslipPdfService $pdfService,
        PayslipPasswordResolver $passwordResolver
    ): void {
        $payroll = Payroll::with([
            'employee.empDetail.department',
            'employee.empDetail.position',
            'employee.empDetail.company',
            'employee.employeeInformation',
        ])->find($this->payrollId);

        if (!$payroll) {
            return; // payroll row no longer exists, nothing to send
        }

        $log = PayslipEmailLog::create([
            'payroll_id'  => $payroll->id,
            'employee_id' => $payroll->employee_id,
            'pay_date'    => $payroll->pay_date,
            'email_to'    => optional($payroll->employee)->email,
            'status'      => 'queued',
            'sent_by'     => $this->triggeredBy ?: 'system',
        ]);

        try {
            $email = optional($payroll->employee)->email;

            if (empty($email)) {
                throw new RuntimeException('Employee has no email address on file.');
            }

            $mailer = $mailManager->mailer();

            if (!$mailer) {
                throw new RuntimeException('No active mail integration is configured. Set one up in Settings -> Mail Integration first.');
            }

            $setting      = PayslipEmailSetting::current();
            $passwordInfo = $passwordResolver->resolve($payroll, $setting->password_source);
            $pdf          = $pdfService->generate($payroll, $passwordInfo['password']);

            $mailer->to($email)->send(new PayslipMailable($payroll, $pdf, $passwordInfo['hint']));

            $activeIntegration = $mailManager->getActiveSetting();

            $log->update([
                'status'                      => 'sent',
                'email_to'                    => $email,
                'mail_integration_setting_id' => optional($activeIntegration)->id,
                'sent_at'                     => now(),
            ]);
        } catch (Throwable $e) {
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Re-throw so a real queue worker still applies retry/backoff.
            // Under QUEUE_CONNECTION=sync this surfaces immediately to the caller.
            throw $e;
        }
    }
}
