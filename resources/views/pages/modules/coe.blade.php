@extends('layout.app')
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8;
        --bg:#f1f5f9; --surface:#ffffff; --border:#e2e8f0;
        --danger:#ef4444; --warning:#f59e0b; --success:#10b981;
        --radius-card:14px; --shadow-card:0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }
    .coe-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .coe-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .coe-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .coe-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .empty-state { text-align:center; padding:60px 20px; color:var(--muted); }
    .empty-state i { font-size:2.4rem; color:var(--teal-light); margin-bottom:12px; display:block; }

    /* Stat chips in the topbar */
    .coe-stats { display:flex; gap:10px; flex-wrap:wrap; }
    .coe-stat { display:flex; align-items:center; gap:9px; background:#fafbfc; border:1px solid var(--border); border-radius:10px; padding:8px 14px; }
    .coe-stat .ic { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.8rem; }
    .coe-stat .ic.p { background:#fef3c7; color:#b45309; } .coe-stat .ic.a { background:#dcfce7; color:#15803d; } .coe-stat .ic.r { background:#fee2e2; color:#b91c1c; } .coe-stat .ic.t { background:var(--teal-light); color:var(--teal); }
    .coe-stat .n { font-size:1.05rem; font-weight:800; color:var(--slate); line-height:1; }
    .coe-stat .l { font-size:.64rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-top:2px; }

    /* Action buttons in the topbar */
    .coe-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .coe-btn { border:none; border-radius:8px; padding:8px 15px; font-size:.82rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
    .coe-btn.primary { background:var(--teal); color:#fff; } .coe-btn.primary:hover { background:var(--teal-dark); }
    .coe-btn.ghost { background:#fff; color:var(--slate); border:1px solid var(--border); } .coe-btn.ghost:hover { border-color:var(--teal-mid); }

    /* ── Workspace: list rail + reading pane ── */
    .coe-workspace { display:grid; grid-template-columns:360px 1fr; gap:16px; align-items:start; }
    @media (max-width:900px){ .coe-workspace { grid-template-columns:1fr; } }
    .coe-pane { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); overflow:hidden; }
    .coe-list-pane { display:flex; flex-direction:column; max-height:calc(100vh - 190px); }
    .coe-list-head { padding:12px 14px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .coe-pills { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px; }
    .pill { border:1.5px solid var(--border); background:var(--surface); color:var(--slate-light); border-radius:20px; padding:5px 12px; font-size:.72rem; font-weight:700; cursor:pointer; transition:all .15s; }
    .pill:hover { border-color:var(--teal-mid); }
    .pill.active { background:var(--teal); color:#fff; border-color:var(--teal); }
    .pill .pc { background:rgba(0,0,0,.12); border-radius:10px; padding:0 7px; margin-left:5px; font-size:.66rem; }
    .pill.active .pc { background:rgba(255,255,255,.25); }
    .coe-search { width:100%; border:1.5px solid var(--border); border-radius:8px; padding:8px 12px; font-size:.82rem; color:var(--slate); background:#fafbfc; }
    .coe-search:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
    .coe-list { overflow-y:auto; flex:1; }

    /* List rows */
    .crow { display:flex; gap:11px; padding:13px 15px; border-bottom:1px solid var(--border); cursor:pointer; transition:background .12s; border-left:3px solid transparent; }
    .crow:hover { background:var(--teal-light); }
    .crow.active { background:var(--teal-light); border-left-color:var(--teal); }
    .crow .dot { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:.82rem; flex-shrink:0; }
    .crow.pending .dot { background:#fef3c7; color:#b45309; } .crow.approved .dot { background:#dcfce7; color:#15803d; } .crow.rejected .dot { background:#fee2e2; color:#b91c1c; }
    .crow .rmain { min-width:0; flex:1; }
    .crow .rtitle { font-size:.83rem; font-weight:700; color:var(--slate); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .crow .rmeta { font-size:.7rem; color:var(--muted); margin-top:2px; display:flex; align-items:center; gap:6px; flex-wrap:wrap; }

    .badge-soft { display:inline-block; border-radius:20px; padding:4px 11px; font-size:.7rem; font-weight:700; white-space:nowrap; }
    .b-pending { background:#fef3c7; color:#b45309; } .b-approved { background:#d1fae5; color:#047857; } .b-rejected { background:#fee2e2; color:#b91c1c; }

    /* Reading pane */
    .coe-detail-pane { min-height:calc(100vh - 190px); }
    .cd-head { padding:22px 26px 16px; border-bottom:1px solid var(--border); }
    .cd-badges { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; align-items:center; }
    .cd-title { font-size:1.25rem; font-weight:800; color:var(--slate); margin:0; line-height:1.3; }
    .cd-sub { font-size:.78rem; color:var(--slate-light); margin-top:4px; }
    .cd-body { padding:22px 26px; }
    .cd-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px 24px; }
    .cd-field .k { font-size:.66rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.4px; margin-bottom:3px; }
    .cd-field .v { font-size:.9rem; color:var(--slate); font-weight:600; }
    .cd-note { margin-top:20px; padding:14px 16px; background:#f8fafc; border:1px solid var(--border); border-radius:10px; font-size:.86rem; color:var(--slate); white-space:pre-wrap; line-height:1.6; }
    .cd-reject { margin-top:20px; padding:14px 16px; background:#fef2f2; border:1px solid #fca5a5; border-radius:10px; font-size:.86rem; color:#b91c1c; }
    .cd-reject b { display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; }
    .cd-foot { padding:18px 26px; border-top:1px solid var(--border); display:flex; gap:10px; flex-wrap:wrap; }
    .cd-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; min-height:420px; text-align:center; color:var(--muted); padding:30px; }
    .cd-empty i { font-size:2.8rem; color:var(--teal-light); margin-bottom:14px; }

    .form-label-sm { font-size:.78rem; font-weight:700; color:var(--slate); margin-bottom:4px; }
    .info-line { font-size:.82rem; color:var(--slate); }
    .info-line b { color:var(--slate); }
</style>

<div class="coe-shell">
    <div class="coe-topbar">
        <div>
            <p class="page-title"><i class="fa-solid fa-file-signature me-2" style="color:var(--teal);"></i> Certificate of Employment</p>
            <p class="page-sub">Review requests, approve with your e-signature, and the employee can then download the certificate.</p>
        </div>
        <div class="coe-stats">
            <div class="coe-stat"><div class="ic p"><i class="fa-solid fa-hourglass-half"></i></div><div><div class="n" id="sPending">{{ $d['stats']['pending'] }}</div><div class="l">Pending</div></div></div>
            <div class="coe-stat"><div class="ic a"><i class="fa-solid fa-circle-check"></i></div><div><div class="n" id="sApproved">{{ $d['stats']['approvedMonth'] }}</div><div class="l">Approved</div></div></div>
            <div class="coe-stat"><div class="ic r"><i class="fa-solid fa-circle-xmark"></i></div><div><div class="n" id="sRejected">{{ $d['stats']['rejectedMonth'] }}</div><div class="l">Rejected</div></div></div>
            <div class="coe-stat"><div class="ic t"><i class="fa-solid fa-layer-group"></i></div><div><div class="n" id="sTotal">{{ $d['stats']['totalMonth'] }}</div><div class="l">This month</div></div></div>
        </div>
    </div>

    <div class="coe-actions" style="margin-bottom:16px;">
        <button class="coe-btn primary" id="btnIssueCoe"><i class="fa-solid fa-file-circle-plus"></i> Issue COE (Separated Employee)</button>
        <a href="{{ route('coe.signatories') }}" class="coe-btn ghost"><i class="fa-solid fa-signature"></i> Manage Signatories</a>
    </div>

    <div class="coe-workspace">
        {{-- List rail --}}
        <aside class="coe-pane coe-list-pane">
            <div class="coe-list-head">
                <div class="coe-pills">
                    <button class="pill active" data-filter="all">All <span class="pc" id="cAll">0</span></button>
                    <button class="pill" data-filter="pending">Pending <span class="pc" id="cPending">0</span></button>
                    <button class="pill" data-filter="approved">Approved <span class="pc" id="cApproved">0</span></button>
                    <button class="pill" data-filter="rejected">Rejected <span class="pc" id="cRejected">0</span></button>
                </div>
                <input type="text" class="coe-search" id="coeSearch" placeholder="Search name, ID, purpose…">
            </div>
            <div class="coe-list" id="coeList">
                <div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><div>Loading…</div></div>
            </div>
        </aside>

        {{-- Reading pane --}}
        <section class="coe-pane coe-detail-pane" id="coeDetail">
            <div class="cd-empty"><i class="fa-solid fa-file-lines"></i><div>Select a request from the list to review it.</div></div>
        </section>
    </div>
</div>

{{-- Approve modal --}}
<div class="modal fade" id="mdlApprove" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border:none;border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h6 class="modal-title fw-bold" style="color:var(--slate);">Approve COE Request</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="apId">
                <div class="mb-3 p-3" style="background:var(--teal-light);border-radius:10px;">
                    <div class="info-line"><b id="apName">—</b> <span class="text-muted" id="apEmpId"></span></div>
                    <div class="info-line">Purpose: <b id="apPurpose">—</b> &middot; <span id="apCopies"></span> copy/copies</div>
                </div>

                <div class="my-3 p-2 px-3" style="border:1px solid var(--border);border-radius:8px;background:#fafbfc;">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" id="apIncludeSalary">
                        <label class="form-check-label" for="apIncludeSalary" style="font-size:.84rem;font-weight:600;color:var(--slate);">Include salary / compensation on the certificate</label>
                    </div>
                </div>

                <div class="mb-2">
                    <div class="form-label-sm">Authorized signatory <span class="text-danger">*</span></div>
                    <select class="form-select" id="apSignatory"><option value="">Loading signatories…</option></select>
                    <div class="text-danger small" id="err-signatory_id"></div>
                </div>
                <div id="apSigPreview" class="text-center p-2" style="display:none;border:1px dashed var(--border);border-radius:8px;background:#fafbfc;">
                    <img src="" alt="signature" style="max-height:60px;">
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border);">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn" style="background:var(--success);color:#fff;font-weight:700;" id="btnConfirmApprove"><i class="fa-solid fa-check me-1"></i> Approve &amp; Sign</button>
            </div>
        </div>
    </div>
</div>

{{-- Issue modal — HR issues a COE for a SEPARATED employee (no self-service request). --}}
<div class="modal fade" id="mdlIssue" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border:none;border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h6 class="modal-title fw-bold" style="color:var(--slate);">Issue COE — Separated Employee</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="form-label-sm">Separated employee <span class="text-danger">*</span></div>
                    <select class="form-select" id="isEmployee"><option value="">Loading…</option></select>
                    <div class="text-danger small" id="err-employee_id"></div>
                </div>

                {{-- Clearance status — blocks issuing until complete --}}
                <div id="isClearancePanel" class="mb-3 p-3" style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;display:none;">
                    <div class="form-label-sm mb-1">Offboarding clearance</div>
                    <div id="isClearanceList" style="font-size:.84rem;"></div>
                </div>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <div class="form-label-sm">Purpose <span class="text-danger">*</span></div>
                        <input type="text" class="form-control" id="isPurpose" placeholder="e.g. New employment, Loan, Visa application">
                        <div class="text-danger small" id="err-purpose"></div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-label-sm">Copies <span class="text-danger">*</span></div>
                        <input type="number" class="form-control" id="isCopies" value="1" min="1" max="20">
                        <div class="text-danger small" id="err-copies"></div>
                    </div>
                </div>

                <div class="my-3 p-2 px-3" style="border:1px solid var(--border);border-radius:8px;background:#fafbfc;">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" id="isIncludeSalary">
                        <label class="form-check-label" for="isIncludeSalary" style="font-size:.84rem;font-weight:600;color:var(--slate);">Include salary / compensation on the certificate</label>
                    </div>
                </div>

                <div class="mb-2">
                    <div class="form-label-sm">Authorized signatory <span class="text-danger">*</span></div>
                    <select class="form-select" id="isSignatory"><option value="">Loading signatories…</option></select>
                    <div class="text-danger small" id="err-signatory_id_issue"></div>
                </div>
                <div id="isSigPreview" class="text-center p-2" style="display:none;border:1px dashed var(--border);border-radius:8px;background:#fafbfc;">
                    <img src="" alt="signature" style="max-height:60px;">
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border);">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn" style="background:var(--teal);color:#fff;font-weight:700;" id="btnConfirmIssue" disabled><i class="fa-solid fa-file-signature me-1"></i> Issue &amp; Sign</button>
            </div>
        </div>
    </div>
</div>

{{-- Preview modal — inline PDF render before downloading --}}
<div class="modal fade" id="mdlPreview" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border:none;border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h6 class="modal-title fw-bold" style="color:var(--slate);" id="coePreviewTitle">Certificate Preview</h6>
                <div class="ms-auto d-flex gap-2 align-items-center">
                    <a href="#" id="coePreviewDownload" class="btn" style="background:var(--teal);color:#fff;font-weight:700;border:none;border-radius:8px;padding:6px 14px;font-size:.82rem;">
                        <i class="fa-solid fa-download me-1"></i> Download
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0">
                <iframe id="coePreviewFrame" src="about:blank" title="COE Preview" style="width:100%;height:78vh;border:none;background:#525659;"></iframe>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/modules/coe.js') }}?v={{ @filemtime(public_path('js/modules/coe.js')) ?: time() }}" defer></script>
@endsection
