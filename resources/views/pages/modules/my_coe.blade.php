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
    .btn-teal { background:var(--teal); border:none; color:#fff; border-radius:9px; padding:9px 18px; font-size:.85rem; font-weight:700; }
    .btn-teal:hover { background:var(--teal-dark); color:#fff; }
    .btn-teal:disabled { background:var(--muted); cursor:not-allowed; }
    .btn-outline-teal { background:var(--teal-light); border:1px solid var(--teal-mid); color:var(--teal-dark); border-radius:9px; font-weight:700; }
    .btn-outline-teal:hover { background:var(--teal-mid); color:#fff; }

    .badge-soft { display:inline-block; border-radius:20px; padding:4px 12px; font-size:.72rem; font-weight:700; white-space:nowrap; }
    .b-pending { background:#fef3c7; color:#b45309; } .b-approved { background:#d1fae5; color:#047857; } .b-rejected { background:#fee2e2; color:#b91c1c; }
    .empty-state { text-align:center; padding:60px 20px; color:var(--muted); }
    .empty-state i { font-size:2.4rem; color:var(--teal-light); margin-bottom:12px; }

    .card-box { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:18px 22px; margin-bottom:16px; }
    .card-box h6 { font-size:.92rem; font-weight:700; color:var(--slate); margin:0 0 12px; }
    .req-item { display:flex; align-items:flex-start; gap:10px; font-size:.84rem; color:var(--slate); padding:6px 0; }
    .req-item .ico { width:18px; flex:none; }
    .req-bad { color:var(--danger); } .req-bad .ico { color:var(--danger); }
    .form-label-sm { font-size:.78rem; font-weight:700; color:var(--slate); margin-bottom:4px; }

    /* Stat chips in the topbar */
    .coe-stats { display:flex; gap:10px; flex-wrap:wrap; }
    .coe-stat { display:flex; align-items:center; gap:9px; background:#fafbfc; border:1px solid var(--border); border-radius:10px; padding:8px 14px; }
    .coe-stat .ic { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.8rem; }
    .coe-stat .ic.t { background:var(--teal-light); color:var(--teal); } .coe-stat .ic.p { background:#fef3c7; color:#b45309; } .coe-stat .ic.a { background:#dcfce7; color:#15803d; } .coe-stat .ic.r { background:#fee2e2; color:#b91c1c; }
    .coe-stat .n { font-size:1.05rem; font-weight:800; color:var(--slate); line-height:1; }
    .coe-stat .l { font-size:.64rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-top:2px; }

    /* ── Workspace: list rail + reading pane ── */
    .coe-workspace { display:grid; grid-template-columns:340px 1fr; gap:16px; align-items:start; }
    @media (max-width:820px){ .coe-workspace { grid-template-columns:1fr; } }
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

    /* Reading pane */
    .coe-detail-pane { min-height:calc(100vh - 190px); }
    .cd-head { padding:22px 26px 16px; border-bottom:1px solid var(--border); }
    .cd-badges { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
    .cd-title { font-size:1.25rem; font-weight:800; color:var(--slate); margin:0; line-height:1.3; }
    .cd-meta { font-size:.76rem; color:var(--slate-light); margin-top:10px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
    .cd-meta i { color:var(--muted); margin-right:5px; }
    .cd-section { padding:20px 26px; border-bottom:1px solid var(--border); }
    .cd-section:last-of-type { border-bottom:none; }
    .cd-label { font-size:.68rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px; }
    .cd-remarks { font-size:.9rem; color:var(--slate); white-space:pre-wrap; line-height:1.6; }
    .cd-reject { margin:0 26px 22px; padding:14px 18px; background:#fef2f2; border:1px solid #fecaca; border-radius:12px; font-size:.84rem; color:#b91c1c; }
    .cd-actions { padding:20px 26px; display:flex; gap:10px; flex-wrap:wrap; }
    .cd-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; min-height:420px; text-align:center; color:var(--muted); padding:30px; }
    .cd-empty i { font-size:2.8rem; color:var(--teal-light); margin-bottom:14px; }
</style>

<div class="coe-shell">
    <div class="coe-topbar">
        <div>
            <p class="page-title"><i class="fa-solid fa-file-signature me-2" style="color:var(--teal);"></i> My Certificate of Employment</p>
            <p class="page-sub">Request a COE, track its approval, and download it once approved by HR.</p>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="coe-stats">
                <div class="coe-stat"><div class="ic t"><i class="fa-solid fa-inbox"></i></div><div><div class="n" id="sTotal">0</div><div class="l">Total</div></div></div>
                <div class="coe-stat"><div class="ic p"><i class="fa-solid fa-hourglass-half"></i></div><div><div class="n" id="sPending">0</div><div class="l">Pending</div></div></div>
                <div class="coe-stat"><div class="ic a"><i class="fa-solid fa-circle-check"></i></div><div><div class="n" id="sApproved">0</div><div class="l">Approved</div></div></div>
                <div class="coe-stat"><div class="ic r"><i class="fa-solid fa-circle-xmark"></i></div><div><div class="n" id="sRejected">0</div><div class="l">Rejected</div></div></div>
            </div>
            <button class="btn btn-teal" id="btnRequestCoe" {{ $requirements['ok'] ? '' : 'disabled' }}>
                <i class="fa-solid fa-plus me-1"></i> Request COE
            </button>
        </div>
    </div>

    {{-- Requirements checklist (server-evaluated gate) --}}
    @unless($requirements['ok'])
        <div class="card-box" style="border-left:5px solid var(--danger);">
            <h6><i class="fa-solid fa-triangle-exclamation me-1" style="color:var(--danger);"></i> Requirements not yet met</h6>
            <p style="font-size:.8rem;color:var(--slate-light);margin:0 0 8px;">You cannot request a COE until the following are resolved. Please coordinate with HR.</p>
            @foreach($requirements['missing'] as $m)
                <div class="req-item req-bad"><span class="ico"><i class="fa-solid fa-circle-xmark"></i></span><span>{{ $m }}</span></div>
            @endforeach
        </div>
    @endunless

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
                <input type="text" class="coe-search" id="coeSearch" placeholder="Search purpose, ref no, remarks…">
            </div>
            <div class="coe-list" id="myCoeList">
                <div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><div>Loading…</div></div>
            </div>
        </aside>

        {{-- Reading pane --}}
        <section class="coe-pane coe-detail-pane" id="coeDetail">
            <div class="cd-empty"><i class="fa-solid fa-file-signature"></i><div>Select a request from the list to view its details.</div></div>
        </section>
    </div>
</div>

{{-- Request modal --}}
<div class="modal fade" id="mdlRequest" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h6 class="modal-title fw-bold" style="color:var(--slate);">Request Certificate of Employment</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="form-label-sm">Purpose <span class="text-danger">*</span></div>
                    <input type="text" class="form-control" id="txtPurpose" placeholder="e.g. Bank loan, Visa application, New employment">
                    <div class="text-danger small" id="err-purpose"></div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="form-label-sm">Number of copies <span class="text-danger">*</span></div>
                        <input type="number" class="form-control" id="txtCopies" value="1" min="1" max="20">
                        <div class="text-danger small" id="err-copies"></div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="form-label-sm">Date needed</div>
                        <input type="date" class="form-control" id="txtDateNeeded">
                        <div class="text-danger small" id="err-date_needed"></div>
                    </div>
                </div>
                <div class="mb-1">
                    <div class="form-label-sm">Remarks (optional)</div>
                    <textarea class="form-control" id="txtRemarks" rows="2" placeholder="Anything HR should know…"></textarea>
                    <div class="text-danger small" id="err-remarks"></div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border);">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-teal" id="btnSubmitRequest"><i class="fa-solid fa-paper-plane me-1"></i> Submit Request</button>
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
                    <a href="#" id="coePreviewDownload" class="btn btn-teal btn-sm">
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

<script src="{{ asset('js/modules/my_coe.js') }}?v={{ @filemtime(public_path('js/modules/my_coe.js')) ?: time() }}" defer></script>
@endsection
