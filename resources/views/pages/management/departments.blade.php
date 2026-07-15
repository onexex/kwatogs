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

    /* ── Page shell ──────────────────────────────────────────── */
    .departments-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    /* ── Top header bar ──────────────────────────────────────── */
    .departments-topbar {
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
    .departments-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .departments-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }

    .btn-add-department {
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
    .btn-add-department:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

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
    .departments-table thead th {
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
    .departments-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .departments-table tbody tr:hover { background: var(--teal-light); }

    .badge-status {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: .3px;
        padding: 5px 12px;
        border-radius: 20px;
        border: 1px solid transparent;
    }
    .badge-status.active {
        background: rgba(16,185,129,.1);
        color: var(--success);
        border-color: var(--success);
    }
    .badge-status.inactive {
        background: var(--bg);
        color: var(--slate-light);
        border-color: var(--border);
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
    }
    .icon-action-btn:hover { border-color: var(--teal-mid); background: var(--teal-light); }
    .icon-action-btn.danger:hover { border-color: var(--danger); background: #fff5f5; }

    /* ── Modal styling ───────────────────────────────────────── */
    #mdlDepartment .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #mdlDepartment .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #mdlDepartment .modal-header .modal-title { color: #fff; }
    #mdlDepartment .modal-header .modal-title i { color: #fff; }
    #mdlDepartment .btn-close { filter: brightness(0) invert(1); }
    #mdlDepartment .modal-body { background: var(--bg); padding: 22px; }
    #mdlDepartment .modal-footer {
        background: var(--surface);
        border-top: 1px solid var(--border);
    }

    .btn-submit-department {
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
    .btn-submit-department:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,.4); color: #fff; }

    .btn-cancel-department {
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
    .btn-cancel-department:hover { background: var(--bg); }
</style>

<div class="departments-shell">

    {{-- ── Top header ── --}}
    <div class="departments-topbar">
        <div>
            <p class="page-title">Departments</p>
            <p class="page-sub">Manage organizational departments</p>
        </div>
        <button type="button" class="btn-add-department" name="department" id="btnCreateDept" data-bs-toggle="modal" data-bs-target="#mdlDepartment">
            <i class="fa-solid fa-sitemap"></i> Add Department
        </button>
    </div>

    {{-- ── Department Records ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-sitemap"></i></div>
                <h5 class="sc-title">Department Records</h5>
            </div>
        </div>
        <div class="sc-body">
            <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover align-middle departments-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Department Name</th>
                            <th class="pe-4 text-end" style="width: 150px;">Action</th>
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
</div>

<div class="modal fade" id="mdlDepartment" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-sitemap me-2"></i>
                    <span id="lblTitleDept">Department</span>
                </h5>
                <button type="button" class="btn-close closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                <form id="frmDepartment">
                    {{-- ── Company Profile ── --}}
                    <div class="sub-divider"><span>Company Profile</span></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="txtDeptName" class="field-label">Department / Company Name <span class="req">*</span></label>
                            <input class="form-control" id="txtDeptName" name="department" type="text" placeholder="e.g. Demo Company Inc." />
                            <span class="text-danger small error-text department_error"></span>
                        </div>
                        <div class="col-md-6">
                            <label for="txtDeptPhone" class="field-label">Contact Phone</label>
                            <input class="form-control" id="txtDeptPhone" name="dep_contact_phone" type="text" placeholder="e.g. (02) 8123 4567" />
                        </div>
                        <div class="col-md-6">
                            <label for="txtDeptEmail" class="field-label">Email</label>
                            <input class="form-control" id="txtDeptEmail" name="dep_email" type="text" placeholder="e.g. hr@company.com" />
                            <span class="text-danger small error-text dep_email_error"></span>
                        </div>
                        <div class="col-md-6">
                            <label for="txtDeptAddress" class="field-label">Address</label>
                            <input class="form-control" id="txtDeptAddress" name="dep_address" type="text" placeholder="Office address" />
                        </div>
                        <div class="col-12">
                            <label for="txtDeptDescription" class="field-label">Description / Notes</label>
                            <textarea class="form-control" id="txtDeptDescription" name="dep_description" rows="2" placeholder="Optional notes about this company/department"></textarea>
                        </div>
                    </div>

                    {{-- ── Government Employer Numbers ── --}}
                    <div class="sub-divider mt-4"><span>Government Employer Numbers</span></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="txtDeptTin" class="field-label">TIN</label>
                            <input class="form-control" id="txtDeptTin" name="dep_tin" type="text" placeholder="000-000-000-000" />
                        </div>
                        <div class="col-md-6">
                            <label for="txtDeptSss" class="field-label">SSS Employer No.</label>
                            <input class="form-control" id="txtDeptSss" name="dep_sss_employer_no" type="text" placeholder="" />
                        </div>
                        <div class="col-md-6">
                            <label for="txtDeptPhilhealth" class="field-label">PhilHealth Employer No.</label>
                            <input class="form-control" id="txtDeptPhilhealth" name="dep_philhealth_employer_no" type="text" placeholder="" />
                        </div>
                        <div class="col-md-6">
                            <label for="txtDeptPagibig" class="field-label">Pag-IBIG Employer No.</label>
                            <input class="form-control" id="txtDeptPagibig" name="dep_pagibig_employer_no" type="text" placeholder="" />
                        </div>
                    </div>

                    {{-- ── Logo ── --}}
                    <div class="sub-divider mt-4"><span>Company Logo</span></div>
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <label for="txtDeptLogo" class="field-label">Upload Logo</label>
                            <input class="form-control" id="txtDeptLogo" name="logo" type="file" accept="image/*" />
                            <span class="text-danger small error-text logo_error"></span>
                        </div>
                        <div class="col-md-4 text-center">
                            <img id="imgDeptLogoPreview" src="" alt="Logo preview"
                                 style="max-height:70px; max-width:100%; display:none; border:1px solid var(--border); border-radius:8px; padding:4px; background:#fff;" />
                        </div>
                    </div>
                </form>

                {{-- ── Related Documents (edit mode only — needs an existing department) ── --}}
                <div id="deptDocsSection" style="display:none;">
                    <div class="sub-divider mt-4"><span>Related Documents</span></div>

                    <form id="frmDeptDoc">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label for="txtDeptDocLabel" class="field-label">Document Label</label>
                                <input class="form-control" id="txtDeptDocLabel" name="label" type="text" placeholder="e.g. BIR Registration" />
                            </div>
                            <div class="col-md-5">
                                <label for="txtDeptDocFile" class="field-label">PDF File</label>
                                <input class="form-control" id="txtDeptDocFile" name="document" type="file" accept="application/pdf,.pdf" />
                                <span class="text-danger small error-text document_error"></span>
                            </div>
                            <div class="col-md-2">
                                <button id="btnUploadDeptDoc" type="button" class="btn btn-add-department w-100 justify-content-center">
                                    <i class="fa-solid fa-upload"></i> Upload
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive mt-3" style="max-height: 240px; overflow-y: auto;">
                        <table class="table table-hover align-middle departments-table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">Label</th>
                                    <th>File</th>
                                    <th class="text-end pe-3" style="width: 110px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="tblDeptDocs"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel-department" data-bs-dismiss="modal">Cancel</button>
                <button id="btnDepSave" type="button" class="btn-submit-department">Save Department</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/department.js') }}" defer></script>

@endsection
