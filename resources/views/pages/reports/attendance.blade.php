@extends('layout.app')

@section('content')
<style>
    /* ── Design tokens (shared with Edit Employee / Home) ────────── */
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
        --shadow-card:  0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }

    .home-shell {
        background: var(--bg);
        min-height: 100vh;
        margin: -1rem -1.5rem;
        padding: 24px 28px 60px;
    }

    /* ── Top header bar ──────────────────────────────────────── */
    .home-topbar {
        background: var(--surface);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        padding: 16px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 20px;
    }
    .home-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
        text-transform: uppercase;
    }
    .home-topbar .breadcrumb {
        font-size: 0.75rem;
        margin: 2px 0 0;
        padding: 0;
        background: none;
    }
    .home-topbar .breadcrumb-item.active {
        color: var(--teal);
        font-weight: 600;
    }

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

    /* ── Search form fields ───────────────────────────────────── */
    .field-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--slate-light);
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: 5px;
        display: block;
    }
    .form-control, .form-select {
        border: 1.5px solid var(--border);
        border-radius: 8px;
        font-size: 0.875rem;
        color: var(--slate);
        background: #fafbfc;
        transition: border-color .15s, box-shadow .15s;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--teal);
        box-shadow: 0 0 0 3px rgba(0,128,128,.1);
        background-color: #fff;
        outline: none;
    }
    .input-group-text {
        background: #fafbfc;
        border: 1.5px solid var(--border);
        color: var(--muted);
        font-size: 0.75rem;
    }

    .btn-teal {
        background: var(--teal);
        border-color: var(--teal);
        color: #fff;
    }
    .btn-teal:hover {
        background: var(--teal-dark);
        border-color: var(--teal-dark);
        color: #fff;
    }
    .btn-outline-teal {
        background: var(--surface);
        border: 1.5px solid var(--border);
        color: var(--slate);
    }
    .btn-outline-teal:hover {
        border-color: var(--teal);
        color: var(--teal);
        background: var(--teal-light);
    }

    /* ── Table refinements ────────────────────────────────────── */
    .table-sticky-header thead th {
        position: sticky !important;
        top: 0;
        background-color: #fafbfc;
        z-index: 10;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--slate-light);
        border-bottom: 2px solid var(--border);
    }

    .table tbody td {
        font-size: 0.8rem;
        vertical-align: middle;
    }

    .table-hover tbody tr:hover {
        background-color: var(--teal-light);
        transition: background-color 0.2s ease;
    }

    /* Modern Badge for Duration/Status */
    .badge-soft-primary {
        background-color: rgba(0, 128, 128, 0.1);
        color: var(--teal);
        border: 1px solid rgba(0, 128, 128, 0.2);
    }
</style>

<div class="home-shell">

    {{-- ── Top header ── --}}
    <div class="home-topbar">
        <div>
            <h4 class="page-title">Attendance Analytics</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted">Reports</li>
                    <li class="breadcrumb-item active fw-semibold" aria-current="page">Attendance Viewer</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- ── Search / filter card ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
            <h5 class="sc-title">Search Filters</h5>
        </div>
        <div class="sc-body">
            <form action='' id="frmSearch">
                <div class="row g-3 align-items-end">

                    <div class="col-lg-2 col-md-6">
                        <label class="field-label">Department</label>
                        <select class="form-select" id="txtDept" name="department">
                            <option value="All">All Departments</option>
                            @foreach($departments as $dep)
                                <option value="{{ $dep->id }}">{{ $dep->dep_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="field-label">Employee Name</label>
                        <select class="form-select text-capitalize" id="txtLastname" name="search">
                            <option value="All">All Personnel</option>
                            @if(count($resultEmp) > 0)
                                @foreach($resultEmp as $emp)
                                @php
                                    $fullName = strtoupper($emp->lname . ', ' . $emp->fname);
                                @endphp
                                    <option value="{{ $emp->empID }}">{{ $fullName }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-8">
                        <label class="field-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" id="txtDateFrom" name="txtDateFrom" value="{{ date('Y-m-d', strtotime('-7 days')) }}" class="form-control rptdatefrom">
                            <span class="input-group-text">to</span>
                            <input type="date" id="txtDateTo" name="txtDateto" value="{{ date('Y-m-d') }}" class="form-control rptdateto">
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-4 text-end">
                        <div class="d-flex gap-2 justify-content-lg-end">
                            <button type="button" id="btn_rptrefresh" class="btn btn-outline-teal rounded-pill px-4 fw-bold flex-fill flex-lg-grow-0 rptbtnref">
                                <i class="fa-solid fa-arrows-rotate me-2"></i>Refresh
                            </button>
                            <button type="button" id="btn_rptprint" class="btn btn-teal rounded-pill px-4 fw-bold shadow-sm flex-fill flex-lg-grow-0 rptbtnprint">
                                <i class="fa-solid fa-print me-2"></i>Print
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- ── Results table ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="bi bi-clock-history"></i></div>
            <h5 class="sc-title">Attendance Records</h5>
        </div>
        <div class="sc-body" style="padding:0;" id="Report_thisPrint">

            <div class="d-none p-4 border-bottom print-header">
                <h3 class="fw-bold mb-1">Attendance Report</h3>
                <p class="text-muted mb-0">Date Range: <span class="rptDateRange"></span></p>
            </div>

            <div class="table-responsive" style="max-height: 70vh;">
                <table class="table table-hover align-middle mb-0 table-sticky-header">
                    <thead class="text-center">
                       <tr>
                        <th scope="col" class="ps-4">No</th>
                        <th scope="col">Employee Name</th>
                        <th scope="col">Date</th>
                        <th scope="col">Schedule</th>
                        <th scope="col" class="text-primary">Time-in</th>
                        <th scope="col" class="text-danger">Time-out</th>
                        <th scope="col">Duration (Gross)</th>
                        <th scope="col" class="text-danger">Deductions (Min)</th> {{-- New Column --}}
                        <th scope="col" class="text-success">Net Duration</th> {{-- New Column --}}
                        <th scope="col">Late</th>
                        <th scope="col">Undertime</th>
                        <th scope="col">Night Diff</th>
                        <th scope="col">Passout</th>
                        <th scope="col" class="pe-4">Over Break</th>
                    </tr>
                    </thead>
                    <tbody id="tbl_rptattendance" name="tbl_rptattendance" class="text-center">
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/reports/rptattendance.js') }}" defer></script>
@endsection