@extends('layout.app', [
    'title' => 'Maintenance Mode'
])
@section('content')
<style>
    :root {
        --teal:        #008080;
        --teal-dark:   #006666;
        --teal-mid:    #4db6ac;
        --teal-light:  #e0f2f1;
        --slate:       #334155;
        --slate-light: #64748b;
        --muted:       #94a3b8;
        --bg:          #f1f5f9;
        --surface:     #ffffff;
        --border:      #e2e8f0;
        --danger:      #ef4444;
        --danger-dark: #b91c1c;
        --danger-light:#fee2e2;
        --warn:        #f59e0b;
        --success:     #10b981;
        --radius-card: 14px;
        --shadow-card: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }

    .mm-shell { padding: 24px; max-width: 1100px; }

    .mm-topbar {
        display: flex; align-items: center; justify-content: space-between;
        gap: 16px; flex-wrap: wrap;
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius-card); box-shadow: var(--shadow-card);
        padding: 18px 22px; margin-bottom: 18px;
    }
    .mm-topbar .page-title { margin: 0; font-size: 20px; font-weight: 800; color: var(--slate); letter-spacing: -.01em; }
    .mm-topbar .page-sub { margin: 2px 0 0; font-size: 13px; color: var(--slate-light); }

    .status-pill {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 7px 15px; border-radius: 999px; font-weight: 700; font-size: 13px;
    }
    .status-pill .dot { width: 9px; height: 9px; border-radius: 50%; }
    .status-pill.is-on  { background: var(--danger-light); color: var(--danger-dark); }
    .status-pill.is-on  .dot { background: var(--danger); box-shadow: 0 0 0 0 rgba(239,68,68,.6); animation: mm-blink 1.6s infinite; }
    .status-pill.is-off { background: var(--teal-light); color: var(--teal-dark); }
    .status-pill.is-off .dot { background: var(--success); }
    @keyframes mm-blink { 0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,.55); } 50% { box-shadow: 0 0 0 6px rgba(239,68,68,0); } }

    .sc {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius-card); box-shadow: var(--shadow-card);
        margin-bottom: 18px; overflow: hidden;
    }
    .sc-head { display: flex; align-items: center; gap: 12px; padding: 16px 22px; border-bottom: 1px solid var(--border); }
    .sc-icon {
        width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center;
        background: var(--teal-light); color: var(--teal-dark); font-size: 16px;
    }
    .sc-title { margin: 0; font-size: 15px; font-weight: 700; color: var(--slate); }
    .sc-title small { display: block; font-size: 12px; font-weight: 500; color: var(--slate-light); }
    .sc-body { padding: 22px; }

    /* Master switch row */
    .master-row { display: flex; align-items: center; justify-content: space-between; gap: 18px; flex-wrap: wrap; }
    .master-copy h6 { margin: 0 0 3px; font-size: 15px; font-weight: 700; color: var(--slate); }
    .master-copy p { margin: 0; font-size: 13px; color: var(--slate-light); max-width: 560px; }

    /* Toggle switch */
    .switch { position: relative; display: inline-block; width: 64px; height: 34px; flex-shrink: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .switch .slider {
        position: absolute; inset: 0; cursor: pointer; background: #cbd5e1;
        border-radius: 999px; transition: .25s;
    }
    .switch .slider::before {
        content: ""; position: absolute; height: 26px; width: 26px; left: 4px; top: 4px;
        background: #fff; border-radius: 50%; transition: .25s; box-shadow: 0 1px 3px rgba(0,0,0,.3);
    }
    .switch input:checked + .slider { background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%); }
    .switch input:checked + .slider::before { transform: translateX(30px); }

    /* Scope cards */
    .scope-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; }
    .scope-card {
        position: relative; border: 1.5px solid var(--border); border-radius: 12px;
        padding: 16px 16px 16px 50px; cursor: pointer; transition: all .18s; background: #fff;
    }
    .scope-card:hover { border-color: var(--teal-mid); background: #fafdfd; }
    .scope-card input { position: absolute; left: 16px; top: 18px; width: 18px; height: 18px; accent-color: var(--teal); cursor: pointer; }
    .scope-card .sc-name { font-weight: 700; color: var(--slate); font-size: 14px; }
    .scope-card .sc-desc { font-size: 12.5px; color: var(--slate-light); margin-top: 2px; }
    .scope-card.selected { border-color: var(--teal); background: var(--teal-light); box-shadow: 0 0 0 3px rgba(0,128,128,.12); }

    /* Department picker */
    #deptPickerWrap { margin-top: 18px; }
    .dept-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; flex-wrap: wrap; }
    .dept-toolbar .count { font-size: 12.5px; color: var(--slate-light); font-weight: 600; }
    .dept-toolbar .link-btn { background: none; border: none; color: var(--teal-dark); font-weight: 700; font-size: 12.5px; cursor: pointer; padding: 4px 8px; border-radius: 6px; }
    .dept-toolbar .link-btn:hover { background: var(--teal-light); }
    .dept-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 8px;
        max-height: 320px; overflow-y: auto; padding: 4px;
        border: 1px solid var(--border); border-radius: 10px; background: var(--bg);
    }
    .dept-chip {
        display: flex; align-items: center; gap: 9px; padding: 9px 12px;
        background: #fff; border: 1px solid var(--border); border-radius: 9px; cursor: pointer;
        font-size: 13px; color: var(--slate); transition: all .15s;
    }
    .dept-chip:hover { border-color: var(--teal-mid); }
    .dept-chip input { width: 16px; height: 16px; accent-color: var(--teal); cursor: pointer; }
    .dept-chip.checked { background: var(--teal-light); border-color: var(--teal); font-weight: 600; }
    .dept-empty { padding: 24px; text-align: center; color: var(--muted); font-size: 13px; }

    .field-label { display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--slate-light); margin-bottom: 6px; }
    .form-control, .form-control:focus { border-radius: 8px; border: 1.5px solid var(--border); box-shadow: none; }
    .form-control:focus { border-color: var(--teal-mid); }
    .help-text { font-size: 12px; color: var(--muted); margin-top: 5px; }
    .error-text { color: var(--danger); font-size: 12.5px; margin-top: 5px; display: block; }

    .schedule-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }

    .info-banner {
        display: flex; gap: 12px; align-items: flex-start;
        background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 14px 16px;
        font-size: 13px; color: #92400e; line-height: 1.55;
    }
    .info-banner i { color: var(--warn); font-size: 16px; margin-top: 1px; }
    .info-banner code { background: #fef3c7; padding: 1px 6px; border-radius: 5px; color: #92400e; font-weight: 600; }

    .mm-actions { display: flex; justify-content: flex-end; gap: 10px; }
    .btn-save-mm {
        border: none; border-radius: 10px; padding: 11px 26px; font-weight: 700; color: #fff; font-size: 14px;
        background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dark) 100%);
        box-shadow: 0 4px 12px -2px rgba(0,128,128,.45); transition: all .18s;
    }
    .btn-save-mm:hover { transform: translateY(-1px); box-shadow: 0 7px 16px -3px rgba(0,128,128,.55); filter: brightness(1.05); }
    .btn-save-mm:disabled { opacity: .6; pointer-events: none; }

    .is-hidden { display: none !important; }

    /* Live preview of the lockout banner */
    .preview-wrap { border: 1px dashed var(--border); border-radius: 12px; padding: 4px; background: var(--bg); }
    .preview-card {
        border-radius: 10px; padding: 26px 24px; text-align: center; color: #fff;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    }
    .preview-card .pv-icon { font-size: 30px; color: var(--teal-mid); margin-bottom: 10px; }
    .preview-card h5 { font-weight: 800; margin: 0 0 8px; }
    .preview-card p { margin: 0; opacity: .85; font-size: 13.5px; max-width: 460px; margin-inline: auto; }
</style>

<div class="mm-shell">

    {{-- ── Header ── --}}
    <div class="mm-topbar">
        <div>
            <p class="page-title"><i class="fa-solid fa-screwdriver-wrench me-2" style="color:var(--teal)"></i>Maintenance Mode</p>
            <p class="page-sub">Lock the system for everyone, or just selected departments, while you work behind the scenes.</p>
        </div>
        <span id="statusPill" class="status-pill {{ $setting->is_active ? 'is-on' : 'is-off' }}">
            <span class="dot"></span>
            <span id="statusText">{{ $setting->is_active ? 'ACTIVE' : 'OFF' }}</span>
        </span>
    </div>

    <form id="frmMaintenance">
        @csrf

        {{-- ── Master switch ── --}}
        <div class="sc">
            <div class="sc-body">
                <div class="master-row">
                    <div class="master-copy">
                        <h6>Maintenance Mode</h6>
                        <p>When ON, affected users see a lockout screen and cannot use the system. You and anyone with the
                           <b>Maintenance Mode Bypass</b> permission stay unaffected so you can keep working.</p>
                    </div>
                    <label class="switch" title="Toggle maintenance mode">
                        <input type="checkbox" id="chkActive" name="is_active" value="1" {{ $setting->is_active ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>

        {{-- ── Scope ── --}}
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon"><i class="fa-solid fa-bullseye"></i></div>
                <h5 class="sc-title">Who is affected? <small>Choose how wide the lockout reaches.</small></h5>
            </div>
            <div class="sc-body">
                <div class="scope-grid">
                    <label class="scope-card" data-scope="global">
                        <input type="radio" name="scope" value="global" {{ $setting->scope === 'global' ? 'checked' : '' }}>
                        <div class="sc-name"><i class="fa-solid fa-globe me-1"></i> Global</div>
                        <div class="sc-desc">Every employee is locked out, regardless of department.</div>
                    </label>
                    <label class="scope-card" data-scope="department">
                        <input type="radio" name="scope" value="department" {{ $setting->scope === 'department' ? 'checked' : '' }}>
                        <div class="sc-name"><i class="fa-solid fa-sitemap me-1"></i> By Department</div>
                        <div class="sc-desc">Only employees in the departments you pick below are locked out.</div>
                    </label>
                </div>

                {{-- Department picker --}}
                <div id="deptPickerWrap" class="{{ $setting->scope === 'department' ? '' : 'is-hidden' }}">
                    <div class="dept-toolbar">
                        <span class="count"><span id="deptCount">0</span> of {{ $departments->count() }} departments selected</span>
                        <div>
                            <button type="button" class="link-btn" id="btnSelectAllDept">Select all</button>
                            <button type="button" class="link-btn" id="btnClearDept">Clear</button>
                        </div>
                    </div>
                    @php $selectedDepts = array_map('strval', (array) $setting->department_ids); @endphp
                    <div class="dept-grid">
                        @forelse ($departments as $dept)
                            <label class="dept-chip {{ in_array((string) $dept->id, $selectedDepts, true) ? 'checked' : '' }}">
                                <input type="checkbox" name="department_ids[]" value="{{ $dept->id }}"
                                    {{ in_array((string) $dept->id, $selectedDepts, true) ? 'checked' : '' }}>
                                <span>{{ $dept->dep_name }}</span>
                            </label>
                        @empty
                            <div class="dept-empty">No departments found. Add departments first under Settings → Departments.</div>
                        @endforelse
                    </div>
                    <span class="error-text is-hidden" id="errDept"></span>
                </div>
            </div>
        </div>

        {{-- ── Message + schedule ── --}}
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon"><i class="fa-solid fa-bullhorn"></i></div>
                <h5 class="sc-title">Lockout message &amp; schedule <small>What users see, and (optionally) when it auto-starts/ends.</small></h5>
            </div>
            <div class="sc-body">
                <label class="field-label" for="txtMessage">Message shown to locked-out users</label>
                <textarea class="form-control" id="txtMessage" name="message" rows="3"
                    placeholder="e.g. We're performing scheduled maintenance and will be back shortly.">{{ $setting->message }}</textarea>
                <span class="help-text">Keep it short and reassuring. Plain text only.</span>

                <div class="schedule-grid" style="margin-top:18px;">
                    <div>
                        <label class="field-label" for="txtStartsAt">Auto-start at <span style="text-transform:none;font-weight:500;">(optional)</span></label>
                        <input type="datetime-local" class="form-control" id="txtStartsAt" name="starts_at"
                            value="{{ $setting->starts_at ? $setting->starts_at->format('Y-m-d\TH:i') : '' }}">
                        <span class="help-text">Leave blank to take effect immediately when toggled on.</span>
                    </div>
                    <div>
                        <label class="field-label" for="txtEndsAt">Auto-end at <span style="text-transform:none;font-weight:500;">(optional)</span></label>
                        <input type="datetime-local" class="form-control" id="txtEndsAt" name="ends_at"
                            value="{{ $setting->ends_at ? $setting->ends_at->format('Y-m-d\TH:i') : '' }}">
                        <span class="help-text">After this time, maintenance lifts automatically.</span>
                        <span class="error-text is-hidden" id="errEnds"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Exemption note ── --}}
        <div class="sc">
            <div class="sc-body">
                <div class="info-banner">
                    <i class="fa-solid fa-shield-halved"></i>
                    <div>
                        Assign the <b>Maintenance Mode Bypass</b> permission (Settings → User Roles) to any role that must keep
                        working during maintenance — IT, HR, payroll, etc. Holders of <b>Maintenance Mode</b> (this screen) and
                        system administrators are <b>always</b> exempt so they can switch it back off.
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Save ── --}}
        <div class="mm-actions">
            <button type="submit" class="btn-save-mm" id="btnSaveMaintenance">
                <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const form    = document.getElementById('frmMaintenance');
    const token   = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const wrap    = document.getElementById('deptPickerWrap');
    const countEl = document.getElementById('deptCount');

    // ── Scope card highlight + dept picker visibility ──
    function syncScope() {
        const scope = form.querySelector('input[name="scope"]:checked')?.value || 'global';
        document.querySelectorAll('.scope-card').forEach(card => {
            card.classList.toggle('selected', card.dataset.scope === scope);
        });
        wrap.classList.toggle('is-hidden', scope !== 'department');
    }
    document.querySelectorAll('input[name="scope"]').forEach(r => r.addEventListener('change', syncScope));

    // ── Department chip checked state + counter ──
    function syncDeptCount() {
        const boxes = [...document.querySelectorAll('input[name="department_ids[]"]')];
        boxes.forEach(b => b.closest('.dept-chip')?.classList.toggle('checked', b.checked));
        countEl.textContent = boxes.filter(b => b.checked).length;
    }
    document.querySelectorAll('input[name="department_ids[]"]').forEach(b => b.addEventListener('change', syncDeptCount));
    document.getElementById('btnSelectAllDept')?.addEventListener('click', () => {
        document.querySelectorAll('input[name="department_ids[]"]').forEach(b => b.checked = true);
        syncDeptCount();
    });
    document.getElementById('btnClearDept')?.addEventListener('click', () => {
        document.querySelectorAll('input[name="department_ids[]"]').forEach(b => b.checked = false);
        syncDeptCount();
    });

    // ── Live status pill reflects the toggle (before saving) ──
    const pill = document.getElementById('statusPill');
    const pillText = document.getElementById('statusText');
    document.getElementById('chkActive').addEventListener('change', function () {
        const on = this.checked;
        pill.classList.toggle('is-on', on);
        pill.classList.toggle('is-off', !on);
        pillText.textContent = on ? 'ACTIVE' : 'OFF';
    });

    syncScope();
    syncDeptCount();

    // ── Submit ──
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('btnSaveMaintenance');
        const active = document.getElementById('chkActive').checked;
        const scope  = form.querySelector('input[name="scope"]:checked')?.value || 'global';

        // Friendly confirm before turning a GLOBAL lockout on.
        const proceed = (active && scope === 'global')
            ? Swal.fire({
                icon: 'warning',
                title: 'Lock out everyone?',
                html: 'This will block <b>all</b> non-exempt users from the entire system until you turn it off.',
                showCancelButton: true,
                confirmButtonText: 'Yes, enable maintenance',
                confirmButtonColor: '#b91c1c',
                cancelButtonText: 'Cancel'
              }).then(r => r.isConfirmed)
            : Promise.resolve(true);

        proceed.then(ok => {
            if (!ok) return;
            btn.disabled = true;

            const data = new FormData(form);
            // Ensure unchecked toggle sends a value the server reads as false.
            if (!active) data.set('is_active', '0');

            axios.post('{{ route('maintenance-mode.update') }}', data, {
                headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
            }).then(res => {
                btn.disabled = false;
                const d = res.data;
                if (d.status === 200) {
                    Swal.fire({ icon: 'success', title: 'Saved', text: d.msg, timer: 1800, showConfirmButton: false });
                } else if (d.status === 202) {
                    Swal.fire({ icon: 'warning', title: 'Heads up', text: d.msg });
                } else if (d.status === 201 && d.error) {
                    const first = Object.values(d.error)[0][0];
                    Swal.fire({ icon: 'error', title: 'Check the form', text: first });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: d.msg || 'Could not save.' });
                }
            }).catch(() => {
                btn.disabled = false;
                Swal.fire({ icon: 'error', title: 'Network error', text: 'Could not reach the server. Try again.' });
            });
        });
    });
})();
</script>
@endsection
