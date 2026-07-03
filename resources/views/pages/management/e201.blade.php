@extends('layout.app')

@section('content')
<style>
    /* ── Shared design tokens (same palette as Pending Leave Requests) ── */
    :root {
        --hr-teal:      #008080;
        --hr-bg:        #f4f7f6;
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
        --radius-card:  14px;
        --radius-input: 8px;
        --shadow-card:  0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }
    body { background-color: var(--bg); overflow-x: hidden; }

    /* ── Page shell + branded topbar (matches Pending Leave Requests) ── */
    .e201-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 40px;
        margin: -1.5rem -1.5rem 0;
    }
    .e201-topbar {
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
    .e201-topbar .page-title { font-size: 1.1rem; font-weight: 700; color: var(--slate); margin: 0; letter-spacing: -.2px; }
    .e201-topbar .page-sub { font-size: .78rem; color: var(--muted); margin: 2px 0 0; }
    .btn-back-mobile {
        border: 1.5px solid var(--border); background: var(--surface); color: var(--slate-light);
        border-radius: 999px; padding: 6px 16px; font-size: .8rem; font-weight: 700; transition: all .15s;
    }
    .btn-back-mobile:hover { background: var(--teal-light); border-color: var(--teal-mid); color: var(--teal); }

    /* ── List panel header to match section-card style ── */
    .list-head { display: flex; align-items: center; gap: 10px; }
    .list-head .sc-icon {
        width: 30px; height: 30px; border-radius: 8px; background: var(--teal-light); color: var(--teal);
        display: flex; align-items: center; justify-content: center; font-size: .78rem; flex-shrink: 0;
    }
    .list-head .sc-title {
        font-size: .78rem; font-weight: 700; color: var(--slate);
        text-transform: uppercase; letter-spacing: .5px; margin: 0;
    }

    /* Master-Detail Wrapper */
    .e201-wrapper { 
        height: calc(100vh - 200px); 
        display: flex; 
        gap: 20px; 
    }

    @media print {

        /* 1. Hide Global Layout Elements (Sidebar, Header, Navbar) */
    aside, header, nav, 
    .sidebar, .main-sidebar, .navbar, .topbar, .main-header {
        display: none !important;
    }

    /* 2. Reset Main Content Margins (Para hindi tabingi ang print) */
    .content-wrapper, .main-content, main, #main-wrapper {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    /* 3. Hide module-specific elements (Yung binigay ko kanina) */
    .e201-topbar,
    .employee-list-panel,
    nav[aria-label="breadcrumb"],
    .container-fluid > .d-flex.mb-4,
    button[onclick="window.print()"],
    #resetPasswordBtn,
    #editEmployee {
        display: none !important;
    }
    /* 1. Hide everything we don't want to print */
    .employee-list-panel,
    nav[aria-label="breadcrumb"],
    .container-fluid > .d-flex.mb-4, /* Hides the top header and back button */
    button[onclick="window.print()"],
    #resetPasswordBtn,
    #editEmployee {
        display: none !important;
    }

    /* 2. Format the page background to save ink */
    body {
        background-color: white !important;
    }

    /* 3. Expand the wrapper so the dossier takes the full width */
    .e201-wrapper {
        height: auto !important;
        display: block !important;
        gap: 0 !important;
    }

    /* 4. Expand the details panel and remove scroll behavior */
    .details-panel {
        width: 100% !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    /* 5. Force the browser to print background colors for the header/avatar */
    .dossier-header, .avatar-circle, #view_status {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* 6. Prevent info cards from breaking in half across multiple pages */
    .info-card {
        box-shadow: none !important;
        border: 1px solid #cbd5e1 !important; /* Add a subtle border for print clarity */
        break-inside: avoid !important;
        page-break-inside: avoid !important;
        margin-bottom: 20px !important;
    }
}
    
    /* Left Sidebar */
    .employee-list-panel { 
        width: 380px; 
        background: white; 
        border-radius: 15px; 
        display: flex; 
        flex-direction: column; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
        flex-shrink: 0;
    }

    .search-area { padding: 15px; border-bottom: 1px solid #f0f0f0; }
    .list-scroll { overflow-y: auto; flex-grow: 1; }
    
    /* Right Content Panel */
    .details-panel { 
        flex-grow: 1; 
        overflow-y: auto; 
        padding-right: 10px; 
        scroll-behavior: smooth;
    }

    /* Employee Card */
    .emp-row { padding: 15px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: 0.2s; }
    .emp-row:hover { background: #f0fdfa; }
    .emp-row.active-selection { 
        background: #e6fffa !important; 
        border-left: 5px solid var(--hr-teal) !important; 
    }

    /* Dossier Styling */
    .dossier-header { background: linear-gradient(135deg, #008080 0%, #005a5a 100%); color: white; border-radius: 15px; padding: 30px; }
    .profile-pic-large { width: 120px; height: 120px; border: 5px solid rgba(255,255,255,0.3); border-radius: 50%; object-fit: cover; background: white; }
    .info-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    
    .label-caps { font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .value-text { font-size: 0.95rem; font-weight: 600; color: #1e293b; }

    .avatar-circle {
        width: 45px; height: 45px; background-color: #e6fffa; color: #008080;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.85rem; flex-shrink: 0; border: 1px solid #b2f5ea;
    }

    @media (max-width: 991.98px) {
        .e201-wrapper { flex-direction: column; height: auto; display: block; }
        .employee-list-panel { width: 100%; height: 50vh; margin-bottom: 20px; }
        .details-panel { width: 100%; }
        .list-hidden-mobile { display: none !important; }
        .dossier-header { padding: 20px; text-align: center; }
        .dossier-header .row { flex-direction: column; }
        .col-auto.text-end { text-align: center !important; width: 100%; margin-top: 15px; }
    }

    .list-scroll::-webkit-scrollbar, .details-panel::-webkit-scrollbar { width: 6px; }
    .list-scroll::-webkit-scrollbar-thumb, .details-panel::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<div class="e201-shell">
    <div class="e201-topbar">
        <div>
            <p class="page-title">E-201 Personnel Viewer</p>
            <p class="page-sub">Management &middot; Electronic 201 Files &mdash; browse and review employee records</p>
        </div>
        <button id="btnBackToList" class="btn-back-mobile d-lg-none" style="display:none;">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to List
        </button>
    </div>

    <div class="e201-wrapper">
        <div class="employee-list-panel" id="sidePanel">
            <div class="search-area">
                <div class="list-head mb-3">
                    <div class="sc-icon"><i class="fa fa-users"></i></div>
                    <h6 class="sc-title">Personnel Records</h6>
                </div>
                <div class="input-group bg-light rounded-pill px-3 py-1 border">
                    <i class="fa-solid fa-magnifying-glass align-self-center text-muted"></i>
                    <input type="text" id="empSearchInput" class="form-control border-0 bg-transparent shadow-none" placeholder="Search name or ID...">
                </div>
            </div>
            
            <div class="list-scroll" id="employeeList">
                @foreach($resultUser as $user)
                <div class="emp-row d-flex align-items-center" 
                     data-search-key="{{ strtolower($user->lname . ' ' . $user->fname . ' ' . $user->empID) }}" 
                     data-id="{{ $user->empID }}">
                    <div class="avatar-circle me-3">
                        <span>{{ strtoupper(substr($user->fname, 0, 1) . substr($user->lname, 0, 1)) }}</span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark mb-0 small">{{ strtoupper($user->lname) }}, {{ strtoupper($user->fname) }}</div>
                        <div class="text-muted" style="font-size: 0.65rem;">
                             {{ $user->empDetail->department->dep_name ?? 'No Dept' }} | {{ $user->empDetail->position->pos_desc ?? 'No Position' }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="details-panel" id="mainDetails">
            <div id="dossierContent" class="animate__animated animate__fadeIn">
                <div class="dossier-header mb-4">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <img id="view_img" src="" class="profile-pic-large" alt="Profile" style="display:none;">
                            <div id="view_img_placeholder" class="profile-pic-large align-items-center justify-content-center" style="background:#f1f5f9;display:none;"></div>
                        </div>
                        <div class="col">
                            <span class="badge bg-secondary text-white mb-2" id="view_status">STATUS</span>
                            <span class="badge bg-dark text-white mb-2 ms-1 d-none" id="view_flag">FLAG</span>
                            <h1 class="fw-bold mb-1 text-capitalize" id="view_name">---</h1>
                            <p class="mb-0 opacity-75 fs-5" id="view_job_title">Position | Department</p>
                        </div>
                        <div class="col-auto text-end d-flex gap-2">
                            <button class="btn btn-light rounded-pill px-4 fw-bold shadow-sm" onclick="window.print()">
                                <i class="fa-solid fa-print me-2"></i>Print
                            </button>
                            @can('manageemployeestatus')
                            <button type="button" class="btn btn-light rounded-pill px-4 fw-bold shadow-sm text-teal" id="updateStatusBtn" data-id="" data-name=""
                                data-emp-status="" data-hired="" data-sep-date="" data-sep-reason="" data-flag-status="" data-flag-reason="">
                                <i class="fa-solid fa-user-gear me-2"></i>Update Status
                            </button>
                            @endcan
                            <button type="button" class="btn btn-light rounded-pill px-4 fw-bold shadow-sm text-danger" id="resetPasswordBtn" data-id="" data-name="">
                                <i class="fa-solid fa-key me-2"></i>Reset Password
                            </button>
                            <a target="_blank" class="btn btn-light rounded-pill px-4 fw-bold shadow-sm" id="editEmployee">
                                <i class="fa-solid fa-pencil me-2"></i>Edit
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="info-card">
                            <h6 class="fw-bold mb-4"><i class="fa-solid fa-user-tie me-2 text-teal"></i>Primary Employment Details</h6>
                            <div class="row g-4">
                                <div class="col-6 col-md-4">
                                    <div class="label-caps">Date Hired</div>
                                    <div class="value-text" id="view_hired">---</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="label-caps">Employment Status</div>
                                    <div class="value-text" id="view_emp_status">---</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="label-caps">Classification</div>
                                    <div class="value-text" id="view_class">---</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="label-caps">Basic Salary</div>
                                    <div class="value-text text-success" id="view_salary">0.00</div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="label-caps">Allowance</div>
                                    <div class="value-text" id="view_allowance">---</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="label-caps">Payroll Type</div>
                                    <div class="value-text" id="view_payroll_type">---</div>
                                </div>
                                <div class="col-6 col-md-4" id="view_card_no_wrap" style="display:none;">
                                    <div class="label-caps">Card / Account No.</div>
                                    <div class="value-text" id="view_card_no">---</div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="label-caps">Years Rendered</div>
                                    <div class="value-text" id="view_years_rendered">---</div>
                                </div>
                                <div class="col-6 col-md-4" id="view_sep_date_wrap" style="display:none;">
                                    <div class="label-caps">Separation Date</div>
                                    <div class="value-text text-danger" id="view_separation_date">---</div>
                                </div>
                                <div class="col-12" id="view_sep_reason_wrap" style="display:none;">
                                    <div class="label-caps">Separation Reason</div>
                                    <div class="value-text" id="view_separation_reason">---</div>
                                </div>
                                <div class="col-12" id="view_flag_reason_wrap" style="display:none;">
                                    <div class="label-caps">Flag Reason</div>
                                    <div class="value-text text-danger" id="view_flag_reason">---</div>
                                </div>
                            </div>
                        </div>

                        {{-- Offboarding clearance — shown only for separated employees --}}
                        <div class="info-card" id="view_clearance_card" style="display:none;">
                            <h6 class="fw-bold mb-3"><i class="fa-solid fa-clipboard-check me-2 text-teal"></i>Offboarding Clearance</h6>
                            <div id="view_clearance_list"></div>
                            <div class="text-muted" style="font-size:.72rem;" id="view_clearance_meta"></div>
                        </div>

                        <div class="info-card">
                            <h6 class="fw-bold mb-4"><i class="fa-solid fa-id-card me-2 text-teal"></i>Statutory Identification</h6>
                            <div class="row g-3">
                                <div class="col-6 col-md-3 border-end">
                                    <div class="label-caps">SSS No.</div>
                                    <div class="value-text" id="view_sss">---</div>
                                </div>
                                <div class="col-6 col-md-3 border-end">
                                    <div class="label-caps">PhilHealth</div>
                                    <div class="value-text" id="view_phil">---</div>
                                </div>
                                <div class="col-6 col-md-3 border-end">
                                    <div class="label-caps">Pag-Ibig</div>
                                    <div class="value-text" id="view_pagibig">---</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="label-caps">TIN</div>
                                    <div class="value-text" id="view_tin">---</div>
                                </div>
                            </div>
                        </div>

                        <div class="info-card">
                            <h6 class="fw-bold mb-4"><i class="fa-solid fa-graduation-cap me-2 text-teal"></i>Educational Background</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless align-middle">
                                    <thead class="text-muted small">
                                        <tr>
                                            <th width="30%">LEVEL</th>
                                            <th width="50%">SCHOOL NAME</th>
                                            <th width="20%">YEAR GRADUATED</th>
                                        </tr>
                                    </thead>
                                    <tbody id="education_list">
                                        <tr>
                                            <td class="label-caps py-2">Tertiary</td>
                                            <td class="value-text" id="view_educ_tertiary">---</td>
                                            <td class="value-text" id="view_grad_tertiary">---</td>
                                        </tr>
                                        <tr>
                                            <td class="label-caps py-2">Secondary</td>
                                            <td class="value-text" id="view_educ_secondary">---</td>
                                            <td class="value-text" id="view_grad_secondary">---</td>
                                        </tr>
                                        <tr>
                                            <td class="label-caps py-2">Primary</td>
                                            <td class="value-text" id="view_educ_primary">---</td>
                                            <td class="value-text" id="view_grad_primary">---</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Employment Documents — the employee's 201 file (contracts, gov IDs, certificates, etc.) --}}
                        <div class="info-card">
                            <h6 class="fw-bold mb-3"><i class="fa-solid fa-folder-open me-2 text-teal"></i>Employment Documents</h6>

                            @can('manageemployeedocuments')
                            <div class="row g-2 align-items-end mb-3">
                                <div class="col-6 col-md-3">
                                    <label class="label-caps" for="ed_doc_type">Type</label>
                                    <select class="form-select form-select-sm" id="ed_doc_type">
                                        <option value="Employment Contract">Employment Contract</option>
                                        <option value="Government ID">Government ID</option>
                                        <option value="Resume/CV">Resume/CV</option>
                                        <option value="Certificate">Certificate</option>
                                        <option value="Clearance">Clearance</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="label-caps" for="ed_doc_label">Label <span class="text-muted text-lowercase">(optional)</span></label>
                                    <input type="text" class="form-control form-control-sm" id="ed_doc_label" maxlength="191" placeholder="e.g. Signed Contract 2024">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="label-caps" for="ed_doc_file">File (PDF / JPG / PNG)</label>
                                    <input type="file" class="form-control form-control-sm" id="ed_doc_file" accept="application/pdf,image/png,image/jpeg,.pdf,.png,.jpg,.jpeg">
                                </div>
                                <div class="col-12 col-md-2">
                                    <button type="button" class="btn btn-teal btn-sm w-100 fw-bold text-white" id="btnUploadEmpDoc" data-id="" style="background-color:#008080;">
                                        <i class="fa-solid fa-upload me-1"></i>Upload
                                    </button>
                                </div>
                            </div>
                            <div class="text-danger small mb-2 d-none" id="ed_doc_error"></div>
                            @endcan

                            <div class="table-responsive" style="max-height:260px; overflow-y:auto;">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="text-muted small">
                                        <tr>
                                            <th width="22%">TYPE</th>
                                            <th>LABEL / FILE</th>
                                            <th width="18%">UPLOADED</th>
                                            <th width="12%" class="text-end">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody id="view_documents_list">
                                        <tr><td colspan="4" class="text-center py-3 text-muted small">No documents uploaded.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="info-card h-100">
                            <h6 class="fw-bold mb-4"><i class="fa-solid fa-address-book me-2 text-teal"></i>Contact Details</h6>
                            <div class="mb-4">
                                <div class="label-caps">Official Email</div>
                                <div class="value-text text-break" id="view_email">---</div>
                            </div>
                            <div class="mb-4">
                                <div class="label-caps">Employee ID</div>
                                <div class="value-text" id="view_empid_val">---</div>
                            </div>
                            <div class="mb-4">
                                <div class="label-caps">Login Username</div>
                                <div class="value-text text-break" id="view_username">---</div>
                            </div>
                            <div class="mb-1">
                                <div class="label-caps">Current Company</div>
                                <div class="value-text" id="view_company">---</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> 
        </div>
    </div>
</div>

@can('manageemployeestatus')
<!-- Update Status / Separation & Flag modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-gear me-2 text-teal"></i>Update Employee Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Updating status for <strong id="us_emp_name">this employee</strong>.</p>

                <h6 class="fw-bold small text-uppercase text-muted mb-2">Employment</h6>
                <div class="mb-3">
                    <label for="us_emp_status" class="form-label small fw-semibold">Employment Status</label>
                    <select class="form-select" id="us_emp_status">
                        <option value="1">Employed (Active)</option>
                        <option value="0">Resigned</option>
                        <option value="2">End Of Contract</option>
                    </select>
                </div>
                <div id="us_separation_fields" class="d-none">
                    <div class="mb-3">
                        <label for="us_separation_date" class="form-label small fw-semibold">Separation Date</label>
                        <input type="date" class="form-control" id="us_separation_date">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Years Rendered <span class="text-muted">(auto-computed)</span></label>
                        <div class="form-control bg-light" id="us_years_preview">—</div>
                    </div>
                    <div class="mb-3">
                        <label for="us_separation_reason" class="form-label small fw-semibold">Separation Reason</label>
                        <textarea class="form-control" id="us_separation_reason" rows="2" placeholder="Reason for resignation / end of contract"></textarea>
                    </div>

                    {{-- Offboarding clearance checklist — HR-attested, no uploads.
                         Each row carries data-applies so the JS shows only the items
                         relevant to the chosen exit type (0=Resigned, 2=End of Contract). --}}
                    <label class="form-label small fw-semibold">Offboarding Clearance</label>
                    <p class="text-muted" style="font-size:.72rem;margin:-2px 0 8px;">Tick each completed requirement. A reference note is optional (e.g. clearance ref #, "laptop received by IT").</p>
                    <div id="us_clearance" class="border rounded-3 p-2 mb-2" style="background:#f8fafc;">
                        @php
                            $clItems = [
                                ['key' => 'resignation_letter', 'label' => 'Resignation Letter',             'applies' => '0'],
                                ['key' => 'office_notice',      'label' => 'Signed Notice from Office',      'applies' => '2'],
                                ['key' => 'clearance_form',     'label' => 'Clearance Form',                 'applies' => '0,2'],
                                ['key' => 'company_items',      'label' => 'Return of Company-Issued Items', 'applies' => '0,2'],
                                ['key' => 'quitclaim',          'label' => 'Signed/Received Quitclaim',      'applies' => '0,2'],
                            ];
                        @endphp
                        @foreach ($clItems as $it)
                            <div class="cl-row mb-2 pb-2 border-bottom" data-applies="{{ $it['applies'] }}" data-key="{{ $it['key'] }}">
                                <div class="form-check">
                                    <input class="form-check-input us-cl-check" type="checkbox" id="us_cl_{{ $it['key'] }}" value="{{ $it['key'] }}">
                                    <label class="form-check-label small fw-semibold" for="us_cl_{{ $it['key'] }}">{{ $it['label'] }}</label>
                                </div>
                                <input type="text" class="form-control form-control-sm us-cl-ref mt-1" id="us_clref_{{ $it['key'] }}" placeholder="Reference (optional)">
                                <input type="file" class="form-control form-control-sm us-cl-file mt-1" id="us_clfile_{{ $it['key'] }}" accept="application/pdf,image/png,image/jpeg,.pdf,.png,.jpg,.jpeg">
                                <div class="us-cl-current small mt-1" id="us_clcur_{{ $it['key'] }}"></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <hr>
                <h6 class="fw-bold small text-uppercase text-muted mb-2">Flag <span class="text-muted text-lowercase fw-normal">(independent of employment)</span></h6>
                <div class="mb-3">
                    <label for="us_flag_status" class="form-label small fw-semibold">Flag</label>
                    <select class="form-select" id="us_flag_status">
                        <option value="">None</option>
                        <option value="redflag">Red Flag</option>
                        <option value="blacklist">Blacklisted</option>
                    </select>
                </div>
                <div id="us_flag_fields" class="d-none">
                    <div class="mb-2">
                        <label for="us_flag_reason" class="form-label small fw-semibold">Flag Reason</label>
                        <textarea class="form-control" id="us_flag_reason" rows="2" placeholder="Reason for flagging this employee"></textarea>
                    </div>
                </div>

                <div class="text-danger small mt-2 d-none" id="us_error"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-teal rounded-pill px-4 fw-bold text-white" id="us_save" style="background-color:#008080;">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>
@endcan

<script src="{{ asset('js/modules/e201_admin.js') }}?v={{ @filemtime(public_path('js/modules/e201_admin.js')) ?: time() }}" defer></script>

@endsection