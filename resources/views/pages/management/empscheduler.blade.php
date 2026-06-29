@extends('layout.app')

@section('content')
<style>
    /* ── Design tokens (shared with Edit Employee / Loan) ── */
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

    .scheduler-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    .scheduler-topbar {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        padding: 16px 22px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .scheduler-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .scheduler-topbar .breadcrumb { margin: 2px 0 0; font-size: .78rem; }
    .scheduler-topbar .breadcrumb-item.active { color: var(--teal); font-weight: 700; }
    .scheduler-topbar .breadcrumb-item { color: var(--muted); }

    .btn-add-schedule {
        background: var(--teal);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: .3px;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(0,128,128,.25);
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-add-schedule:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

    /* ── Filter bar ──────────────────────────────────────────── */
    .scheduler-filterbar {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        padding: 16px 22px;
        margin-bottom: 20px;
    }
    .scheduler-filterbar .input-group {
        border: 1.5px solid var(--border);
        border-radius: 999px;
        overflow: hidden;
        background: #fafbfc;
    }
    .scheduler-filterbar .input-group:focus-within {
        border-color: var(--teal);
        box-shadow: 0 0 0 3px rgba(0,128,128,.1);
    }
    .scheduler-filterbar .input-group-text { background: transparent; border: none; }
    .scheduler-filterbar .form-control { border: none; background: transparent; box-shadow: none !important; }
    .scheduler-filterbar .form-select {
        border: 1.5px solid var(--border);
        border-radius: 999px;
        background: #fafbfc;
        font-size: 0.85rem;
    }
    .scheduler-filterbar .form-select:focus {
        border-color: var(--teal);
        box-shadow: 0 0 0 3px rgba(0,128,128,.1);
        outline: none;
    }

    /* ── Section card ────────────────────────────────────────── */
    .sc {
        background: var(--surface);
        border-radius: var(--radius-card);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-card);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .sc-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 22px;
        border-bottom: 1px solid var(--border);
        background: linear-gradient(to right, #fafcff, #f8fbfa);
    }
    .sc-head-left { display: flex; align-items: center; gap: 10px; }
    .sc-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: var(--teal-light);
        color: var(--teal);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        flex-shrink: 0;
    }
    .sc-title {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--slate);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin: 0;
    }
    .sc-body { padding: 0; }

    /* ── Field helpers ───────────────────────────────────────── */
    .field-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--slate-light);
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: 5px;
        display: block;
    }
    .field-label .req { color: var(--danger); margin-left: 2px; }

    .form-control, .form-select {
        border: 1.5px solid var(--border);
        border-radius: var(--radius-input);
        font-size: 0.875rem;
        color: var(--slate);
        background: #fafbfc;
        transition: border-color .15s, box-shadow .15s;
        padding: 0.55rem 0.85rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--teal);
        box-shadow: 0 0 0 3px rgba(0,128,128,.1);
        background-color: #fff;
        outline: none;
    }

    /* ── Sub-section divider ─────────────────────────────────── */
    .sub-divider {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 6px 0 18px;
    }
    .sub-divider span {
        font-size: 0.73rem;
        font-weight: 700;
        color: var(--teal);
        text-transform: uppercase;
        letter-spacing: .4px;
        white-space: nowrap;
    }
    .sub-divider::after {
        content: '';
        flex-grow: 1;
        height: 1px;
        background: var(--border);
    }

    /* ── Table styling ───────────────────────────────────────── */
    .scheduler-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: var(--surface);
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--slate-light);
        text-transform: uppercase;
        letter-spacing: .4px;
        border-bottom: 2px solid var(--border);
        white-space: nowrap;
        padding: 12px 16px;
    }
    .scheduler-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .scheduler-table tbody tr:hover { background: var(--teal-light); }

    .icon-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1.5px solid var(--border);
        background: var(--surface);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all .15s;
    }
    .icon-action-btn:hover { border-color: var(--teal-mid); background: var(--teal-light); }
    .icon-action-btn.danger:hover { border-color: var(--danger); background: #fff5f5; }

    /* ── Day pill checkboxes ─────────────────────────────────── */
    .day-label {
        background: var(--surface);
        color: var(--slate-light);
        border: 1.5px solid var(--border) !important;
        font-size: 0.75rem;
        font-weight: 700;
        transition: all .15s;
    }
    .day-check:checked + .day-label {
        background: var(--teal) !important;
        color: #fff !important;
        border-color: var(--teal) !important;
    }

    /* ── Employee tags ───────────────────────────────────────── */
    .employee-tag {
        background: var(--teal-light);
        color: var(--teal-dark);
        border: 1px solid var(--teal-mid);
        font-weight: 700;
        font-size: 0.72rem;
    }

    /* ── Modal styling ───────────────────────────────────────── */
    #mdlEmpScheduler .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #mdlEmpScheduler .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #mdlEmpScheduler .modal-header .modal-title { color: #fff; }
    #mdlEmpScheduler .modal-header .modal-title i { color: #fff; }
    #mdlEmpScheduler .btn-close { filter: brightness(0) invert(1); }
    #mdlEmpScheduler .modal-body { background: var(--bg); padding: 22px; }
    #mdlEmpScheduler .modal-footer {
        background: var(--surface);
        border-top: 1px solid var(--border);
    }
    #mdlEmpScheduler .bg-light.p-3.rounded-3 {
        background: var(--surface) !important;
        border: 1px solid var(--border);
    }

    .btn-submit-schedule {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 10px 26px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(245,158,11,.3);
        transition: all .2s;
    }
    .btn-submit-schedule:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,.4); color: #fff; }

    .btn-cancel-schedule {
        background: var(--surface);
        color: var(--slate-light);
        border: 1.5px solid var(--border);
        border-radius: 10px;
        padding: 10px 22px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        cursor: pointer;
        transition: all .2s;
    }
    .btn-cancel-schedule:hover { background: var(--bg); }

    /* ── Pagination ──────────────────────────────────────────── */
    #paginationContainer .page-link {
        color: var(--slate-light);
    }
    #paginationContainer .page-item.active .page-link {
        background: var(--teal) !important;
        border-color: var(--teal) !important;
        color: #fff !important;
    }

    /* ── Add-employee button ─────────────────────────────────── */
    #btnAddEmployee {
        background: var(--teal);
        border-color: var(--teal);
    }
    #btnAddEmployee:hover {
        background: var(--teal-dark);
        border-color: var(--teal-dark);
    }
</style>

<div class="scheduler-shell">

    {{-- ── Top header ── --}}
    <div class="scheduler-topbar">
        <div>
            <p class="page-title">Employee Scheduler</p>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">Settings</li>
                    <li class="breadcrumb-item active" aria-current="page">Scheduling Module</li>
                </ol>
            </nav>
        </div>
        <button class="btn-add-schedule" id="btnCreateModal" data-bs-toggle="modal" data-bs-target="#mdlEmpScheduler">
            <i class="fa-solid fa-plus"></i> Add Schedule
        </button>
    </div>

    {{-- ── Filters ── --}}
    <div class="scheduler-filterbar">
        <div class="row g-3 align-items-center">
            <div class="col-lg-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" id="txtSearchEmp" class="form-control" placeholder="Search by Employee Name...">
                </div>
            </div>
            <div class="col-lg-4">
                <select id="selScheduleView" class="form-select">
                    <option value="scheduled">Show: All schedules</option>
                    <option value="unscheduled">Show: Unscheduled employees</option>
                </select>
            </div>
            <div class="col-lg-3" id="perPageWrap">
                <select id="selPerPage" class="form-select">
                    <option value="10">10 entries per page</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                    <option value="100">100 entries</option>
                </select>
            </div>
        </div>

        {{-- Date-range scope (only for the Unscheduled view) --}}
        <div class="row g-3 align-items-end mt-1 d-none" id="unschedRangeWrap">
            <div class="col-lg-3">
                <label class="field-label" for="fromDate">From</label>
                <input type="date" id="fromDate" class="form-control">
            </div>
            <div class="col-lg-3">
                <label class="field-label" for="toDate">To</label>
                <input type="date" id="toDate" class="form-control">
            </div>
            <div class="col-lg-6 d-flex gap-2">
                <button type="button" class="btn-cancel-schedule" id="btnThisMonth" style="padding:8px 16px;">This Month</button>
                <button type="button" class="btn-cancel-schedule" id="btnClearRange" style="padding:8px 16px;">Clear (Never Scheduled)</button>
            </div>
            <div class="col-12">
                <small class="text-muted">Leave both dates blank to list employees who have <strong>never</strong> been given a schedule.</small>
            </div>
        </div>
    </div>

    {{-- ── Unscheduled employees panel (always visible) ── --}}
    <div class="sc" id="unscheduledPanel">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon" style="background:#fff5f5;color:var(--danger);"><i class="fa-solid fa-user-clock"></i></div>
                <h5 class="sc-title">Employees with no schedule</h5>
                <span class="badge rounded-pill ms-1" id="unscheduledCount"
                      style="background:var(--danger);color:#fff;font-weight:700;">0</span>
            </div>
            <span class="text-muted small" id="unscheduledScopeLabel">Never scheduled</span>
        </div>
        <div class="sc-body" style="padding:16px 22px;">
            <div id="unscheduledList" class="d-flex flex-wrap gap-2"></div>
        </div>
    </div>

    {{-- ── Schedule list ── --}}
    <div class="sc" id="scheduledView">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-calendar-days"></i></div>
                <h5 class="sc-title">Employee Schedules</h5>
            </div>
        </div>
        <div class="sc-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 scheduler-table">
                    <thead>
                        <tr>
                            <th class="ps-4">Employee Name</th>
                            <th>From (Date &amp; Time)</th>
                            <th>To (Date &amp; Time)</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tblEmpScheduler" class="border-top-0">
                        </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="paginationContainer" class="mt-4"></div>
</div>

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
                        </div>
                        <div class="col-md-3">
                            <label class="field-label" for="break_end">Break End</label>
                            <input type="time" class="form-control" name="break_end" id="break_end">
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

    // 🎨 UI Helper for Checkboxes (Pill buttons)
    $('.day-check').on('change', function() {
        if($(this).is(':checked')) {
            $(this).next('.day-label').addClass('bg-primary text-white border-primary').removeClass('bg-transparent text-muted');
        } else {
            $(this).next('.day-label').removeClass('bg-primary text-white border-primary').addClass('bg-transparent text-muted');
        }
    });

    // 🧩 LOAD SCHEDULES
    const loadSchedules = (search = '', page = 1, perPage = 10) => {
        $("#tblEmpScheduler").html('<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary opacity-50" role="status"></div></td></tr>');

        axios.get("{{ route('employee-schedules.get') }}", {
            params: { search, page, per_page: perPage }
        })
        .then(res => {
            const data = res.data.data;
            let html = '';

            if(data.length > 0) {
                data.forEach(s => {
                    html += `
                        <tr>
                            <td class="ps-4 fw-bold text-dark text-uppercase small">${s.employee_name}</td>
                            <td class="text-muted">${s.sched_start_date} <span class="badge bg-light text-dark border-0 ms-1 fw-bold">${s.sched_in}</span></td>
                            <td class="text-muted">${s.sched_end_date} <span class="badge bg-light text-dark border-0 ms-1 fw-bold">${s.sched_out}</span></td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="icon-action-btn btnEdit" data-id="${s.id}" title="Edit">
                                        <i class="fa-solid fa-pencil" style="color: var(--teal);"></i>
                                    </button>
                                    <button class="icon-action-btn danger btnDelete" data-id="${s.id}" title="Delete">
                                        <i class="fa-solid fa-trash text-danger"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>`;
                });
            } else {
                html = '<tr><td colspan="4" class="text-center py-5 text-muted small">No scheduling records found.</td></tr>';
            }

            $('#tblEmpScheduler').html(html);

            // Pagination builder (windowed: Prev/Next, current ±1, first/last with ellipses)
            const lastPage = res.data.last_page;
            const currentPage = res.data.current_page;
            let pagination = '';
            if (lastPage > 1) {
                const pageItem = (label, page, { active = false, disabled = false } = {}) => {
                    if (disabled) {
                        return `<li class="page-item disabled"><span class="page-link rounded border-0 text-muted">${label}</span></li>`;
                    }
                    return `
                        <li class="page-item ${active ? 'active' : ''}">
                            <a href="#" class="page-link rounded border-0 ${active ? 'bg-primary shadow-sm' : 'text-muted'}" data-page="${page}">${label}</a>
                        </li>`;
                };
                const ellipsis = () => `<li class="page-item disabled"><span class="page-link rounded border-0 text-muted">&hellip;</span></li>`;

                // Decide which page numbers to show
                const windowSize = 1; // pages on each side of current
                const pages = [];
                for (let i = 1; i <= lastPage; i++) {
                    if (i === 1 || i === lastPage || (i >= currentPage - windowSize && i <= currentPage + windowSize)) {
                        pages.push(i);
                    }
                }

                pagination += `<nav><ul class="pagination pagination-sm justify-content-end gap-1">`;
                pagination += pageItem('&lsaquo;', currentPage - 1, { disabled: currentPage <= 1 });

                let prev = 0;
                pages.forEach(i => {
                    if (prev && i - prev > 1) pagination += ellipsis();
                    pagination += pageItem(i, i, { active: i === currentPage });
                    prev = i;
                });

                pagination += pageItem('&rsaquo;', currentPage + 1, { disabled: currentPage >= lastPage });
                pagination += `</ul></nav>`;
            }
            $('#paginationContainer').html(pagination);
        })
        .catch(err => {
            Swal.fire({ icon: 'error', title: 'Fetch Error', text: 'Unable to load schedules.' });
        });
    };

    // 🧩 LOAD UNSCHEDULED EMPLOYEES
    const loadUnscheduled = () => {
        const from = $('#fromDate').val();
        const to   = $('#toDate').val();

        // Scope label
        let scope = 'Never scheduled';
        if (from && to)       scope = `No schedule ${from} → ${to}`;
        else if (from)        scope = `No schedule from ${from}`;
        else if (to)          scope = `No schedule until ${to}`;
        $('#unscheduledScopeLabel').text(scope);

        $('#unscheduledList').html('<div class="spinner-border spinner-border-sm text-danger opacity-50" role="status"></div>');

        axios.get("{{ route('employee-schedules.unscheduled') }}", { params: { from, to } })
            .then(res => {
                const emps = res.data.employees || [];
                $('#unscheduledCount').text(res.data.count);

                if (emps.length === 0) {
                    $('#unscheduledList').html('<span class="text-success small fw-bold">🎉 Everyone has a schedule for this period.</span>');
                    return;
                }

                let html = '';
                emps.forEach(e => {
                    html += `
                        <span class="badge rounded-pill d-inline-flex align-items-center gap-2 px-3 py-2 fw-medium employee-tag">
                            ${e.name}
                            <button type="button" class="btn btn-sm p-0 border-0 bg-transparent btnQuickSched"
                                    data-id="${e.empID}" data-name="${e.name}" title="Add schedule for this employee"
                                    style="line-height:1;">
                                <i class="fa-solid fa-circle-plus" style="color:var(--teal-dark);"></i>
                            </button>
                        </span>`;
                });
                $('#unscheduledList').html(html);
            })
            .catch(() => {
                $('#unscheduledList').html('<span class="text-danger small">Unable to load unscheduled employees.</span>');
            });
    };

    // View toggle: schedule table vs. unscheduled employees
    $('#selScheduleView').on('change', function () {
        const isUnsched = $(this).val() === 'unscheduled';
        $('#unschedRangeWrap').toggleClass('d-none', !isUnsched);
        // In the unscheduled view, the schedule table / pagination / per-page are not relevant
        $('#scheduledView, #paginationContainer, #perPageWrap').toggleClass('d-none', isUnsched);
        if (isUnsched) loadUnscheduled();
    });

    // Date-range controls
    $('#fromDate, #toDate').on('change', loadUnscheduled);
    $('#btnThisMonth').on('click', function () {
        const now = new Date();
        const first = new Date(now.getFullYear(), now.getMonth(), 1);
        const last  = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        const fmt = d => d.toISOString().slice(0, 10);
        $('#fromDate').val(fmt(first));
        $('#toDate').val(fmt(last));
        loadUnscheduled();
    });
    $('#btnClearRange').on('click', function () {
        $('#fromDate, #toDate').val('');
        loadUnscheduled();
    });

    // Quick "Add Schedule" from an unscheduled pill → open modal pre-selecting that employee
    $(document).on('click', '.btnQuickSched', function () {
        const id   = $(this).data('id');
        const name = String($(this).data('name'));

        // Reset modal like the Create button does
        $('#schedule_id').val('');
        $('#frmEmpScheduler')[0].reset();
        $('.day-label').removeClass('bg-primary text-white border-primary').addClass('bg-transparent text-muted');
        $('.error-text').text('');
        $('input, select').removeClass('border-danger');
        resetEmployeeArray();

        // Pre-tag the chosen employee
        selectedEmployees.push({ id: id, name: name });
        renderEmployeeTags();

        $('#mdlEmpScheduler').modal('show');
    });

    // 🔹 Initial Load
    loadSchedules();
    loadUnscheduled();

    // Reset Modal on Open
    $('#btnCreateModal').on('click', function() {
        $('#schedule_id').val('');
        $('#frmEmpScheduler')[0].reset();
        $('.day-label').removeClass('bg-primary text-white border-primary').addClass('bg-transparent text-muted');
        $('.error-text').text('');
        $('input, select').removeClass('border-danger');
        resetEmployeeArray(); //
    });

    // 🔍 Search & Filters
    $('#txtSearchEmp').on('keyup', function(){ loadSchedules($(this).val(), 1, $('#selPerPage').val()); });
    $('#selPerPage').on('change', function(){ loadSchedules($('#txtSearchEmp').val(), 1, $(this).val()); });
    $(document).on('click', '.page-link', function(e){
        e.preventDefault();
        if ($(this).closest('.page-item').hasClass('disabled')) return;
        const page = $(this).data('page');
        if (!page) return;
        loadSchedules($('#txtSearchEmp').val(), page, $('#selPerPage').val());
    });

    // 💾 Save Logic
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

            Swal.fire({
                title: 'Processing...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            axios({ method, url, data })
                .then(res => {
                    if (res.data.warning) {
                        Swal.fire({
                            title: 'Confirm Schedule?',
                            text: res.data.message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, proceed'
                        }).then(result => { if (result.isConfirmed) submitSchedule(data, true); });
                        return;
                    }

                    Swal.fire({ icon: 'success', title: 'Success', text: res.data.message, timer: 1500, showConfirmButton: false });
                    $('#mdlEmpScheduler').modal('hide');
                    loadSchedules();
                    loadUnscheduled();
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
                        Swal.fire({ icon: 'error', title: 'Error', text: 'An unexpected error occurred.' });
                    }
                });
        }
        submitSchedule(formData);
    });

    // 📝 Edit
    $(document).on('click', '.btnEdit', function(){
        let id = $(this).data('id');
        const trimSeconds = (t) => t ? t.substring(0, 5) : '';

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

            // Note: If you store days in DB, you'd trigger them here
            $('#mdlEmpScheduler').modal('show');
        });
    });

    // 🗑️ Delete
    $(document).on('click', '.btnDelete', function(){
        let id = $(this).data('id');
        Swal.fire({
            title: 'Delete Schedule?',
            text: "This record will be permanently removed.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            confirmButtonColor: '#d33'
        }).then((result) => {
            if(result.isConfirmed){
                axios.delete(`{{ url('employee-schedules/delete') }}/${id}`).then(res => {
                    Swal.fire('Deleted!', res.data.message, 'success');
                    loadSchedules();
                    loadUnscheduled();
                });
            }
        });
    });

    let selectedEmployees = []; // { id, name }

    $('#btnAddEmployee').on('click', function () {
        const sel = $('#selEmployee');
        const empId = sel.val();
        const empName = sel.find('option:selected').text().trim();

        if (!empId) return;

        // Prevent duplicates
        if (selectedEmployees.find(e => e.id == empId)) {
            Swal.fire({ icon: 'info', title: 'Already added', text: `${empName} is already in the list.`, timer: 1500, showConfirmButton: false });
            return;
        }

        selectedEmployees.push({ id: empId, name: empName });
        renderEmployeeTags();

        // Reset select to default
        sel.val('');
    });

    function renderEmployeeTags() {
        const container = $('#employeeTagsContainer');
        container.empty();

        selectedEmployees.forEach((emp, index) => {
            container.append(`
                <span class="badge rounded-pill d-inline-flex align-items-center gap-2 px-3 py-2 fw-medium employee-tag">
                    ${emp.name}
                    <button type="button" class="btn-close btn-close-sm ms-1 btnRemoveEmployee"
                            data-index="${index}"
                            style="font-size: 10px; filter: none; opacity: 0.6;"
                            aria-label="Remove"></button>
                </span>
            `);
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
});
</script>
@endsection
