@extends('layout.app')
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8;
        --bg:#f1f5f9; --surface:#ffffff; --border:#e2e8f0;
        --danger:#ef4444; --success:#10b981; --warning:#f59e0b;
        --radius-card:14px; --radius-input:8px;
        --shadow-card:0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }
    .ntc-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .ntc-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
    .ntc-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .ntc-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .btn-teal { background:var(--teal); color:#fff; border:none; border-radius:8px; padding:10px 20px; font-size:.82rem; font-weight:700; cursor:pointer; box-shadow:0 4px 14px rgba(0,128,128,.25); transition:all .2s; display:inline-flex; align-items:center; gap:8px; }
    .btn-teal:hover { background:var(--teal-dark); transform:translateY(-1px); color:#fff; }

    /* Alert flash */
    .flash-alert { background:linear-gradient(135deg,#fee2e2,#fff1f2); border:1px solid #fca5a5; border-left:5px solid var(--danger); border-radius:var(--radius-card); padding:16px 20px; margin-bottom:20px; box-shadow:var(--shadow-card); }
    .flash-alert .fa-title { color:#b91c1c; font-weight:800; font-size:.9rem; display:flex; align-items:center; gap:9px; }
    .flash-alert .names { margin-top:8px; display:flex; flex-wrap:wrap; gap:8px; }
    .flash-name { background:#fff; border:1px solid #fca5a5; color:#b91c1c; border-radius:20px; padding:5px 12px; font-size:.78rem; font-weight:700; }
    .flash-name .n { background:#b91c1c; color:#fff; border-radius:10px; padding:1px 7px; margin-left:6px; font-size:.68rem; }

    .ntc-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:20px; }
    @media (max-width:900px){ .ntc-stats{ grid-template-columns:repeat(2,1fr); } }
    .stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 18px; display:flex; align-items:center; gap:14px; }
    .stat-ic { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
    .stat-ic.t{ background:var(--teal-light); color:var(--teal);} .stat-ic.w{ background:#fef3c7; color:#b45309;} .stat-ic.d{ background:#fee2e2; color:#b91c1c;} .stat-ic.b{ background:#e0e7ff; color:#4338ca;}
    .stat .num { font-size:1.4rem; font-weight:800; color:var(--slate); line-height:1; }
    .stat .lbl { font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-top:4px; }

    .sc { background:var(--surface); border-radius:var(--radius-card); border:1px solid var(--border); box-shadow:var(--shadow-card); margin-bottom:20px; overflow:hidden; }
    .sc-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:14px 22px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); flex-wrap:wrap; }
    .sc-head-left { display:flex; align-items:center; gap:10px; }
    .sc-icon { width:30px; height:30px; border-radius:8px; background:var(--teal-light); color:var(--teal); display:flex; align-items:center; justify-content:center; font-size:.78rem; flex-shrink:0; }
    .sc-title { font-size:.78rem; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; margin:0; }

    .ntc-table { width:100%; margin:0; }
    .ntc-table thead th { position:sticky; top:0; z-index:10; background:var(--surface); font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; border-bottom:2px solid var(--border); white-space:nowrap; padding:12px 16px; }
    .ntc-table tbody td { font-size:.83rem; color:var(--slate); vertical-align:middle; padding:12px 16px; border-bottom:1px solid var(--border); }
    .ntc-table tbody tr:hover { background:var(--teal-light); }
    .empty-row td { text-align:center; color:var(--muted); padding:28px 16px; font-size:.85rem; }

    .badge-soft { display:inline-block; border-radius:20px; padding:4px 12px; font-size:.72rem; font-weight:700; }
    .b-memo { background:#e0e7ff; color:#4338ca; } .b-disc { background:#fee2e2; color:#b91c1c; }
    .b-active { background:#dcfce7; color:#15803d; } .b-void { background:#f1f5f9; color:#64748b; }
    .b-cat { background:#fef3c7; color:#b45309; }

    .btn-mini { border:1.5px solid var(--border); background:var(--surface); border-radius:8px; padding:5px 11px; font-size:.74rem; font-weight:700; cursor:pointer; transition:all .15s; display:inline-flex; align-items:center; gap:5px; }
    .btn-mini.edit:hover { background:var(--teal-light); border-color:var(--teal-mid); }
    .btn-mini.del { color:var(--danger); } .btn-mini.del:hover { background:#fff5f5; border-color:var(--danger); }
    .btn-mini.ok { color:var(--success); } .btn-mini.ok:hover { background:#f0fdf4; border-color:var(--success); }
    .btn-mini.warn { color:var(--warning); } .btn-mini.warn:hover { background:#fffbeb; border-color:var(--warning); }

    .rec-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 18px; border-bottom:1px solid var(--border); }
    .rec-row:last-child { border-bottom:0; }
    .rec-row .who { font-weight:700; color:var(--slate); }
    .rec-row .why { font-size:.76rem; color:var(--slate-light); margin-top:3px; max-width:560px; }

    .field-label { font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px; display:block; }
    .field-label .req { color:var(--danger); margin-left:2px; }
    .form-control, .form-select { border:1.5px solid var(--border); border-radius:var(--radius-input); font-size:.875rem; color:var(--slate); background:#fafbfc; padding:.55rem .85rem; }
    .form-control:focus, .form-select:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
    #mdlNotice .modal-content { border-radius:var(--radius-card); border:none; overflow:hidden; }
    #mdlNotice .modal-header { background:var(--teal); color:#fff; border-bottom:none; padding:16px 22px; }
    #mdlNotice .modal-title { color:#fff; } #mdlNotice .btn-close { filter:brightness(0) invert(1); }
    #mdlNotice .modal-body { background:var(--bg); padding:22px; }
    /* Uniform, all-caps dropdowns in the Issue Notice modal (display only) */
    #mdlNotice .form-select, #mdlNotice .form-select option { text-transform:uppercase; letter-spacing:.3px; }
    #mdlNotice .modal-footer { background:var(--surface); border-top:1px solid var(--border); }

    /* Bulk recipient pickers (Send To = multiple / department) */
    .recip-toolbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:8px; }
    .recip-toolbar .count { font-size:.72rem; font-weight:700; color:var(--slate-light); white-space:nowrap; }
    .recip-toolbar .form-control { flex:1; min-width:160px; text-transform:none; }
    .link-btn { background:none; border:none; color:var(--teal); font-size:.74rem; font-weight:700; cursor:pointer; padding:2px 6px; }
    .link-btn:hover { color:var(--teal-dark); text-decoration:underline; }
    .recip-list { max-height:200px; overflow-y:auto; background:var(--surface); border:1.5px solid var(--border); border-radius:var(--radius-input); text-transform:none; }
    .recip-row { display:flex; align-items:center; gap:10px; padding:8px 12px; border-bottom:1px solid var(--border); cursor:pointer; margin:0; font-size:.82rem; color:var(--slate); }
    .recip-row:last-child { border-bottom:0; }
    .recip-row:hover { background:var(--teal-light); }
    .recip-row input { width:15px; height:15px; accent-color:var(--teal); flex-shrink:0; cursor:pointer; }
    .recip-row .dept { font-size:.7rem; color:var(--muted); margin-left:auto; white-space:nowrap; }
    .recip-empty { padding:14px 12px; text-align:center; color:var(--muted); font-size:.8rem; }
    .recip-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(170px,1fr)); gap:8px; }
    .recip-chip { display:flex; align-items:center; gap:8px; background:var(--surface); border:1.5px solid var(--border); border-radius:var(--radius-input); padding:8px 12px; font-size:.8rem; font-weight:600; color:var(--slate); cursor:pointer; margin:0; transition:all .15s; }
    .recip-chip:hover { border-color:var(--teal-mid); }
    .recip-chip.checked { background:var(--teal-light); border-color:var(--teal); }
    .recip-chip input { width:15px; height:15px; accent-color:var(--teal); flex-shrink:0; cursor:pointer; }
    .recip-hint { font-size:.72rem; color:var(--muted); margin-top:4px; display:block; }
    .btn-submit { background:linear-gradient(135deg,#008080,#006666); color:#fff; border:none; border-radius:10px; padding:10px 26px; font-size:.82rem; font-weight:700; letter-spacing:.4px; text-transform:uppercase; cursor:pointer; }
    .btn-submit:hover { color:#fff; transform:translateY(-1px); }
    /* Fill the row next to the title and right-align the controls. */
    .filters { display:flex; flex:1 1 auto; gap:8px; flex-wrap:wrap; align-items:center; justify-content:flex-end; }
    /* Bootstrap defaults .form-select/.form-control to width:100%, which makes
       each control claim its own row inside the flex bar and balloons the header
       height. Pin them to fixed widths so all three stay on one line. */
    .filters .form-select, .filters .form-control { padding:6px 10px; font-size:.8rem; width:auto; flex:0 0 auto; }
    .filters .form-select { min-width:120px; }
    .filters .form-control { width:220px; max-width:100%; }
</style>

<div class="ntc-shell">

    <div class="ntc-topbar">
        <div>
            <p class="page-title">Notices &amp; Memos</p>
            <p class="page-sub">Issue memos and disciplinary notices to employees. {{ $d['stats']['suspend'] }} active disciplinary notices auto-recommends suspension for HR review.</p>
        </div>
        <button class="btn-teal" id="btnIssueNotice"><i class="fa-solid fa-plus"></i> Issue Notice</button>
    </div>

    {{-- Alert flash: employees over the suspension threshold --}}
    @if (!empty($d['over']))
        <div class="flash-alert">
            <div class="fa-title"><i class="fa-solid fa-triangle-exclamation"></i> {{ count($d['over']) }} employee(s) exceeded the disciplinary limit ({{ $d['stats']['suspend'] }}+ notices) &mdash; recommended for suspension review:</div>
            <div class="names">
                @foreach ($d['over'] as $o)
                    <span class="flash-name">{{ $o['name'] }} <span class="n">{{ $o['count'] }}</span></span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Stat cards --}}
    <div class="ntc-stats">
        <div class="stat"><div class="stat-ic t"><i class="fa-solid fa-file-lines"></i></div><div><div class="num">{{ $d['stats']['issuedThisMonth'] }}</div><div class="lbl">Issued This Month</div></div></div>
        <div class="stat"><div class="stat-ic d"><i class="fa-solid fa-gavel"></i></div><div><div class="num">{{ $d['stats']['activeDisc'] }}</div><div class="lbl">Active Disciplinary</div></div></div>
        <div class="stat"><div class="stat-ic w"><i class="fa-solid fa-user-clock"></i></div><div><div class="num">{{ $d['stats']['atRiskCount'] }}</div><div class="lbl">At Risk ({{ $d['stats']['warn'] }}+)</div></div></div>
        <div class="stat"><div class="stat-ic b"><i class="fa-solid fa-ban"></i></div><div><div class="num">{{ $d['stats']['pendingRecs'] }}</div><div class="lbl">Pending Suspension Recs</div></div></div>
    </div>

    {{-- Suspension recommendations --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left"><div class="sc-icon"><i class="fa-solid fa-ban"></i></div><h5 class="sc-title">Suspension Recommendations (auto)</h5></div>
        </div>
        <div id="recList">
            <div class="rec-row"><span class="text-muted">Loading&hellip;</span></div>
        </div>
    </div>

    {{-- Notices table --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left"><div class="sc-icon"><i class="fa-solid fa-list"></i></div><h5 class="sc-title">All Notices</h5></div>
            <div class="filters">
                <select class="form-select" id="fType"><option value="">All Types</option><option value="disciplinary">Disciplinary</option><option value="memo">Memo</option></select>
                <select class="form-select" id="fStatus"><option value="">All Status</option><option value="active">Active</option><option value="void">Void</option></select>
                <input type="text" class="form-control" id="fSearch" placeholder="Search name / title…">
            </div>
        </div>
        <div class="table-responsive" style="max-height:60vh; overflow-y:auto;">
            <table class="ntc-table">
                <thead>
                    <tr>
                        <th>Employee</th><th>Type</th><th>Title</th><th>Category</th><th>Issued</th><th>By</th><th>Status</th><th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody id="tblNotices"><tr class="empty-row"><td colspan="8">Loading&hellip;</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Issue / Edit Notice Modal --}}
<div class="modal fade" id="mdlNotice" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-file-circle-exclamation me-2"></i><span id="mdlTitle">Issue Notice</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="noticeId">
                <div class="row g-3">
                    <div class="col-lg-7" id="recipientModeWrap">
                        <label class="field-label" for="selRecipientMode">Send To <span class="req">*</span></label>
                        <select class="form-select" id="selRecipientMode">
                            <option value="single">Single employee</option>
                            <option value="employees">Multiple employees</option>
                            <option value="department">Department(s)</option>
                            <option value="all">All active employees</option>
                        </select>
                        <span class="recip-hint">Bulk sending is available for memos only.</span>
                        <span class="text-danger small d-block mt-1" id="err-recipient_mode"></span>
                    </div>
                    <div class="col-lg-5">
                        <label class="field-label" for="selType">Type <span class="req">*</span></label>
                        <select class="form-select" id="selType">
                            <option value="memo">Memo (informational)</option>
                            <option value="disciplinary">Disciplinary Notice (counts toward suspension)</option>
                        </select>
                    </div>
                    <div class="col-12" id="empSingleWrap">
                        <label class="field-label" for="selEmployee">Employee <span class="req">*</span></label>
                        <select class="form-select" id="selEmployee"><option value="">Select employee…</option></select>
                        <span class="text-danger small d-block mt-1" id="err-employee_id"></span>
                    </div>
                    <div class="col-12" id="empMultiWrap" style="display:none;">
                        <label class="field-label">Employees <span class="req">*</span></label>
                        <div class="recip-toolbar">
                            <input type="text" class="form-control form-control-sm" id="txtEmpSearch" placeholder="Search name / department…">
                            <span class="count"><span id="empPickCount">0</span> selected</span>
                            <button type="button" class="link-btn" id="btnEmpAll">Select all</button>
                            <button type="button" class="link-btn" id="btnEmpClear">Clear</button>
                        </div>
                        <div class="recip-list" id="empCheckList"></div>
                        <span class="text-danger small d-block mt-1" id="err-employee_ids"></span>
                    </div>
                    <div class="col-12" id="deptMultiWrap" style="display:none;">
                        <label class="field-label">Departments <span class="req">*</span></label>
                        <div class="recip-toolbar">
                            <span class="count"><span id="deptPickCount">0</span> of {{ $departments->count() }} selected</span>
                            <button type="button" class="link-btn" id="btnDeptAll">Select all</button>
                            <button type="button" class="link-btn" id="btnDeptClear">Clear</button>
                        </div>
                        <div class="recip-grid">
                            @forelse ($departments as $dept)
                                <label class="recip-chip"><input type="checkbox" class="chk-dept" value="{{ $dept->id }}"><span>{{ $dept->dep_name }}</span></label>
                            @empty
                                <div class="text-muted small">No departments found.</div>
                            @endforelse
                        </div>
                        <span class="text-danger small d-block mt-1" id="err-department_ids"></span>
                    </div>
                    <div class="col-12" id="allWrap" style="display:none;">
                        <div class="recip-hint" style="font-size:.8rem; color:var(--slate-light);"><i class="fa-solid fa-users me-1"></i> The memo will be sent to every active employee.</div>
                    </div>
                    <div class="col-lg-7" id="catWrap" style="display:none;">
                        <label class="field-label" for="selCategory">Category</label>
                        <select class="form-select" id="selCategory">
                            <option value="">— Select reason —</option>
                            <option>Tardiness</option>
                            <option>Absenteeism / AWOL</option>
                            <option>Misconduct</option>
                            <option>Insubordination</option>
                            <option>Policy Violation</option>
                            <option>Performance</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="col-lg-5">
                        <label class="field-label" for="txtIssuedAt">Date Issued</label>
                        <input type="date" class="form-control" id="txtIssuedAt">
                    </div>
                    <div class="col-12">
                        <label class="field-label" for="txtTitle">Title / Subject <span class="req">*</span></label>
                        <input type="text" class="form-control" id="txtTitle" placeholder="e.g. Notice to Explain — Repeated Tardiness">
                        <span class="text-danger small d-block mt-1" id="err-title"></span>
                    </div>
                    <div class="col-12">
                        <label class="field-label" for="txtBody">Details <span class="req">*</span></label>
                        <textarea class="form-control" id="txtBody" rows="5" placeholder="Describe the notice / memo…"></textarea>
                        <span class="text-danger small d-block mt-1" id="err-body"></span>
                    </div>
                    <div class="col-lg-5" id="statusWrap" style="display:none;">
                        <label class="field-label" for="selStatus">Status</label>
                        <select class="form-select" id="selStatus"><option value="active">Active</option><option value="void">Void (exclude from counts)</option></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-mini" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-submit" id="btnSaveNotice">Save Notice</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/modules/notices.js') }}" defer></script>
@endsection
