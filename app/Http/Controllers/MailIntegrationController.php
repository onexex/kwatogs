<?php

namespace App\Http\Controllers;

use App\Models\MailIntegrationSetting;
use App\Services\DynamicMailManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MailIntegrationController extends Controller
{
    /**
     * Settings screen: list configured providers + the add/edit form.
     */
    public function index()
    {
        $settings = MailIntegrationSetting::orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->get();

        return view('pages.management.mailintegration', [
            'settings'  => $settings,
            'providers' => MailIntegrationSetting::PROVIDERS,
        ]);
    }

    /**
     * Save a new provider configuration. Never activated automatically —
     * it must pass a test send first (enforced in activate()).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider'     => 'required|string|in:'.implode(',', array_keys(MailIntegrationSetting::PROVIDERS)),
            'label'        => 'nullable|string|max:120',
            'from_address' => 'required|email',
            'from_name'    => 'required|string|max:120',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $provider = $request->input('provider');
        $config   = $this->extractProviderConfig($request, $provider);

        $missing = $this->missingRequiredFields($provider, $config);
        if (!empty($missing)) {
            return back()->withErrors(['config' => 'Missing required field(s): '.implode(', ', $missing)])->withInput();
        }

        $actor = $this->actorName($request);

        MailIntegrationSetting::create([
            'provider'     => $provider,
            'label'        => $request->input('label'),
            'from_address' => $request->input('from_address'),
            'from_name'    => $request->input('from_name'),
            'config'       => $config,
            'is_active'    => false,
            'created_by'   => $actor,
            'updated_by'   => $actor,
        ]);

        return redirect()->route('mail-integration.index')
            ->with('success', 'Mail integration saved. Send a test email before activating it.');
    }

    /**
     * Update an existing provider configuration. Blank secret fields are
     * left untouched so editing the From name doesn't force re-entering
     * an API key/password every time.
     */
    public function update(Request $request, MailIntegrationSetting $mailIntegration)
    {
        $validator = Validator::make($request->all(), [
            'label'        => 'nullable|string|max:120',
            'from_address' => 'required|email',
            'from_name'    => 'required|string|max:120',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $incoming = $this->extractProviderConfig($request, $mailIntegration->provider);
        $config   = $mailIntegration->config ?? [];

        foreach ($incoming as $key => $value) {
            if ($value !== null && $value !== '') {
                $config[$key] = $value;
            }
        }

        $missing = $this->missingRequiredFields($mailIntegration->provider, $config);
        if (!empty($missing)) {
            return back()->withErrors(['config' => 'Missing required field(s): '.implode(', ', $missing)])->withInput();
        }

        $mailIntegration->update([
            'label'        => $request->input('label'),
            'from_address' => $request->input('from_address'),
            'from_name'    => $request->input('from_name'),
            'config'       => $config,
            'updated_by'   => $this->actorName($request),
            // Credentials changed underneath it — require a fresh passing test before reuse.
            'last_test_status'  => null,
            'last_test_message' => null,
        ]);

        return redirect()->route('mail-integration.index')
            ->with('success', 'Mail integration updated. Send a test email to confirm it still works before relying on it.');
    }

    /**
     * Send a one-off test email through this provider's saved credentials.
     */
    public function test(Request $request, MailIntegrationSetting $mailIntegration, DynamicMailManager $mailManager)
    {
        $validator = Validator::make($request->all(), [
            'test_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $result = $mailManager->sendTest($mailIntegration, $request->input('test_email'));

        return redirect()->route('mail-integration.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Make this the provider used for all future sends. Only one integration
     * can be active at a time, and only after it has a passing test on record.
     */
    public function activate(Request $request, MailIntegrationSetting $mailIntegration)
    {
        if ($mailIntegration->last_test_status !== 'success') {
            return redirect()->route('mail-integration.index')
                ->with('error', 'Send a successful test email before activating this integration.');
        }

        MailIntegrationSetting::where('is_active', true)->update(['is_active' => false]);

        $mailIntegration->update([
            'is_active'  => true,
            'updated_by' => $this->actorName($request),
        ]);

        return redirect()->route('mail-integration.index')
            ->with('success', ($mailIntegration->label ?: $mailIntegration->providerLabel()).' is now the active mail provider.');
    }

    public function destroy(MailIntegrationSetting $mailIntegration)
    {
        if ($mailIntegration->is_active) {
            return redirect()->route('mail-integration.index')
                ->with('error', 'Cannot delete the active mail integration. Activate another one first.');
        }

        $mailIntegration->delete();

        return redirect()->route('mail-integration.index')->with('success', 'Mail integration deleted.');
    }

    /**
     * Form fields are namespaced per provider as config[{provider}][{field}]
     * (see the Blade view) precisely so that, e.g., Mailgun's and SES's
     * "secret" fields can never collide or leak into each other when both
     * fieldsets are present in the same <form>. We also only ever read keys
     * that exist in MailIntegrationSetting::PROVIDERS, so arbitrary extra
     * POST fields can't sneak into the stored config.
     */
    private function extractProviderConfig(Request $request, string $provider): array
    {
        $fields = MailIntegrationSetting::PROVIDERS[$provider]['fields'] ?? [];
        $input  = (array) $request->input('config.'.$provider, []);
        $config = [];

        foreach ($fields as $key => $definition) {
            if (array_key_exists($key, $input)) {
                $config[$key] = $input[$key];
            }
        }

        return $config;
    }

    private function missingRequiredFields(string $provider, array $config): array
    {
        $fields  = MailIntegrationSetting::PROVIDERS[$provider]['fields'] ?? [];
        $missing = [];

        foreach ($fields as $key => $definition) {
            if (!($definition['required'] ?? false)) {
                continue;
            }
            if (empty($config[$key]) && $config[$key] !== 0) {
                $missing[] = $definition['label'] ?? $key;
            }
        }

        return $missing;
    }

    private function actorName(Request $request): ?string
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        return $user->community_full_name
            ?: trim(($user->fname ?? '').' '.($user->lname ?? ''))
            ?: ($user->name ?? null);
    }
}
