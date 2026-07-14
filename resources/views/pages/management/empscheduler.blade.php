@extends('layout.app')

@section('content')
<style>
    /* ── Design tokens (shared workspace look) ── */
    :root {
        --teal:         #008080;
        --teal-dark:    #006666;
        --teal-mid:     #4db6ac;
        --teal-light:   #e0f2f1;
        --slate:        #334155;
        --slate-light:  #64748b;
        --muted:        #94a3b8;
        --bg:           #f1f5f9;
        --surface:      #ffffff;
        --border:       #e2e8f0;
        --danger:       #ef4444;
        --success:      #10b981;
        --warning:      #f59e0b;
        --radius-card:  14px;
        --radius-input: 8px;
        --shadow-card:  0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }

    .sch-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }

    /* ── Topbar ── */
    .sch-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .sch-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .sch-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .sch-topbar-right { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }

    .sch-stats { display:flex; gap:10px; flex-wrap:wrap; }
    .sch-stat { display:flex; align-items:center; gap:9px; background:#fafbfc; border:1px solid var(--border); border-radius:10px; padding:8px 14px; }
    .sch-stat .ic { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.8rem; }
    .sch-stat .ic.t { background:var(--teal-light); color:var(--teal); }
    .sch-stat .ic.d { background:#fee2e2; color:#b91c1c; }
    .sch-stat .ic.b { background:#e0e7ff; color:#4338ca; }
    .sch-stat .n { font-size:1.05rem; font-weight:800; color:var(--slate); line-height:1; }
    .sch-stat .l { font-size:.64rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-top:2px; }

    .btn-add-schedule { background:var(--teal); color:#fff; border:none; border-radius:8px; padding:10px 20px; font-size:.82rem; font-weight:700; letter-spacing:.3px; cursor:pointer; box-shadow:0 4px 14px rgba(0,128,128,.25); transition:all .2s; display:inline-flex; align-items:center; gap:8px; }
    .btn-add-schedule:hover { background:var(--teal-dark); transform:translateY(-1px); box-shadow:0 6px 20px rgba(0,128,128,.35); color:#fff; }

    /* ── Workspace: list rail + detail pane ── */
    .sch-workspace { display:grid; grid-template-columns:360px 1fr; gap:16px; align-items:start; }
    @media (max-width:900px){ .sch-workspace { grid-template-columns:1fr; } }
    .sch-pane { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); overflow:hidden; }
    .sch-list-pane { display:flex; flex-direction:column; max-height:calc(100vh - 200px); }
    .sch-list-head { padding:12px 14px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); }

    .sch-pills { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px; }
    .pill { border:1.5px solid var(--border); background:var(--surface); color:var(--slate-light); border-radius:20px; padding:5px 12px; font-size:.72rem; font-weight:700; cursor:pointer; transition:all .15s; }
    .pill:hover { border-color:var(--teal-mid); }
    .pill.active { background:var(--teal); color:#fff; border-color:var(--teal); }
    .pill .pc { background:rgba(0,0,0,.12); border-radius:10px; padding:0 7px; margin-left:5px; font-size:.66rem; }
    .pill.active .pc { background:rgba(255,255,255,.25); }

    .sch-search { width:100%; border:1.5px solid var(--border); border-radius:8px; padding:8px 12px; font-size:.82rem; color:var(--slate); background:#fafbfc; }
    .sch-search:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }

    /* Unscheduled controls (inside list head) */
    .unsched-controls { margin-top:10px; display:flex; flex-direction:column; gap:8px; }
    .unsched-controls .form-select, .unsched-controls .form-control { border:1.5px solid var(--border); border-radius:8px; font-size:.8rem; background:#fafbfc; color:var(--slate); padding:.4rem .6rem; }
    .unsched-controls .form-select:focus, .unsched-controls .form-control:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
    .unsched-controls .mini-btn { background:var(--surface); color:var(--slate-light); border:1.5px solid var(--border); border-radius:8px; padding:6px 10px; font-size:.72rem; font-weight:700; cursor:pointer; transition:all .15s; }
    .unsched-controls .mini-btn:hover { background:var(--teal-light); border-color:var(--teal-mid); }
    .unsched-scope { font-size:.68rem; color:var(--muted); font-weight:600; }

    .sch-list { overflow-y:auto; flex:1; min-height:120px; }
    .sch-list-foot { border-top:1px solid var(--border); padding:8px 12px; background:#fafbfc; }
    .sch-list-foot .pagination { margin:0; }

    /* ── List rows ── */
    .srow { display:flex; gap:11px; padding:12px 15px; border-bottom:1px solid var(--border); cursor:pointer; transition:background .12s; border-left:3px solid transparent; align-items:center; }
    .srow:hover { background:var(--teal-light); }
    .srow.active { background:var(--teal-light); border-left-color:var(--teal); }
    .srow .avatar { width:36px; height:36px; border-radius:50%; background:var(--teal-light); color:var(--teal-dark); display:inline-flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:800; letter-spacing:.3px; border:1px solid var(--teal-mid); flex-shrink:0; }
    .srow.miss .avatar { background:#fff5f5; color:var(--danger); border-color:var(--danger); }
    .srow .rmain { min-width:0; flex:1; }
    .srow .rtitle { font-size:.82rem; font-weight:700; color:var(--slate); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; text-transform:uppercase; }
    .srow .rmeta { font-size:.7rem; color:var(--muted); margin-top:3px; display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
    .srow .miss-badge { background:var(--danger); color:#fff; border-radius:20px; padding:1px 8px; font-size:.62rem; font-weight:700; }

    .dept-chip { display:inline-block; background:#eef2ff; color:#4338ca; border:1px solid #c7d2fe; border-radius:999px; padding:2px 9px; font-size:.66rem; font-weight:700; white-space:nowrap; }
    .shift-chip { display:inline-block; background:var(--teal-light); color:var(--teal-dark); border:1px solid var(--teal-mid); border-radius:999px; padding:2px 9px; font-size:.66rem; font-weight:700; white-space:nowrap; }

    .empty-state { text-align:center; padding:50px 20px; color:var(--muted); }
    .empty-state i { font-size:2rem; color:var(--teal-light); margin-bottom:10px; display:block; }
    .empty-state .es-loading i { color:var(--teal); }

    /* ── Detail pane ── */
    .sch-detail-pane { min-height:calc(100vh - 200px); }
    .sd-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; min-height:440px; text-align:center; color:var(--muted); padding:30px; }
    .sd-empty i { font-size:2.8rem; color:var(--teal-light); margin-bottom:14px; }

    .sd-head { padding:22px 26px 18px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
    .sd-head .avatar-lg { width:48px; height:48px; border-radius:50%; background:var(--teal-light); color:var(--teal-dark); display:inline-flex; align-items:center; justify-content:center; font-size:.9rem; font-weight:800; border:1px solid var(--teal-mid); flex-shrink:0; }
    .sd-head.miss .avatar-lg { background:#fff5f5; color:var(--danger); border-color:var(--danger); }
    .sd-head .sd-name { font-size:1.15rem; font-weight:800; color:var(--slate); margin:0; text-transform:uppercase; letter-spacing:-.2px; }
    .sd-head .sd-sub { font-size:.74rem; color:var(--slate-light); margin-top:4px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .sd-actions { margin-left:auto; display:flex; gap:8px; }

    .sd-body { padding:22px 26px; }
    .sd-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    @media (max-width:560px){ .sd-grid { grid-template-columns:1fr; } }
    .sd-field { background:#fafbfc; border:1px solid var(--border); border-radius:10px; padding:12px 14px; }
    .sd-field .fl { font-size:.66rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px; display:flex; align-items:center; gap:6px; }
    .sd-field .fl i { color:var(--teal); }
    .sd-field .fv { font-size:.9rem; font-weight:700; color:var(--slate); }
    .sd-field .fv .time-chip { display:inline-block; margin-left:6px; background:#eef2f6; color:var(--slate); border:1px solid var(--border); border-radius:6px; padding:1px 8px; font-size:.78rem; font-weight:700; }

    .icon-action-btn { width:34px; height:34px; border-radius:8px; border:1.5px solid var(--border); background:var(--surface); display:inline-flex; align-items:center; justify-content:center; transition:all .15s; cursor:pointer; }
    .icon-action-btn:hover { border-color:var(--teal-mid); background:var(--teal-light); }
    .icon-action-btn.danger:hover { border-color:var(--danger); background:#fff5f5; }
    .btn-sd { display:inline-flex; align-items:center; gap:7px; border-radius:8px; padding:8px 16px; font-size:.78rem; font-weight:700; cursor:pointer; transition:all .15s; border:1.5px solid var(--border); background:var(--surface); color:var(--slate); }
    .btn-sd.primary { background:var(--teal); border-color:var(--teal); color:#fff; }
    .btn-sd.primary:hover { background:var(--teal-dark); }
    .btn-sd.danger { color:var(--danger); }
    .btn-sd.danger:hover { background:#fff5f5; border-color:var(--danger); }

    .sd-note { margin:0 26px 22px; font-size:.72rem; color:var(--slate-light); background:#f8fafc; border:1px dashed var(--border); border-radius:8px; padding:10px 14px; display:flex; gap:8px; align-items:flex-start; }
    .sd-note i { color:var(--warning); margin-top:1px; }

    /* ── Unscheduled per-day breakdown (detail pane) ── */
    .unsched-legend { display:flex; gap:16px; align-items:center; font-size:.72rem; color:var(--slate-light); margin-bottom:14px; font-weight:600; flex-wrap:wrap; }
    .unsched-legend .dot { width:10px; height:10px; border-radius:3px; display:inline-block; margin-right:5px; }
    .day-grid { display:flex; flex-wrap:wrap; gap:8px; }
    .day-chip { display:inline-flex; align-items:center; gap:6px; border-radius:8px; padding:6px 11px; font-size:.72rem; font-weight:700; white-space:nowrap; }
    .day-chip.ok   { background:var(--teal-light); color:var(--teal-dark); border:1px solid var(--teal-mid); }
    .day-chip.miss { background:#fff5f5; color:var(--danger); border:1px dashed var(--danger); }
    .day-chip .chip-date { font-weight:800; }
    .day-chip.miss .chip-add { border:none; background:var(--danger); color:#fff; border-radius:50%; width:16px; height:16px; line-height:1; font-size:.62rem; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; }

    /* ── Field helpers (modal) ── */
    .field-label { font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px; display:block; }
    .field-label .req { color:var(--danger); margin-left:2px; }
    .sub-divider { display:flex; align-items:center; gap:10px; margin:6px 0 18px; }
    .sub-divider span { font-size:.73rem; font-weight:700; color:var(--teal); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; }
    .sub-divider::after { content:''; flex-grow:1; height:1px; background:var(--border); }

    .day-label { background:var(--surface); color:var(--slate-light); border:1.5px solid var(--border) !important; font-size:.75rem; font-weight:700; transition:all .15s; }
    .day-check:checked + .day-label { background:var(--teal) !important; color:#fff !important; border-color:var(--teal) !important; }

    .employee-tag { background:var(--teal-light); color:var(--teal-dark); border:1px solid var(--teal-mid); font-weight:700; font-size:.72rem; }

    /* ── Modal styling ── */
    #mdlEmpScheduler .modal-content { border-radius:var(--radius-card); border:none; overflow:hidden; }
    #mdlEmpScheduler .modal-header { background:var(--teal); color:#fff; border-bottom:none; padding:16px 22px; }
    #mdlEmpScheduler .modal-header .modal-title, #mdlEmpScheduler .modal-header .modal-title i { color:#fff; }
    #mdlEmpScheduler .btn-close { filter:brightness(0) invert(1); }
    #mdlEmpScheduler .modal-body { background:var(--bg); padding:22px; }
    #mdlEmpScheduler .modal-footer { background:var(--surface); border-top:1px solid var(--border); }
    #mdlEmpScheduler .form-control, #mdlEmpScheduler .form-select { border:1.5px solid var(--border); border-radius:var(--radius-input); font-size:.875rem; color:var(--slate); background:#fafbfc; transition:border-color .15s, box-shadow .15s; padding:.55rem .85rem; }
    #mdlEmpScheduler .form-control:focus, #mdlEmpScheduler .form-select:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
    #mdlEmpScheduler .bg-light.p-3.rounded-3 { background:var(--surface) !important; border:1px solid var(--border); }

    .btn-submit-schedule { background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%); color:#fff; border:none; border-radius:10px; padding:10px 26px; font-size:.82rem; font-weight:700; letter-spacing:.4px; text-transform:uppercase; cursor:pointer; box-shadow:0 4px 14px rgba(245,158,11,.3); transition:all .2s; }
    .btn-submit-schedule:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(245,158,11,.4); color:#fff; }
    .btn-cancel-schedule { background:var(--surface); color:var(--slate-light); border:1.5px solid var(--border); border-radius:10px; padding:10px 22px; font-size:.82rem; font-weight:700; letter-spacing:.4px; text-transform:uppercase; cursor:pointer; transition:all .2s; }
    .btn-cancel-schedule:hover { background:var(--bg); }

    #btnAddEmployee { background:var(--teal); border-color:var(--teal); }
    #btnAddEmployee:hover { background:var(--teal-dark); border-color:var(--teal-dark); }

    #paginationContainer .page-link { color:var(--slate-light); border:0; }
    #paginationContainer .page-item.active .page-link { background:var(--teal) !important; border-color:var(--teal) !important; color:#fff !important; }
</style>

<div class="sch-shell">

    {{-- ── Topbar ── --}}
    <div class="sch-topbar">
        <div>
            <p class="page-title"><i class="fa-solid fa-calendar-days me-2" style="color:var(--teal);"></i> Employee Scheduler</p>
            <p class="page-sub">Settings · Scheduling Module — assign shifts and spot employees with no schedule.</p>
        </div>
        <div class="sch-topbar-right">
            <div class="sch-stats">
                <div class="sch-stat"><div class="ic t"><i class="fa-solid fa-calendar-check"></i></div><div><div class="n" id="sTotal">0</div><div class="l">Schedules</div></div></div>
                <div class="sch-stat"><div class="ic d"><i class="fa-solid fa-user-clock"></i></div><div><div class="n" id="sUnsched">0</div><div class="l">Unscheduled</div></div></div>
                <div class="sch-stat"><div class="ic b"><i class="fa-solid fa-building"></i></div><div><div class="n" id="sDepts">{{ count($departments) }}</div><div class="l">Departments</div></div></div>
            </div>
            <button class="btn-add-schedule" id="btnCreateModal" data-bs-toggle="modal" data-bs-target="#mdlEmpScheduler">
                <i class="fa-solid fa-plus"></i> Add Schedule
            </button>
        </div>
    </div>

    {{-- ── Workspace ── --}}
    <div class="sch-workspace">

        {{-- List rail --}}
        <aside class="sch-pane sch-list-pane">
            <div class="sch-list-head">
                <div class="sch-pills">
                    <button class="pill active" data-mode="schedules"><i class="fa-solid fa-list-ul me-1"></i>All Schedules <span class="pc" id="cSched">0</span></button>
                    <button class="pill" data-mode="unscheduled"><i class="fa-solid fa-user-clock me-1"></i>Unscheduled <span class="pc" id="cUnsched">0</span></button>
                </div>

                {{-- Schedules-mode search --}}
                <div id="schedControls">
                    <input type="text" id="txtSearchEmp" class="sch-search" placeholder="Search by employee name…">
                    <div class="d-flex justify-content-end mt-2">
                        <select id="selPerPage" class="form-select form-select-sm" style="width:auto; border:1.5px solid var(--border); border-radius:8px; font-size:.72rem;">
                            <option value="10">10 / page</option>
                            <option value="25">25 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                        </select>
                    </div>
                </div>

                {{-- Unscheduled-mode controls --}}
                <div id="unschedControls" class="unsched-controls d-none">
                    <select id="selDepartment" class="form-select">
                        <option value="">All Departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->dep_name }}</option>
                        @endforeach
                    </select>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="date" id="fromDate" class="form-control" title="From">
                        </div>
                        <div class="col-6">
                            <input type="date" id="toDate" class="form-control" title="To">
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="mini-btn flex-fill" id="btnThisMonth">This Month</button>
                        <button type="button" class="mini-btn flex-fill" id="btnClearRange">Never Scheduled</button>
                    </div>
                    <span class="unsched-scope" id="unscheduledScopeLabel">Never scheduled</span>
                </div>
            </div>

            <div class="sch-list" id="schList">
                <div class="empty-state es-loading"><i class="fa-solid fa-spinner fa-spin"></i>Loading…</div>
            </div>

            <div class="sch-list-foot d-none" id="listFoot">
                <div id="paginationContainer"></div>
            </div>
        </aside>

        {{-- Detail pane --}}
        <section class="sch-pane sch-detail-pane" id="schDetail">
            <div class="sd-empty"><i class="fa-solid fa-calendar-day"></i><div>Select a schedule from the list to view its details.</div></div>
        </section>
    </div>
</div>

{{-- ── Add / Edit modal (unchanged behavior) ── --}}
<div class="modal fade" id="mdlEmpScheduler" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-calendar-days me-2"></i> Employee Schedule
                </h5>
                <button type="button" class="btn-close closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="frmEmpScheduler" autocomplete="off">
                    <input type="hidden" id="schedule_id">

                    <div class="sub-divider"><span>Employees</span></div>
                    <div class="mb-4">
                        <label class="field-label" for="selEmployee">Select Employee <span class="req">*</span></label>
                        <div class="input-group">
                            <select class="form-select text-uppercase" id="selEmployee" name="employee_id">
                                <option selected disabled value="">Choose...</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->empID }}">{{ strtoupper($emp->lname) }}, {{ strtoupper($emp->fname) }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-primary" id="btnAddEmployee">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <span class="text-danger small error-text employee_ids_error"></span>

                        {{-- Employee Tags Container --}}
                        <div id="employeeTagsContainer" class="d-flex flex-wrap gap-2 mt-2"></div>
                        <input type="hidden" id="employeeArrayInput">
                    </div>

                    <div class="sub-divider"><span>Schedule Period</span></div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="field-label" for="sched_start_date">Start Date</label>
                            <input type="date" class="form-control" name="sched_start_date" id="sched_start_date">
                            <span class="text-danger small error-text sched_start_date_error"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label" for="sched_end_date">End Date</label>
                            <input type="date" class="form-control" name="sched_end_date" id="sched_end_date">
                            <span class="text-danger small error-text sched_end_date_error"></span>
                        </div>
                    </div>

                    <div class="mb-4 p-3 rounded-3 bg-light">
                        <label class="field-label d-block mb-2">Repeat on these Days:</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input day-check d-none" type="checkbox" value="{{ $day }}" id="chk{{ $day }}">
                                    <label class="badge rounded-pill border py-2 px-3 fw-medium day-label" for="chk{{ $day }}" style="cursor: pointer;">
                                        {{ $day }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="sub-divider"><span>Working Hours</span></div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="field-label" for="sched_in">Time In</label>
                            <input type="time" class="form-control" name="sched_in" id="sched_in">
                            <span class="text-danger small error-text sched_in_error"></span>
                        </div>
                        <div class="col-md-3">
                            <label class="field-label" for="sched_out">Time Out</label>
                            <input type="time" class="form-control" name="sched_out" id="sched_out">
                            <span class="text-danger small error-text sched_out_error"></span>
                        </div>
                        <div class="col-md-3">
                            <label class="field-label" for="break_start">Break Start</label>
                            <input type="time" class="form-control" name="break_start" id="break_start">
                            <span class="text-danger small error-text break_start_error"></span>
                        </div>
                        <div class="col-md-3">
                            <label class="field-label" for="break_end">Break End</label>
                            <input type="time" class="form-control" name="break_end" id="break_end">
                            <span class="text-danger small error-text break_end_error"></span>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="field-label" for="shift_type">Shift Type</label>
                        <input type="text" class="form-control" name="shift_type" id="shift_type" placeholder="e.g. Regular Morning">
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel-schedule" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="btnSaveScheduler" class="btn-submit-schedule">Save Schedule</button>
            </div>
        </div>
    </div>
</div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function(){

    // Escape user-supplied strings before injecting into HTML.
    const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    const trimSeconds = (t) => t ? String(t).substring(0, 5) : '';
    const initialsOf = (name) => (String(name || '').split(',').map(p => p.trim()[0] || '').join('').substring(0, 2).toUpperCase()) || '?';

    // ── Shared state ─────────────────────────────────────────────
    let currentMode = 'schedules';        // 'schedules' | 'unscheduled'
    let scheduleRows = {};                 // id -> list row (for detail header)
    let selectedScheduleId = null;
    let unschedState = { employees: [], range: [], total_days: 0, perDay: false };
    let selectedEmpID = null;

    // ── Checkbox pill helper (modal) ─────────────────────────────
    $('.day-check').on('change', function() {
        if($(this).is(':checked')) {
            $(this).next('.day-label').addClass('bg-primary text-white border-primary').removeClass('bg-transparent text-muted');
        } else {
            $(this).next('.day-label').removeClass('bg-primary text-white border-primary').addClass('bg-transparent text-muted');
        }
    });

    /* =========================================================
     *  SCHEDULES MODE
     * ========================================================= */
    const loadSchedules = (search = '', page = 1, perPage = 10) => {
        $('#schList').html('<div class="empty-state es-loading"><i class="fa-solid fa-spinner fa-spin"></i>Loading…</div>');

        axios.get("{{ route('employee-schedules.get') }}", { params: { search, page, per_page: perPage } })
            .then(res => {
                const data = res.data.data || [];
                $('#sTotal').text(res.data.total ?? data.length);
                $('#cSched').text(res.data.total ?? data.length);

                scheduleRows = {};
                let html = '';

                if (data.length) {
                    data.forEach(s => {
                        scheduleRows[s.id] = s;
                        const name  = (s.employee_name || '').trim();
                        const dept  = s.department_name
                            ? `<span class="dept-chip">${escapeHtml(s.department_name)}</span>`
                            : `<span class="text-muted" style="font-size:.66rem;font-style:italic;">No department</span>`;
                        const shift = s.shift_type ? `<span class="shift-chip">${escapeHtml(s.shift_type)}</span>` : '';
                        html += `
                            <div class="srow ${selectedScheduleId == s.id ? 'active' : ''}" data-id="${s.id}">
                                <span class="avatar">${initialsOf(name)}</span>
                                <div class="rmain">
                                    <div class="rtitle">${escapeHtml(name)}</div>
                                    <div class="rmeta">
                                        ${dept}${shift}
                                        <span><i class="fa-regular fa-calendar me-1"></i>${escapeHtml(s.sched_start_date)}</span>
                                    </div>
                                </div>
                            </div>`;
                    });
                } else {
                    html = '<div class="empty-state"><i class="fa-solid fa-calendar-xmark"></i>No scheduling records found.</div>';
                }
                $('#schList').html(html);

                buildPagination(res.data.last_page, res.data.current_page);

                // Auto-open the first row on a fresh listing when nothing is selected.
                if (data.length && (selectedScheduleId === null || !scheduleRows[selectedScheduleId])) {
                    selectSchedule(data[0].id);
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Fetch Error', text: 'Unable to load schedules.' }));
    };

    const buildPagination = (lastPage, currentPage) => {
        let pagination = '';
        if (lastPage > 1) {
            const pageItem = (label, p, { active = false, disabled = false } = {}) => disabled
                ? `<li class="page-item disabled"><span class="page-link rounded text-muted">${label}</span></li>`
                : `<li class="page-item ${active ? 'active' : ''}"><a href="#" class="page-link rounded ${active ? 'shadow-sm' : 'text-muted'}" data-page="${p}">${label}</a></li>`;
            const ellipsis = () => `<li class="page-item disabled"><span class="page-link rounded text-muted">&hellip;</span></li>`;
            const windowSize = 1, pages = [];
            for (let i = 1; i <= lastPage; i++) {
                if (i === 1 || i === lastPage || (i >= currentPage - windowSize && i <= currentPage + windowSize)) pages.push(i);
            }
            pagination += `<nav><ul class="pagination pagination-sm justify-content-center gap-1 mb-0">`;
            pagination += pageItem('&lsaquo;', currentPage - 1, { disabled: currentPage <= 1 });
            let prev = 0;
            pages.forEach(i => { if (prev && i - prev > 1) pagination += ellipsis(); pagination += pageItem(i, i, { active: i === currentPage }); prev = i; });
            pagination += pageItem('&rsaquo;', currentPage + 1, { disabled: currentPage >= lastPage });
            pagination += `</ul></nav>`;
        }
        $('#paginationContainer').html(pagination);
        $('#listFoot').toggleClass('d-none', !pagination);
    };

    const selectSchedule = (id) => {
        selectedScheduleId = id;
        selectedEmpID = null;
        $('.srow').removeClass('active');
        $(`.srow[data-id="${id}"]`).addClass('active');

        const row = scheduleRows[id] || {};
        const name = (row.employee_name || '').trim();

        // Paint header immediately from the list row, then hydrate hours from the edit endpoint.
        $('#schDetail').html(`
            <div class="sd-head">
                <span class="avatar-lg">${initialsOf(name)}</span>
                <div>
                    <p class="sd-name">${escapeHtml(name)}</p>
                    <div class="sd-sub">
                        ${row.department_name ? `<span class="dept-chip">${escapeHtml(row.department_name)}</span>` : ''}
                        ${row.shift_type ? `<span class="shift-chip">${escapeHtml(row.shift_type)}</span>` : ''}
                    </div>
                </div>
                <div class="sd-actions">
                    <button class="btn-sd primary btnEdit" data-id="${id}"><i class="fa-solid fa-pencil"></i> Edit</button>
                    <button class="btn-sd danger btnDelete" data-id="${id}"><i class="fa-solid fa-trash"></i> Delete</button>
                </div>
            </div>
            <div class="sd-body" id="sdBody">
                <div class="empty-state es-loading"><i class="fa-solid fa-spinner fa-spin"></i>Loading details…</div>
            </div>`);

        axios.get(`{{ url('employee-schedules/edit') }}/${id}`).then(res => {
            const s = res.data;
            $('#sdBody').html(`
                <div class="sd-grid">
                    <div class="sd-field">
                        <div class="fl"><i class="fa-regular fa-calendar-plus"></i> Start (Date &amp; Time)</div>
                        <div class="fv">${escapeHtml(s.sched_start_date)}<span class="time-chip">${escapeHtml(trimSeconds(s.sched_in))}</span></div>
                    </div>
                    <div class="sd-field">
                        <div class="fl"><i class="fa-regular fa-calendar-check"></i> End (Date &amp; Time)</div>
                        <div class="fv">${escapeHtml(s.sched_end_date)}<span class="time-chip">${escapeHtml(trimSeconds(s.sched_out))}</span></div>
                    </div>
                    <div class="sd-field">
                        <div class="fl"><i class="fa-solid fa-mug-hot"></i> Break</div>
                        <div class="fv">${escapeHtml(trimSeconds(s.break_start)) || '—'}<span class="time-chip">to ${escapeHtml(trimSeconds(s.break_end)) || '—'}</span></div>
                    </div>
                    <div class="sd-field">
                        <div class="fl"><i class="fa-solid fa-tag"></i> Shift Type</div>
                        <div class="fv">${s.shift_type ? escapeHtml(s.shift_type) : '—'}</div>
                    </div>
                </div>
            `);
        }).catch(() => {
            $('#sdBody').html('<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i>Unable to load schedule details.</div>');
        });
    };

    $(document).on('click', '.srow[data-id]', function () {
        if (currentMode === 'schedules') selectSchedule($(this).data('id'));
    });

    /* =========================================================
     *  UNSCHEDULED MODE
     * ========================================================= */
    const loadUnscheduled = () => {
        const from = $('#fromDate').val();
        const to   = $('#toDate').val();
        const department_id = $('#selDepartment').val();
        const perDay = !!(from && to);

        let scope = 'Never scheduled';
        if (perDay)      scope = `Missing any day ${from} → ${to}`;
        else if (from)   scope = `No schedule from ${from}`;
        else if (to)     scope = `No schedule until ${to}`;
        $('#unscheduledScopeLabel').text(scope);

        if (currentMode === 'unscheduled') {
            $('#schList').html('<div class="empty-state es-loading"><i class="fa-solid fa-spinner fa-spin"></i>Loading…</div>');
        }

        axios.get("{{ route('employee-schedules.unscheduled') }}", { params: { from, to, department_id } })
            .then(res => {
                const emps = res.data.employees || [];
                unschedState = { employees: emps, range: res.data.range || [], total_days: res.data.total_days || 0, perDay };
                $('#sUnsched').text(res.data.count);
                $('#cUnsched').text(res.data.count);
                if (currentMode === 'unscheduled') renderUnschedList();
            })
            .catch(err => {
                const msg = err.response?.data?.error || 'Unable to load unscheduled employees.';
                $('#sUnsched').text('0'); $('#cUnsched').text('0');
                if (currentMode === 'unscheduled') $('#schList').html(`<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i>${escapeHtml(msg)}</div>`);
            });
    };

    const renderUnschedList = () => {
        const emps = unschedState.employees;
        if (!emps.length) {
            $('#schList').html('<div class="empty-state" style="color:var(--success);"><i class="fa-solid fa-circle-check" style="color:var(--success);"></i>Everyone has a schedule for this period.</div>');
            $('#schDetail').html('<div class="sd-empty"><i class="fa-solid fa-circle-check" style="color:var(--success);"></i><div>No unscheduled employees. 🎉</div></div>');
            return;
        }
        let html = '';
        emps.forEach(e => {
            const missBadge = unschedState.perDay
                ? `<span class="miss-badge">${e.missing_count}/${unschedState.total_days} days missing</span>`
                : `<span class="miss-badge">No schedule</span>`;
            html += `
                <div class="srow miss" data-emp="${escapeHtml(String(e.empID))}">
                    <span class="avatar">${initialsOf(e.name)}</span>
                    <div class="rmain">
                        <div class="rtitle">${escapeHtml(e.name)}</div>
                        <div class="rmeta">${missBadge}</div>
                    </div>
                </div>`;
        });
        $('#schList').html(html);
        if (selectedEmpID !== null) $(`.srow[data-emp="${selectedEmpID}"]`).addClass('active');
    };

    const fmtDay = (iso) => {
        const [, m, d] = iso.split('-').map(Number);
        return ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][m-1] + ' ' + d;
    };

    const selectUnschedEmp = (empID) => {
        selectedEmpID = empID;
        selectedScheduleId = null;
        $('.srow').removeClass('active');
        $(`.srow[data-emp="${empID}"]`).addClass('active');

        const e = unschedState.employees.find(x => String(x.empID) === String(empID));
        if (!e) { $('#schDetail').html('<div class="sd-empty"><i class="fa-solid fa-user-slash"></i><div>Employee not found.</div></div>'); return; }

        let bodyHtml = '';
        if (unschedState.perDay) {
            const schedMap = {};
            (e.scheduled || []).forEach(s => { schedMap[s.date] = s; });
            let days = '';
            unschedState.range.forEach(d => {
                if (schedMap[d]) {
                    const s = schedMap[d];
                    days += `<span class="day-chip ok"><span class="chip-date">${fmtDay(d)}</span> ${escapeHtml(trimSeconds(s.in))}–${escapeHtml(trimSeconds(s.out))}</span>`;
                } else {
                    days += `<span class="day-chip miss">
                                <span class="chip-date">${fmtDay(d)}</span> missing
                                <button type="button" class="chip-add btnQuickSchedDay" data-id="${escapeHtml(String(e.empID))}" data-name="${escapeHtml(e.name)}" data-date="${d}" title="Add schedule for ${fmtDay(d)}">+</button>
                             </span>`;
                }
            });
            bodyHtml = `
                <div class="unsched-legend">
                    <span><span class="dot" style="background:var(--teal-mid)"></span>Scheduled</span>
                    <span><span class="dot" style="background:var(--danger)"></span>Missing</span>
                </div>
                <div class="day-grid">${days}</div>`;
        } else {
            bodyHtml = `<div class="sd-note"><i class="fa-solid fa-circle-info"></i><div>This employee has <strong>no schedule</strong> in the selected window. Pick a date range above to see a day-by-day breakdown.</div></div>`;
        }

        $('#schDetail').html(`
            <div class="sd-head miss">
                <span class="avatar-lg">${initialsOf(e.name)}</span>
                <div>
                    <p class="sd-name">${escapeHtml(e.name)}</p>
                    <div class="sd-sub">${unschedState.perDay ? `<span class="miss-badge">${e.missing_count}/${unschedState.total_days} days missing</span>` : `<span class="miss-badge">No schedule</span>`}</div>
                </div>
                <div class="sd-actions">
                    <button class="btn-sd primary btnQuickSched" data-id="${escapeHtml(String(e.empID))}" data-name="${escapeHtml(e.name)}"><i class="fa-solid fa-circle-plus"></i> Schedule</button>
                </div>
            </div>
            <div class="sd-body">${bodyHtml}</div>`);
    };

    $(document).on('click', '.srow[data-emp]', function () {
        selectUnschedEmp($(this).data('emp'));
    });

    /* =========================================================
     *  MODE TOGGLE (pills)
     * ========================================================= */
    $('.pill[data-mode]').on('click', function () {
        const mode = $(this).data('mode');
        if (mode === currentMode) return;
        currentMode = mode;
        $('.pill[data-mode]').removeClass('active');
        $(this).addClass('active');

        const isUnsched = mode === 'unscheduled';
        $('#schedControls').toggleClass('d-none', isUnsched);
        $('#unschedControls').toggleClass('d-none', !isUnsched);
        $('#listFoot').toggleClass('d-none', isUnsched);

        // Reset selection + detail on switch.
        selectedScheduleId = null; selectedEmpID = null;
        $('#schDetail').html(`<div class="sd-empty"><i class="fa-solid fa-${isUnsched ? 'user-clock' : 'calendar-day'}"></i><div>${isUnsched ? 'Select an employee to see their missing days.' : 'Select a schedule from the list to view its details.'}</div></div>`);

        if (isUnsched) { loadUnscheduled(); }
        else { loadSchedules($('#txtSearchEmp').val(), 1, $('#selPerPage').val()); }
    });

    // Unscheduled controls
    $('#fromDate, #toDate, #selDepartment').on('change', loadUnscheduled);
    $('#btnThisMonth').on('click', function () {
        const now = new Date();
        const fmt = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
        $('#fromDate').val(fmt(new Date(now.getFullYear(), now.getMonth(), 1)));
        $('#toDate').val(fmt(new Date(now.getFullYear(), now.getMonth() + 1, 0)));
        loadUnscheduled();
    });
    $('#btnClearRange').on('click', function () { $('#fromDate, #toDate').val(''); loadUnscheduled(); });

    /* =========================================================
     *  MODAL: open / quick-schedule
     * ========================================================= */
    const openSchedulerFor = (id, name, date) => {
        $('#schedule_id').val('');
        $('#frmEmpScheduler')[0].reset();
        $('.day-label').removeClass('bg-primary text-white border-primary').addClass('bg-transparent text-muted');
        $('.error-text').text('');
        $('input, select').removeClass('border-danger');
        resetEmployeeArray();

        selectedEmployees.push({ id: id, name: name });
        renderEmployeeTags();

        if (date) { $('#sched_start_date').val(date); $('#sched_end_date').val(date); }
        $('#mdlEmpScheduler').modal('show');
    };

    $(document).on('click', '.btnQuickSched', function () {
        openSchedulerFor($(this).data('id'), String($(this).data('name')));
    });
    $(document).on('click', '.btnQuickSchedDay', function () {
        openSchedulerFor($(this).data('id'), String($(this).data('name')), $(this).data('date'));
    });

    $('#btnCreateModal').on('click', function() {
        $('#schedule_id').val('');
        $('#frmEmpScheduler')[0].reset();
        $('.day-label').removeClass('bg-primary text-white border-primary').addClass('bg-transparent text-muted');
        $('.error-text').text('');
        $('input, select').removeClass('border-danger');
        resetEmployeeArray();
    });

    /* =========================================================
     *  SEARCH / PER-PAGE / PAGINATION
     * ========================================================= */
    $('#txtSearchEmp').on('keyup', function(){ loadSchedules($(this).val(), 1, $('#selPerPage').val()); });
    $('#selPerPage').on('change', function(){ loadSchedules($('#txtSearchEmp').val(), 1, $(this).val()); });
    $(document).on('click', '.page-link', function(e){
        e.preventDefault();
        if ($(this).closest('.page-item').hasClass('disabled')) return;
        const page = $(this).data('page');
        if (!page) return;
        loadSchedules($('#txtSearchEmp').val(), page, $('#selPerPage').val());
    });

    /* =========================================================
     *  SAVE
     * ========================================================= */
    $('#btnSaveScheduler').on('click', function() {
        let schedule_id = $('#schedule_id').val();
        let url = schedule_id ? `{{ url('employee-schedules/update') }}/${schedule_id}` : "{{ route('employee-schedules.store') }}";
        let method = schedule_id ? 'put' : 'post';

        let selectedDays = [];
        $('.day-check:checked').each(function() { selectedDays.push($(this).val()); });

        let formData = {
            employee_id: $('#selEmployee').val(),
            sched_start_date: $('#sched_start_date').val(),
            sched_in: $('#sched_in').val(),
            sched_end_date: $('#sched_end_date').val(),
            sched_out: $('#sched_out').val(),
            shift_type: $('#shift_type').val(),
            break_start: $('#break_start').val(),
            break_end: $('#break_end').val(),
            days: selectedDays,
            employee_ids: selectedEmployees.map(e => e.id),
        };

        function submitSchedule(data, isConfirmed = false) {
            if (isConfirmed) data.confirm_long_shift = true;

            Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            axios({ method, url, data })
                .then(res => {
                    if (res.data.warning) {
                        Swal.fire({
                            title: 'Confirm Schedule?', text: res.data.message, icon: 'warning',
                            showCancelButton: true, confirmButtonText: 'Yes, proceed'
                        }).then(result => { if (result.isConfirmed) submitSchedule(data, true); });
                        return;
                    }
                    Swal.fire({ icon: 'success', title: 'Success', text: res.data.message, timer: 1500, showConfirmButton: false });
                    $('#mdlEmpScheduler').modal('hide');
                    refreshCurrentView();
                })
                .catch(err => {
                    Swal.close();
                    if (err.response && err.response.status === 422) {
                        let errors = err.response.data.errors;
                        $('.error-text').text('');
                        Object.keys(errors).forEach(key => {
                            $(`.${key}_error`).text(errors[key][0]);
                            $(`#${key}`).addClass('border-danger');
                        });
                    } else {
                        const msg = (err.response && err.response.data && (err.response.data.message || err.response.data.error)) || 'An unexpected error occurred.';
                        Swal.fire({ icon: 'error', title: 'Error', text: msg });
                    }
                });
        }

        // Client-side R1 (break required) + R2 (net must equal 8h) — mirrors the server rule.
        $('.break_start_error, .break_end_error').text('');
        $('#break_start, #break_end').removeClass('border-danger');
        const toMin = t => { if (!t) return null; const [h, m] = t.split(':').map(Number); return h * 60 + m; };

        if (!formData.break_start || !formData.break_end) {
            $('.break_end_error').text('Break Start and Break End are required.');
            $('#break_end').addClass('border-danger');
            return;
        }

        if (formData.sched_in && formData.sched_out) {
            let span = toMin(formData.sched_out) - toMin(formData.sched_in); if (span <= 0) span += 1440;
            let brk  = toMin(formData.break_end) - toMin(formData.break_start); if (brk <= 0) brk += 1440;
            if (span - brk !== 480) {
                const fmt = m => { const s = m < 0 ? '-' : ''; m = Math.abs(m); const h = Math.floor(m / 60), mm = m % 60; return s + h + 'h' + (mm ? ' ' + mm + 'm' : ''); };
                $('.break_end_error').text(`Net working hours must equal 8:00. This shift is ${fmt(span)} with a ${fmt(brk)} break = ${fmt(span - brk)}. Adjust the break so (shift − break) = 8 hours.`);
                $('#break_end').addClass('border-danger');
                return;
            }
        }

        submitSchedule(formData);
    });

    /* =========================================================
     *  EDIT (opens modal pre-filled)
     * ========================================================= */
    $(document).on('click', '.btnEdit', function(){
        let id = $(this).data('id');
        axios.get(`{{ url('employee-schedules/edit') }}/${id}`).then(res => {
            let s = res.data;
            $('#schedule_id').val(s.id);
            $('#selEmployee').val(s.employee_id);
            $('#sched_start_date').val(s.sched_start_date);
            $('#sched_end_date').val(s.sched_end_date);
            $('#shift_type').val(s.shift_type);
            $('#sched_in').val(trimSeconds(s.sched_in));
            $('#sched_out').val(trimSeconds(s.sched_out));
            $('#break_start').val(trimSeconds(s.break_start));
            $('#break_end').val(trimSeconds(s.break_end));
            $('#mdlEmpScheduler').modal('show');
        });
    });

    /* =========================================================
     *  DELETE
     * ========================================================= */
    $(document).on('click', '.btnDelete', function(){
        let id = $(this).data('id');
        Swal.fire({
            title: 'Delete Schedule?', text: "This record will be permanently removed.", icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Yes, delete it!', confirmButtonColor: '#d33'
        }).then((result) => {
            if(result.isConfirmed){
                axios.delete(`{{ url('employee-schedules/delete') }}/${id}`).then(res => {
                    Swal.fire('Deleted!', res.data.message, 'success');
                    selectedScheduleId = null;
                    $('#schDetail').html('<div class="sd-empty"><i class="fa-solid fa-calendar-day"></i><div>Select a schedule from the list to view its details.</div></div>');
                    refreshCurrentView();
                }).catch(err => {
                    const msg = (err.response && err.response.data && (err.response.data.message || err.response.data.error)) || 'Unable to delete schedule.';
                    Swal.fire({ icon: 'error', title: 'Cannot Delete', text: msg });
                });
            }
        });
    });

    // Refresh whichever view is active, plus keep the opposite tab's count fresh.
    const refreshCurrentView = () => {
        if (currentMode === 'unscheduled') { loadUnscheduled(); loadSchedules('', 1, $('#selPerPage').val()); }
        else { loadSchedules($('#txtSearchEmp').val(), 1, $('#selPerPage').val()); loadUnscheduled(); }
    };

    /* =========================================================
     *  MODAL: multi-employee selector
     * ========================================================= */
    let selectedEmployees = []; // { id, name }

    $('#btnAddEmployee').on('click', function () {
        const sel = $('#selEmployee');
        const empId = sel.val();
        const empName = sel.find('option:selected').text().trim();
        if (!empId) return;
        if (selectedEmployees.find(e => e.id == empId)) {
            Swal.fire({ icon: 'info', title: 'Already added', text: `${empName} is already in the list.`, timer: 1500, showConfirmButton: false });
            return;
        }
        selectedEmployees.push({ id: empId, name: empName });
        renderEmployeeTags();
        sel.val('');
    });

    function renderEmployeeTags() {
        const container = $('#employeeTagsContainer');
        container.empty();
        selectedEmployees.forEach((emp, index) => {
            container.append(`
                <span class="badge rounded-pill d-inline-flex align-items-center gap-2 px-3 py-2 fw-medium employee-tag">
                    ${escapeHtml(emp.name)}
                    <button type="button" class="btn-close btn-close-sm ms-1 btnRemoveEmployee" data-index="${index}" style="font-size: 10px; filter: none; opacity: 0.6;" aria-label="Remove"></button>
                </span>`);
        });
    }

    $(document).on('click', '.btnRemoveEmployee', function () {
        const index = $(this).data('index');
        selectedEmployees.splice(index, 1);
        renderEmployeeTags();
    });

    function resetEmployeeArray() {
        selectedEmployees = [];
        $('#employeeTagsContainer').empty();
        $('#selEmployee').val('');
    }

    /* =========================================================
     *  INITIAL LOAD
     * ========================================================= */
    loadSchedules();
    loadUnscheduled();  // populates the Unscheduled count/badge on first paint
});
</script>
@endsection
