@extends('layout.app')
@section('content')

<style>
    :root {
        --teal:         #008080;
        --teal-dark:    #006666;
        --teal-mid:     #4db6ac;
        --teal-light:   #e0f2f1;
        --slate:        #334155;
        --slate-light:  #64748b;
        --muted:        #94a3b8;
        --bg:           #f1f5f9;
        --surface:      #ffffff;
        --border:       #e2e8f0;
        --danger:       #ef4444;
        --success:      #10b981;
        --radius-card:  14px;
        --radius-input: 8px;
        --shadow-card:  0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }

    .mi-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    .mi-topbar {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        padding: 16px 22px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .mi-topbar h4 { margin: 0; color: var(--slate); font-weight: 700; }
    .mi-topbar p  { margin: 4px 0 0; color: var(--slate-light); font-size: 0.875rem; }

    .btn-teal {
        background: var(--teal);
        border: none;
        color: #fff;
        border-radius: var(--radius-input);
        padding: 10px 18px;
        font-weight: 600;
        transition: background .15s;
    }
    .btn-teal:hover { background: var(--teal-dark); color: #fff; }

    .mi-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        padding: 22px;
    }

    table.mi-table th {
        color: var(--slate-light);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        border-bottom: 2px solid var(--border);
    }
    table.mi-table td { vertical-align: middle; border-bottom: 1px solid var(--border); color: var(--slate); }

    .badge-active { background: var(--teal-light); color: var(--teal-dark); font-weight: 600; padding: 4px 10px; border-radius: 999px; font-size: 0.78rem; }
    .badge-inactive { background: #f1f5f9; color: var(--slate-light); font-weight: 600; padding: 4px 10px; border-radius: 999px; font-size: 0.78rem; }
    .badge-test-success { background: #ecfdf5; color: #047857; font-weight: 600; padding: 3px 9px; border-radius: 999px; font-size: 0.75rem; }
    .badge-test-failed { background: #fef2f2; color: #b91c1c; font-weight: 600; padding: 3px 9px; border-radius: 999px; font-size: 0.75rem; }
    .badge-test-none { background: #f1f5f9; color: var(--muted); font-weight: 600; padding: 3px 9px; border-radius: 999px; font-size: 0.75rem; }

    .empty-state { text-align: center; padding: 50px 0; color: var(--muted); }

    .mi-help { font-size: 0.78rem; color: var(--slate-light); margin-top: 4px; }
    .provider-fieldset { display: none; }
    .provider-fieldset.active { display: block; }
</style>

<div class="mi-shell">
    <div class="mi-topbar">
        <div>
            <h4><i class="fa-solid fa-paper-plane me-2"></i>Mail Integration</h4>
            <p>Configure the email provider used to send automated payslips and other system email. Add credentials, send a test, then activate.</p>
        </div>
        <button type="button" class="btn btn-teal" data-bs-toggle="modal" data-bs-target="#addIntegrationModal">
            <i class="fa-solid fa-circle-plus me-1"></i> Add Integration
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mi-card">
        @if ($settings->count() > 0)
            <div class="table-responsive">
                <table class="table mi-table">
                    <thead>
                        <tr>
                            <th>Integration</th>
                            <th>Provider</th>
                            <th>From</th>
                            <th>Last Test</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($settings as $setting)
                            <tr>
                                <td>{{ $setting->label ?: '(untitled)' }}</td>
                                <td>{{ $setting->providerLabel() }}</td>
                                <td>{{ $setting->from_name }} &lt;{{ $setting->from_address }}&gt;</td>
                                <td>
                                    @if ($setting->last_test_status === 'success')
                                        <span class="badge-test-success"><i class="fa-solid fa-check"></i> Passed</span>
                                    @elseif ($setting->last_test_status === 'failed')
                                        <span class="badge-test-failed"><i class="fa-solid fa-xmark"></i> Failed</span>
                                    @else
                                        <span class="badge-test-none">Never tested</span>
                                    @endif
                                    @if ($setting->last_tested_at)
                                        <div class="mi-help">{{ $setting->last_tested_at->format('M d, Y h:i A') }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($setting->is_active)
                                        <span class="badge-active"><i class="fa-solid fa-circle-check"></i> Active</span>
                                    @else
                                        <span class="badge-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#testModal{{ $setting->id }}">
                                        <i class="fa-solid fa-vial"></i> Test
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $setting->id }}">
                                        <i class="fa-solid fa-pen"></i> Edit
                                    </button>
                                    @if (!$setting->is_active)
                                        <form action="{{ route('mail-integration.activate', $setting->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Make this the active mail provider for all outgoing system email?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="fa-solid fa-power-off"></i> Activate
                                            </button>
                                        </form>
                                        <form action="{{ route('mail-integration.destroy', $setting->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this mail integration?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="fa-solid fa-envelope-circle-check fa-2x mb-2"></i>
                <p>No mail integrations configured yet. Click "Add Integration" to connect Brevo, SMTP, Mailgun, SES, or Postmark.</p>
            </div>
        @endif
    </div>
</div>

{{-- ===================== Add Integration Modal ===================== --}}
<div class="modal fade" id="addIntegrationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('mail-integration.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Mail Integration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Provider</label>
                        <select name="provider" class="form-select provider-select" data-target="add" required>
                            <option value="" disabled selected>Select a provider</option>
                            @foreach ($providers as $key => $definition)
                                <option value="{{ $key }}">{{ $definition['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Label (optional)</label>
                            <input type="text" name="label" class="form-control" placeholder="e.g. Brevo - Production">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">From Address</label>
                            <input type="email" name="from_address" class="form-control" required placeholder="payroll@yourcompany.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">From Name</label>
                            <input type="text" name="from_name" class="form-control" required placeholder="Your Company HR">
                        </div>
                    </div>

                    @foreach ($providers as $key => $definition)
                        <div class="provider-fieldset" data-provider-group="add" data-provider="{{ $key }}">
                            <hr>
                            <h6 class="mb-3">{{ $definition['label'] }} credentials</h6>

                            @if (!empty($definition['note']))
                                <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:0.8rem;">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $definition['note'] }}
                                </div>
                            @endif

                            @if ($key === 'smtp')
                                <div class="mb-3">
                                    <label class="form-label">Quick setup</label>
                                    <select class="form-select smtp-preset-select">
                                        <option value="">Custom / other SMTP server</option>
                                        <option value="brevo">Brevo</option>
                                        <option value="gmail">Gmail / Google Workspace</option>
                                        <option value="office365">Office 365 / Outlook</option>
                                        <option value="zoho">Zoho Mail</option>
                                    </select>
                                    <div class="mi-help">Picks the right host/port/encryption for you. You'll still just need the username and password (or app/SMTP key) from that provider.</div>
                                </div>
                            @endif

                            <div class="row">
                                @foreach ($definition['fields'] as $fieldKey => $field)
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ $field['label'] }}</label>
                                        @if ($field['type'] === 'select')
                                            <select name="config[{{ $key }}][{{ $fieldKey }}]" class="form-select" data-smtp-field="{{ $fieldKey }}">
                                                @foreach ($field['options'] as $optValue => $optLabel)
                                                    <option value="{{ $optValue }}" {{ ($field['default'] ?? '') === $optValue ? 'selected' : '' }}>{{ $optLabel }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input
                                                type="{{ $field['type'] }}"
                                                name="config[{{ $key }}][{{ $fieldKey }}]"
                                                class="form-control"
                                                value="{{ $field['default'] ?? '' }}"
                                                autocomplete="off"
                                                data-smtp-field="{{ $fieldKey }}"
                                            >
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-teal">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===================== Per-row Edit / Test Modals ===================== --}}
@foreach ($settings as $setting)
    <div class="modal fade" id="editModal{{ $setting->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('mail-integration.update', $setting->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit: {{ $setting->label ?: $setting->providerLabel() }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="provider" value="{{ $setting->provider }}">
                        <div class="mb-3">
                            <label class="form-label">Provider</label>
                            <input type="text" class="form-control" value="{{ $setting->providerLabel() }}" disabled>
                            <div class="mi-help">To switch providers, add a new integration instead — keeps a clean history of what was used to send past payslips.</div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Label</label>
                                <input type="text" name="label" class="form-control" value="{{ $setting->label }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">From Address</label>
                                <input type="email" name="from_address" class="form-control" required value="{{ $setting->from_address }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">From Name</label>
                                <input type="text" name="from_name" class="form-control" required value="{{ $setting->from_name }}">
                            </div>
                        </div>

                        <hr>
                        <h6 class="mb-3">{{ $setting->providerLabel() }} credentials</h6>
                        @if (!empty($providers[$setting->provider]['note']))
                            <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:0.8rem;">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $providers[$setting->provider]['note'] }}
                            </div>
                        @endif
                        <div class="row">
                            @foreach (($providers[$setting->provider]['fields'] ?? []) as $fieldKey => $field)
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ $field['label'] }}</label>
                                    @if ($field['type'] === 'select')
                                        <select name="config[{{ $setting->provider }}][{{ $fieldKey }}]" class="form-select">
                                            @foreach ($field['options'] as $optValue => $optLabel)
                                                <option value="{{ $optValue }}" {{ ($setting->config[$fieldKey] ?? $field['default'] ?? '') === $optValue ? 'selected' : '' }}>{{ $optLabel }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($field['secret'] ?? false)
                                        <input
                                            type="{{ $field['type'] }}"
                                            name="config[{{ $setting->provider }}][{{ $fieldKey }}]"
                                            class="form-control"
                                            value=""
                                            autocomplete="off"
                                            placeholder="{{ !empty($setting->config[$fieldKey]) ? 'Currently set — leave blank to keep' : 'Enter value' }}"
                                        >
                                    @else
                                        <input
                                            type="{{ $field['type'] }}"
                                            name="config[{{ $setting->provider }}][{{ $fieldKey }}]"
                                            class="form-control"
                                            value="{{ $setting->config[$fieldKey] ?? $field['default'] ?? '' }}"
                                            autocomplete="off"
                                        >
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-teal">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="testModal{{ $setting->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('mail-integration.test', $setting->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Send Test Email</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mi-help">Sends a real email through {{ $setting->providerLabel() }} using the saved credentials, so you can confirm it actually works before activating it.</p>
                        <label class="form-label">Send test to</label>
                        <input type="email" name="test_email" class="form-control" required value="{{ auth()->user()->email ?? '' }}">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-teal">Send Test</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<script>
    // Quick-setup presets so SMTP providers don't require knowing host/port/encryption by heart.
    var SMTP_PRESETS = {
        brevo:     { host: 'smtp-relay.brevo.com', port: 587, encryption: 'tls' },
        gmail:     { host: 'smtp.gmail.com', port: 587, encryption: 'tls' },
        office365: { host: 'smtp.office365.com', port: 587, encryption: 'tls' },
        zoho:      { host: 'smtp.zoho.com', port: 587, encryption: 'tls' },
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.smtp-preset-select').forEach(function (sel) {
            sel.addEventListener('change', function () {
                var preset = SMTP_PRESETS[this.value];
                if (!preset) return;
                var scope = this.closest('.provider-fieldset');
                if (!scope) return;
                var hostInput = scope.querySelector('[data-smtp-field="host"]');
                var portInput = scope.querySelector('[data-smtp-field="port"]');
                var encSelect = scope.querySelector('[data-smtp-field="encryption"]');
                if (hostInput) hostInput.value = preset.host;
                if (portInput) portInput.value = preset.port;
                if (encSelect) encSelect.value = preset.encryption;
            });
        });
    });

    // Show only the credential fieldset matching the selected provider.
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.provider-select').forEach(function (select) {
            select.addEventListener('change', function () {
                var target = this.getAttribute('data-target');
                document.querySelectorAll('.provider-fieldset[data-provider-group="' + target + '"]').forEach(function (fieldset) {
                    fieldset.classList.toggle('active', fieldset.getAttribute('data-provider') === select.value);
                });
            });
        });

        @if ($errors->any() && old('provider'))
            var addModalEl = document.getElementById('addIntegrationModal');
            if (addModalEl) {
                var modal = new bootstrap.Modal(addModalEl);
                modal.show();
                var select = addModalEl.querySelector('.provider-select');
                if (select) {
                    select.value = @json(old('provider'));
                    select.dispatchEvent(new Event('change'));
                }
            }
        @endif
    });
</script>

@endsection
