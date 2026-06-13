@extends('layout.app')

@section('content')

<style>
    /* ── Design tokens (shared with Edit Employee / Leave / Overtime / Loans) ── */
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
    .e201-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    /* ── Search header ───────────────────────────────────────── */
    .search-header {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        border-left: 4px solid var(--teal);
        padding: 16px 22px;
        margin-bottom: 20px;
    }
    .search-header .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .search-header .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }
    .search-header .form-select,
    .search-header .input-group-text {
        border: 1.5px solid var(--border) !important;
        border-radius: var(--radius-input);
        background: #fafbfc;
        font-size: 0.875rem;
        color: var(--slate);
    }
    .search-header .input-group-text { border-right: none !important; background: var(--teal-light); color: var(--teal); }
    .search-header .form-select { border-left: none !important; }
    .search-header .form-select:focus {
        border-color: var(--teal) !important;
        box-shadow: 0 0 0 3px rgba(0,128,128,.1);
        outline: none;
    }

    /* ── Empty state ─────────────────────────────────────────── */
    #profileEmptyState {
        background: var(--surface);
        border: 1px dashed var(--border);
        border-radius: var(--radius-card);
        padding: 60px 20px;
    }
    #profileEmptyState i { color: var(--muted); }
    #profileEmptyState h4 { color: var(--slate); font-weight: 700; }
    #profileEmptyState p { color: var(--muted); font-size: 0.85rem; }

    /* ── Profile hero ────────────────────────────────────────── */
    .profile-hero {
        background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dark) 100%);
        border-radius: var(--radius-card);
        color: #fff;
        border: none;
        box-shadow: var(--shadow-card);
    }
    .profile-img-container { width: 130px; height: 130px; border: 4px solid rgba(255,255,255,.25); object-fit: cover; }
    #disp_status_badge.bg-success { background-color: var(--success) !important; }
    #disp_status_badge.bg-danger { background-color: var(--danger) !important; }

    /* ── Tabs ─────────────────────────────────────────────────── */
    .nav-resume { border: none; gap: 8px; }
    .nav-resume .nav-link {
        border: none;
        color: var(--slate-light);
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 10px 22px;
        border-radius: 50px;
        transition: all .15s;
    }
    .nav-resume .nav-link:hover { background-color: var(--teal-light); color: var(--teal-dark); }
    .nav-resume .nav-link.active { background-color: var(--teal) !important; color: #fff !important; }

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
    .sc-body { padding: 22px; }

    /* ── Field helpers ───────────────────────────────────────── */
    .field-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--teal);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 4px;
        display: block;
    }
    .field-value {
        font-size: 0.95rem;
        color: var(--slate);
        font-weight: 500;
    }

    .fade-in-profile { animation: fadeIn 0.4s ease-in; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="e201-shell">

    {{-- ── Search header ── --}}
    <div class="search-header">
        <div class="row align-items-center g-3">
            <div class="col-md-6">
                <p class="page-title">Personnel Record Viewer</p>
                <p class="page-sub">Select an employee name to view their full e-201 profile</p>
            </div>
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <select class="form-select fw-bold" id="txtSearchEmployee">
                        <option selected value="">Choose Personnel...</option>
                        @foreach($resultUser as $user)
                            <option value="{{ $user->empID }}">{{ strtoupper($user->lname) }}, {{ strtoupper($user->fname) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Empty state ── --}}
    <div id="profileEmptyState" class="text-center">
        <div class="opacity-50 mb-3">
            <i class="fa-solid fa-address-card fa-6x"></i>
        </div>
        <h4>No Employee Selected</h4>
        <p>Search or select a name from the dropdown above to display the record.</p>
    </div>

    {{-- ── Profile display ── --}}
    <div id="profileDisplay" class="d-none fade-in-profile">

        <div class="card profile-hero mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center">
                    <div class="col-lg-2 text-center mb-3 mb-lg-0">
                        <img id="disp_path" src="{{ URL::asset('/img/undraw_profile.svg') }}" class="rounded-circle profile-img-container shadow-lg">
                    </div>
                    <div class="col-lg-7 text-center text-lg-start">
                        <div class="d-flex align-items-center justify-content-center justify-content-lg-start mb-2 flex-wrap gap-2">
                            <h1 class="fw-bold mb-0 me-1" id="disp_fullname">Name</h1>
                            <span id="disp_status_badge" class="badge rounded-pill px-3">STATUS</span>
                        </div>
                        <p class="fs-5 opacity-75 mb-3"><span id="disp_position">Position</span> <span class="mx-2">|</span> <span id="disp_department">Dept</span></p>
                        <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-2">
                            <span class="badge bg-white bg-opacity-20 rounded-pill px-3 py-2"><i class="fa-solid fa-id-card me-1"></i> <span id="disp_id">ID</span></span>
                            <span class="badge bg-white bg-opacity-20 rounded-pill px-3 py-2"><i class="fa-solid fa-envelope me-1"></i> <span id="disp_email">Email</span></span>
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

        <div class="tab-content">
            <div class="tab-pane fade show active" id="personal" role="tabpanel">
                <div class="sc">
                    <div class="sc-head">
                        <div class="sc-head-left">
                            <div class="sc-icon"><i class="fa-solid fa-user"></i></div>
                            <h5 class="sc-title">Personal Information</h5>
                        </div>
                    </div>
                    <div class="sc-body">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <span class="field-label">Birth Date</span>
                                <div class="field-value" id="disp_dob">---</div>
                            </div>
                            <div class="col-md-8">
                                <span class="field-label">Current Address</span>
                                <div class="field-value" id="disp_address">---</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/e201_viewer.js') }}" defer></script>
@endsection
