@extends('layout.app')

@section('content')
<style>
    :root { --hr-teal: #008080; --hr-bg: #f4f7f6; }
    body { background-color: var(--hr-bg); }

    /* Master-Detail Container */
    .e201-wrapper { height: calc(100vh - 120px); display: flex; gap: 20px; overflow: hidden; }
    
    /* Left Sidebar */
    .employee-list-panel { width: 380px; background: white; border-radius: 15px; display: flex; flex-direction: column; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .list-scroll { overflow-y: auto; flex-grow: 1; }
    
    /* Right Content */
    .details-panel { flex-grow: 1; overflow-y: auto; padding-right: 5px; }

    /* Employee Card in List */
    .emp-row { padding: 15px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: 0.2s; }
    .emp-row:hover { background: #f0fdfa; }
    .emp-row.active { background: #e6fffa; border-left: 5px solid var(--hr-teal); }

    /* Dossier Styling */
    .dossier-header { background: linear-gradient(135deg, #008080 0%, #005a5a 100%); color: white; border-radius: 15px; padding: 30px; position: relative; }
    .profile-pic-large { width: 120px; height: 120px; border: 5px solid rgba(255,255,255,0.3); border-radius: 15px; object-fit: cover; background: white; }
    .info-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    
    /* Typography */
    .label-caps { font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .value-text { font-size: 0.95rem; font-weight: 600; color: #1e293b; }

    /* Avatar Circle Design */
.avatar-circle {
    width: 45px;
    height: 45px;
    background-color: #e6fffa; /* Light teal background */
    color: #008080;           /* Dark teal text */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;           /* Prevents circle from squeezing */
    border: 1px solid #b2f5ea;
}

/* Hover Effect */
.emp-row:hover {
    background-color: #f8fafc;
}

/* Active/Selected State (Triggered by JS) */
.emp-row.active-selection {
    background-color: #e6fffa !important;
    border-left: 4px solid #008080 !important;
}

.emp-row.active-selection .avatar-circle {
    background-color: #008080;
    color: white;
    border-color: #008080;
}

/* Custom Scrollbar for the list */
.list-scroll::-webkit-scrollbar {
    width: 5px;
}
.list-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

/* Custom class to hide filtered out employees */
.search-hidden {
    display: none !important;
}
</style>

<div class="container-fluid py-3">
     <div class="d-flex align-items-center justify-content-between mb-4">
         <div  >
            <h4 class="fw-bold text-dark m-0">E-201 Personnel Viewer</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted">Management</li>
                    <li class="breadcrumb-item active fw-semibold text-teal" aria-current="page" style="color: var(--hr-teal) !important;">
                        Electronic 201 Files
                    </li>
                </ol>
            </nav>
        </div>
        </div>
    <div class="e201-wrapper">
       
        <div class="employee-list-panel">
            <div class="p-3 border-bottom">
                <h5 class="fw-bold text-teal mb-3">Personnel Records</h5>
                <div class="input-group bg-light rounded-pill px-3">
                    <i class="fa-solid fa-magnifying-glass align-self-center text-muted"></i>
                    <input type="text" id="empSearch" class="form-control border-0 bg-transparent" placeholder="Search name or ID...">
                </div>
            </div>
            
           <div class="list-scroll" id="employeeList" style="max-height: 700px; overflow-y: auto;">
                @foreach($resultUser as $user)
                <div class="emp-row d-flex align-items-center p-3 border-bottom" data-id="{{ $user->empID }}" style="cursor: pointer;">
                    <div class="avatar-circle me-3 text-capitalize">
                        <span>{{ (substr($user->fname, 0, 1) . substr($user->lname, 0, 1)) }}</span>
                    </div>
                    
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark mb-0">{{ strtoupper($user->lname) }}, {{ $user->fname }}</div>
                        <div class="text-muted text-capitalize" style="font-size: 0.7rem;">
                             {{ $user->empDetail->department->dep_name ?? 'No Dept' }}

                            <span class="mx-1 text-silver">•</span> 
        

                            {{ $user->empDetail->position->pos_desc ?? 'No Position' }}

                        </div>
                    </div>
                    
                    <i class="fa-solid fa-chevron-right fa-xs text-light"></i>
                </div>
                @endforeach
            </div>

            
        </div>

        <div class="details-panel">
            
            {{-- <div id="emptyState" class="h-100 d-flex flex-column align-items-center justify-content-center text-center">
                <i class="fa-solid fa-folder-open fa-4x text-light mb-3"></i>
                <h4 class="text-muted">Select an employee record to view</h4>
            </div> --}}

            <div id="dossierContent" class="d-none animate__animated animate__fadeIn">
                <div class="dossier-header mb-4">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            {{-- <img id="view_img" src="{{ asset('img/undraw_profile.svg') }}" class="profile-pic-large"> --}}
                            <img id="view_img" src="" class="profile-pic-large">
                        </div>
                        <div class="col">
                            <span class="badge text-teal  mb-2" id="view_status">STATUS</span>
                            <h1 class="fw-bold mb-1 text-capitalize" id="view_name">---</h1>
                            <p class="mb-0 opacity-75 fs-5" id="view_job_title">Position | Department</p>
                        </div>
                        <div class="col-auto text-end d-flex gap-2">
                            <button class="btn btn-light rounded-pill px-4 fw-bold shadow-sm" onclick="window.print()">
                                <i class="fa-solid fa-print me-2"></i>Export PDF
                            </button>
                            <button class="btn btn-light rounded-pill px-4 fw-bold shadow-sm" onclick="window.print()">
                                <i class="fa-solid fa-pencil me-2"></i>Edit
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="info-card">
                            <h6 class="fw-bold mb-4"><i class="fa-solid fa-user-tie me-2 text-teal"></i>Primary Employment Details</h6>
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="label-caps">Date Hired</div>
                                    <div class="value-text" id="view_hired">---</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="label-caps">Employment Status</div>
                                    <div class="value-text" id="view_emp_status">---</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="label-caps">Classification</div>
                                    <div class="value-text" id="view_class">---</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="label-caps">Basic Salary</div>
                                    <div class="value-text text-success" id="view_salary">0.00</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="label-caps">HMO Number</div>
                                    <div class="value-text" id="view_hmo">---</div>
                                </div>
                            </div>
                        </div>

                        <div class="info-card">
                            <h6 class="fw-bold mb-4"><i class="fa-solid fa-id-card me-2 text-teal"></i>Statutory Identification</h6>
                            <div class="row g-3">
                                <div class="col-md-3 border-end">
                                    <div class="label-caps">SSS No.</div>
                                    <div class="value-text" id="view_sss">---</div>
                                </div>
                                <div class="col-md-3 border-end">
                                    <div class="label-caps">PhilHealth</div>
                                    <div class="value-text" id="view_phil">---</div>
                                </div>
                                <div class="col-md-3 border-end">
                                    <div class="label-caps">Pag-Ibig</div>
                                    <div class="value-text" id="view_pagibig">---</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="label-caps">TIN</div>
                                    <div class="value-text" id="view_tin">---</div>
                                </div>
                            </div>
                        </div>

                        <div class="info-card mt-4">
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
                                <div class="value-text" id="view_email">---</div>
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
            </div> </div>
    </div>
</div>

<script src="{{ asset('js/modules/e201_admin.js') }}" defer></script>

@endsection