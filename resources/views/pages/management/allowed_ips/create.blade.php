@extends('layout.app', ['title' => 'Add Allowed IP'])
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
        --radius-card:  14px;
        --radius-input: 8px;
        --shadow-card:  0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }

    .aip-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    /* ── Topbar ─────────────────────────────────────────────────────────────── */
    .aip-topbar {
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
    .aip-topbar .page-title { font-size: 1.1rem; font-weight: 700; color: var(--slate); margin: 0; }
    .aip-topbar .page-sub   { font-size: .78rem; color: var(--muted); margin: 2px 0 0; }

    .btn-back {
        display: inline-flex; align-items: center; gap: 8px;
        color: var(--slate-light);
        border: 1.5px solid var(--border);
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 0.82rem; font-weight: 600;
        text-decoration: none;
        background: var(--surface);
        transition: all .15s;
    }
    .btn-back:hover { background: var(--bg); color: var(--slate); border-color: var(--teal-mid); }

    /* ── Card ───────────────────────────────────────────────────────────────── */
    .sc {
        background: var(--surface);
        border-radius: var(--radius-card);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-card);
        overflow: hidden;
        max-width: 640px;
    }
    .sc-head {
        display: flex; align-items: center; gap: 10px;
        padding: 14px 22px;
        border-bottom: 1px solid var(--border);
        background: linear-gradient(to right, #fafcff, #f8fbfa);
    }
    .sc-icon {
        width: 30px; height: 30px; border-radius: 8px;
        background: var(--teal-light); color: var(--teal);
        display: flex; align-items: center; justify-content: center;
        font-size: 0.78rem; flex-shrink: 0;
    }
    .sc-title { font-size: 0.78rem; font-weight: 700; color: var(--slate); text-transform: uppercase; letter-spacing: .5px; margin: 0; }

    .sc-body { padding: 26px 24px; }

    /* ── Fields ─────────────────────────────────────────────────────────────── */
    .field-label {
        font-size: 0.7rem; font-weight: 700;
        color: var(--slate-light); text-transform: uppercase;
        letter-spacing: .4px; margin-bottom: 5px; display: block;
    }
    .field-label .req { color: var(--danger); margin-left: 2px; }

    .form-control, .form-select {
        border: 1.5px solid var(--border);
        border-radius: var(--radius-input);
        font-size: 0.875rem; color: var(--slate);
        background: #fafbfc;
        transition: border-color .15s, box-shadow .15s;
        padding: 0.55rem 0.85rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--teal);
        box-shadow: 0 0 0 3px rgba(0,128,128,.1);
        background-color: #fff; outline: none;
    }
    .form-control.is-invalid { border-color: var(--danger); }
    .invalid-feedback { font-size: 0.75rem; }

    .field-hint {
        font-size: 0.72rem; color: var(--muted); margin-top: 4px;
    }

    /* ── IP preview chip ────────────────────────────────────────────────────── */
    .ip-preview-chip {
        display: inline-flex; align-items: center; gap: 6px;
        background: var(--teal-light); color: var(--teal-dark);
        border: 1px solid var(--teal-mid);
        border-radius: 6px; padding: 4px 10px;
        font-size: 0.8rem; font-weight: 600;
        font-family: monospace;
        margin-top: 6px;
        transition: opacity .2s;
    }

    /* ── Footer buttons ─────────────────────────────────────────────────────── */
    .sc-footer {
        padding: 16px 24px;
        border-top: 1px solid var(--border);
        background: var(--bg);
        display: flex; gap: 10px; justify-content: flex-end;
    }
    .btn-submit-aip {
        background: var(--teal);
        color: #fff; border: none; border-radius: 10px;
        padding: 10px 26px; font-size: 0.82rem; font-weight: 700;
        letter-spacing: .4px; text-transform: uppercase;
        box-shadow: 0 4px 14px rgba(0,128,128,.25);
        transition: all .2s; cursor: pointer;
    }
    .btn-submit-aip:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }
    .btn-cancel-aip {
        background: var(--surface); color: var(--slate-light);
        border: 1.5px solid var(--border); border-radius: 10px;
        padding: 10px 22px; font-size: 0.82rem; font-weight: 700;
        letter-spacing: .4px; text-transform: uppercase;
        cursor: pointer; text-decoration: none; transition: all .2s;
    }
    .btn-cancel-aip:hover { background: var(--bg); color: var(--slate); }
</style>

<div class="aip-shell">

    {{-- ── Topbar ── --}}
    <div class="aip-topbar">
        <div>
            <p class="page-title"><i class="fa-solid fa-plus-circle me-2" style="color:var(--teal)"></i>Add Allowed IP</p>
            <p class="page-sub">Register a new IP address that employees may use to access the system</p>
        </div>
        <a href="{{ route('allowed-ips.index') }}" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa-solid fa-shield-plus"></i></div>
            <h5 class="sc-title">IP Details</h5>
        </div>

        <form method="POST" action="{{ route('allowed-ips.store') }}" autocomplete="off">
            @csrf

            <div class="sc-body">
                <div class="row g-4">

                    {{-- IP Address --}}
                    <div class="col-12">
                        <label class="field-label" for="ip_address">
                            IP Address <span class="req">*</span>
                        </label>
                        <input type="text"
                               id="ip_address"
                               name="ip_address"
                               class="form-control @error('ip_address') is-invalid @enderror"
                               placeholder="e.g. 192.168.1.100 or 2001:db8::1"
                               value="{{ old('ip_address') }}"
                               autofocus />
                        @error('ip_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <p class="field-hint">Supports both IPv4 (e.g. 192.168.1.1) and IPv6 (e.g. 2001:db8::1).</p>

                        {{-- Live preview --}}
                        <div id="ipPreview" class="ip-preview-chip" style="display:none;">
                            <i class="fa-solid fa-circle-check" style="font-size:.7rem;"></i>
                            <span id="ipPreviewText"></span>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="col-12">
                        <label class="field-label" for="description">Description</label>
                        <input type="text"
                               id="description"
                               name="description"
                               class="form-control @error('description') is-invalid @enderror"
                               placeholder="e.g. Main Office Gateway, Branch 2 Network"
                               value="{{ old('description') }}"
                               maxlength="255" />
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <p class="field-hint">Optional — a short label to identify this IP's location or purpose.</p>
                    </div>

                </div>
            </div>

            <div class="sc-footer">
                <a href="{{ route('allowed-ips.index') }}" class="btn-cancel-aip">Cancel</a>
                <button type="submit" class="btn-submit-aip">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save IP Address
                </button>
            </div>
        </form>
    </div>

</div>

<script>
    // Live IP preview chip
    const ipInput   = document.getElementById('ip_address');
    const ipPreview = document.getElementById('ipPreview');
    const ipText    = document.getElementById('ipPreviewText');

    const ipv4 = /^(\d{1,3}\.){3}\d{1,3}$/;
    const ipv6 = /^[0-9a-fA-F:]+$/;

    ipInput.addEventListener('input', function () {
        const val = this.value.trim();
        if (val && (ipv4.test(val) || (val.includes(':') && ipv6.test(val)))) {
            ipText.textContent = val;
            ipPreview.style.display = 'inline-flex';
        } else {
            ipPreview.style.display = 'none';
        }
    });

    // Trigger on old value restore (validation fail)
    if (ipInput.value.trim()) ipInput.dispatchEvent(new Event('input'));
</script>

@endsection
