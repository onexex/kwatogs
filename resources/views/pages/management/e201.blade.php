@extends('layout.app')

@section('content')
<style>
    :root { --hr-teal: #008080; --hr-bg: #f4f7f6; }
    body { background-color: var(--hr-bg); overflow-x: hidden; }

    /* Master-Detail Wrapper */
    .e201-wrapper { 
        height: calc(100vh - 140px); 
        display: flex; 
        gap: 20px; 
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

<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">E-201 Personnel Viewer</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted small">Management</li>
                    <li class="breadcrumb-item active fw-semibold small" style="color: var(--hr-teal) !important;">Electronic 201 Files</li>
                </ol>
            </nav>
        </div>
        <button id="btnBackToList" class="btn btn-sm btn-outline-secondary d-lg-none rounded-pill px-3" style="display:none;">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to List
        </button>
    </div>

    <div class="e201-wrapper">
        <div class="employee-list-panel" id="sidePanel">
            <div class="search-area">
                <h6 class="fw-bold text-teal mb-3">Personnel Records</h6>
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
                            <span class="badge text-teal mb-2" id="view_status" style="background: white;">STATUS</span>
                            <h1 class="fw-bold mb-1 text-capitalize" id="view_name">---</h1>
                            <p class="mb-0 opacity-75 fs-5" id="view_job_title">Position | Department</p>
                        </div>
                        <div class="col-auto text-end d-flex gap-2">
                            <button class="btn btn-light rounded-pill px-4 fw-bold shadow-sm" onclick="window.print()">
                                <i class="fa-solid fa-print me-2"></i>Export
                            </button>
                            <a class="btn btn-light rounded-pill px-4 fw-bold shadow-sm" id="editEmployee">
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
                                    <div class="label-caps">HMO Number</div>
                                    <div class="value-text" id="view_hmo">---</div>
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

<script src="{{ asset('js/modules/e201_admin.js') }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('empSearchInput');
    const rows = document.querySelectorAll('.emp-row');
    const sidePanel = document.getElementById('sidePanel');
    const backBtn = document.getElementById('btnBackToList');
    const mainDetails = document.getElementById('mainDetails');

    // 🔍 SEARCH LOGIC
    if(searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            rows.forEach(row => {
                const searchKey = row.getAttribute('data-search-key') || '';
                // I-check kung nagma-match sa pangalan o ID
                if(searchKey.includes(query)) {
                    row.style.setProperty('display', 'flex', 'important');
                } else {
                    row.style.setProperty('display', 'none', 'important');
                }
            });
        });
    }

    // ⚡ CLICK & SCROLL RESET
    rows.forEach(row => {
        row.addEventListener('click', function() {
            // UI Update: Highlight selected row
            rows.forEach(r => r.classList.remove('active-selection'));
            this.classList.add('active-selection');

            // Dossier display logic (siguraduhing hindi d-none)
            const dossier = document.getElementById('dossierContent');
            dossier.classList.remove('d-none');

            // Mobile logic: Hide list and show back button
            if (window.innerWidth < 992) {
                sidePanel.classList.add('list-hidden-mobile');
                backBtn.style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'instant' });
            }

            // Big Screen Auto-scroll: Reset the panel scroll position to top
            // Gagamit ng scrollTop manual reset para sigurado
            mainDetails.scrollTop = 0; 
            mainDetails.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // 🔙 BACK BUTTON (Mobile Only)
    backBtn.addEventListener('click', function() {
        sidePanel.classList.remove('list-hidden-mobile');
        this.style.display = 'none';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});
</script>
@endsection