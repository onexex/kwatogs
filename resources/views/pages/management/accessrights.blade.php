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
        padding: 24px 28px 60px;
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
    }
    .role-badge:hover {
        background: var(--danger);
        color: #fff;
        border-color: var(--danger);
        transform: translateY(-1px);
    }

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
        <button type="button" class="btn-add-access" id="btnCreateModal" data-bs-toggle="modal" data-bs-target="#mdlEmpRole">
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
        </div>
        <div class="sc-body">
            <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover align-middle access-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Employee Name</th>
                            <th>Current Roles</th>
                        </tr>
                    </thead>
                    <tbody id="tblAccessRights" class="border-top-0">
                        @foreach ($users as $user)
                            <tr>
                                <td class="ps-4 fw-bold text-dark text-uppercase small">{{ $user->lname }}, {{ $user->fname }}</td>
                                <td class="py-3">
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($user->roles as $role)
                                            <span
                                                class="role-badge text-uppercase"
                                                onclick="confirmRemoveRole('{{ $user->id }}', '{{ $role->name }}')"
                                                title="Click to remove role"
                                            >
                                                {{ $role->name }} <i class="fas fa-times ms-1 small"></i>
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    <div class="modal fade" id="mdlEmpRole" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-tag me-2"></i> Assign Role
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form id="frmEmpRoleAssign" method="POST" action="{{ route('employee.roles.assign') }}">
                        @csrf
                        <div class="sub-divider"><span>Role Assignment</span></div>
                        <div class="mb-3">
                            <label class="field-label" for="selEmployee">Employee <span class="req">*</span></label>
                            <select class="form-select text-uppercase" id="selEmployee" name="employee_id" required>
                                <option value="" selected disabled>Choose Employee...</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->lname }}, {{ $emp->fname }}</option>
                                @endforeach
                            </select>
                            <span class="text-danger small error-text employee_id_error"></span>
                        </div>

                        <div class="mb-0">
                            <label class="field-label" for="selRole">Assign Role <span class="req">*</span></label>
                            <select class="form-select" id="selRole" name="role_id" required>
                                <option value="" selected disabled>Select Role Type...</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <span class="text-danger small error-text role_id_error"></span>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel-access" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="frmEmpRoleAssign" class="btn-submit-access">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Clean confirmation for removing roles
    function confirmRemoveRole(userId, roleName) {
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
                // Submit via hidden form or Axios
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
    }
</script>

@endsection
