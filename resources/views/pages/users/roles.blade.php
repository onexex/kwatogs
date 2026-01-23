@extends('layout.app', ['title' => 'User Roles'])

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
</style>

<div class="container-fluid px-4 py-3">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">Settings</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted">Settings</li>
                    <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">User Roles Definition</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" id="createUserRole" data-bs-toggle="modal" data-bs-target="#createUserRoleModal">
            <i class="fas fa-plus me-2"></i> Add User Role
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover align-middle table-sticky-header mb-0">
                    <thead class="bg-light">
                        <tr class="text-secondary small fw-bold text-uppercase tracking-wider">
                            <th class="ps-4 py-3" style="width: 80px;">No</th>
                            <th class="py-3">Role Name</th>
                            <th class="pe-4 py-3 text-end" style="width: 250px;">Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @foreach($roles as $role)
                        <tr>
                            <td class="ps-4 text-muted small">{{ $loop->iteration }}</td>
                            <td class="fw-bold text-dark text-uppercase">{{ $role->name }}</td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-light btn-sm rounded-pill shadow-sm px-3 fw-bold text-primary editRoleBtn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editRoleBtnModal{{ $role->id }}">
                                        <i class="fa fa-edit me-1"></i> Edit
                                    </button>
                                    
                                    <a href="{{ route('user-roles.show', $role->id) }}" class="btn btn-light btn-sm rounded-pill shadow-sm px-3 fw-bold text-success">
                                        <i class="fa fa-eye me-1"></i> Permissions
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade" id="editRoleBtnModal{{ $role->id }}" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow rounded-4">
                                    <div class="modal-header border-0 pt-4 px-4">
                                        <h5 class="modal-title fw-bold text-secondary text-uppercase tracking-wide">
                                            <i class="fas fa-edit me-2 text-primary"></i> Edit Role
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <form method="POST" action="{{ route('user-roles.update', $role->id) }}" id="frmEditRoles{{ $role->id }}">
                                            @csrf 
                                            @method('PUT')
                                            <div class="form-group mb-0">
                                                <label class="form-label small fw-semibold text-muted">Role Name <span class="text-danger">*</span></label>
                                                <input class="form-control form-control-lg bg-light border-0 fs-6" name="role" type="text" value="{{ $role->name }}" required />
                                                <span class="text-danger small error-text role_error"></span>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer border-0 pb-4 px-4">
                                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted me-2" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" form="frmEditRoles{{ $role->id }}" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Update Role</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createUserRoleModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-secondary text-uppercase tracking-wide">
                        <i class="fas fa-plus-circle me-2 text-primary"></i> New User Role
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" action="{{ route('user-roles.store') }}" id="frmRoles">
                        @csrf 
                        <div class="form-group mb-0">
                            <label class="form-label small fw-semibold text-muted">Role Name <span class="text-danger">*</span></label>
                            <input class="form-control form-control-lg bg-light border-0 fs-6" name="role" type="text" placeholder="e.g., Department Head" required />
                            <span class="text-danger small error-text role_error"></span>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="frmRoles" id="btnSaveOBT" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Submit Role</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection