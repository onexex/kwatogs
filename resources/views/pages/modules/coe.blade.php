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
    .coe-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:18px; }
    .coe-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .coe-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }

    .stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:18px; }
    .stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 18px; }
    .stat .v { font-size:1.6rem; font-weight:800; color:var(--slate); line-height:1; }
    .stat .l { font-size:.74rem; color:var(--muted); margin-top:6px; font-weight:600; }
    .stat.warn .v { color:var(--warning); } .stat.ok .v { color:var(--success); }

    .panel { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); overflow:hidden; }
    .panel-h { display:flex; align-items:center; gap:12px; flex-wrap:wrap; padding:14px 20px; border-bottom:1px solid var(--border); }
    .panel-h h6 { font-size:.92rem; font-weight:700; color:var(--slate); margin:0; }
    .flt { margin-left:auto; display:flex; gap:8px; flex-wrap:wrap; }
    .flt select, .flt input { border:1.5px solid var(--border); border-radius:8px; padding:6px 11px; font-size:.82rem; color:var(--slate); background:#fafbfc; }
    .flt input:focus, .flt select:focus { border-color:var(--teal); outline:none; }

    table.coe-tbl { width:100%; border-collapse:collapse; }
    .coe-tbl th { font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--slate-light); text-align:left; padding:11px 14px; border-bottom:1px solid var(--border); background:#f8fafc; }
    .coe-tbl td { padding:12px 14px; border-bottom:1px solid var(--border); font-size:.84rem; color:var(--slate); vertical-align:middle; }
    .coe-tbl tr:last-child td { border-bottom:none; }
    .empty-row td { text-align:center; color:var(--muted); padding:36px; }
    .badge-soft { display:inline-block; border-radius:20px; padding:4px 11px; font-size:.7rem; font-weight:700; white-space:nowrap; }
    .b-pending { background:#fef3c7; color:#b45309; } .b-approved { background:#d1fae5; color:#047857; } .b-rejected { background:#fee2e2; color:#b91c1c; }
    .btn-mini { border:none; border-radius:7px; padding:5px 10px; font-size:.74rem; font-weight:700; cursor:pointer; }
    .btn-mini.ok { background:var(--success); color:#fff; } .btn-mini.warn { background:#fee2e2; color:#b91c1c; } .btn-mini.dl { background:var(--teal-light); color:var(--teal-dark); }

    .form-label-sm { font-size:.78rem; font-weight:700; color:var(--slate); margin-bottom:4px; }
    .info-line { font-size:.82rem; color:var(--slate); }
    .info-line b { color:var(--slate); }
</style>

<div class="coe-shell">
    <div class="coe-topbar">
        <p class="page-title"><i class="fa-solid fa-file-signature me-2" style="color:var(--teal);"></i> Certificate of Employment</p>
        <p class="page-sub">Review employee COE requests, approve with your e-signature, and the employee can then download the certificate.</p>
    </div>

    <div class="stat-grid">
        <div class="stat warn"><div class="v">{{ $d['stats']['pending'] }}</div><div class="l">Pending review</div></div>
        <div class="stat ok"><div class="v">{{ $d['stats']['approvedMonth'] }}</div><div class="l">Approved this month</div></div>
        <div class="stat"><div class="v">{{ $d['stats']['rejectedMonth'] }}</div><div class="l">Rejected this month</div></div>
        <div class="stat"><div class="v">{{ $d['stats']['totalMonth'] }}</div><div class="l">Requests this month</div></div>
    </div>

    <div class="panel">
        <div class="panel-h">
            <h6>COE Requests</h6>
            <button class="btn" id="btnIssueCoe" style="background:var(--teal);color:#fff;font-weight:700;border:none;border-radius:8px;padding:7px 14px;font-size:.82rem;">
                <i class="fa-solid fa-file-circle-plus me-1"></i> Issue COE (Separated Employee)
            </button>
            <a href="{{ route('coe.signatories') }}" class="btn" style="background:#fff;color:var(--slate);font-weight:700;border:1px solid var(--border);border-radius:8px;padding:7px 14px;font-size:.82rem;">
                <i class="fa-solid fa-signature me-1"></i> Manage Signatories
            </a>
            <div class="flt">
                <select id="fStatus">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <input type="text" id="fSearch" placeholder="Search name, ID, purpose…">
            </div>
        </div>
        <div style="overflow:auto;">
            <table class="coe-tbl">
                <thead>
                    <tr>
                        <th>Employee</th><th>Purpose</th><th>Copies</th><th>Needed</th>
                        <th>Status</th><th>Reviewed by</th><th class="text-end pe-4">Requested Data</th>
                    </tr>
                </thead>
                <tbody id="tblCoe">
                    <tr class="empty-row"><td colspan="7">Loading…</td></tr>
                </tbody>
            </table>
        </div>
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

<script src="{{ asset('js/modules/coe.js') }}" defer></script>
@endsection
