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

    /* Top header */
    .prog-topbar {
        background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:20px;
        display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;
    }
    .prog-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; letter-spacing:-.2px; }
    .prog-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }

    .btn-teal {
        background:var(--teal); color:#fff; border:none; border-radius:8px; padding:10px 20px;
        font-size:.82rem; font-weight:700; letter-spacing:.3px; cursor:pointer;
        box-shadow:0 4px 14px rgba(0,128,128,.25); transition:all .2s;
        display:inline-flex; align-items:center; gap:8px;
    }
    .btn-teal:hover { background:var(--teal-dark); transform:translateY(-1px); color:#fff; }

    /* Stat cards */
    .prog-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:20px; }
    @media (max-width:900px){ .prog-stats{ grid-template-columns:repeat(2,1fr); } }
    .stat {
        background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); padding:16px 18px; display:flex; align-items:center; gap:14px;
    }
    .stat-ic { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
    .stat-ic.t { background:var(--teal-light); color:var(--teal); }
    .stat-ic.w { background:#fef3c7; color:#b45309; }
    .stat-ic.g { background:#dcfce7; color:#15803d; }
    .stat-ic.b { background:#e0e7ff; color:#4338ca; }
    .stat .num { font-size:1.4rem; font-weight:800; color:var(--slate); line-height:1; }
    .stat .lbl { font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-top:4px; }

    /* Section card */
    .sc { background:var(--surface); border-radius:var(--radius-card); border:1px solid var(--border); box-shadow:var(--shadow-card); margin-bottom:20px; overflow:hidden; }
    .sc-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:14px 22px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); flex-wrap:wrap; }
    .sc-head-left { display:flex; align-items:center; gap:10px; }
    .sc-icon { width:30px; height:30px; border-radius:8px; background:var(--teal-light); color:var(--teal); display:flex; align-items:center; justify-content:center; font-size:.78rem; flex-shrink:0; }
    .sc-title { font-size:.78rem; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; margin:0; }

    /* Filter pills */
    .pill { border:1.5px solid var(--border); background:var(--surface); color:var(--slate-light); border-radius:20px; padding:5px 14px; font-size:.74rem; font-weight:700; cursor:pointer; transition:all .15s; }
    .pill.active { background:var(--teal); color:#fff; border-color:var(--teal); }

    /* Tables */
    .prog-table { width:100%; margin:0; }
    .prog-table thead th { position:sticky; top:0; z-index:10; background:var(--surface); font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; border-bottom:2px solid var(--border); white-space:nowrap; padding:12px 16px; }
    .prog-table tbody td { font-size:.83rem; color:var(--slate); vertical-align:middle; padding:12px 16px; border-bottom:1px solid var(--border); }
    .prog-table tbody tr:hover { background:var(--teal-light); }
    .empty-row td { text-align:center; color:var(--muted); padding:28px 16px; font-size:.85rem; }

    /* Chips / badges */
    .chip { display:inline-block; background:var(--teal-light); color:var(--teal-dark); border-radius:14px; padding:3px 10px; font-size:.72rem; font-weight:600; margin:2px 3px 2px 0; }
    .badge-soft { display:inline-block; border-radius:20px; padding:4px 12px; font-size:.72rem; font-weight:700; }
    .badge-pending { background:#fef3c7; color:#b45309; }
    .badge-granted { background:#dcfce7; color:#15803d; }
    .badge-inactive { background:#f1f5f9; color:#64748b; }
    .tenure-pill { background:#eef2ff; color:#4338ca; border-radius:14px; padding:3px 10px; font-size:.72rem; font-weight:700; }

    .btn-mini { border:1.5px solid var(--border); background:var(--surface); border-radius:8px; padding:5px 11px; font-size:.74rem; font-weight:700; cursor:pointer; transition:all .15s; display:inline-flex; align-items:center; gap:5px; }
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

    {{-- Header --}}
    <div class="prog-topbar">
        <div>
            <p class="page-title">Programs &mdash; Tenure Milestones</p>
            <p class="page-sub">Define years-of-service milestones and the benefits employees earn (e.g. 2 years &rarr; bigas). Eligibility is computed from each employee's hire date.</p>
        </div>
        <button class="btn-teal" id="btnAddProgram"><i class="fa-solid fa-plus"></i> Add Milestone</button>
    </div>

    {{-- Stat cards --}}
    <div class="prog-stats">
        <div class="stat"><div class="stat-ic t"><i class="fa-solid fa-award"></i></div><div><div class="num" id="statPrograms">0</div><div class="lbl">Active Milestones</div></div></div>
        <div class="stat"><div class="stat-ic w"><i class="fa-solid fa-hourglass-half"></i></div><div><div class="num" id="statPending">0</div><div class="lbl">Pending Grants</div></div></div>
        <div class="stat"><div class="stat-ic g"><i class="fa-solid fa-circle-check"></i></div><div><div class="num" id="statGranted">0</div><div class="lbl">Granted</div></div></div>
        <div class="stat"><div class="stat-ic b"><i class="fa-solid fa-calendar-day"></i></div><div><div class="num" id="statUpcoming">0</div><div class="lbl">Upcoming (60d)</div></div></div>
    </div>

    {{-- Reached milestones --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-trophy"></i></div>
                <h5 class="sc-title">Milestone Recipients</h5>
            </div>
            <div class="d-flex gap-2">
                <button class="pill active" data-filter="all">All</button>
                <button class="pill" data-filter="pending">Pending</button>
                <button class="pill" data-filter="granted">Granted</button>
            </div>
        </div>
        <div class="table-responsive" style="max-height:60vh; overflow-y:auto;">
            <table class="prog-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Tenure</th>
                        <th>Milestone</th>
                        <th>Benefits</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody id="tblReached">
                    <tr class="empty-row"><td colspan="7">Loading&hellip;</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Upcoming anniversaries --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-calendar-day"></i></div>
                <h5 class="sc-title">Upcoming Anniversaries (next 60 days)</h5>
            </div>
        </div>
        <div class="table-responsive" style="max-height:45vh; overflow-y:auto;">
            <table class="prog-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Current Tenure</th>
                        <th>Reaches</th>
                        <th>Benefits Due</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody id="tblUpcoming">
                    <tr class="empty-row"><td colspan="6">Loading&hellip;</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Milestone configuration --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-list-check"></i></div>
                <h5 class="sc-title">Milestone Programs</h5>
            </div>
        </div>
        <div class="table-responsive" style="max-height:55vh; overflow-y:auto;">
            <table class="prog-table">
                <thead>
                    <tr>
                        <th>Milestone</th>
                        <th>Years Required</th>
                        <th>Benefits</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody id="tblPrograms">
                    <tr class="empty-row"><td colspan="5">Loading&hellip;</td></tr>
                </tbody>
            </table>
        </div>
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

<script src="{{ asset('js/modules/programs.js') }}" defer></script>
@endsection
