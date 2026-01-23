@extends('layout.app')

@section('content')
<style>
    /* Styling for the Search Bar */
    .search-header {
        background: white;
        border-radius: 1rem;
        border-left: 5px solid #008080;
    }

    /* Professional Resume styles */
    .profile-hero { background: linear-gradient(135deg, #008080 0%, #005a5a 100%); border-radius: 1.5rem; color: white; border: none; }
    .profile-img-container { width: 150px; height: 150px; border: 5px solid rgba(255, 255, 255, 0.2); object-fit: cover; }
    .nav-resume { border: none; gap: 8px; }
    .nav-resume .nav-link { border: none; color: #6c757d; font-weight: 600; padding: 10px 20px; border-radius: 50px; }
    .nav-resume .nav-link.active { background-color: #008080 !important; color: white !important; }
    .info-label { font-size: 0.72rem; font-weight: 800; color: #008080; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-value { font-size: 0.95rem; color: #2d3748; font-weight: 500; }
    .resume-card { border: none; border-radius: 1.25rem; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05); }

    /* Animation for smooth loading */
    .fade-in-profile { animation: fadeIn 0.5s ease-in; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="container-fluid px-4 py-3">
    
    <div class="card search-header shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="fw-bold mb-1">Personnel Record Viewer</h5>
                    <p class="small text-muted mb-0">Select an employee name to view their full e-201 profile.</p>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fa-solid fa-magnifying-glass text-teal"></i></span>
                        <select class="form-select border-0 bg-light fw-bold" id="txtSearchEmployee">
                            <option selected value="">Choose Personnel...</option>
                            @foreach($resultUser as $user)
                                <option value="{{ $user->empID }}">{{ strtoupper($user->lname) }}, {{ $user->fname }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="profileEmptyState" class="text-center py-5">
        <div class="opacity-25 mb-3">
            <i class="fa-solid fa-address-card fa-6x text-muted"></i>
        </div>
        <h4 class="text-muted fw-bold">No Employee Selected</h4>
        <p class="text-muted">Search or select a name from the dropdown above to display the record.</p>
    </div>

    <div id="profileDisplay" class="d-none fade-in-profile">
        
        <div class="card profile-hero shadow-sm mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center">
                    <div class="col-lg-2 text-center mb-3 mb-lg-0">
                        <img id="disp_path" src="{{ URL::asset('/img/undraw_profile.svg') }}" class="rounded-circle profile-img-container shadow-lg">
                    </div>
                    <div class="col-lg-7 text-center text-lg-start">
                        <div class="d-flex align-items-center justify-content-center justify-content-lg-start mb-2">
                            <h1 class="fw-bold mb-0 me-3" id="disp_fullname">Name</h1>
                            <span id="disp_status_badge" class="badge rounded-pill px-3">STATUS</span>
                        </div>
                        <p class="fs-5 opacity-75 mb-3"><span id="disp_position">Position</span> <span class="mx-2">|</span> <span id="disp_department">Dept</span></p>
                        <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-2">
                            <span class="badge bg-white bg-opacity-20 rounded-pill px-3 py-2"><i class="fa-solid fa-id-card me-1 text-info"></i> <span id="disp_id">ID</span></span>
                            <span class="badge bg-white bg-opacity-20 rounded-pill px-3 py-2"><i class="fa-solid fa-envelope me-1 text-info"></i> <span id="disp_email">Email</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-resume mb-4" id="resumeTab" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#personal">Personal Info</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#education">Education</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#compliance">Compliance</button></li>
        </ul>

        <div class="tab-content mt-3">
             <div class="tab-pane fade show active" id="personal" role="tabpanel">
                <div class="card resume-card p-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="info-label">Birth Date</div>
                            <div class="info-value" id="disp_dob">---</div>
                        </div>
                        <div class="col-md-8">
                            <div class="info-label">Current Address</div>
                            <div class="info-value" id="disp_address">---</div>
                        </div>
                    </div>
                </div>
             </div>
             </div>
    </div>
</div>

<script src="{{ asset('js/settings/e201_viewer.js') }}" defer></script>
@endsection