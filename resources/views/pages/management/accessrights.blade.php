@extends('layout.app')
@section('content')

<style>
    /* Consistent Sticky Header and Table Design */
    .table-sticky-header thead th {
        position: sticky !important;
        top: 0;
        background-color: #ffffff;
        z-index: 10;
        border-bottom: 2px solid #f8f9fa;
    }

    .table-hover tbody tr:hover {
        background-color: #fcfcfc;
        transition: background-color 0.2s ease;
    }

    /* Modern Role Badges */
    .role-badge {
        font-weight: 600;
        letter-spacing: 0.5px;
        padding: 0.5em 1em;
        transition: transform 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .role-badge:hover {
        transform: translateY(-2px);
        filter: brightness(90%);
    }
</style>

<div class="container-fluid px-4 py-3">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">Settings</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted">Settings</li>
                    <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Employee Roles</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" id="btnCreateModal" data-bs-toggle="modal" data-bs-target="#mdlEmpRole">
            <i class="fas fa-plus me-2"></i> Assign New Role
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover align-middle table-sticky-header mb-0">
                    <thead class="bg-light">
                        <tr class="text-secondary small fw-bold text-uppercase tracking-wider">
                            <th class="ps-4 py-3">Employee Name</th>
                            <th class="py-3">Current Roles</th>
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
                                                class="badge rounded-pill bg-primary role-badge text-uppercase"
                                                onclick="confirmRemoveRole('{{ $user->id }}', '{{ $role->name }}')"
                                                style="cursor: pointer;"
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

    <div class="modal fade" id="mdlEmpRole" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-secondary text-uppercase tracking-wide">
                        <i class="fas fa-user-tag me-2 text-primary"></i> Assign Role
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <form id="frmEmpRoleAssign" method="POST" action="{{ route('employee.roles.assign') }}">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-muted">Employee <span class="text-danger">*</span></label>
                            <select class="form-select form-control-lg bg-light border-0 fs-6" id="selEmployee" name="employee_id" required>
                                <option value="" selected disabled>Choose Employee...</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->lname }}, {{ $emp->fname }}</option>
                                @endforeach
                            </select>
                            <span class="text-danger small error-text employee_id_error"></span>
                        </div>

                        <div class="mb-0">
                            <label class="form-label small fw-semibold text-muted">Assign Role <span class="text-danger">*</span></label>
                            <select class="form-select form-control-lg bg-light border-0 fs-6" id="selRole" name="role_id" required>
                                <option value="" selected disabled>Select Role Type...</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <span class="text-danger small error-text role_id_error"></span>
                        </div>
                    </form>
                </div>
                
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="frmEmpRoleAssign" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Save Changes</button>
                </div>
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