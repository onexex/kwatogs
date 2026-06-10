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
    .companies-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    /* ── Top header bar ──────────────────────────────────────── */
    .companies-topbar {
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
    .companies-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .companies-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }

    .btn-add-company {
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
    .btn-add-company:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

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
    .companies-table thead th {
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
    .companies-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .companies-table tbody tr:hover { background: var(--teal-light); }

    /* ── Color preview circle ────────────────────────────────── */
    .color-preview {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: inline-block;
        border: 2px solid #fff;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }

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
    #mdlCompany .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #mdlCompany .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #mdlCompany .modal-header .modal-title { color: #fff; }
    #mdlCompany .modal-header .modal-title i { color: #fff; }
    #mdlCompany .btn-close { filter: brightness(0) invert(1); }
    #mdlCompany .modal-body { background: var(--bg); padding: 22px; }
    #mdlCompany .modal-footer {
        background: var(--surface);
        border-top: 1px solid var(--border);
    }

    .btn-submit-company {
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
    .btn-submit-company:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,.4); color: #fff; }

    .btn-cancel-company {
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
    .btn-cancel-company:hover { background: var(--bg); }
</style>

<div class="companies-shell">

    {{-- ── Top header ── --}}
    <div class="companies-topbar">
        <div>
            <p class="page-title">Companies</p>
            <p class="page-sub">Manage company profiles, branding, and codes</p>
        </div>
        <button type="button" class="btn-add-company" id="createCompany" data-bs-toggle="modal" data-bs-target="#mdlCompany">
            <i class="fa-solid fa-building"></i> Add New Company
        </button>
    </div>

    {{-- ── Company Records ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-building"></i></div>
                <h5 class="sc-title">Company Records</h5>
            </div>
        </div>
        <div class="sc-body">
            <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover align-middle companies-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Code</th>
                            <th>Company Name</th>
                            <th class="text-center">Brand Color</th>
                            <th class="pe-4 text-end">Action</th>
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
</div>

<div class="modal fade" id="mdlCompany" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-building me-2"></i>
                    <span class="lblActionDesc">Create Company</span>
                </h5>
                <button type="button" class="btn-close closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                <div class="sub-divider"><span>Company Details</span></div>

                <form action="" id="frmCreateCompany">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="field-label">Company ID <span class="req">*</span></label>
                            <input class="form-control" id="txtCompanyID" name="companyid" type="text" placeholder="e.g. COMP-001" />
                            <span class="text-danger small error-text companyid_error"></span>
                        </div>

                        <div class="col-lg-6">
                            <label class="field-label">Company Code <span class="req">*</span></label>
                            <input class="form-control" id="txtCompanyCode" name="code" type="text" placeholder="Short code (e.g. GOOG)" />
                            <span class="text-danger small error-text code_error"></span>
                        </div>

                        <div class="col-lg-12">
                            <label class="field-label">Company Name <span class="req">*</span></label>
                            <input class="form-control" id="txtCompanyName" name="company" type="text" placeholder="Full Legal Name" />
                            <span class="text-danger small error-text company_error"></span>
                        </div>
                    </div>

                    <div class="sub-divider mt-4"><span>Branding</span></div>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="field-label">Brand Color <span class="req">*</span></label>
                            <div class="d-flex align-items-center" style="background:#fafbfc; border:1.5px solid var(--border); border-radius: var(--radius-input); padding: 0.45rem 0.85rem;">
                                <input class="form-control-color border-0 bg-transparent" id="txtCompanyColor" name="color" type="color" value="#0d6efd" style="width: 40px; height: 40px;" />
                                <span class="ms-2 text-muted small">Pick a theme color</span>
                            </div>
                            <span class="text-danger small error-text color_error"></span>
                        </div>

                        <div class="col-lg-6">
                            <label class="field-label">Company Logo <span class="req">*</span></label>
                            <input class="form-control" id="txtCompanyLogo" name="logo" type="file" accept="image/*" />
                            <div class="form-text small opacity-75">Upload a high-res PNG or JPG.</div>
                            <span class="text-danger small error-text logo_error"></span>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel-company" data-bs-dismiss="modal">Cancel</button>
                <button id="btnSaveCompany" type="button" class="btn-submit-company">Save Company</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/company.js') }}" defer></script>
@endsection
