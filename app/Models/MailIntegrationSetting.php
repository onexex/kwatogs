<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores admin-configured credentials for whichever email provider the
 * client company wants to use to send payslips (and, later, other
 * system email). Designed so adding a new provider is a registry entry,
 * not a rewrite — see PROVIDERS below and App\Services\DynamicMailManager.
 */
class MailIntegrationSetting extends Model
{
    use \App\Traits\Auditable;

    protected $table = 'mail_integration_settings';

    /**
     * 'config' holds the raw provider credentials (passwords/API keys included).
     * It is intentionally excluded from the audit trail diff — only non-sensitive
     * fields (provider, label, is_active, test status) get audited automatically.
     */
    protected static function auditIgnore(): array
    {
        return ['updated_at', 'created_at', 'remember_token', 'password', 'config'];
    }

    protected $fillable = [
        'provider',
        'label',
        'from_address',
        'from_name',
        'config',
        'is_active',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'config'         => 'encrypted:array', // encrypted at rest using APP_KEY
        'is_active'      => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    /**
     * Field definitions per provider. Drives both the dynamic settings form
     * and which keys DynamicMailManager treats as secret (masked in the UI,
     * never written to the audit trail).
     */
    public const PROVIDERS = [
        'smtp' => [
            'label'  => 'SMTP (Brevo, Gmail Workspace, Office 365, generic)',
            'fields' => [
                'host'       => ['label' => 'SMTP Host', 'type' => 'text', 'required' => true],
                'port'       => ['label' => 'Port', 'type' => 'number', 'required' => true, 'default' => 587],
                'encryption' => ['label' => 'Encryption', 'type' => 'select', 'options' => ['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None'], 'default' => 'tls'],
                'username'   => ['label' => 'Username', 'type' => 'text', 'required' => true],
                'password'   => ['label' => 'Password / SMTP Key', 'type' => 'password', 'required' => true, 'secret' => true],
            ],
        ],
        'mailgun' => [
            'label'  => 'Mailgun',
            'fields' => [
                'domain'   => ['label' => 'Domain', 'type' => 'text', 'required' => true],
                'secret'   => ['label' => 'Private API Key', 'type' => 'password', 'required' => true, 'secret' => true],
                'endpoint' => ['label' => 'API Endpoint', 'type' => 'text', 'required' => false, 'default' => 'api.mailgun.net'],
            ],
        ],
        'ses' => [
            'label'  => 'Amazon SES',
            'note'   => 'Requires the AWS SDK. Run "composer require aws/aws-sdk-php" before activating this — it\'s left out by default since it\'s a large dependency most companies running SMTP/Brevo don\'t need.',
            'fields' => [
                'key'    => ['label' => 'AWS Access Key ID', 'type' => 'text', 'required' => true],
                'secret' => ['label' => 'AWS Secret Access Key', 'type' => 'password', 'required' => true, 'secret' => true],
                'region' => ['label' => 'AWS Region', 'type' => 'text', 'required' => true, 'default' => 'us-east-1'],
            ],
        ],
        'postmark' => [
            'label'  => 'Postmark',
            'fields' => [
                'token' => ['label' => 'Server API Token', 'type' => 'password', 'required' => true, 'secret' => true],
            ],
        ],
    ];

    /**
     * Returns the config array with secret values masked, safe to send to the browser.
     */
    public function maskedConfig(): array
    {
        $definition = self::PROVIDERS[$this->provider]['fields'] ?? [];
        $config     = $this->config ?? [];
        $masked     = [];

        foreach ($config as $key => $value) {
            $isSecret      = $definition[$key]['secret'] ?? false;
            $masked[$key]  = ($isSecret && $value !== null && $value !== '') ? '••••••••' : $value;
        }

        return $masked;
    }

    public function providerLabel(): string
    {
        return self::PROVIDERS[$this->provider]['label'] ?? $this->provider;
    }
}
