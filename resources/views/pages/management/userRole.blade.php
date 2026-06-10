@extends('layout.app')
@section('content')

<style>
    /* ── Design tokens (shared with Edit Employee / Leave / Overtime / Loan) ── */
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

    /* ── Page shell ──────────────────────────────────────────── */
    .userrole-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    /* ── Top header bar ──────────────────────────────────────── */
    .userrole-topbar {
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
    .userrole-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .userrole-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
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
    .sc-body { padding: 22px; }

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
    .field-icon {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: var(--teal-light);
        color: var(--teal);
        border-radius: 6px;
        margin-right: 10px;
    }

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
    .userrole-table thead th {
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
    .userrole-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .userrole-table tbody tr:hover { background: var(--teal-light); }

    /* ── Role badges (rendered via JS) ───────────────────────── */
    .badge-role-superuser {
        background: rgba(239,68,68,.1);
        color: var(--danger);
        border: 1px solid var(--danger);
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: .3px;
        padding: 5px 12px;
        border-radius: 20px;
    }
    .badge-role-admin {
        background: rgba(245,158,11,.1);
        color: var(--warning);
        border: 1px solid var(--warning);
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: .3px;
        padding: 5px 12px;
        border-radius: 20px;
    }
    .badge-role-user {
        background: var(--teal-light);
        color: var(--teal-dark);
        border: 1px solid var(--teal-mid);
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: .3px;
        padding: 5px 12px;
        border-radius: 20px;
    }

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
        color: var(--teal);
    }
    .icon-action-btn:hover { border-color: var(--teal-mid); background: var(--teal-light); }

    /* ── Modal styling ───────────────────────────────────────── */
    #mdlUserRole .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #mdlUserRole .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #mdlUserRole .modal-header .modal-title { color: #fff; }
    #mdlUserRole .modal-header .modal-title i { color: #fff; }
    #mdlUserRole .btn-close { filter: brightness(0) invert(1); }
    #mdlUserRole .modal-body { background: var(--bg); padding: 22px; }
    #mdlUserRole .modal-footer {
        background: var(--surface);
        border-top: 1px solid var(--border);
    }

    .btn-submit-userrole {
        background: var(--teal);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 10px 26px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(0,128,128,.25);
        transition: all .2s;
    }
    .btn-submit-userrole:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

    .btn-cancel-userrole {
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
    .btn-cancel-userrole:hover { background: var(--bg); }

    /* ── Empty state ─────────────────────────────────────────── */
    .userrole-empty {
        padding: 50px 20px;
        text-align: center;
        color: var(--muted);
    }
    .userrole-empty i {
        font-size: 2.2rem;
        color: var(--teal-mid);
        margin-bottom: 10px;
        display: block;
    }
</style>

<div class="userrole-shell">

    {{-- ── Top header ── --}}
    <div class="userrole-topbar">
        <div>
            <p class="page-title">User Roles</p>
            <p class="page-sub">Search an employee and manage their assigned system role</p>
        </div>
    </div>

    {{-- ── Search ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                <h5 class="sc-title">Search Employee</h5>
            </div>
        </div>
        <div class="sc-body">
            <form action="" id="frmSearch">
                <div class="row g-3">
                    <div class="col-md-5 col-lg-4">
                        <label class="field-label" for="txtSearchStr">
                            <div class="field-icon"><i class="fa fa-user"></i></div> Search by Lastname
                        </label>
                        <input class="form-control" id="txtSearchStr" name="lastname" type="text" placeholder="Type employee lastname..."/>
                        <span class="text-danger small error-text lastname_error"></span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Records ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-users-gear"></i></div>
                <h5 class="sc-title">Employee Roles</h5>
            </div>
        </div>
        <div class="sc-body p-0">
            <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                <table class="table table-hover align-middle userrole-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>User Role</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tblUserRole" class="border-top-0">
                        <tr>
                            <td colspan="3">
                                <div class="userrole-empty">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    Search for an employee by lastname to manage their role.
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="mdlUserRole" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">
                    <i class="fa-solid fa-user-shield me-2"></i>
                    <span class="lblActionDesc">User Role</span>
                </h5>
                <button type="button" class="btn-close closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" id="frmUserRole">
                    <div class="sub-divider"><span>Role Assignment</span></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="field-label" for="txtUserRole">User Role <span class="req">*</span></label>
                            <select class="form-select" name="role" id="txtUserRole">
                                <option value="1">Superuser</option>
                                <option value="2">Admin</option>
                                <option value="3">User</option>
                            </select>
                            <span class="text-danger small error-text role_error"></span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel-userrole closereset_update" data-bs-dismiss="modal">Cancel</button>
                <button id="btnSaveUserRole" type="button" class="btn-submit-userrole">Save Entries</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/userroles.js') }}" defer></script>
@endsection
