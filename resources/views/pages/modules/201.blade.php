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

    /* ── Standard top header bar (uniform with other modules) ── */
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

    /* ── Profile hero ────────────────────────────────────────── */
    .profile-hero {
        background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dark) 100%);
        border-radius: var(--radius-card);
        color: #fff;
        border: none;
        box-shadow: var(--shadow-card);
    }
    .profile-img-container { width: 130px; height: 130px; border: 4px solid rgba(255,255,255,.25); object-fit: cover; }

    .btn-hero {
        background: rgba(255,255,255,.15);
        border: 1px solid rgba(255,255,255,.25);
        color: #fff;
        backdrop-filter: blur(4px);
    }
    .btn-hero:hover { background: rgba(255,255,255,.25); color: #fff; }

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

    /* ── Sub-section divider ─────────────────────────────────── */
    .sub-divider {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0 0 18px;
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

    /* ── Highlight tiles (Compensation / Compliance) ─────────── */
    .tile {
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: var(--radius-input);
        padding: 14px 16px;
    }
    .tile-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: var(--teal-light);
        color: var(--teal);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    /* ── Education entry ─────────────────────────────────────── */
    .educ-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: var(--teal-light);
        color: var(--teal);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
</style>

<div class="e201-shell">

    {{-- Top header (uniform with other modules) --}}
    <div class="e201-topbar">
        <div>
            <p class="page-title">Employee 201 File</p>
            <p class="page-sub">Personal, education, employment and compliance records</p>
        </div>
    </div>

    {{-- Hero Section --}}
    <div class="card profile-hero mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center">
                <div class="col-lg-2 text-center mb-3 mb-lg-0">
                    @if($emp->empPicPath && file_exists(public_path('img/profile/' . $emp->empPicPath)))
                        <img src="{{ asset('img/profile/' . $emp->empPicPath) }}"
                             alt="profile" class="rounded-circle profile-img-container shadow-lg">
                    @else
                        @php $gender = $user->employeeInformation->gender ?? null; @endphp
                        <div class="rounded-circle profile-img-container shadow-lg d-flex align-items-center justify-content-center"
                             style="background:#f1f5f9;">
                            <i class="fa-solid fa-circle-user"
                               style="font-size:4rem;color:{{ $gender == 2 ? '#e91e8c' : '#1976d2' }};"></i>
                        </div>
                    @endif
                </div>
                <div class="col-lg-10 text-center text-lg-start">
                    <div class="d-flex align-items-center justify-content-center justify-content-lg-start mb-2 flex-wrap gap-2">
                        <h1 class="fw-bold mb-0 me-1 text-capitalize">
                            {{ $user->fname ?? 'Select' }} {{ $user->mname ?? '' }} {{ $user->lname ?? 'Employee' }} {{ $user->suffix ?? '' }}
                        </h1>
                        @if(isset($user->status))
                            <span class="badge {{ $user->status == 1 ? 'bg-success' : 'bg-danger' }} rounded-pill px-3">
                                {{ $user->status == 1 ? 'ACTIVE' : 'RESIGNED' }}
                            </span>
                        @endif
                    </div>
                    <p class="fs-5 opacity-75 mb-3">{{ $emp->position->pos_desc ?? 'Position' }} <span class="mx-2">|</span> {{ $emp->department->dep_name ?? 'Department' }}</p>
                    <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-2">
                        <span class="badge rounded-pill px-3 py-2" style="background:#ffffff; color: var(--teal-dark); font-weight:600;"><i class="fa-solid fa-id-card me-1"></i> {{ $emp->empID ?? '---' }}</span>
                        <span class="badge rounded-pill px-3 py-2" style="background:#ffffff; color: var(--teal-dark); font-weight:600;"><i class="fa-solid fa-envelope me-1"></i> {{ $user->email ?? '---' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-resume mb-4" id="resumeTab" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#personal">Personal Info</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#education">Education</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#employment">Employment</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#compliance">Compliance</button></li>
    </ul>

    <div class="tab-content">
        {{-- Personal Tab --}}
        <div class="tab-pane fade show active" id="personal" role="tabpanel">
            <div class="sc">
                <div class="sc-head">
                    <div class="sc-head-left">
                        <div class="sc-icon"><i class="fa-solid fa-user"></i></div>
                        <h5 class="sc-title">General Details</h5>
                    </div>
                </div>
                <div class="sc-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <span class="field-label">Gender / Citizenship</span>
                            <div class="field-value">
                                {{ isset($user->employeeInformation?->gender) ? ($user->employeeInformation->gender == 1 ? 'Male' : 'Female') : '---' }} / {{ $user->employeeInformation->citizenship ?? '---' }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <span class="field-label">Birth Date</span>
                            <div class="field-value">{{ isset($user->employeeInformation->empBdate) ? date('M d, Y', strtotime($user->employeeInformation->empBdate)) : '---' }}</div>
                        </div>
                        <div class="col-md-4">
                            <span class="field-label">Contact Numbers</span>
                            <div class="field-value">{{ $user->employeeInformation->empPContact ?? '---' }} {{ isset($user->employeeInformation->empHContact) ? '/ '.$user->employeeInformation->empHContact : '' }}</div>
                        </div>
                        <div class="col-md-12">
                            <span class="field-label">Mailing Address</span>
                            <div class="field-value">
                                {{ $user->employeeInformation->empAddStreet ?? '---' }}, Brgy. {{ $user->employeeInformation->empAddBrgyDesc ?? '---' }}, {{ $user->employeeInformation->empAddCityDesc ?? '---' }}, {{ $user->employeeInformation->empProvDesc ?? '---' }}, {{ $user->employeeInformation->empZipcode ?? '' }}, {{ $user->employeeInformation->empCountry ?? '' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Education Tab --}}
        <div class="tab-pane fade" id="education" role="tabpanel">
            <div class="sc">
                <div class="sc-head">
                    <div class="sc-head-left">
                        <div class="sc-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                        <h5 class="sc-title">Educational Background</h5>
                    </div>
                </div>
                <div class="sc-body">
                    <div class="row g-4">
                        @foreach ($user->education as $education)
                            <div class="col-md-12 d-flex align-items-center gap-3">
                                <div class="educ-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                                <div>
                                    <span class="field-label">{{ $education->schoolLevel }} Education</span>
                                    <div class="field-value fw-bold text-capitalize">{{ $education->schoolName ?? 'Not Specified' }}</div>
                                    <div class="small text-muted text-capitalize">{{ $education->schoolYearStarted ?? '' }} - {{ $education->schoolYearEnded ?? '' }} | {{ $education->schoolAddress ?? '' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Employment Tab --}}
        <div class="tab-pane fade" id="employment" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="sc h-100">
                        <div class="sc-head">
                            <div class="sc-head-left">
                                <div class="sc-icon"><i class="fa-solid fa-coins"></i></div>
                                <h5 class="sc-title">Compensation</h5>
                            </div>
                        </div>
                        <div class="sc-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 1px solid var(--border);">
                                <span class="field-label mb-0">Basic Salary</span>
                                <span class="field-value fw-bold">₱ {{ number_format($emp->empBasic ?? 0, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 1px solid var(--border);">
                                <span class="field-label mb-0">Allowance</span>
                                <span class="field-value fw-bold">₱ {{ number_format($emp->empAllowance ?? 0, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 1px solid var(--border);">
                                <span class="field-label mb-0">Hourly Rate</span>
                                <span class="field-value fw-bold">₱ {{ number_format($emp->empHrate ?? 0, 2) }}</span>
                            </div>
                            @php $__ptype = strtoupper($emp->empPayrollType ?? 'CASH'); @endphp
                            <div class="d-flex justify-content-between align-items-center @if($__ptype === 'CARD') mb-3 pb-2 @endif" @if($__ptype === 'CARD') style="border-bottom: 1px solid var(--border);" @endif>
                                <span class="field-label mb-0">Payroll Type</span>
                                <span class="field-value fw-bold">
                                    <span class="badge {{ $__ptype === 'CARD' ? 'bg-info' : 'bg-secondary' }}">{{ ucfirst(strtolower($__ptype)) }}</span>
                                </span>
                            </div>
                            @if($__ptype === 'CARD')
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="field-label mb-0">Card / Account No.</span>
                                <span class="field-value fw-bold">{{ $emp->empCardNo ?: '---' }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="sc h-100">
                        <div class="sc-head">
                            <div class="sc-head-left">
                                <div class="sc-icon"><i class="fa-solid fa-briefcase"></i></div>
                                <h5 class="sc-title">Employment Details</h5>
                            </div>
                        </div>
                        <div class="sc-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <span class="field-label">Company / Agency</span>
                                    <div class="field-value">{{ $emp->company->comp_name ?? '---' }} / {{ $emp->agency->ag_name ?? 'Direct' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <span class="field-label">Date Hired</span>
                                    <div class="field-value fw-bold" style="color: var(--teal);">{{ isset($emp->empDateHired) ? date('M d, Y', strtotime($emp->empDateHired)) : '---' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Compliance Tab --}}
        <div class="tab-pane fade" id="compliance" role="tabpanel">
            <div class="sc">
                <div class="sc-head">
                    <div class="sc-head-left">
                        <div class="sc-icon"><i class="fa-solid fa-shield-halved"></i></div>
                        <h5 class="sc-title">Government & Compliance</h5>
                    </div>
                </div>
                <div class="sc-body">
                    <div class="row g-4">
                        @php
                            $governmentFields = [
                                ['label' => 'SSS Number', 'val' => $emp->empSSS ?? null, 'icon' => 'fa-shield-halved'],
                                ['label' => 'PhilHealth', 'val' => $emp->empPhilhealth ?? null, 'icon' => 'fa-kit-medical'],
                                ['label' => 'Pag-IBIG', 'val' => $emp->empPagibig ?? null, 'icon' => 'fa-house-chimney-user'],
                                ['label' => 'TIN', 'val' => $emp->empTIN ?? null, 'icon' => 'fa-file-invoice'],
                                ['label' => 'UMID', 'val' => $emp->empUMID ?? null, 'icon' => 'fa-address-card'],
                                ['label' => 'Passport', 'val' => $emp->empPassport ?? null, 'icon' => 'fa-passport']
                            ];
                        @endphp
                        @foreach($governmentFields as $item)
                            <div class="col-md-4">
                                <div class="tile d-flex align-items-center gap-3">
                                    <div class="tile-icon"><i class="fa-solid {{ $item['icon'] }}"></i></div>
                                    <div>
                                        <span class="field-label">{{ $item['label'] }}</span>
                                        <div class="field-value fw-bold">{{ $item['val'] ?? 'Not Provided' }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
