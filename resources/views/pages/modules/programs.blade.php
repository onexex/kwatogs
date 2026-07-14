@extends('layout.app')
@section('content')

<style>
    /* ── Design tokens (shared across pages) ── */
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8;
        --bg:#f1f5f9; --surface:#ffffff; --border:#e2e8f0;
        --danger:#ef4444; --success:#10b981; --warning:#f59e0b;
        --radius-card:14px; --radius-input:8px;
        --shadow-card:0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }

    .prog-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }

    /* Topbar */
    .prog-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; }
    .prog-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; letter-spacing:-.2px; }
    .prog-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .btn-teal { background:var(--teal); color:#fff; border:none; border-radius:8px; padding:10px 20px; font-size:.82rem; font-weight:700; letter-spacing:.3px; cursor:pointer; box-shadow:0 4px 14px rgba(0,128,128,.25); transition:all .2s; display:inline-flex; align-items:center; gap:8px; }
    .btn-teal:hover { background:var(--teal-dark); transform:translateY(-1px); color:#fff; }

    /* Compact stat chips (topbar) */
    .prog-stats { display:flex; gap:10px; flex-wrap:wrap; }
    .prog-stat { display:flex; align-items:center; gap:9px; background:#fafbfc; border:1px solid var(--border); border-radius:10px; padding:8px 14px; }
    .prog-stat .ic { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.8rem; flex-shrink:0; }
    .prog-stat .ic.t { background:var(--teal-light); color:var(--teal); }
    .prog-stat .ic.w { background:#fef3c7; color:#b45309; }
    .prog-stat .ic.g { background:#dcfce7; color:#15803d; }
    .prog-stat .ic.b { background:#e0e7ff; color:#4338ca; }
    .prog-stat .n { font-size:1.05rem; font-weight:800; color:var(--slate); line-height:1; }
    .prog-stat .l { font-size:.64rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-top:2px; }

    /* ── Workspace: milestone rail + detail pane ── */
    .prog-workspace { display:grid; grid-template-columns:340px 1fr; gap:16px; align-items:start; }
    @media (max-width:900px){ .prog-workspace { grid-template-columns:1fr; } }
    .prog-pane { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); overflow:hidden; }
    .prog-list-pane { display:flex; flex-direction:column; max-height:calc(100vh - 190px); }
    .prog-list-head { padding:12px 14px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .prog-pills { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px; }
    .pill { border:1.5px solid var(--border); background:var(--surface); color:var(--slate-light); border-radius:20px; padding:5px 12px; font-size:.72rem; font-weight:700; cursor:pointer; transition:all .15s; }
    .pill:hover { border-color:var(--teal-mid); }
    .pill.active { background:var(--teal); color:#fff; border-color:var(--teal); }
    .pill .pc { background:rgba(0,0,0,.12); border-radius:10px; padding:0 7px; margin-left:5px; font-size:.66rem; }
    .pill.active .pc { background:rgba(255,255,255,.25); }
    .prog-search { width:100%; border:1.5px solid var(--border); border-radius:8px; padding:8px 12px; font-size:.82rem; color:var(--slate); background:#fafbfc; }
    .prog-search:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
    .prog-list { overflow-y:auto; flex:1; }

    /* Milestone list rows */
    .prow { display:flex; gap:11px; padding:13px 15px; border-bottom:1px solid var(--border); cursor:pointer; transition:background .12s; border-left:3px solid transparent; }
    .prow:hover { background:var(--teal-light); }
    .prow.active { background:var(--teal-light); border-left-color:var(--teal); }
    .prow.inactive { opacity:.62; }
    .prow .dot { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:.82rem; flex-shrink:0; background:var(--teal-light); color:var(--teal); }
    .prow .rmain { min-width:0; flex:1; }
    .prow .rtop { display:flex; align-items:center; gap:6px; }
    .prow .rname { font-size:.83rem; font-weight:700; color:var(--slate); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex:1; min-width:0; }
    .prow .ryrs { font-size:.66rem; color:var(--muted); flex-shrink:0; }
    .prow .rmeta { font-size:.7rem; color:var(--muted); margin-top:4px; display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
    .mini-tag { display:inline-flex; align-items:center; gap:3px; border-radius:6px; padding:1px 7px; font-size:.64rem; font-weight:700; }
    .t-pend { background:#fef3c7; color:#b45309; } .t-grant { background:#dcfce7; color:#15803d; } .t-off { background:#f1f5f9; color:#64748b; }
    .list-empty { text-align:center; padding:50px 20px; color:var(--muted); }
    .list-empty i { font-size:2rem; color:var(--teal-light); margin-bottom:10px; display:block; }

    /* Detail pane */
    .prog-detail-pane { min-height:calc(100vh - 190px); display:flex; flex-direction:column; }
    .pd-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; flex:1; min-height:420px; text-align:center; color:var(--muted); padding:30px; }
    .pd-empty i { font-size:2.8rem; color:var(--teal-light); margin-bottom:14px; }
    .pd-head { padding:22px 26px 18px; border-bottom:1px solid var(--border); }
    .pd-badges { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; align-items:center; }
    .pd-title { font-size:1.25rem; font-weight:800; color:var(--slate); margin:0; line-height:1.3; }
    .pd-desc { font-size:.82rem; color:var(--slate-light); margin-top:8px; }
    .pd-actions { margin-left:auto; display:flex; gap:8px; }
    .pd-benefits { padding:18px 26px; border-bottom:1px solid var(--border); }
    .pd-sec-h { font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.5px; margin:0 0 10px; display:flex; align-items:center; gap:8px; }
    .pd-sec-h .cnt { background:var(--teal-light); color:var(--teal-dark); border-radius:10px; padding:1px 8px; font-size:.66rem; }

    .pd-body { padding:18px 26px; }
    .pd-recip-tools { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:12px; }

    /* Chips / badges */
    .chip { display:inline-block; background:var(--teal-light); color:var(--teal-dark); border-radius:14px; padding:3px 10px; font-size:.72rem; font-weight:600; margin:2px 3px 2px 0; }
    .badge-soft { display:inline-block; border-radius:20px; padding:4px 12px; font-size:.72rem; font-weight:700; }
    .badge-pending { background:#fef3c7; color:#b45309; }
    .badge-granted { background:#dcfce7; color:#15803d; }
    .badge-inactive { background:#f1f5f9; color:#64748b; }
    .tenure-pill { background:#eef2ff; color:#4338ca; border-radius:14px; padding:3px 10px; font-size:.72rem; font-weight:700; }

    /* Tables */
    .prog-table { width:100%; margin:0; }
    .prog-table thead th { position:sticky; top:0; z-index:10; background:var(--surface); font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; border-bottom:2px solid var(--border); white-space:nowrap; padding:11px 14px; }
    .prog-table tbody td { font-size:.83rem; color:var(--slate); vertical-align:middle; padding:11px 14px; border-bottom:1px solid var(--border); }
    .prog-table tbody tr:hover { background:var(--teal-light); }
    .empty-row td { text-align:center; color:var(--muted); padding:24px 16px; font-size:.85rem; }

    .btn-mini { border:1.5px solid var(--border); background:var(--surface); border-radius:8px; padding:5px 11px; font-size:.74rem; font-weight:700; cursor:pointer; transition:all .15s; display:inline-flex; align-items:center; gap:5px; color:var(--slate); }
    .btn-mini.grant { color:var(--success); }
    .btn-mini.grant:hover { background:#f0fdf4; border-color:var(--success); }
    .btn-mini.revoke { color:var(--slate-light); }
    .btn-mini.revoke:hover { background:var(--bg); }
    .btn-mini.edit:hover { background:var(--teal-light); border-color:var(--teal-mid); }
    .btn-mini.del { color:var(--danger); }
    .btn-mini.del:hover { background:#fff5f5; border-color:var(--danger); }

    /* Modal */
    #mdlProgram .modal-content { border-radius:var(--radius-card); border:none; overflow:hidden; }
    #mdlProgram .modal-header { background:var(--teal); color:#fff; border-bottom:none; padding:16px 22px; }
    #mdlProgram .modal-header .modal-title { color:#fff; }
    #mdlProgram .btn-close { filter:brightness(0) invert(1); }
    #mdlProgram .modal-body { background:var(--bg); padding:22px; }
    #mdlProgram .modal-footer { background:var(--surface); border-top:1px solid var(--border); }
    .field-label { font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px; display:block; }
    .field-label .req { color:var(--danger); margin-left:2px; }
    .form-control, .form-select { border:1.5px solid var(--border); border-radius:var(--radius-input); font-size:.875rem; color:var(--slate); background:#fafbfc; padding:.55rem .85rem; }
    .form-control:focus, .form-select:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
    .benefit-row { display:flex; gap:8px; margin-bottom:8px; align-items:center; }
    .benefit-row .form-control { background:#fff; }
    .btn-row-del { border:1.5px solid var(--border); background:#fff; color:var(--danger); border-radius:8px; width:38px; height:38px; flex-shrink:0; cursor:pointer; }
    .btn-row-del:hover { background:#fff5f5; border-color:var(--danger); }
    .btn-add-benefit { background:none; border:1.5px dashed var(--teal-mid); color:var(--teal); border-radius:8px; padding:8px 14px; font-size:.78rem; font-weight:700; cursor:pointer; }
    .btn-add-benefit:hover { background:var(--teal-light); }
    .btn-submit { background:linear-gradient(135deg,#008080,#006666); color:#fff; border:none; border-radius:10px; padding:10px 26px; font-size:.82rem; font-weight:700; letter-spacing:.4px; text-transform:uppercase; cursor:pointer; }
    .btn-submit:hover { color:#fff; transform:translateY(-1px); }
    .form-check-input:checked { background-color:var(--teal); border-color:var(--teal); }
</style>

<div class="prog-shell">

    {{-- Topbar --}}
    <div class="prog-topbar">
        <div>
            <p class="page-title"><i class="fa-solid fa-award me-2" style="color:var(--teal);"></i> Programs &mdash; Tenure Milestones</p>
            <p class="page-sub">Define years-of-service milestones and the benefits employees earn. Eligibility is computed from each employee's hire date.</p>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="prog-stats">
                <div class="prog-stat"><div class="ic t"><i class="fa-solid fa-award"></i></div><div><div class="n" id="statPrograms">0</div><div class="l">Active</div></div></div>
                <div class="prog-stat"><div class="ic w"><i class="fa-solid fa-hourglass-half"></i></div><div><div class="n" id="statPending">0</div><div class="l">Pending</div></div></div>
                <div class="prog-stat"><div class="ic g"><i class="fa-solid fa-circle-check"></i></div><div><div class="n" id="statGranted">0</div><div class="l">Granted</div></div></div>
                <div class="prog-stat"><div class="ic b"><i class="fa-solid fa-calendar-day"></i></div><div><div class="n" id="statUpcoming">0</div><div class="l">Upcoming</div></div></div>
            </div>
            <button class="btn-teal" id="btnAddProgram"><i class="fa-solid fa-plus"></i> Add Milestone</button>
        </div>
    </div>

    {{-- Workspace: milestone rail + detail pane --}}
    <div class="prog-workspace">
        <aside class="prog-pane prog-list-pane">
            <div class="prog-list-head">
                <div class="prog-pills">
                    <button class="pill active" data-mfilter="all">All <span class="pc" id="cAll">0</span></button>
                    <button class="pill" data-mfilter="active">Active <span class="pc" id="cActive">0</span></button>
                    <button class="pill" data-mfilter="inactive">Inactive <span class="pc" id="cInactive">0</span></button>
                </div>
                <input type="text" class="prog-search" id="progSearch" placeholder="Search milestone…">
            </div>
            <div class="prog-list" id="programList">
                <div class="list-empty"><i class="fa-solid fa-spinner fa-spin"></i>Loading…</div>
            </div>
        </aside>

        <section class="prog-pane prog-detail-pane" id="programDetail">
            <div class="pd-empty"><i class="fa-solid fa-trophy"></i><div>Select a milestone to view its recipients and upcoming anniversaries.</div></div>
        </section>
    </div>
</div>

{{-- Add / Edit Milestone Modal --}}
<div class="modal fade" id="mdlProgram" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-award me-2"></i><span id="mdlTitle">Add Milestone</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="programId">
                <div class="row g-3">
                    <div class="col-lg-8">
                        <label class="field-label" for="txtTitle">Milestone Title <span class="req">*</span></label>
                        <input class="form-control" id="txtTitle" type="text" placeholder="e.g. 2 Years of Service">
                        <span class="text-danger small d-block mt-1" id="err-title"></span>
                    </div>
                    <div class="col-lg-4">
                        <label class="field-label" for="txtYears">Years Required <span class="req">*</span></label>
                        <input class="form-control" id="txtYears" type="number" step="0.5" min="0" placeholder="2">
                        <span class="text-danger small d-block mt-1" id="err-years_required"></span>
                    </div>
                    <div class="col-12">
                        <label class="field-label" for="txtDesc">Description</label>
                        <textarea class="form-control" id="txtDesc" rows="2" placeholder="Optional notes about this milestone"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="field-label">Benefits</label>
                        <div id="benefitRows"></div>
                        <button type="button" class="btn-add-benefit mt-1" id="btnAddBenefit"><i class="fa-solid fa-plus me-1"></i> Add Benefit</button>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="chkActive" checked>
                            <label class="form-check-label field-label mb-0" for="chkActive" style="text-transform:none; letter-spacing:0;">Active (counts toward eligibility)</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-mini revoke" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-submit" id="btnSaveProgram">Save Milestone</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/modules/programs.js') }}?v={{ @filemtime(public_path('js/modules/programs.js')) ?: time() }}" defer></script>
@endsection
