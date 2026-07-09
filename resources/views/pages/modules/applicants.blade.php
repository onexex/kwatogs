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
    .apl-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; }
    .apl-topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; gap:16px; flex-wrap:wrap; }
    .apl-shell .page-title { font-size:1.5rem; font-weight:800; color:var(--slate); margin:0; }
    .apl-shell .page-sub { color:var(--slate-light); margin:2px 0 0; font-size:.9rem; }
    .btn-teal { background:var(--teal); color:#fff; border:none; border-radius:8px; padding:10px 18px; font-weight:700; cursor:pointer; }
    .btn-teal:hover { background:var(--teal-dark); color:#fff; }

    .apl-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:22px; }
    .stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 18px; display:flex; align-items:center; gap:14px; }
    .stat .stat-ic { width:44px; height:44px; border-radius:12px; display:grid; place-items:center; font-size:1.1rem; color:#fff; background:var(--teal); }
    .stat .stat-ic.pool { background:var(--teal-mid); }
    .stat .stat-ic.hired { background:var(--success); }
    .stat .stat-ic.rejected { background:var(--muted); }
    .stat .num { font-size:1.5rem; font-weight:800; color:var(--slate); line-height:1; }
    .stat .lbl { font-size:.8rem; color:var(--slate-light); margin-top:4px; }

    .sc { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); overflow:hidden; }
    .sc-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 18px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
    .sc-head-left { display:flex; align-items:center; gap:10px; }
    .sc-icon { width:34px; height:34px; border-radius:9px; background:var(--teal-light); color:var(--teal-dark); display:grid; place-items:center; }
    .sc-title { font-size:1.05rem; font-weight:800; color:var(--slate); margin:0; }
    .apl-filters { display:flex; gap:8px; flex-wrap:wrap; }
    .apl-filters .form-select, .apl-filters .form-control { min-width:150px; }

    .apl-table { width:100%; border-collapse:collapse; }
    .apl-table th { text-align:left; font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; color:var(--slate-light); padding:12px 18px; border-bottom:1px solid var(--border); background:#f8fafc; }
    .apl-table td { padding:12px 18px; border-bottom:1px solid var(--border); font-size:.9rem; color:var(--slate); vertical-align:middle; }
    .apl-table tr:hover td { background:#f8fafc; }
    .badge-soft { display:inline-block; padding:3px 10px; border-radius:999px; font-size:.72rem; font-weight:700; background:var(--teal-light); color:var(--teal-dark); }
    .badge-soft.hired { background:#dcfce7; color:#166534; }
    .badge-soft.rejected { background:#f1f5f9; color:var(--slate-light); }
    .badge-soft.pool { background:#e0f2f1; color:var(--teal-dark); }
    .btn-mini { border:1px solid var(--border); background:#fff; border-radius:7px; padding:5px 10px; font-size:.78rem; font-weight:700; cursor:pointer; color:var(--slate); }
    .btn-mini:hover { background:#f1f5f9; }
    .btn-mini.hire { border-color:var(--success); color:var(--success); }
    .btn-mini.hire:hover { background:var(--success); color:#fff; }
    .btn-mini.del { border-color:var(--danger); color:var(--danger); }
    .btn-mini.del:hover { background:var(--danger); color:#fff; }
    .empty-row td { text-align:center; color:var(--muted); padding:30px; }
    .field-label { font-weight:700; font-size:.82rem; color:var(--slate); margin-bottom:4px; display:block; }
    .req { color:var(--danger); }

    /* Inputs — match the branded form styling used across modules. */
    .apl-shell .form-control, .apl-shell .form-select,
    #mdlApplicant .form-control, #mdlApplicant .form-select {
        border:1.5px solid var(--border); border-radius:var(--radius-input);
        font-size:.875rem; color:var(--slate); background:#fafbfc; padding:.55rem .85rem;
    }
    .apl-shell .form-control:focus, .apl-shell .form-select:focus,
    #mdlApplicant .form-control:focus, #mdlApplicant .form-select:focus {
        border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none;
    }

    /* Branded modal (teal header, tinted body) — same as Notices/COE modals. */
    #mdlApplicant .modal-content { border-radius:var(--radius-card); border:none; overflow:hidden; }
    #mdlApplicant .modal-header { background:var(--teal); color:#fff; border-bottom:none; padding:16px 22px; }
    #mdlApplicant .modal-title { color:#fff; font-weight:800; }
    #mdlApplicant .btn-close { filter:brightness(0) invert(1); }
    #mdlApplicant .modal-body { background:var(--bg); padding:22px; }
    #mdlApplicant .modal-footer { background:#fff; border-top:1px solid var(--border); padding:14px 22px; }
</style>

<div class="apl-shell">
    <div class="apl-topbar">
        <div>
            <h1 class="page-title">Applicants</h1>
            <p class="page-sub">Track applications, keep a searchable talent pool, and hire when a role opens.</p>
        </div>
        <button class="btn-teal" id="btnAdd"><i class="fa-solid fa-plus"></i> New Applicant</button>
    </div>

    <div class="apl-stats">
        <div class="stat"><div class="stat-ic"><i class="fa-solid fa-users"></i></div><div><div class="num">{{ $stats['total'] }}</div><div class="lbl">Total Applicants</div></div></div>
        <div class="stat"><div class="stat-ic pool"><i class="fa-solid fa-layer-group"></i></div><div><div class="num">{{ $stats['pool'] }}</div><div class="lbl">In Talent Pool</div></div></div>
        <div class="stat"><div class="stat-ic hired"><i class="fa-solid fa-user-check"></i></div><div><div class="num">{{ $stats['hired'] }}</div><div class="lbl">Hired</div></div></div>
        <div class="stat"><div class="stat-ic rejected"><i class="fa-solid fa-user-slash"></i></div><div><div class="num">{{ $stats['rejected'] }}</div><div class="lbl">Not Pursued</div></div></div>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-list"></i></div>
                <h3 class="sc-title">Applicant List</h3>
            </div>
            <div class="apl-filters">
                <input type="text" id="fSearch" class="form-control" placeholder="Search name / position / contact…">
                <select id="fStatus" class="form-select">
                    <option value="">Pool &amp; Hired</option>
                    <option value="pool">Pool only</option>
                    <option value="hired">Hired</option>
                    <option value="rejected">Not Pursued</option>
                </select>
                <select id="fDept" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}">{{ $d->dep_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="apl-table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Desired Position</th>
                        <th>Department</th>
                        <th>Contact</th>
                        <th>Source</th>
                        <th>Applied</th>
                        <th>Status</th>
                        <th style="width:1%;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tblApplicants">
                    <tr class="empty-row"><td colspan="8">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Add / Edit modal ── --}}
<div class="modal fade" id="mdlApplicant" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="frmApplicant" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-user-tie me-2"></i><span id="mdlApplicantTitle">New Applicant</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="apId" name="id">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="field-label">First Name <span class="req">*</span></label>
                            <input type="text" class="form-control" name="first_name" id="apFirst">
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name" id="apMiddle">
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Last Name <span class="req">*</span></label>
                            <input type="text" class="form-control" name="last_name" id="apLast">
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">Mobile</label>
                            <input type="text" class="form-control" name="mobile" id="apMobile" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">Email</label>
                            <input type="email" class="form-control" name="email" id="apEmail">
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">Desired Position <span class="req">*</span></label>
                            <input type="text" class="form-control" name="desired_position" id="apPosition" list="apPositionList" placeholder="e.g. Dishwasher">
                            <datalist id="apPositionList">
                                @foreach($positions as $p)
                                    <option value="{{ $p->pos_desc }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">Department</label>
                            <select class="form-select" name="department_id" id="apDept">
                                <option value="">— Not specified —</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->dep_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Source</label>
                            <select class="form-select" name="source" id="apSource">
                                <option value="">—</option>
                                <option>Walk-in</option>
                                <option>Referral</option>
                                <option>Online</option>
                                <option>Agency</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Applied On</label>
                            <input type="date" class="form-control" name="applied_at" id="apApplied">
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Rating (1–5)</label>
                            <input type="number" min="1" max="5" class="form-control" name="rating" id="apRating">
                        </div>
                        <div class="col-md-12">
                            <label class="field-label">Résumé (PDF/DOC, optional)</label>
                            <input type="file" class="form-control" name="resume" id="apResume" accept=".pdf,.doc,.docx">
                            <small id="apResumeCurrent" class="text-muted"></small>
                        </div>
                        <div class="col-md-12">
                            <label class="field-label">Notes</label>
                            <textarea class="form-control" name="notes" id="apNotes" rows="3" placeholder="Interview notes, availability, contact remarks…"></textarea>
                        </div>
                    </div>
                    <div id="apErr" class="text-danger mt-2" style="font-size:.85rem;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-teal" id="btnSaveApplicant">Save Applicant</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/modules/applicants.js') }}" defer></script>
@endsection
