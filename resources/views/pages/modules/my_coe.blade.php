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
    .coe-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .coe-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .coe-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .btn-teal { background:var(--teal); border:none; color:#fff; border-radius:9px; padding:9px 18px; font-size:.85rem; font-weight:700; }
    .btn-teal:hover { background:var(--teal-dark); color:#fff; }
    .btn-teal:disabled { background:var(--muted); cursor:not-allowed; }
    .btn-outline-teal { background:var(--teal-light); border:1px solid var(--teal-mid); color:var(--teal-dark); border-radius:9px; font-weight:700; }
    .btn-outline-teal:hover { background:var(--teal-mid); color:#fff; }

    .card-box { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:18px 22px; margin-bottom:20px; }
    .card-box h6 { font-size:.92rem; font-weight:700; color:var(--slate); margin:0 0 12px; }

    .req-item { display:flex; align-items:flex-start; gap:10px; font-size:.84rem; color:var(--slate); padding:6px 0; }
    .req-item .ico { width:18px; flex:none; }
    .req-ok .ico { color:var(--success); } .req-bad { color:var(--danger); } .req-bad .ico { color:var(--danger); }

    .coe-card { border:1px solid var(--border); border-radius:12px; padding:14px 18px; margin-bottom:12px; }
    .coe-card.pending { border-left:5px solid var(--warning); }
    .coe-card.approved { border-left:5px solid var(--success); }
    .coe-card.rejected { border-left:5px solid var(--danger); }
    .cc-top { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
    .cc-purpose { font-size:.95rem; font-weight:700; color:var(--slate); margin:0; }
    .cc-meta { font-size:.72rem; color:var(--muted); margin-top:4px; }
    .badge-soft { display:inline-block; border-radius:20px; padding:4px 12px; font-size:.72rem; font-weight:700; white-space:nowrap; }
    .b-pending { background:#fef3c7; color:#b45309; } .b-approved { background:#d1fae5; color:#047857; } .b-rejected { background:#fee2e2; color:#b91c1c; }
    .empty-state { text-align:center; padding:50px 20px; color:var(--muted); }
    .empty-state i { font-size:2.2rem; color:var(--teal-light); margin-bottom:10px; }
    .form-label-sm { font-size:.78rem; font-weight:700; color:var(--slate); margin-bottom:4px; }
</style>

<div class="coe-shell">
    <div class="coe-topbar">
        <div>
            <p class="page-title"><i class="fa-solid fa-file-signature me-2" style="color:var(--teal);"></i> My Certificate of Employment</p>
            <p class="page-sub">Request a COE, track its approval, and download it once approved by HR.</p>
        </div>
        <button class="btn btn-teal" id="btnRequestCoe" {{ $requirements['ok'] ? '' : 'disabled' }}>
            <i class="fa-solid fa-plus me-1"></i> Request COE
        </button>
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

    <div id="myCoeList">
        <div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><div>Loading your requests…</div></div>
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

<script src="{{ asset('js/modules/my_coe.js') }}" defer></script>
@endsection
