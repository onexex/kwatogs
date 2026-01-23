@extends('layout.app')
@section('content')

<style>
    /* Modern Badge Styles */
    .badge-regular {
        background-color: #e3f2fd;
        color: #0d47a1;
        border: 1px solid #bbdefb;
    }
    .badge-special {
        background-color: #fff3e0;
        color: #e65100;
        border: 1px solid #ffe0b2;
    }
    
    /* Sticky Header Polish */
    .table-sticky-header thead th {
        position: sticky !important;
        top: 0;
        background-color: #ffffff;
        z-index: 10;
        border-bottom: 2px solid #f8f9fa;
    }

    /* Row transition */
    .table-hover tbody tr {
        transition: all 0.2s ease;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        transform: scale(1.002);
    }
</style>

<div class="container-fluid px-4 py-3">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">Holiday Logger</h4>
            <p class="text-muted small mb-0">Manage and track company holidays and observances.</p>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" name="btnCreateHoliday" id="btnCreateHoliday" data-bs-toggle="modal" data-bs-target="#mdlHoliday">
            <i class="fas fa-calendar-plus me-2"></i> Add Holiday
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover align-middle table-sticky-header mb-0">
                    <thead class="bg-light">
                        <tr class="text-secondary small fw-bold text-uppercase tracking-wider">
                            <th class="ps-4 py-3">Date</th>
                            <th class="py-3">Type</th>
                            <th class="py-3">Description</th>
                            <th class="pe-4 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tblHolidaysLog" class="border-top-0">
                        
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mdlHoliday" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-secondary text-uppercase tracking-wide">
                        <i class="fas fa-calendar-check me-2 text-primary"></i> 
                        <span id="lblTitleHoliday">Holiday Logger</span>
                    </h5>
                    <button type="button" class="btn-close closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <form id="frmHoliday">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label small fw-semibold text-muted">Date <span class="text-danger">*</span></label>
                                    <input class="form-control form-control-lg bg-light border-0 fs-6" id="txtDate" name="date" type="date" />
                                    <span class="text-danger small error-text date_error"></span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label small fw-semibold text-muted">Holiday Type <span class="text-danger">*</span></label>
                                    <select class="form-select form-control-lg bg-light border-0 fs-6" name="type" id="selTypeHoliday">
                                        <option value="0">Regular Holiday</option>
                                        <option value="1">Special Holiday</option>
                                    </select>
                                    <span class="text-danger small error-text type_error"></span>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label small fw-semibold text-muted">Description <span class="text-danger">*</span></label>
                                    <input class="form-control form-control-lg bg-light border-0 fs-6" id="txtDescription" name="description" type="text" placeholder="e.g. Independence Day" />
                                    <span class="text-danger small error-text description_error"></span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted me-2" data-bs-dismiss="modal">Cancel</button>
                    <button id="btnSaveHoliday" type="button" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Create Holiday</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="{{ asset('js/settings/holidaylogger.js') }}" defer></script>

@endsection