@extends('layout.app', ['title' => 'Edit Allowed IP'])
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

    .aip-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    .aip-topbar {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        padding: 16px 22px;
        margin-bottom: 20px;
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 12px;
    }
    .aip-topbar .page-title { font-size: 1.1rem; font-weight: 700; color: var(--slate); margin: 0; }
    .aip-topbar .page-sub   { font-size: .78rem; color: var(--muted); margin: 2px 0 0; }

    .btn-back {
        display: inline-flex; align-items: center; gap: 8px;
        color: var(--slate-light); border: 1.5px solid var(--border);
        border-radius: 8px; padding: 8px 16px;
        font-size: 0.82rem; font-weight: 600;
        text-decoration: none; background: var(--surface); transition: all .15s;
    }
    .btn-back:hover { background: var(--bg); color: var(--slate); border-color: var(--teal-mid); }

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
    .field-hint { font-size: 0.72rem; color: var(--muted); margin-top: 4px; }

    /* ── Status toggle ──────────────────────────────────────────────────────── */
    .status-toggle-wrap {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 16px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-input);
        background: #fafbfc;
    }
    .form-check-input[type="checkbox"] {
        width: 2.4rem; height: 1.3rem;
        cursor: pointer;
        border-color: var(--border);
    }
    .form-check-input:checked { background-color: var(--teal); border-color: var(--teal); }
    .form-check-input:focus { box-shadow: 0 0 0 3px rgba(0,128,128,.15); }
    .status-label-text { font-size: 0.85rem; font-weight: 600; color: var(--slate); }
    .status-label-sub  { font-size: 0.73rem; color: var(--muted); }

    /* ── Meta info strip ────────────────────────────────────────────────────── */
    .meta-strip {
        background: var(--teal-light);
        border: 1px solid rgba(0,128,128,.15);
        border-radius: 10px;
        padding: 12px 16px;
        display: flex; gap: 28px; flex-wrap: wrap;
    }
    .meta-item { font-size: 0.75rem; }
    .meta-item .meta-key { color: var(--muted); font-weight: 600; display: block; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 2px; }
    .meta-item .meta-val { color: var(--slate); font-weight: 700; font-family: monospace; }

    /* ── Footer buttons ─────────────────────────────────────────────────────── */
    .sc-footer {
        padding: 16px 24px; border-top: 1px solid var(--border);
        background: var(--bg);
        display: flex; gap: 10px; justify-content: flex-end;
    }
    .btn-submit-aip {
        background: var(--teal); color: #fff; border: none;
        border-radius: 10px; padding: 10px 26px;
        font-size: 0.82rem; font-weight: 700;
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
            <p class="page-title"><i class="fa-solid fa-pen-to-square me-2" style="color:var(--teal)"></i>Edit Allowed IP</p>
            <p class="page-sub">Update the details for this allowlisted IP address</p>
        </div>
        <a href="{{ route('allowed-ips.index') }}" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <h5 class="sc-title">IP Details</h5>
        </div>

        <form method="POST" action="{{ route('allowed-ips.update', $allowedIp) }}" autocomplete="off">
            @csrf @method('PUT')

            <div class="sc-body">
                <div class="row g-4">

                    {{-- Meta strip --}}
                    <div class="col-12">
                        <div class="meta-strip">
                            <div class="meta-item">
                                <span class="meta-key">Record ID</span>
                                <span class="meta-val">#{{ $allowedIp->id }}</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-key">Added On</span>
                                <span class="meta-val" style="font-family:inherit;">{{ $allowedIp->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-key">Last Updated</span>
                                <span class="meta-val" style="font-family:inherit;">{{ $allowedIp->updated_at->format('M d, Y g:i A') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- IP Address --}}
                    <div class="col-12">
                        <label class="field-label" for="ip_address">
                            IP Address <span class="req">*</span>
                        </label>
                        <input type="text"
                               id="ip_address"
                               name="ip_address"
                               class="form-control @error('ip_address') is-invalid @enderror"
                               placeholder="e.g. 192.168.1.100 or 203.0.113.0/24"
                               value="{{ old('ip_address', $allowedIp->ip_address) }}"
                               autofocus />
                        @error('ip_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <p class="field-hint">Single IP (IPv4/IPv6) or a CIDR range like <strong>203.0.113.0/24</strong> for ISPs that rotate dynamic IPs within a block.</p>
                    </div>

                    {{-- Description --}}
                    <div class="col-12">
                        <label class="field-label" for="description">Description</label>
                        <input type="text"
                               id="description"
                               name="description"
                               class="form-control @error('description') is-invalid @enderror"
                               placeholder="e.g. Main Office Gateway, Branch 2 Network"
                               value="{{ old('description', $allowedIp->description) }}"
                               maxlength="255" />
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <p class="field-hint">Optional label for this IP's location or purpose.</p>
                    </div>

                    {{-- Status toggle --}}
                    <div class="col-12">
                        <label class="field-label">Status</label>
                        <div class="status-toggle-wrap">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input"
                                       type="checkbox"
                                       role="switch"
                                       id="statusToggle"
                                       name="status"
                                       value="1"
                                       {{ old('status', $allowedIp->status) ? 'checked' : '' }} />
                            </div>
                            <div>
                                <div class="status-label-text" id="statusLabelText">
                                    {{ old('status', $allowedIp->status) ? 'Active' : 'Disabled' }}
                                </div>
                                <div class="status-label-sub" id="statusLabelSub">
                                    {{ old('status', $allowedIp->status)
                                        ? 'Employees from this IP can access the system.'
                                        : 'Employees from this IP will be blocked.' }}
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="sc-footer">
                <a href="{{ route('allowed-ips.index') }}" class="btn-cancel-aip">Cancel</a>
                <button type="submit" class="btn-submit-aip">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Update IP Address
                </button>
            </div>
        </form>
    </div>

</div>

<script>
    const toggle      = document.getElementById('statusToggle');
    const labelText   = document.getElementById('statusLabelText');
    const labelSub    = document.getElementById('statusLabelSub');

    toggle.addEventListener('change', function () {
        if (this.checked) {
            labelText.textContent = 'Active';
            labelSub.textContent  = 'Employees from this IP can access the system.';
        } else {
            labelText.textContent = 'Disabled';
            labelSub.textContent  = 'Employees from this IP will be blocked.';
        }
    });
</script>

@endsection
