@extends('layout.app')
@section('content')

<style>
    /* Sticky Header with modern blur effect */
    .table-sticky-header thead th {
        position: sticky !important;
        top: 0;
        background-color: #ffffff;
        z-index: 10;
        border-bottom: 2px solid #f8f9fa;
    }
    
    /* Clean color preview circle */
    .color-preview {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: inline-block;
        border: 2px solid #fff;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }
</style>

<div class="container-fluid px-4 py-3">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">Settings</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted">Settings</li>
                    <li class="breadcrumb-item active fw-semibold" aria-current="page text-primary">Companies</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" id="createCompany" data-bs-toggle="modal" data-bs-target="#mdlCompany">
            <i class="fas fa-plus me-2"></i> Add New Company
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0"> <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                <table class="table table-hover align-middle table-sticky-header mb-0">
                    <thead class="bg-light">
                        <tr class="text-secondary small fw-bold text-uppercase tracking-wider">
                            <th class="ps-4 py-3">ID</th>
                            <th class="py-3">Code</th>
                            <th class="py-3">Company Name</th>
                            <th class="py-3 text-center">Brand Color</th>
                            <th class="pe-4 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tblCompanies" class="border-top-0">
                        <tr class="text-center py-5">
                            <td colspan="5" class="text-muted py-5">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                Loading companies...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mdlCompany" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-secondary text-uppercase tracking-wide">
                        <i class="fas fa-building me-2 text-primary"></i> 
                        <span class="lblActionDesc">Create Company</span>
                    </h5>
                    <button type="button" class="btn-close closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <form action="" id="frmCreateCompany">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="form-group mb-3">
                                    <label class="form-label small fw-semibold text-muted">Company ID <span class="text-danger">*</span></label>
                                    <input class="form-control form-control-lg bg-light border-0 fs-6" id="txtCompanyID" name="companyid" type="text" placeholder="e.g. COMP-001" />
                                    <span class="text-danger small error-text companyid_error"></span>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label class="form-label small fw-semibold text-muted">Company Name <span class="text-danger">*</span></label>
                                    <input class="form-control form-control-lg bg-light border-0 fs-6" id="txtCompanyName" name="company" type="text" placeholder="Full Legal Name" />
                                    <span class="text-danger small error-text company_error"></span>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label small fw-semibold text-muted">Company Code <span class="text-danger">*</span></label>
                                    <input class="form-control form-control-lg bg-light border-0 fs-6" id="txtCompanyCode" name="code" type="text" placeholder="Short code (e.g. GOOG)" />
                                    <span class="text-danger small error-text code_error"></span>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="form-group mb-3">
                                    <label class="form-label small fw-semibold text-muted">Brand Color <span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-center bg-light rounded-3 px-3 py-2">
                                        <input class="form-control-color border-0 bg-transparent" id="txtCompanyColor" name="color" type="color" value="#0d6efd" style="width: 40px; height: 40px;" />
                                        <span class="ms-2 text-muted small">Pick a theme color</span>
                                    </div>
                                    <span class="text-danger small error-text color_error"></span>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label small fw-semibold text-muted">Company Logo <span class="text-danger">*</span></label>
                                    <input class="form-control form-control-lg bg-light border-0 fs-6" id="txtCompanyLogo" name="logo" type="file" accept="image/*" />
                                    <div class="form-text small opacity-75">Upload a high-res PNG or JPG.</div>
                                    <span class="text-danger small error-text logo_error"></span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted me-2" data-bs-dismiss="modal">Cancel</button>
                    <button id="btnSaveCompany" type="button" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Save Company</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/company.js') }}" defer></script>
@endsection