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

    .access-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 90px;
        margin: -1.5rem -1.5rem 0;
    }

    .access-topbar {
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
    .access-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .access-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }
    .access-topbar .breadcrumb { margin: 2px 0 0; font-size: .78rem; }
    .access-topbar .breadcrumb-item.active { color: var(--teal); font-weight: 700; }
    .access-topbar .breadcrumb-item { color: var(--muted); }

    .btn-add-access {
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
    .btn-add-access:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

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
        flex-wrap: wrap;
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

    /* ── Toolbar (search + filter) ───────────────────────────── */
    .access-toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .search-wrap { position: relative; }
    .search-wrap i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--muted);
        font-size: .8rem;
        pointer-events: none;
    }
    .search-wrap input {
        padding-left: 32px !important;
        min-width: 240px;
    }
    .count-pill {
        font-size: .72rem;
        font-weight: 700;
        color: var(--slate-light);
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 4px 12px;
        white-space: nowrap;
    }

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
        padding: 0.5rem 0.85rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--teal);
        box-shadow: 0 0 0 3px rgba(0,128,128,.1);
        background-color: #fff;
        outline: none;
    }

    /* ── Table styling ───────────────────────────────────────── */
    .access-table thead th {
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
    .access-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .access-table tbody tr:hover { background: var(--teal-light); }
    .access-table tbody tr.row-selected { background: #fff7ed; }
    .access-table tbody tr.row-selected:hover { background: #ffedd5; }

    .row-check, .check-all { width: 16px; height: 16px; cursor: pointer; accent-color: var(--teal); }

    .dept-tag {
        font-size: .68rem;
        font-weight: 700;
        color: var(--slate-light);
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 3px 8px;
        white-space: nowrap;
    }
    .no-role { font-size: .72rem; color: var(--muted); font-style: italic; }
    .empty-row td { text-align: center; color: var(--muted); padding: 28px 16px; font-size: .85rem; }

    /* ── Role badges (assigned roles, click-to-remove) ───────── */
    .role-badge {
        background: var(--teal-light);
        color: var(--teal-dark);
        border: 1px solid var(--teal-mid);
        font-weight: 700;
        letter-spacing: .3px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.68rem;
        transition: all .2s;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
    }
    .role-badge:hover {
        background: var(--danger);
        color: #fff;
        border-color: var(--danger);
        transform: translateY(-1px);
    }

    /* ── Sticky bulk action bar ──────────────────────────────── */
    .bulk-bar {
        position: fixed;
        left: 50%;
        bottom: 24px;
        transform: translateX(-50%) translateY(140%);
        z-index: 1045;
        background: var(--slate);
        color: #fff;
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(15,23,42,.35);
        padding: 12px 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: transform .25s ease;
        max-width: 94vw;
    }
    .bulk-bar.show { transform: translateX(-50%) translateY(0); }
    .bulk-bar .bulk-count { font-weight: 700; font-size: .85rem; white-space: nowrap; }
    .bulk-bar .bulk-count b { color: var(--teal-mid); }
    .bulk-bar .btn-bulk {
        background: var(--teal);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: .8rem;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
    }
    .bulk-bar .btn-bulk:hover { background: var(--teal-dark); }
    .bulk-bar .btn-bulk-clear {
        background: transparent;
        color: #cbd5e1;
        border: 1px solid #475569;
        border-radius: 8px;
        padding: 8px 14px;
        font-size: .8rem;
        font-weight: 700;
        cursor: pointer;
    }
    .bulk-bar .btn-bulk-clear:hover { background: #475569; color: #fff; }

    /* ── Modal styling ───────────────────────────────────────── */
    #mdlEmpRole .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #mdlEmpRole .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #mdlEmpRole .modal-header .modal-title { color: #fff; }
    #mdlEmpRole .modal-header .modal-title i { color: #fff; }
    #mdlEmpRole .btn-close { filter: brightness(0) invert(1); }
    #mdlEmpRole .modal-body { background: var(--bg); padding: 22px; }
    #mdlEmpRole .modal-footer {
        background: var(--surface);
        border-top: 1px solid var(--border);
    }

    /* picker columns inside modal */
    .picker {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 10px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 320px;
    }
    .picker-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 10px 12px;
        border-bottom: 1px solid var(--border);
        background: #fafcff;
    }
    .picker-head .field-label { margin: 0; }
    .picker-search {
        padding: 8px 12px;
        border-bottom: 1px solid var(--border);
    }
    .picker-list { overflow-y: auto; padding: 6px 8px; flex: 1; }
    .picker-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 7px 8px;
        border-radius: 8px;
        cursor: pointer;
        font-size: .82rem;
        color: var(--slate);
        margin: 0;
    }
    .picker-item:hover { background: var(--teal-light); }
    .picker-item input { width: 15px; height: 15px; accent-color: var(--teal); cursor: pointer; flex-shrink: 0; }
    .picker-item .sub { font-size: .68rem; color: var(--muted); }
    .link-mini {
        font-size: .68rem;
        font-weight: 700;
        color: var(--teal);
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
    }
    .link-mini:hover { text-decoration: underline; }
    .sel-summary { font-size: .72rem; color: var(--slate-light); margin-top: 8px; }
    .sel-summary b { color: var(--teal-dark); }

    .btn-submit-access {
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
    .btn-submit-access:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,.4); color: #fff; }

    .btn-cancel-access {
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
    .btn-cancel-access:hover { background: var(--bg); }
</style>

<div class="access-shell">

    {{-- ── Top header ── --}}
    <div class="access-topbar">
        <div>
            <p class="page-title">Employee Access Rights</p>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">Settings</li>
                    <li class="breadcrumb-item active" aria-current="page">Employee Roles</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn-add-access" id="btnCreateModal">
            <i class="fa-solid fa-plus"></i> Assign New Role
        </button>
    </div>

    {{-- ── Access Rights Records ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-users-gear"></i></div>
                <h5 class="sc-title">Employee Roles &amp; Access</h5>
            </div>
            <div class="access-toolbar">
                <div class="search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="empSearch" class="form-control" placeholder="Search name or department...">
                </div>
                <select id="roleFilter" class="form-select" style="min-width:180px;">
                    <option value="">All roles</option>
                    <option value="__none__">— No role assigned —</option>
                    @foreach($roles as $role)
                        <option value="{{ strtolower($role->name) }}">{{ $role->name }}</option>
                    @endforeach
                </select>
                <span class="count-pill" id="rowCount">{{ count($users) }} employees</span>
            </div>
        </div>
        <div class="sc-body">
            <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                <table class="table table-hover align-middle access-table mb-0">
                    <thead>
                        <tr>
                            <th style="width:40px;" class="ps-4">
                                <input type="checkbox" class="check-all" id="checkAll" title="Select all">
                            </th>
                            <th>Employee Name</th>
                            <th>Department</th>
                            <th>Current Roles</th>
                        </tr>
                    </thead>
                    <tbody id="tblAccessRights" class="border-top-0">
                        @foreach ($users as $user)
                            @php
                                $deptName = optional(optional($user->empDetail)->department)->dep_name;
                                $roleNames = $user->roles->pluck('name')->map(fn($r) => strtolower($r))->implode(' ');
                            @endphp
                            <tr class="emp-row"
                                data-name="{{ strtolower($user->lname.' '.$user->fname) }}"
                                data-dept="{{ strtolower($deptName ?? '') }}"
                                data-roles="{{ $roleNames }}"
                                data-id="{{ $user->id }}">
                                <td class="ps-4">
                                    <input type="checkbox" class="row-check" value="{{ $user->id }}">
                                </td>
                                <td class="fw-bold text-dark text-uppercase small">{{ $user->lname }}, {{ $user->fname }}</td>
                                <td>
                                    @if($deptName)
                                        <span class="dept-tag">{{ $deptName }}</span>
                                    @else
                                        <span class="no-role">—</span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    <div class="d-flex flex-wrap gap-2">
                                        @forelse($user->roles as $role)
                                            <span
                                                class="role-badge text-uppercase"
                                                onclick="confirmRemoveRole('{{ $user->id }}', '{{ $role->name }}')"
                                                title="Click to remove role"
                                            >
                                                {{ $role->name }} <i class="fas fa-times ms-1 small"></i>
                                            </span>
                                        @empty
                                            <span class="no-role">No role assigned</span>
                                        @endforelse
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="empty-row" id="emptyRow" style="display:none;">
                            <td colspan="4">No employees match your search.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── Sticky bulk action bar ── --}}
<div class="bulk-bar" id="bulkBar">
    <span class="bulk-count"><b id="bulkCount">0</b> selected</span>
    <button type="button" class="btn-bulk" id="btnBulkAssign">
        <i class="fa-solid fa-user-tag me-1"></i> Assign role(s)
    </button>
    <button type="button" class="btn-bulk-clear" id="btnBulkClear">Clear</button>
</div>

{{-- ── Assign modal (multi employee + multi role) ── --}}
<div class="modal fade" id="mdlEmpRole" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-tag me-2"></i> Assign Roles
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="frmEmpRoleAssign" method="POST" action="{{ route('employee.roles.assign') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        {{-- Employees --}}
                        <div class="col-md-7">
                            <div class="picker">
                                <div class="picker-head">
                                    <label class="field-label">Employees <span class="req">*</span></label>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="link-mini" data-pick="emp" data-act="all">Select all</button>
                                        <button type="button" class="link-mini" data-pick="emp" data-act="none">Clear</button>
                                    </div>
                                </div>
                                <div class="picker-search">
                                    <input type="text" class="form-control" id="empPickerSearch" placeholder="Filter employees...">
                                </div>
                                <div class="picker-list" id="empPickerList">
                                    @foreach($employees as $emp)
                                        @php $eDept = optional(optional($emp->empDetail)->department)->dep_name; @endphp
                                        <label class="picker-item emp-pick-item"
                                               data-search="{{ strtolower($emp->lname.' '.$emp->fname.' '.($eDept ?? '')) }}">
                                            <input type="checkbox" class="emp-pick" name="employee_id[]" value="{{ $emp->id }}">
                                            <span>
                                                {{ strtoupper($emp->lname) }}, {{ strtoupper($emp->fname) }}
                                                @if($eDept)<span class="sub d-block">{{ $eDept }}</span>@endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="sel-summary"><b id="empSelCount">0</b> employee(s) selected</div>
                        </div>

                        {{-- Roles --}}
                        <div class="col-md-5">
                            <div class="picker">
                                <div class="picker-head">
                                    <label class="field-label">Roles to assign <span class="req">*</span></label>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="link-mini" data-pick="role" data-act="all">All</button>
                                        <button type="button" class="link-mini" data-pick="role" data-act="none">Clear</button>
                                    </div>
                                </div>
                                <div class="picker-list" id="rolePickerList">
                                    @foreach($roles as $role)
                                        <label class="picker-item">
                                            <input type="checkbox" class="role-pick" name="role_id[]" value="{{ $role->id }}">
                                            <span>{{ $role->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="sel-summary"><b id="roleSelCount">0</b> role(s) selected</div>
                        </div>
                    </div>
                    <span class="text-danger small mt-2" id="assignError" style="display:none;"></span>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel-access" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-submit-access" id="btnSubmitAssign">Assign Roles</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function () {
    const empRoleModal = new bootstrap.Modal(document.getElementById('mdlEmpRole'));

    /* ───────── Table search + role filter ───────── */
    const search     = document.getElementById('empSearch');
    const roleFilter = document.getElementById('roleFilter');
    const rows       = Array.from(document.querySelectorAll('.emp-row'));
    const emptyRow   = document.getElementById('emptyRow');
    const rowCount   = document.getElementById('rowCount');

    function applyFilter() {
        const q  = (search.value || '').toLowerCase().trim();
        const rf = roleFilter.value;
        let visible = 0;
        rows.forEach(r => {
            const name  = r.dataset.name || '';
            const dept  = r.dataset.dept || '';
            const roles = r.dataset.roles || '';
            let ok = !q || name.includes(q) || dept.includes(q);
            if (ok && rf) {
                if (rf === '__none__') ok = roles.trim() === '';
                else                   ok = (' ' + roles + ' ').includes(' ' + rf + ' ');
            }
            r.style.display = ok ? '' : 'none';
            if (ok) visible++;
        });
        emptyRow.style.display = visible ? 'none' : '';
        rowCount.textContent = visible + ' employee' + (visible === 1 ? '' : 's');
        syncCheckAll();
    }
    search.addEventListener('input', applyFilter);
    roleFilter.addEventListener('change', applyFilter);

    /* ───────── Row selection + bulk bar ───────── */
    const checkAll  = document.getElementById('checkAll');
    const bulkBar   = document.getElementById('bulkBar');
    const bulkCount = document.getElementById('bulkCount');

    function selectedIds() {
        return Array.from(document.querySelectorAll('.row-check:checked')).map(c => c.value);
    }
    function visibleChecks() {
        return rows.filter(r => r.style.display !== 'none').map(r => r.querySelector('.row-check'));
    }
    function syncCheckAll() {
        const vis = visibleChecks();
        const checked = vis.filter(c => c.checked);
        checkAll.checked = vis.length > 0 && checked.length === vis.length;
        checkAll.indeterminate = checked.length > 0 && checked.length < vis.length;
    }
    function refreshBulk() {
        const ids = selectedIds();
        bulkCount.textContent = ids.length;
        bulkBar.classList.toggle('show', ids.length > 0);
        document.querySelectorAll('.row-check').forEach(c => {
            c.closest('tr').classList.toggle('row-selected', c.checked);
        });
        syncCheckAll();
    }
    document.querySelectorAll('.row-check').forEach(c =>
        c.addEventListener('change', refreshBulk));
    checkAll.addEventListener('change', () => {
        visibleChecks().forEach(c => c.checked = checkAll.checked);
        refreshBulk();
    });
    document.getElementById('btnBulkClear').addEventListener('click', () => {
        document.querySelectorAll('.row-check').forEach(c => c.checked = false);
        refreshBulk();
    });

    /* ───────── Modal pickers ───────── */
    const empPicks    = () => Array.from(document.querySelectorAll('.emp-pick'));
    const rolePicks   = () => Array.from(document.querySelectorAll('.role-pick'));
    const empSelCount  = document.getElementById('empSelCount');
    const roleSelCount = document.getElementById('roleSelCount');
    const assignError  = document.getElementById('assignError');

    function refreshPickerCounts() {
        empSelCount.textContent  = empPicks().filter(c => c.checked).length;
        roleSelCount.textContent = rolePicks().filter(c => c.checked).length;
    }
    document.getElementById('frmEmpRoleAssign').addEventListener('change', refreshPickerCounts);

    // employee filter inside modal
    document.getElementById('empPickerSearch').addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.emp-pick-item').forEach(item => {
            item.style.display = (item.dataset.search || '').includes(q) ? '' : 'none';
        });
    });

    // select all / clear links
    document.querySelectorAll('.link-mini').forEach(btn => {
        btn.addEventListener('click', () => {
            const set = btn.dataset.act === 'all';
            if (btn.dataset.pick === 'emp') {
                empPicks().forEach(c => {
                    const item = c.closest('.emp-pick-item');
                    if (item.style.display !== 'none') c.checked = set;
                });
            } else {
                rolePicks().forEach(c => c.checked = set);
            }
            refreshPickerCounts();
        });
    });

    function openModal(preselectIds) {
        empPicks().forEach(c => c.checked = preselectIds.includes(c.value));
        rolePicks().forEach(c => c.checked = false);
        document.getElementById('empPickerSearch').value = '';
        document.querySelectorAll('.emp-pick-item').forEach(i => i.style.display = '');
        assignError.style.display = 'none';
        refreshPickerCounts();
        empRoleModal.show();
    }

    document.getElementById('btnCreateModal').addEventListener('click', () => openModal([]));
    document.getElementById('btnBulkAssign').addEventListener('click', () => openModal(selectedIds()));

    // validation before submit
    document.getElementById('frmEmpRoleAssign').addEventListener('submit', function (e) {
        const emps  = empPicks().filter(c => c.checked).length;
        const roles = rolePicks().filter(c => c.checked).length;
        if (!emps || !roles) {
            e.preventDefault();
            assignError.textContent = 'Select at least one employee and one role.';
            assignError.style.display = 'block';
        }
    });

    /* ───────── Remove role (existing behaviour) ───────── */
    window.confirmRemoveRole = function (userId, roleName) {
        Swal.fire({
            title: 'Remove Role?',
            html: `Are you sure you want to remove the role <b>${roleName}</b> from this user?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, remove it',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/users/${userId}/roles/${roleName}`;

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';

                form.appendChild(csrfInput);
                form.appendChild(methodInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    };

    // init
    applyFilter();
    refreshBulk();
})();
</script>

@endsection
