<?php

namespace App\Services;

use App\Mail\MailIntegrationTestMail;
use App\Models\MailIntegrationSetting;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

/**
 * Resolves and configures a Laravel mailer at runtime from whichever
 * App\Models\MailIntegrationSetting row is marked active in the database,
 * instead of relying on a single fixed driver in config/mail.php / .env.
 *
 * This is what makes the payslip-email feature "modular": the job that
 * actually sends a payslip only ever calls DynamicMailManager::mailer()
 * and never needs to know whether it's going out via SMTP, Mailgun, SES,
 * or Postmark.
 */
class DynamicMailManager
{
    /**
     * The single mail integration currently in use, if any has been activated.
     */
    public function getActiveSetting(): ?MailIntegrationSetting
    {
        return MailIntegrationSetting::where('is_active', true)->first();
    }

    /**
     * Push the given setting's credentials into Laravel's runtime config
     * under a throwaway 'dynamic' mailer name, so Mail::mailer('dynamic')
     * picks it up for this request/job only.
     */
    public function configure(MailIntegrationSetting $setting): void
    {
        $config = $setting->config ?? [];

        $mailerConfig = match ($setting->provider) {
            'smtp' => [
                'transport'  => 'smtp',
                'host'       => $config['host'] ?? null,
                'port'       => $config['port'] ?? 587,
                'encryption' => ($config['encryption'] ?? '') !== '' ? $config['encryption'] : null,
                'username'   => $config['username'] ?? null,
                'password'   => $config['password'] ?? null,
                'timeout'    => null,
            ],
            'mailgun'  => ['transport' => 'mailgun'],
            'ses'      => ['transport' => 'ses'],
            'postmark' => ['transport' => 'postmark'],
            default    => throw new RuntimeException("Unsupported mail provider [{$setting->provider}]."),
        };

        config(['mail.mailers.dynamic' => $mailerConfig]);
        config(['mail.from' => [
            'address' => $setting->from_address,
            'name'    => $setting->from_name,
        ]]);

        // API-based drivers in Laravel read their credentials from config('services.*')
        // rather than the mailer array itself.
        match ($setting->provider) {
            'mailgun' => config(['services.mailgun' => [
                'domain'   => $config['domain'] ?? null,
                'secret'   => $config['secret'] ?? null,
                'endpoint' => $config['endpoint'] ?? 'api.mailgun.net',
                'scheme'   => 'https',
            ]]),
            'ses' => config(['services.ses' => [
                'key'    => $config['key'] ?? null,
                'secret' => $config['secret'] ?? null,
                'region' => $config['region'] ?? 'us-east-1',
            ]]),
            'postmark' => config(['services.postmark' => [
                'token' => $config['token'] ?? null,
            ]]),
            default => null,
        };
    }

    /**
     * Returns a ready-to-use mailer for the active provider, or null if no
     * mail integration has been configured/activated yet.
     */
    public function mailer()
    {
        $setting = $this->getActiveSetting();

        if (!$setting) {
            return null;
        }

        $this->configure($setting);

        return Mail::mailer('dynamic');
    }

    /**
     * Sends a one-off test email using the given (not necessarily active) setting,
     * and records the outcome back onto the row for the settings UI to display.
     */
    public function sendTest(MailIntegrationSetting $setting, string $toEmail): array
    {
        $this->configure($setting);

        try {
            Mail::mailer('dynamic')
                ->to($toEmail)
                ->send(new MailIntegrationTestMail($setting->providerLabel()));

            $setting->forceFill([
                'last_tested_at'    => now(),
                'last_test_status'  => 'success',
                'last_test_message' => 'Test email sent successfully to '.$toEmail.'.',
            ])->save();

            return ['success' => true, 'message' => 'Test email sent successfully to '.$toEmail.'.'];
        } catch (Throwable $e) {
            $setting->forceFill([
                'last_tested_at'    => now(),
                'last_test_status'  => 'failed',
                'last_test_message' => $e->getMessage(),
            ])->save();

            return ['success' => false, 'message' => 'Test email failed: '.$e->getMessage()];
        }
    }
}
