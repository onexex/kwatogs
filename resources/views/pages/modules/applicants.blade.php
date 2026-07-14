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
    .apl-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }

    /* ── Topbar (title + stat chips + action) ── */
    .apl-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .apl-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .apl-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .apl-topbar-right { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
    .btn-teal { background:var(--teal); color:#fff; border:none; border-radius:8px; padding:10px 18px; font-weight:700; cursor:pointer; white-space:nowrap; }
    .btn-teal:hover { background:var(--teal-dark); color:#fff; }

    .apl-stats { display:flex; gap:10px; flex-wrap:wrap; }
    .apl-stat { display:flex; align-items:center; gap:9px; background:#fafbfc; border:1px solid var(--border); border-radius:10px; padding:8px 14px; }
    .apl-stat .ic { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.8rem; color:#fff; background:var(--teal); }
    .apl-stat .ic.pool { background:var(--teal-mid); } .apl-stat .ic.hired { background:var(--success); } .apl-stat .ic.rejected { background:var(--muted); }
    .apl-stat .n { font-size:1.05rem; font-weight:800; color:var(--slate); line-height:1; }
    .apl-stat .l { font-size:.64rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-top:2px; }

    .badge-soft { display:inline-block; border-radius:20px; padding:4px 12px; font-size:.72rem; font-weight:700; white-space:nowrap; background:var(--teal-light); color:var(--teal-dark); }
    .badge-soft.hired { background:#dcfce7; color:#166534; }
    .badge-soft.rejected { background:#f1f5f9; color:var(--slate-light); }
    .badge-soft.pool { background:#e0f2f1; color:var(--teal-dark); }

    /* ── Workspace: list rail + profile pane ── */
    .apl-workspace { display:grid; grid-template-columns:360px 1fr; gap:16px; align-items:start; }
    @media (max-width:860px){ .apl-workspace { grid-template-columns:1fr; } }
    .apl-pane { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); overflow:hidden; }
    .apl-list-pane { display:flex; flex-direction:column; max-height:calc(100vh - 190px); }
    .apl-list-head { padding:12px 14px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .apl-pills { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px; }
    .pill { border:1.5px solid var(--border); background:var(--surface); color:var(--slate-light); border-radius:20px; padding:5px 12px; font-size:.72rem; font-weight:700; cursor:pointer; transition:all .15s; }
    .pill:hover { border-color:var(--teal-mid); }
    .pill.active { background:var(--teal); color:#fff; border-color:var(--teal); }
    .pill .pc { background:rgba(0,0,0,.12); border-radius:10px; padding:0 7px; margin-left:5px; font-size:.66rem; }
    .pill.active .pc { background:rgba(255,255,255,.25); }
    .apl-search { width:100%; border:1.5px solid var(--border); border-radius:8px; padding:8px 12px; font-size:.82rem; color:var(--slate); background:#fafbfc; margin-bottom:8px; }
    .apl-search:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
    .apl-deptfilter { width:100%; border:1.5px solid var(--border); border-radius:8px; padding:7px 12px; font-size:.8rem; color:var(--slate); background:#fafbfc; }
    .apl-deptfilter:focus { border-color:var(--teal); outline:none; }
    .apl-list { overflow-y:auto; flex:1; }

    /* List rows */
    .arow { display:flex; gap:11px; padding:13px 15px; border-bottom:1px solid var(--border); cursor:pointer; transition:background .12s; border-left:3px solid transparent; }
    .arow:hover { background:var(--teal-light); }
    .arow.active { background:var(--teal-light); border-left-color:var(--teal); }
    .arow .av { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.82rem; font-weight:800; flex-shrink:0; background:var(--teal-light); color:var(--teal-dark); }
    .arow .rmain { min-width:0; flex:1; }
    .arow .rtitle { font-size:.86rem; font-weight:700; color:var(--slate); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .arow .rpos { font-size:.75rem; color:var(--teal-dark); font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .arow .rmeta { font-size:.7rem; color:var(--muted); margin-top:3px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .arow .rstars { color:var(--warning); letter-spacing:1px; }

    /* ── Profile pane ── */
    .apl-detail-pane { min-height:calc(100vh - 190px); }
    .pd-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; min-height:440px; text-align:center; color:var(--muted); padding:30px; }
    .pd-empty i { font-size:2.8rem; color:var(--teal-light); margin-bottom:14px; }

    .pd-head { padding:24px 28px 18px; border-bottom:1px solid var(--border); display:flex; gap:18px; align-items:flex-start; }
    .pd-avatar { width:64px; height:64px; border-radius:16px; background:var(--teal); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.4rem; font-weight:800; flex-shrink:0; }
    .pd-htext { min-width:0; flex:1; }
    .pd-name { font-size:1.35rem; font-weight:800; color:var(--slate); margin:0; line-height:1.25; }
    .pd-pos { font-size:.92rem; color:var(--teal-dark); font-weight:700; margin-top:3px; }
    .pd-hbadges { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-top:10px; }
    .pd-stars { color:var(--warning); font-size:.95rem; letter-spacing:2px; }
    .pd-stars .off { color:var(--border); }

    .pd-actions { display:flex; gap:8px; flex-wrap:wrap; padding:16px 28px; border-bottom:1px solid var(--border); background:#fafbfc; }
    .btn-act { border:1.5px solid var(--border); background:#fff; border-radius:8px; padding:8px 14px; font-size:.8rem; font-weight:700; cursor:pointer; color:var(--slate); display:inline-flex; align-items:center; gap:7px; text-decoration:none; }
    .btn-act:hover { background:#f1f5f9; color:var(--slate); }
    .btn-act.hire { border-color:var(--success); color:var(--success); }
    .btn-act.hire:hover { background:var(--success); color:#fff; }
    .btn-act.warn { border-color:var(--warning); color:#b45309; }
    .btn-act.warn:hover { background:var(--warning); color:#fff; }
    .btn-act.del { border-color:var(--danger); color:var(--danger); }
    .btn-act.del:hover { background:var(--danger); color:#fff; }
    .btn-act.primary { border-color:var(--teal); color:var(--teal); }
    .btn-act.primary:hover { background:var(--teal); color:#fff; }

    .pd-grid { display:grid; grid-template-columns:1fr 1fr; gap:22px 28px; padding:24px 28px; }
    @media (max-width:560px){ .pd-grid { grid-template-columns:1fr; } }
    .pd-sec { min-width:0; }
    .pd-sec.full { grid-column:1 / -1; }
    .pd-sec h4 { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--slate-light); margin:0 0 10px; display:flex; align-items:center; gap:7px; }
    .pd-sec h4 i { color:var(--teal); }
    .pd-field { margin-bottom:10px; }
    .pd-field .k { font-size:.72rem; color:var(--muted); font-weight:600; }
    .pd-field .v { font-size:.9rem; color:var(--slate); font-weight:600; word-break:break-word; }
    .pd-field .v a { color:var(--teal-dark); font-weight:700; }
    .pd-quals { font-size:.88rem; color:var(--slate); line-height:1.6; white-space:pre-wrap; background:#f8fafc; border:1px solid var(--border); border-radius:10px; padding:12px 14px; }
    .pd-notes { font-size:.88rem; color:var(--slate); line-height:1.6; white-space:pre-wrap; background:#fffdf5; border:1px solid #fde9c8; border-radius:10px; padding:12px 14px; }
    .pd-resume { display:flex; align-items:center; gap:12px; background:var(--teal-light); border:1px solid var(--teal-mid); border-radius:10px; padding:12px 14px; }
    .pd-resume .ri { width:38px; height:38px; border-radius:9px; background:#fff; color:var(--teal); display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
    .pd-resume .rn { font-size:.82rem; font-weight:700; color:var(--teal-dark); }
    .pd-resume a.open { margin-left:auto; background:var(--teal); color:#fff; border-radius:7px; padding:6px 12px; font-size:.76rem; font-weight:700; text-decoration:none; white-space:nowrap; }
    .pd-reject { grid-column:1 / -1; background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:12px 16px; }
    .pd-reject .k { font-size:.72rem; font-weight:800; color:#b91c1c; text-transform:uppercase; letter-spacing:.04em; }
    .pd-reject .v { font-size:.86rem; color:#7f1d1d; margin-top:3px; }
    .pd-muted { color:var(--muted); font-weight:500; }

    .empty-state { text-align:center; padding:50px 20px; color:var(--muted); font-size:.85rem; }
    .empty-state i { font-size:2.2rem; color:var(--teal-light); margin-bottom:10px; display:block; }
    .field-label { font-weight:700; font-size:.82rem; color:var(--slate); margin-bottom:4px; display:block; }
    .req { color:var(--danger); }

    /* Inputs — match the branded form styling used across modules. */
    #mdlApplicant .form-control, #mdlApplicant .form-select {
        border:1.5px solid var(--border); border-radius:var(--radius-input);
        font-size:.875rem; color:var(--slate); background:#fafbfc; padding:.55rem .85rem;
    }
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
            <p class="page-title"><i class="fa-solid fa-user-tie me-2" style="color:var(--teal);"></i> Applicants</p>
            <p class="page-sub">Your talent pool — search candidates, review profiles, and hire when a role opens.</p>
        </div>
        <div class="apl-topbar-right">
            <div class="apl-stats">
                <div class="apl-stat"><div class="ic"><i class="fa-solid fa-users"></i></div><div><div class="n">{{ $stats['total'] }}</div><div class="l">Total</div></div></div>
                <div class="apl-stat"><div class="ic pool"><i class="fa-solid fa-layer-group"></i></div><div><div class="n">{{ $stats['pool'] }}</div><div class="l">In Pool</div></div></div>
                <div class="apl-stat"><div class="ic hired"><i class="fa-solid fa-user-check"></i></div><div><div class="n">{{ $stats['hired'] }}</div><div class="l">Hired</div></div></div>
                <div class="apl-stat"><div class="ic rejected"><i class="fa-solid fa-user-slash"></i></div><div><div class="n">{{ $stats['rejected'] }}</div><div class="l">Not Pursued</div></div></div>
            </div>
            <button class="btn-teal" id="btnAdd"><i class="fa-solid fa-plus"></i> New Applicant</button>
        </div>
    </div>

    <div class="apl-workspace">
        {{-- List rail --}}
        <aside class="apl-pane apl-list-pane">
            <div class="apl-list-head">
                <div class="apl-pills">
                    <button class="pill active" data-filter="">Pool &amp; Hired</button>
                    <button class="pill" data-filter="pool">Pool</button>
                    <button class="pill" data-filter="hired">Hired</button>
                    <button class="pill" data-filter="rejected">Not Pursued</button>
                </div>
                <input type="text" class="apl-search" id="fSearch" placeholder="Search name / position / skills / contact…">
                <select id="fDept" class="apl-deptfilter">
                    <option value="">All Departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}">{{ $d->dep_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="apl-list" id="aplList">
                <div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><div>Loading…</div></div>
            </div>
        </aside>

        {{-- Profile pane --}}
        <section class="apl-pane apl-detail-pane" id="aplDetail">
            <div class="pd-empty"><i class="fa-solid fa-id-card"></i><div>Select an applicant from the list to view their profile.</div></div>
        </section>
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
                        <div class="col-md-6">
                            <label class="field-label">Highest Education</label>
                            <select class="form-select" name="highest_education" id="apEducation">
                                <option value="">— Select —</option>
                                @foreach(\App\Models\Applicant::EDUCATION_LEVELS as $lvl)
                                    <option value="{{ $lvl }}">{{ $lvl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">Years of Experience</label>
                            <input type="number" step="0.5" min="0" max="60" class="form-control" name="years_experience" id="apExperience" placeholder="e.g. 3">
                        </div>
                        <div class="col-md-12">
                            <label class="field-label">Skills / Qualifications</label>
                            <textarea class="form-control" name="qualifications" id="apQualifications" rows="2" placeholder="e.g. Food handling certificate, 2 yrs kitchen experience, can work night shift…"></textarea>
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

<script src="{{ asset('js/modules/applicants.js') }}?v={{ @filemtime(public_path('js/modules/applicants.js')) ?: time() }}" defer></script>
@endsection
