@extends('layout.app')
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8;
        --bg:#f1f5f9; --surface:#ffffff; --border:#e2e8f0;
        --danger:#ef4444; --success:#10b981;
        --radius-card:14px; --shadow-card:0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }
    .sig-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .sig-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .sig-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .sig-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .btn-teal { background:var(--teal); border:none; color:#fff; border-radius:9px; padding:9px 18px; font-size:.85rem; font-weight:700; }
    .btn-teal:hover { background:var(--teal-dark); color:#fff; }
    .panel { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); overflow:hidden; }
    table.sig-tbl { width:100%; border-collapse:collapse; }
    .sig-tbl th { font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--slate-light); text-align:left; padding:11px 16px; border-bottom:1px solid var(--border); background:#f8fafc; }
    .sig-tbl td { padding:12px 16px; border-bottom:1px solid var(--border); font-size:.86rem; color:var(--slate); vertical-align:middle; }
    .sig-tbl tr:last-child td { border-bottom:none; }
    .empty-row td { text-align:center; color:var(--muted); padding:36px; }
    .sig-thumb { max-height:42px; max-width:160px; }
    .badge-soft { display:inline-block; border-radius:20px; padding:4px 11px; font-size:.7rem; font-weight:700; }
    .b-active { background:#d1fae5; color:#047857; } .b-inactive { background:#f1f5f9; color:#64748b; }
    .btn-mini { border:none; border-radius:7px; padding:5px 10px; font-size:.74rem; font-weight:700; cursor:pointer; }
    .btn-mini.edit { background:var(--teal-light); color:var(--teal-dark); } .btn-mini.del { background:#fee2e2; color:#b91c1c; }
    .form-label-sm { font-size:.78rem; font-weight:700; color:var(--slate); margin-bottom:4px; }
</style>

<div class="sig-shell">
    <div class="sig-topbar">
        <div>
            <p class="page-title"><i class="fa-solid fa-signature me-2" style="color:var(--teal);"></i> COE Signatories</p>
            <p class="page-sub">Authorized signatories whose e-signature can be stamped on a Certificate of Employment. Pick one per COE when approving/issuing.</p>
        </div>
        <button class="btn btn-teal" id="btnAddSig"><i class="fa-solid fa-plus me-1"></i> Add Signatory</button>
    </div>

    <div class="panel">
        <table class="sig-tbl">
            <thead>
                <tr><th>Signature</th><th>Name</th><th>Title</th><th>Status</th><th class="text-end pe-4">Actions</th></tr>
            </thead>
            <tbody id="tblSig"><tr class="empty-row"><td colspan="5">Loading…</td></tr></tbody>
        </table>
    </div>
</div>

{{-- Add/Edit modal --}}
<div class="modal fade" id="mdlSig" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h6 class="modal-title fw-bold" id="sigMdlTitle" style="color:var(--slate);">Add Signatory</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="sigId">
                <div class="mb-3">
                    <div class="form-label-sm">Name <span class="text-danger">*</span></div>
                    <input type="text" class="form-control" id="sigName" placeholder="e.g. Juan Dela Cruz">
                    <div class="text-danger small" id="err-name"></div>
                </div>
                <div class="mb-3">
                    <div class="form-label-sm">Title</div>
                    <input type="text" class="form-control" id="sigTitle" placeholder="e.g. HR Manager">
                    <div class="text-danger small" id="err-title"></div>
                </div>
                <div class="mb-3">
                    <div class="form-label-sm">Signature image <span class="text-danger" id="sigImgReq">*</span></div>
                    <input type="file" class="form-control" id="sigFile" accept="image/png,image/jpeg">
                    <div class="text-muted" style="font-size:.72rem;margin-top:3px;">PNG with a transparent background works best. Max 2 MB.</div>
                    <div class="text-danger small" id="err-signature"></div>
                    <div id="sigCurrentWrap" class="mt-2" style="display:none;">
                        <div class="text-muted" style="font-size:.72rem;">Current:</div>
                        <img id="sigCurrent" class="sig-thumb" src="">
                    </div>
                </div>
                <div class="mt-3 p-2 px-3" style="border:1px solid var(--border);border-radius:8px;background:#fafbfc;">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" id="sigActive" checked>
                        <label class="form-check-label" for="sigActive" style="font-size:.84rem;font-weight:600;color:var(--slate);">Active (available for selection)</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border);">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-teal" id="btnSaveSig"><i class="fa-solid fa-floppy-disk me-1"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/modules/coe_signatories.js') }}" defer></script>
@endsection
