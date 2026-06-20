<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent synchronously (not queued) when an admin clicks "Send Test Email" on the
 * Mail Integration settings screen, so the UI can show pass/fail immediately.
 */
class MailIntegrationTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $providerLabel)
    {
    }

    public function build()
    {
        return $this->subject('Test Email - Mail Integration Settings')
            ->html(
                '<p>This is a test email sent from your HRIS Mail Integration settings ('.e($this->providerLabel).').</p>'.
                '<p>If you received this, the connection is configured correctly and ready to use for automated payslip delivery.</p>'
            );
    }
}
