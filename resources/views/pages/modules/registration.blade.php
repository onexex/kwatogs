@extends('layout.app')
@section('content')

<style>
    /* ── Base resets ─────────────────────────────────────────── */
    input, select, textarea { text-transform: uppercase; }

    .tab-content > .tab-pane:not(.active) {
        display: none !important;
        height: 0;
        overflow: hidden;
    }
    .tab-content > .active {
        display: block !important;
        height: auto !important;
    }

    /* ── Design tokens ───────────────────────────────────────── */
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
    .enroll-shell {
        background: var(--bg);
        min-height: 100vh;
        padding-bottom: 60px;
    }

    /* ── Top header bar ──────────────────────────────────────── */
    .enroll-topbar {
        background: var(--surface);
        border-bottom: 1px solid var(--border);
        padding: 16px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .enroll-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .enroll-topbar .breadcrumb {
        font-size: 0.75rem;
        margin: 2px 0 0;
        padding: 0;
        background: none;
    }
    .enroll-topbar .breadcrumb-item.active {
        color: var(--teal);
        font-weight: 600;
    }
    .badge-enroll {
        background: var(--teal-light);
        color: var(--teal);
        border: 1px solid #b2dfdb;
        font-size: 0.73rem;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    /* ── Sticky stepper nav ───────────────────────────────────── */
    .stepper-nav {
        background: var(--surface);
        border-bottom: 1px solid var(--border);
        padding: 0 28px;
        position: sticky;
        top: 0;
        z-index: 200;
        box-shadow: 0 2px 10px rgba(0,0,0,.06);
    }
    .stepper-list {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .stepper-list::-webkit-scrollbar { display: none; }

    .stepper-item { flex: 1 1 0; min-width: 120px; }

    .step-btn {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        color: var(--muted);
        transition: all .2s ease;
        white-space: nowrap;
        text-align: left;
        position: relative;
    }
    .step-btn:hover { color: var(--slate); background: var(--bg); }
    .step-btn.active {
        color: var(--teal);
        border-bottom-color: var(--teal);
    }
    .step-btn.visited:not(.active) {
        color: var(--slate-light);
        border-bottom-color: var(--teal-mid);
    }
    .step-circle {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        border: 2px solid currentColor;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
        flex-shrink: 0;
        transition: all .2s;
    }
    .step-btn.active .step-circle {
        background: var(--teal);
        border-color: var(--teal);
        color: #fff;
    }
    .step-btn.visited:not(.active) .step-circle {
        background: var(--teal-light);
        border-color: var(--teal-mid);
        color: var(--teal);
    }
    .step-label {
        font-size: 0.77rem;
        font-weight: 600;
        display: block;
        line-height: 1.2;
    }
    .step-sub {
        font-size: 0.65rem;
        color: var(--muted);
        font-weight: 400;
        display: block;
        margin-top: 1px;
    }
    .step-btn.active .step-sub { color: var(--teal-mid); }
    .step-btn.has-error { border-bottom-color: var(--danger) !important; }
    .step-btn.has-error .step-circle {
        background: var(--danger) !important;
        border-color: var(--danger) !important;
        color: #fff !important;
        animation: errorPulse .5s ease;
    }

    /* ── Error badge on stepper ───────────────────────────────── */
    .step-error-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 9px;
        background: var(--danger);
        color: #fff;
        font-size: 0.6rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        box-shadow: 0 2px 6px rgba(239,68,68,.4);
        animation: badgePop .25s cubic-bezier(.175,.885,.32,1.275);
        pointer-events: none;
    }

    /* ── Error summary banner ─────────────────────────────────── */
    .error-banner {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        background: #fff5f5;
        border: 1px solid #fecaca;
        border-left: 4px solid var(--danger);
        border-radius: 10px;
        padding: 14px 18px;
        margin-bottom: 20px;
        animation: slideDown .25s ease;
    }
    .error-banner-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #fee2e2;
        color: var(--danger);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .85rem;
        flex-shrink: 0;
    }
    .error-banner-body { flex: 1; }
    .error-banner-title {
        font-size: .82rem;
        font-weight: 700;
        color: #b91c1c;
        margin: 0 0 6px;
    }
    .error-banner-links {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .error-tab-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 20px;
        background: #fee2e2;
        border: 1px solid #fca5a5;
        color: #b91c1c;
        font-size: .7rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
    }
    .error-tab-chip:hover { background: var(--danger); color: #fff; border-color: var(--danger); }
    .error-tab-chip .chip-count {
        background: var(--danger);
        color: #fff;
        border-radius: 10px;
        padding: 1px 6px;
        font-size: .62rem;
    }
    .error-tab-chip:hover .chip-count { background: rgba(255,255,255,.3); }

    @keyframes badgePop {
        0%   { transform: scale(0); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }
    @keyframes errorPulse {
        0%, 100% { transform: scale(1); }
        50%       { transform: scale(1.15); }
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Content wrapper ──────────────────────────────────────── */
    .enroll-content { padding: 24px 28px; }

    /* ── Section card ─────────────────────────────────────────── */
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
        gap: 10px;
        padding: 14px 22px;
        border-bottom: 1px solid var(--border);
        background: linear-gradient(to right, #fafcff, #f8fbfa);
    }
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

    /* ── Sub-section divider ──────────────────────────────────── */
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

    /* ── Field helpers ────────────────────────────────────────── */
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
    }
    .form-control {
        padding: 0.55rem 0.85rem;
        text-transform: uppercase;
    }
    /* Keep select padding-right wide enough for Bootstrap's chevron arrow */
    .form-select {
        padding: 0.55rem 2.5rem 0.55rem 0.85rem;
        text-transform: uppercase;
        background-position: right 0.85rem center;
        background-size: 16px 12px; /* pin the chevron size so it never tiles */
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--teal);
        box-shadow: 0 0 0 3px rgba(0,128,128,.1);
        background-color: #fff;
        outline: none;
    }
    /* Override Bootstrap's repeating warning-icon background on invalid selects */
    .form-select.is-invalid,
    .form-select.is-invalid:focus {
        border-color: var(--danger);
        background-color: #fff8f8;
        /* Keep ONLY the chevron arrow — strip the validation icon */
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.85rem center;
        background-size: 16px 12px;
        box-shadow: none;
        padding-right: 2.5rem;
    }
    .form-control.is-invalid {
        border-color: var(--danger);
        background-color: #fff8f8;
        background-image: none; /* strip the tiling warning icon from text inputs too */
        box-shadow: none;
    }
    .form-control.is-valid { border-color: var(--success); }
    .form-control[readonly] {
        background: var(--teal-light);
        color: var(--teal);
        font-weight: 600;
        cursor: default;
    }

    .error-text {
        font-size: 0.68rem;
        font-weight: 500;
        display: block;
        min-height: 14px;
        margin-top: 3px;
    }

    /* ── Upload zone ──────────────────────────────────────────── */
    .upload-zone {
        border: 2px dashed var(--border);
        border-radius: 12px;
        padding: 30px 20px;
        text-align: center;
        cursor: pointer;
        background: #fafbfc;
        transition: all .2s;
        display: block;
    }
    .upload-zone:hover {
        border-color: var(--teal-mid);
        background: var(--teal-light);
    }

    /* ── Avatar ring ──────────────────────────────────────────── */
    .avatar-ring {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        border: 4px solid var(--border);
        overflow: hidden;
        background: var(--bg);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        transition: border-color .2s, box-shadow .2s;
    }
    .avatar-ring.loaded {
        border-color: var(--teal);
        box-shadow: 0 0 0 4px var(--teal-light);
    }

    /* ── Bottom nav bar ───────────────────────────────────────── */
    .tab-nav-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 16px;
    }
    .btn-nav {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 20px;
        border-radius: 8px;
        font-size: 0.79rem;
        font-weight: 600;
        cursor: pointer;
        border: 1.5px solid var(--border);
        background: var(--surface);
        color: var(--slate);
        transition: all .15s;
        text-decoration: none;
    }
    .btn-nav:hover { border-color: var(--teal); color: var(--teal); background: var(--teal-light); }
    .btn-nav.next  { background: var(--teal); color: #fff; border-color: var(--teal); }
    .btn-nav.next:hover { background: var(--teal-dark); }

    /* ── Save button ──────────────────────────────────────────── */
    .btn-save {
        background: linear-gradient(135deg, var(--teal) 0%, #009e9e 100%);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 12px 30px;
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(0,128,128,.3);
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-save:hover  { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.4); }
    .btn-save:active { transform: translateY(0); }
    .btn-save:disabled { opacity: .6; transform: none; cursor: not-allowed; }
</style>

<div class="enroll-shell">

    {{-- ── Top header ── --}}
    <div class="enroll-topbar">
        <div>
            <p class="page-title mb-0">Personnel Onboarding</p>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted">Operations</li>
                    <li class="breadcrumb-item active" aria-current="page">Employee Registration</li>
                </ol>
            </nav>
        </div>
        <span class="badge-enroll d-none d-md-inline-flex">
            <i class="fa fa-user-plus" style="font-size:.72rem"></i> New Enrollment
        </span>
    </div>

    {{-- ── Sticky stepper ── --}}
    <div class="stepper-nav">
        <ul class="stepper-list" id="enrollmentTabs" role="tablist">

            <li class="stepper-item" role="presentation">
                <button class="step-btn active" id="home-tab"
                    data-bs-toggle="tab" data-bs-target="#home-tab-pane"
                    type="button" role="tab" aria-selected="true">
                    <span class="step-circle">1</span>
                    <span>
                        <span class="step-label">General Info</span>
                        <span class="step-sub">Personal details</span>
                    </span>
                </button>
            </li>

            <li class="stepper-item" role="presentation">
                <button class="step-btn" id="educational-tab"
                    data-bs-toggle="tab" data-bs-target="#educational-tab-pane"
                    type="button" role="tab">
                    <span class="step-circle">2</span>
                    <span>
                        <span class="step-label">Educational</span>
                        <span class="step-sub">School background</span>
                    </span>
                </button>
            </li>

            <li class="stepper-item" role="presentation">
                <button class="step-btn" id="employment-tab"
                    data-bs-toggle="tab" data-bs-target="#employment-tab-pane"
                    type="button" role="tab">
                    <span class="step-circle">3</span>
                    <span>
                        <span class="step-label">Employment</span>
                        <span class="step-sub">Job & salary</span>
                    </span>
                </button>
            </li>

            <li class="stepper-item" role="presentation">
                <button class="step-btn" id="compliance-tab"
                    data-bs-toggle="tab" data-bs-target="#complaince"
                    type="button" role="tab">
                    <span class="step-circle">4</span>
                    <span>
                        <span class="step-label">Compliance</span>
                        <span class="step-sub">Gov't IDs</span>
                    </span>
                </button>
            </li>

            <li class="stepper-item" role="presentation">
                <button class="step-btn" id="profile-pic-tab"
                    data-bs-toggle="tab" data-bs-target="#profile-tab-pane"
                    type="button" role="tab">
                    <span class="step-circle">5</span>
                    <span>
                        <span class="step-label">Profile Photo</span>
                        <span class="step-sub">Upload & save</span>
                    </span>
                </button>
            </li>

        </ul>
    </div>

    {{-- ── Form ── --}}
    <div class="enroll-content">
        <form id="frmEnrolment" autocomplete="off">
            @csrf

            {{-- Error summary banner --}}
            <div id="errorBanner" style="display:none;" class="mb-2"></div>

            <div class="tab-content" id="myTabContent">

                {{-- ══════════════ TAB 1 · GENERAL INFO ══════════════ --}}
                <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" tabindex="0">

                    {{-- Personal details --}}
                    <div class="sc">
                        <div class="sc-head">
                            <div class="sc-icon"><i class="fa fa-user"></i></div>
                            <h5 class="sc-title">General Information</h5>
                        </div>
                        <div class="sc-body">
                            <div class="row g-3">
                                <div class="col-lg-3 col-md-6">
                                    <label for="txtfname" class="field-label">First Name <span class="req">*</span></label>
                                    <input type="text" class="form-control" id="txtfname" name="firstname" autocomplete="off">
                                    <span class="text-danger error-text firstname_error"></span>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="txtMiddleName" class="field-label">Middle Name</label>
                                    <input type="text" class="form-control" id="txtMiddleName" name="middlename" autocomplete="off">
                                    <span class="text-danger error-text middlename_error"></span>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="txtLastName" class="field-label">Last Name <span class="req">*</span></label>
                                    <input type="text" class="form-control" id="txtLastName" name="lastname" autocomplete="off">
                                    <span class="text-danger error-text lastname_error"></span>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="txtSuffix" class="field-label">Suffix</label>
                                    <input type="text" class="form-control" id="txtSuffix" name="suffix" placeholder="e.g. Jr." autocomplete="off">
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="selGender" class="field-label">Gender <span class="req">*</span></label>
                                    <select class="form-select" name="gender" id="selGender">
                                        <option value="Female">Female</option>
                                        <option value="Male">Male</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="txtCitizenship" class="field-label">Citizenship <span class="req">*</span></label>
                                    <input type="text" class="form-control" id="txtCitizenship" name="citizenship" autocomplete="off">
                                    <span class="text-danger error-text citizenship_error"></span>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="txtDOB" class="field-label">Date of Birth <span class="req">*</span></label>
                                    <input type="date" class="form-control" id="txtDOB" name="birthdate" autocomplete="off">
                                    <span class="text-danger error-text birthdate_error"></span>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="selCivilStatus" class="field-label">Civil Status <span class="req">*</span></label>
                                    <select class="form-select" name="status" id="selCivilStatus">
                                        <option value="">— Select —</option>
                                        <option value="0">Single</option>
                                        <option value="1">Married</option>
                                        <option value="2">Divorced</option>
                                    </select>
                                    <span class="text-danger error-text status_error"></span>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="txtHomePhone" class="field-label">Home Phone</label>
                                    <input type="number" class="form-control" id="txtHomePhone" name="homephone" autocomplete="off">
                                    <span class="text-danger error-text homephone_error"></span>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="txtMobileNumber" class="field-label">Mobile Number <span class="req">*</span></label>
                                    <input type="number" class="form-control" id="txtMobileNumber" name="mobile" autocomplete="off">
                                    <span class="text-danger error-text mobile_error"></span>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="txtEmailAddress" class="field-label">Email Address <span class="req">*</span></label>
                                    <input type="email" class="form-control" id="txtEmailAddress" name="email" autocomplete="off">
                                    <span class="error-text email_error"></span>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="txtUsername" class="field-label">Login Username</label>
                                    <input type="text" class="form-control" id="txtUsername" name="username" autocomplete="off" placeholder="Leave blank to auto-generate">
                                    <small class="text-muted" style="font-size:.75rem;">Blank = first initial + surname (e.g. jdelacruz)</small>
                                    <span class="error-text username_error"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Mailing address --}}
                    <div class="sc">
                        <div class="sc-head">
                            <div class="sc-icon"><i class="fa fa-map-marker-alt"></i></div>
                            <h5 class="sc-title">Complete Mailing Address</h5>
                        </div>
                        <div class="sc-body">
                            <div class="row g-3">
                                <div class="col-lg-4">
                                    <label for="txtProvince" class="field-label">Province <span class="req">*</span></label>
                                    <select class="form-select" id="txtProvince" name="province"></select>
                                    <span class="text-danger error-text province_error"></span>
                                </div>
                                <div class="col-lg-4">
                                    <label for="txtCity" class="field-label">City <span class="req">*</span></label>
                                    <select class="form-select" id="txtCity" name="city"></select>
                                    <span class="text-danger error-text city_error"></span>
                                </div>
                                <div class="col-lg-4">
                                    <label for="txtBrgy" class="field-label">Barangay <span class="req">*</span></label>
                                    <select class="form-select" id="txtBrgy" name="barangay"></select>
                                    <span class="text-danger error-text barangay_error"></span>
                                </div>
                                <div class="col-lg-6">
                                    <label for="txtStreet" class="field-label">Street No. / Subdivision</label>
                                    <input type="text" class="form-control" id="txtStreet" name="street" autocomplete="off">
                                </div>
                                <div class="col-lg-3">
                                    <label for="txtZipCode" class="field-label">Zip Code <span class="req">*</span></label>
                                    <input type="text" class="form-control" id="txtZipCode" name="zipcode" autocomplete="off">
                                    <span class="text-danger error-text zipcode_error"></span>
                                </div>
                                <div class="col-lg-3">
                                    <label for="txtCountry" class="field-label">Country <span class="req">*</span></label>
                                    <input type="text" class="form-control" id="txtCountry" name="country" value="Philippines" readonly>
                                    <span class="text-danger error-text country_error"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-nav-bar">
                        <span></span>
                        <button type="button" class="btn-nav next" onclick="goTab('educational-tab')">
                            Next: Education <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- ══════════════ TAB 2 · EDUCATIONAL ══════════════ --}}
                <div class="tab-pane fade" id="educational-tab-pane" role="tabpanel" tabindex="0">
                    <div class="sc">
                        <div class="sc-head">
                            <div class="sc-icon"><i class="fa fa-book-open"></i></div>
                            <h5 class="sc-title">Educational Background</h5>
                        </div>
                        <div class="sc-body">

                            {{-- Primary --}}
                            <div class="sub-divider"><span>Primary Education</span></div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="field-label">School Name</label>
                                    <input type="text" class="form-control" id="txtPrimarySchool" name="primary_school" placeholder="Enter school name" autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <label class="field-label">Year Started</label>
                                    <input type="text" class="form-control" id="txtPrimaryStarted" name="primary_year_started" placeholder="YYYY" autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <label class="field-label">Year Graduated</label>
                                    <input type="text" class="form-control" id="txtPrimaryGraduated" name="primary_year_graduated" placeholder="YYYY" autocomplete="off">
                                </div>
                                <div class="col-12">
                                    <label class="field-label">School Address</label>
                                    <input type="text" class="form-control" id="txtPrimaryAddress" name="primary_school_address" placeholder="Street, City, Province" autocomplete="off">
                                    <span class="text-danger error-text primary_address_error"></span>
                                </div>
                            </div>

                            {{-- Secondary --}}
                            <div class="sub-divider"><span>Secondary Education</span></div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="field-label">School Name</label>
                                    <input type="text" class="form-control" id="txtSecondarySchool" name="secondary_school" placeholder="Enter school name" autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <label class="field-label">Year Started</label>
                                    <input type="text" class="form-control" id="txtSecondaryStarted" name="secondary_year_started" placeholder="YYYY" autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <label class="field-label">Year Graduated</label>
                                    <input type="text" class="form-control" id="txtSecondaryGraduated" name="secondary_year_graduated" placeholder="YYYY" autocomplete="off">
                                </div>
                                <div class="col-12">
                                    <label class="field-label">School Address</label>
                                    <input type="text" class="form-control" id="txtSecondaryAddress" name="secondary_school_address" placeholder="Street, City, Province" autocomplete="off">
                                    <span class="text-danger error-text secondary_address_error"></span>
                                </div>
                            </div>

                            {{-- Tertiary --}}
                            <div class="sub-divider"><span>Tertiary Education</span></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="field-label">School Name</label>
                                    <input type="text" class="form-control" id="txtTertiarySchool" name="tertiary_school" placeholder="Enter school name" autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <label class="field-label">Year Started</label>
                                    <input type="text" class="form-control" id="txtTertiaryStarted" name="tertiary_year_started" placeholder="YYYY" autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <label class="field-label">Year Graduated</label>
                                    <input type="text" class="form-control" id="txtTertiaryGraduated" name="tertiary_year_graduated" placeholder="YYYY" autocomplete="off">
                                </div>
                                <div class="col-12">
                                    <label class="field-label">School Address</label>
                                    <input type="text" class="form-control" id="txtTertiaryAddress" name="tertiary_school_address" placeholder="Street, City, Province" autocomplete="off">
                                    <span class="text-danger error-text tertiary_address_error"></span>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="tab-nav-bar">
                        <button type="button" class="btn-nav" onclick="goTab('home-tab')">
                            <i class="fa fa-arrow-left"></i> Back
                        </button>
                        <button type="button" class="btn-nav next" onclick="goTab('employment-tab')">
                            Next: Employment <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- ══════════════ TAB 3 · EMPLOYMENT ══════════════ --}}
                <div class="tab-pane fade" id="employment-tab-pane" role="tabpanel" tabindex="0">
                    <div class="sc">
                        <div class="sc-head">
                            <div class="sc-icon"><i class="fa fa-briefcase"></i></div>
                            <h5 class="sc-title">Employment Information</h5>
                        </div>
                        <div class="sc-body">

                            <div class="row g-3">
                                {{-- Col 1 --}}
                                <div class="col-lg-4">
                                    <div class="mb-3 d-none">
                                        <label for="txtEmployeeNo" class="field-label">Employee No.</label>
                                        <input class="form-control fw-bold" id="txtEmployeeNo" name="employee_number" type="text" readonly>
                                        <span class="text-danger error-text employee_number_error"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label for="selCompany" class="field-label">Company <span class="req">*</span></label>
                                        <select class="form-select" name="company" id="selCompany">
                                            <option value="">— Select Company —</option>
                                            @if(count($companyData) > 0)
                                                @foreach($companyData as $c)
                                                    <option value="{{ $c->comp_id }}">{{ $c->comp_name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <span class="text-danger error-text company_error"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label for="selDepartment" class="field-label">Department <span class="req">*</span></label>
                                        <select class="form-select" name="department" id="selDepartment">
                                            <option value="">— Select Department —</option>
                                            @if(count($departmentData) > 0)
                                                @foreach($departmentData as $d)
                                                    <option value="{{ $d->id }}">{{ $d->dep_name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <span class="text-danger error-text department_error"></span>
                                    </div>
                                    <div class="mb-0">
                                        <label for="selPosition" class="field-label">Position <span class="req">*</span></label>
                                        <select class="form-select" name="position" id="selPosition">
                                            <option value="">— Select Position —</option>
                                            @if(count($positionData) > 0)
                                                @foreach($positionData as $p)
                                                    <option value="{{ $p->id }}">{{ $p->pos_desc }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <span class="text-danger error-text position_error"></span>
                                    </div>
                                </div>

                                {{-- Col 2 --}}
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="selClassification" class="field-label">Classification <span class="req">*</span></label>
                                        <select class="form-select" name="classification" id="selClassification">
                                            <option value="">— Select Classification —</option>
                                            @if(count($employeeClassification) > 0)
                                                @foreach($employeeClassification as $ec)
                                                    <option value="{{ $ec->class_code }}">{{ $ec->class_desc }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <span class="text-danger error-text classification_error"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label for="selImmediate" class="field-label">Immediate Superior <span class="req">*</span></label>
                                        <select class="form-select" name="immediate" id="selImmediate">
                                            <option value="">— Select Superior —</option>
                                            @if(count($immediateData) > 0)
                                                @foreach($immediateData as $im)
                                                    <option value="{{ $im->empID }}">{{ strtoupper($im->lname . ', ' . $im->fname) }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <span class="text-danger error-text immediate_error"></span>
                                    </div>
                                    <div class="mb-0">
                                        <label for="selStatus" class="field-label">Status <span class="req">*</span></label>
                                        <select class="form-select" name="status" id="selStatus">
                                            <option value="">— Select Status —</option>
                                            <option value="1">Employed</option>
                                            <option value="0">Resigned</option>
                                        </select>
                                        <span class="text-danger error-text status_error"></span>
                                    </div>
                                </div>

                                {{-- Col 3 --}}
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="field-label">Previous Position</label>
                                        <input type="text" class="form-control" name="previous_position" id="selPreviousPosition" autocomplete="off">
                                    </div>
                                    <div class="mb-3">
                                        <label class="field-label">Previous Department</label>
                                        <input type="text" class="form-control" id="txtPreviousDepartment" name="previous_department" autocomplete="off">
                                    </div>
                                    <div class="mb-0">
                                        <label class="field-label">Previous Designation</label>
                                        <input type="text" class="form-control" id="txtPreviousDesignation" name="previous_designation" autocomplete="off">
                                    </div>
                                </div>
                            </div>

                            <hr style="border-color:var(--border);margin:22px 0;">

                            {{-- Dates & Compensation --}}
                            <div class="sub-divider"><span>Dates &amp; Compensation</span></div>
                            <div class="row g-3">
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="txtDateHired" class="field-label">Date Hired <span class="req">*</span></label>
                                        <input type="date" class="form-control" id="txtDateHired" name="date_hired">
                                        <span class="text-danger error-text date_hired_error"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label for="txtDateRegular" class="field-label">Date Regular</label>
                                        <input type="date" class="form-control" id="txtDateRegular" name="date_regularization">
                                        <span class="text-danger error-text date_regularization_error"></span>
                                    </div>
                                    <div class="mb-0">
                                        <label for="txtDateResigned" class="field-label">Date Resigned</label>
                                        <input type="date" class="form-control" id="txtDateResigned" name="date_resigned">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="txtBasic" class="field-label">Basic Salary <span class="req">*</span></label>
                                        <input type="number" class="form-control" name="basic" id="txtBasic" autocomplete="off">
                                        <span class="text-danger error-text basic_error"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label for="txtAllowance" class="field-label">Allowance <span class="req">*</span></label>
                                        <input type="number" class="form-control" id="txtAllowance" name="allowance" autocomplete="off">
                                        <span class="text-danger error-text allowance_error"></span>
                                    </div>
                                    <div class="mb-0">
                                        <label for="txtHourlyRate" class="field-label">Hourly Rate</label>
                                        <input type="number" class="form-control" id="txtHourlyRate" name="hourly_rate" value="0" autocomplete="off">
                                        <span class="text-danger error-text hourly_rate_error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="selPayrollType" class="field-label">Payroll Type <span class="req">*</span></label>
                                        <select class="form-select" name="payroll_type" id="selPayrollType"
                                            onchange="document.getElementById('cardNoWrap').style.display = this.value === 'CARD' ? 'block' : 'none';">
                                            <option value="CASH" selected>Cash</option>
                                            <option value="CARD">Card</option>
                                        </select>
                                        <span class="text-danger error-text payroll_type_error"></span>
                                    </div>
                                    <div class="mb-0" id="cardNoWrap" style="display:none;">
                                        <label for="txtCardNo" class="field-label">Card / Account Number <span class="req">*</span></label>
                                        <input type="text" class="form-control" id="txtCardNo" name="card_number" placeholder="0000 0000 0000" autocomplete="off">
                                        <span class="text-danger error-text card_number_error"></span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="tab-nav-bar">
                        <button type="button" class="btn-nav" onclick="goTab('educational-tab')">
                            <i class="fa fa-arrow-left"></i> Back
                        </button>
                        <button type="button" class="btn-nav next" onclick="goTab('compliance-tab')">
                            Next: Compliance <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- ══════════════ TAB 4 · COMPLIANCE ══════════════ --}}
                <div class="tab-pane fade" id="complaince" role="tabpanel" tabindex="0">
                    <div class="sc">
                        <div class="sc-head">
                            <div class="sc-icon"><i class="fa fa-user-shield"></i></div>
                            <h5 class="sc-title">Compliance Information</h5>
                        </div>
                        <div class="sc-body">
                            <div class="row g-3">
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="txtPhilhealth" class="field-label">PhilHealth No.</label>
                                        <input type="text" class="form-control" name="philhealth" id="txtPhilhealth" placeholder="00-000000000-0" autocomplete="off">
                                        <span class="text-danger error-text philhealth_error"></span>
                                    </div>
                                    <div class="mb-0">
                                        <label for="txtSSS" class="field-label">SSS No.</label>
                                        <input type="text" class="form-control" id="txtSSS" name="sss" placeholder="00-0000000-0" autocomplete="off">
                                        <span class="text-danger error-text sss_error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label for="txtPagibig" class="field-label">Pag-IBIG No.</label>
                                        <input type="text" class="form-control" id="txtPagibig" name="pagibig" placeholder="0000-0000-0000" autocomplete="off">
                                        <span class="text-danger error-text pagibig_error"></span>
                                    </div>
                                    <div class="mb-0">
                                        <label for="txtTIN" class="field-label">TIN No.</label>
                                        <input type="text" class="form-control" id="txtTIN" name="tin" placeholder="000-000-000-000" autocomplete="off">
                                        <span class="text-danger error-text tin_error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-0">
                                        <label for="txtUMIDNo" class="field-label">UMID</label>
                                        <input type="text" class="form-control" id="txtUMIDNo" name="umid" placeholder="0000-0000000-0" autocomplete="off">
                                        <span class="text-danger error-text umid_error"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-nav-bar">
                        <button type="button" class="btn-nav" onclick="goTab('employment-tab')">
                            <i class="fa fa-arrow-left"></i> Back
                        </button>
                        <button type="button" class="btn-nav next" onclick="goTab('profile-pic-tab')">
                            Next: Photo <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- ══════════════ TAB 5 · PROFILE PHOTO ══════════════ --}}
                <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" tabindex="0">
                    <div class="sc">
                        <div class="sc-head">
                            <div class="sc-icon"><i class="fa fa-image"></i></div>
                            <h5 class="sc-title">Profile Picture</h5>
                        </div>
                        <div class="sc-body">
                            <div class="row g-4 align-items-center">

                                <div class="col-lg-6">
                                    <label class="upload-zone" for="formFileLg">
                                        <i class="fa fa-cloud-upload-alt fa-2x mb-2" style="color:var(--teal-mid)"></i>
                                        <p class="mb-1" style="font-size:.88rem;font-weight:600;color:var(--slate)">Click to upload a photo</p>
                                        <p class="mb-0" style="font-size:.75rem;color:var(--muted)">JPG or PNG &bull; Max 2MB</p>
                                    </label>
                                    <input class="d-none" id="formFileLg" name="path" type="file"
                                        accept="image/*" onchange="previewImage(this)">
                                    <span class="text-danger error-text path_error mt-1 d-block"></span>

                                    <div class="mt-4">
                                        <button id="btnSaveAll" type="button" class="btn-save">
                                            <i class="fa fa-save"></i> Save All Information
                                        </button>
                                    </div>
                                </div>

                                <div class="col-lg-6 text-center">
                                    <div class="avatar-ring" id="avatarRing">
                                        <i id="previewIcon" class="fas fa-user"
                                            style="font-size:3.5rem;color:var(--border)"></i>
                                        <img id="imagePreview" src="#" alt="Preview"
                                            style="display:none;width:100%;height:100%;object-fit:cover;">
                                    </div>
                                    <p class="mt-3 mb-0" style="font-size:.75rem;color:var(--muted)">Photo Preview</p>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="tab-nav-bar">
                        <button type="button" class="btn-nav" onclick="goTab('compliance-tab')">
                            <i class="fa fa-arrow-left"></i> Back
                        </button>
                        <span></span>
                    </div>
                </div>

            </div>{{-- /tab-content --}}
        </form>
    </div>{{-- /enroll-content --}}

</div>{{-- /enroll-shell --}}

<script>
    /* ─── Image preview ─── */
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        const icon    = document.getElementById('previewIcon');
        const ring    = document.getElementById('avatarRing');
        const file    = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                preview.style.display = 'block';
                icon.style.display    = 'none';
                ring.classList.add('loaded');
            };
            reader.readAsDataURL(file);
        } else {
            preview.src           = '#';
            preview.style.display = 'none';
            icon.style.display    = 'block';
            ring.classList.remove('loaded');
        }
    }

    /* ─── Tab helper (called by Back / Next buttons) ─── */
    function goTab(id) {
        const btn = document.getElementById(id);
        if (btn) { btn.click(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
    }
</script>

<script src="{{ asset('js/modules/enrollment.js') }}" defer></script>
@endsection
