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
</style>

<div class="container-fluid px-4 py-3">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">Settings</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted">Settings</li>
                    <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Department</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" name="department" id="btnCreateDept" data-bs-toggle="modal" data-bs-target="#mdlDepartment">
            <i class="fas fa-plus me-2"></i> Add Department
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                <table class="table table-hover align-middle table-sticky-header mb-0">
                    <thead class="bg-light">
                        <tr class="text-secondary small fw-bold text-uppercase tracking-wider">
                            <th class="ps-4 py-3">Department Name</th>
                            <th class="pe-4 py-3 text-end" style="width: 150px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tblDepartments" class="border-top-0">
                        <tr>
                            <td colspan="2" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                Loading departments...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mdlDepartment" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-secondary text-uppercase tracking-wide">
                        <i class="fas fa-sitemap me-2 text-primary"></i> 
                        <span id="lblTitleDept">Department</span>
                    </h5>
                    <button type="button" class="btn-close closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <form id="frmDepartment">
                        <div class="form-group mb-0">
                            <label for="txtDeptName" class="form-label small fw-semibold text-muted">Department Name <span class="text-danger">*</span></label>
                            <input class="form-control form-control-lg bg-light border-0 fs-6" id="txtDeptName" name="department" type="text" placeholder="e.g. Human Resources" />
                            <span class="text-danger small error-text department_error"></span>
                        </div>
                    </form>
                </div>
                
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted me-2" data-bs-dismiss="modal">Cancel</button>
                    <button id="btnDepSave" type="button" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Save Department</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="{{ asset('js/settings/department.js') }}" defer></script>

@endsection