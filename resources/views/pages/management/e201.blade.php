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
    #editEmployee {
        display: none !important;
    }
    /* 1. Hide everything we don't want to print */
    .employee-list-panel,
    nav[aria-label="breadcrumb"],
    .container-fluid > .d-flex.mb-4, /* Hides the top header and back button */
    button[onclick="window.print()"], 
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
    .profile-pic-large { width: 120px; height: 120px; border: 5px solid rgba(255,255,255,0.3); border-radius: 15px; object-fit: cover; background: white; }
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
                        <div class="fw-bold text-dark mb-0 small">{{ strtoupper($user->lname) }}, {{ $user->fname }}</div>
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
                            <img id="view_img" src="" class="profile-pic-large" alt="Profile">
                        </div>
                        <div class="col">
                            <span class="badge bg-secondary text-white mb-2" id="view_status">STATUS</span>
                            <h1 class="fw-bold mb-1 text-capitalize" id="view_name">---</h1>
                            <p class="mb-0 opacity-75 fs-5" id="view_job_title">Position | Department</p>
                        </div>
                        <div class="col-auto text-end d-flex gap-2">
                            <button class="btn btn-light rounded-pill px-4 fw-bold shadow-sm" onclick="window.print()">
                                <i class="fa-solid fa-print me-2"></i>Print
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
                            </div>
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

<script src="{{ asset('js/modules/e201_admin.js') }}?v={{ @filemtime(public_path('js/modules/e201_admin.js')) ?: time() }}" defer></script>
 
@endsection