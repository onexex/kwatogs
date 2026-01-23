@extends('layout.app', ['title' => 'Roles Permission'])

@section('content')
    
    {{-- <div class="container-fluid">
        
        <div class="mb-2 d-sm-flex align-items-center justify-content-between">
            <h4 class=" mb-0 text-gray-800">Roles Permission : {{ $role->name }}</h4>
            <div>
                <a 
                    href="{{ route('user-roles.index') }}" 
                    class=" btn text-white" 
                    style="background-color: #008080" 
                > <i class="fa fa-arrow-left"></i> 
                    Back to Roles
                </a>
            </div>
        </div>
        <div>
            <div class="row">
                <ul class="nav nav-pills list-inline px-4" role="tablist">
                    <li class="nav-item pr-2">
                        <a
                            href="{{ route('user-roles.show', ['user_role' => $role->id, 'permission' => 'page']) }}"
                            class="nav-link text-secondary list-inline-item shadow-sm {{ $permissiontab === 'page' ? 'active' : '' }}"
                        >
                            <i class="tf-icons bx bx-user"></i> Page Permission
                        </a>
                    </li>
                    <li class="nav-item pr-2">
                        <a
                            href="{{ route('user-roles.show', ['user_role' => $role->id, 'permission' => 'overtime']) }}"
                            class="nav-link text-secondary list-inline-item shadow-sm {{ $permissiontab === 'overtime' ? 'active' : '' }}"
                        >
                            <i class="tf-icons bx bx-user"></i> Overtime Permission
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-xl-12 col-lg-12">
                <div class="card mb-4">
                    <!-- Card Body -->
                    <div class="card-body">
                        <div class="chart-area">
                            <div class="table-responsive border-0">
                                <table class="table table-hover table-border-none  ">
                                    <thead>
                                        <tr>
                                            <th class="text-dark" scope="col">No</th>
                                            <th class="text-dark" scope="col">Permissions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($permissions as $group)
                                            <tr class="table-primary">
                                                <td colspan="3"><strong>{{ $group['title'] }}</strong></td>
                                            </tr> --}}

<style>
    /* Uniform Design Elements */
    .table-sticky-header thead th {
        position: sticky;
        top: 0;
        background-color: #ffffff;
        z-index: 10;
        border-bottom: 2px solid #f8f9fa;
    }

    /* Modern Toggle Switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 20px;
    }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #e9ecef;
        transition: .3s;
        border-radius: 20px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 14px;
        width: 14px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    input:checked + .slider { background-color: #008080; }
    input:checked + .slider:before { transform: translateX(20px); }

    /* Pill Navigation Styling */
    .nav-pills .nav-link {
        border-radius: 50px;
        font-weight: 600;
        padding: 8px 20px;
        color: #6c757d;
        background: #fff;
        border: 1px solid #dee2e6;
        margin-right: 10px;
    }
    .nav-pills .nav-link.active {
        background-color: #008080 !important;
        border-color: #008080 !important;
        color: #fff !important;
        box-shadow: 0 4px 6px rgba(0,128,128,0.2);
    }
</style>

<div class="container-fluid px-4 py-3">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">Roles Permission</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item text-muted">Settings</li>
                    <li class="breadcrumb-item text-muted">User Roles</li>
                    <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">{{ $role->name }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('user-roles.index') }}" class="btn btn-light rounded-pill px-4 shadow-sm fw-bold border">
            <i class="fa fa-arrow-left me-2"></i> Back to Roles
        </a>
    </div>

    <div class="mb-4">
        <ul class="nav nav-pills" role="tablist">
            <li class="nav-item">
                <a href="{{ route('user-roles.show', ['user_role' => $role->id, 'permission' => 'page']) }}"
                   class="nav-link {{ $permissiontab === 'page' ? 'active' : '' }}">
                   <i class="fas fa-file-alt me-2"></i> Page Permissions
                </a>
            </li>
            </ul>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-sticky-header">
                    <thead class="bg-light">
                        <tr class="text-secondary small fw-bold text-uppercase tracking-wider">
                            <th class="ps-4 py-3" style="width: 100px;">Index</th>
                            <th class="py-3">Permission Name</th>
                            <th class="pe-4 py-3 text-center" style="width: 150px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions as $group)
                            <tr class="bg-light bg-gradient">
                                <td colspan="3" class="ps-4 py-2 border-bottom text-primary fw-bold small">
                                    <i class="fas fa-folder-open me-2"></i> {{ strtoupper($group['title']) }}
                                </td>
                            </tr>

                            @foreach($group['permissions'] as $key => $name)
                                <tr>
                                    <td class="ps-4 text-muted small">
                                        {{ $loop->parent->iteration }}.{{ $loop->iteration }}
                                    </td>
                                    <td class="fw-medium text-dark">
                                        {{ $name }}
                                        <br><small class="text-muted fw-normal">{{ $key }}</small>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <label class="switch">
                                            <input 
                                                type="checkbox"
                                                class="permission-checkbox"
                                                data-role-id="{{ $role->id }}"
                                                data-permission="{{ $key }}"
                                                {{ $role->hasPermissionTo($key) ? 'checked' : '' }}
                                            >
                                            <span class="slider"></span>
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', async (e) => {
            const checkboxEl = e.target;
            const roleId = checkboxEl.dataset.roleId;
            const permission = checkboxEl.dataset.permission;
            const checked = checkboxEl.checked;

            // Optional: Toast notification setup
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });

            try {
                const response = await fetch(`/roles/${roleId}/permissions`, {
                    method: checked ? 'POST' : 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ permission })
                });

                if (!response.ok) throw new Error(await response.text());

                Toast.fire({
                    icon: 'success',
                    title: `Permission ${checked ? 'granted' : 'revoked'}`
                });

            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: 'Unable to change permission status.'
                });
                checkboxEl.checked = !checked; // Revert
            }
        });
    });
});
</script>
@endsection