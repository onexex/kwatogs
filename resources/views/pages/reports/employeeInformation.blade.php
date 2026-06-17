@extends('layout.app', [
    'title' => 'Employee Information Report'
])

@section('content')
<style>
    /* ── Design tokens (shared with Edit Employee / Home / Attendance) ── */
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

    .filter-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: var(--teal-light);
        color: var(--teal-dark);
        border: 1px solid var(--teal-mid);
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .filter-badge .count {
        background: var(--teal);
        color: #fff;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
        font-weight: 800;
        margin-left: 2px;
    }
    .btn-reset {
        background: transparent;
        border: 1px solid #e2e8f0;
        color: var(--muted);
        border-radius: 20px;
        padding: 4px 14px;
        font-size: 0.7rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .btn-reset:hover {
        color: var(--danger);
        border-color: var(--danger);
        background: #fef2f2;
    }

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

    /* Professional Table Refinements (matches Leave Report header) */
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
        padding: 10px 8px;
        white-space: nowrap;
    }

    .table tbody td {
        font-size: 0.78rem;
        vertical-align: middle;
        padding: 8px;
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.12s ease;
    }
    .table tbody tr:nth-child(even) td {
        background-color: #fafcfd;
    }
    .table-hover tbody tr:hover td {
        background-color: #e8f6f5 !important;
    }

    .emp-name-cell {
        font-weight: 700;
        color: var(--slate);
        text-transform: uppercase;
        font-size: 0.78rem;
        letter-spacing: -0.1px;
        display: block;
    }
    .emp-id-sub {
        font-size: 0.68rem;
        color: var(--muted);
        font-weight: 500;
        display: block;
        margin-top: 1px;
    }

    .badge-status {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .badge-employed {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.25);
    }
    .badge-resigned {
        background: rgba(239, 68, 68, 0.08);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .cell-salary {
        font-weight: 600;
        color: var(--slate);
        font-variant-numeric: tabular-nums;
        text-align: right;
        white-space: nowrap;
    }
    .cell-text-trunc {
        max-width: 140px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: inline-block;
    }
    .cell-center { text-align: center; }
    .cell-muted { color: var(--muted); font-size: 0.72rem; }

    /* ── Pagination ───────────────────────────────────────────── */
    .pagination-wrap { background: #fafbfc; }
    .pagination { margin: 0; gap: 4px; }
    .pagination .page-link {
        border: 1.5px solid var(--border);
        color: var(--slate-light);
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 8px !important;
        margin: 0 2px;
        min-width: 36px;
        text-align: center;
    }
    .pagination .page-link:hover {
        background: var(--teal-light);
        border-color: var(--teal-mid);
        color: var(--teal-dark);
    }
    .pagination .page-item.active .page-link {
        background: var(--teal);
        border-color: var(--teal);
        color: #fff;
    }
    .pagination .page-item.disabled .page-link {
        color: var(--muted);
        background: var(--surface);
        border-color: var(--border);
    }
</style>

<div class="home-shell">

    <div class="home-topbar">
        <div>
            <h4 class="page-title">Employee Information</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted">Reports</li>
                    <li class="breadcrumb-item active fw-semibold" aria-current="page">Employee Information</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
            <h5 class="sc-title">Search Filters</h5>
        </div>
        <div class="sc-body">
            <form action='' id="frmSearch">
                {{-- Row 1: Date Range + Search + Status --}}
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-lg-3 col-md-6">
                        <label class="field-label">Date Hired Range</label>
                        <div class="input-group">
                            <input value="{{ request('date_from') ? date('Y-m-d', strtotime(request('date_from'))) : '' }}" type="date" id="date_from" name="date_from" class="form-control">
                            <span class="input-group-text">to</span>
                            <input value="{{ request('date_to') ? date('Y-m-d', strtotime(request('date_to'))) : '' }}" type="date" id="date_to" name="date_to" class="form-control">
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="field-label">Search Employee</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                            <input type="text" id="fltSearch" name="search" class="form-control" placeholder="Name or Employee ID..."
                                   value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label for="fltStatus" class="field-label">Status</label>
                        <select class="form-select" name="status" id="fltStatus">
                            <option value="all">All Statuses</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Employed</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Resigned</option>
                        </select>
                    </div>

                    <div class="col-lg-auto col-md-auto d-flex align-items-end">
                        <button type="submit" id="btn_rptrefresh" class="btn btn-teal rounded-pill px-4 fw-bold shadow-sm">
                            <i class="fa-solid fa-magnifying-glass me-2"></i>Search
                        </button>
                    </div>
                </div>

                {{-- Row 2: Org filters + Actions --}}
                <div class="row g-3 align-items-end">
                    <div class="col-lg-2 col-md-4">
                        <label for="selClassification" class="field-label">Classification</label>
                        <select class="form-select" name="classification_id" id="selClassification">
                            <option value="all">All</option>
                            @if (count($classifications) > 0)
                                @foreach ($classifications as $employeeClassification)
                                    <option {{ request('classification_id') == $employeeClassification->class_code ? 'selected' : '' }} value='{{ $employeeClassification->class_code }}'>{{ $employeeClassification->class_desc }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label for="selCompany" class="field-label">Company</label>
                        <select class="form-select" name="company_id" id="selCompany">
                            <option value="all">All</option>
                            @if (count($companies) > 0)
                                @foreach ($companies as $company)
                                    <option {{ request('company_id') == $company->comp_id ? 'selected' : '' }} value='{{ $company->comp_id }}'>{{ $company->comp_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label for="selDepartment" class="field-label">Department</label>
                        <select class="form-select" name="department_id" id="selDepartment">
                            <option value="all">All</option>
                            @if (count($departments) > 0)
                                @foreach ($departments as $department)
                                    <option {{ request('department_id') == $department->id ? 'selected' : '' }} value='{{ $department->id }}'>{{ $department->dep_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label for="selPosition" class="field-label">Position</label>
                        <select class="form-select" name="position_id" id="selPosition">
                            <option value="all">All</option>
                            @if (count($positions) > 0)
                                @foreach ($positions as $position)
                                    <option {{ request('position_id') == $position->id ? 'selected' : '' }} value='{{ $position->id }}'>{{ $position->pos_desc }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-8">
                        <div class="d-flex gap-2 align-items-end justify-content-lg-end flex-wrap">
                            @php
                                $activeFilters = [];
                                if (request('date_from') || request('date_to')) $activeFilters[] = 'date';
                                if (request('search')) $activeFilters[] = 'search';
                                if (request('status') && request('status') !== 'all') $activeFilters[] = 'status';
                                if (request('classification_id') && request('classification_id') !== 'all') $activeFilters[] = 'class';
                                if (request('company_id') && request('company_id') !== 'all') $activeFilters[] = 'company';
                                if (request('department_id') && request('department_id') !== 'all') $activeFilters[] = 'dept';
                                if (request('position_id') && request('position_id') !== 'all') $activeFilters[] = 'pos';
                            @endphp
                            @if (count($activeFilters) > 0)
                                <span class="filter-badge">
                                    Filters <span class="count">{{ count($activeFilters) }}</span>
                                </span>
                                <button type="button" class="btn-reset" onclick="clearFilters()" title="Clear all filters">
                                    <i class="fa-solid fa-xmark me-1"></i>Clear
                                </button>
                            @endif
                            <button
                                onclick="printData()"
                                type="button" class="btn btn-outline-teal rounded-pill px-3 fw-bold" title="Print Report">
                                <i class="fa fa-print"></i>
                            </button>
                            <button
                                onclick="exportData()"
                                type="button" id="btn_rptprint" class="btn btn-teal rounded-pill px-4 fw-bold shadow-sm">
                                <i class="fa-solid fa-download me-2"></i>Export
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-users"></i></div>
            <h5 class="sc-title">Employee Records</h5>
        </div>
        <div class="sc-body" style="padding:0;" id="Report_thisPrint">

            <div class="d-none p-4 border-bottom print-header">
                <h3 class="fw-bold mb-1">Employee Information Report</h3>
                <p class="text-muted mb-0">Date Range: <span class="rptDateRange"></span></p>
            </div>

            <div class="table-responsive" style="max-height: 70vh;">
                <table class="table table-hover align-middle mb-0 table-sticky-header">
                    <thead>
                       <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Gender</th>
                        <th>Date of Birth</th>
                        <th>Civil Status</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Company</th>
                        <th>Class</th>
                        <th>Dept</th>
                        <th>Position</th>
                        <th>Supervisor</th>
                        <th class="cell-center">Status</th>
                        <th>Date Hired</th>
                        <th>Date Regular</th>
                        <th class="cell-center">Basic Salary</th>
                        <th class="cell-center">Allowance</th>
                    </tr>
                    </thead>
                    <tbody id="tbl_rptattendance" name="tbl_rptattendance">
                        @foreach ($employees as $employee)
                            @php
                                $info = $employee->employeeInformation;
                                $empLoc = $loop->iteration;
                            @endphp
                            <tr>
                                <td class="cell-muted cell-center">{{ $empLoc }}</td>
                                <td>
                                    <span class="emp-name-cell">{{ trim(($employee->user?->lname ?? '') . ', ' . ($employee->user?->fname ?? '')) }}</span>
                                    <span class="emp-id-sub">{{ $employee->empID }}</span>
                                </td>
                                <td>{{ $info?->gender ?? '' }}{{ $employee->user?->suffix ? ' / ' . $employee->user?->suffix : '' }}</td>
                                <td>{{ $info?->empBdate ? date('M d, Y', strtotime($info->empBdate)) : '' }}</td>
                                <td>
                                    @php $cs = $info?->empCStatus; @endphp
                                    {{ $cs === '0' ? 'Single' : ($cs === '1' ? 'Married' : ($cs === '2' ? 'Divorced' : '—')) }}
                                </td>
                                <td>{{ $info?->empPContact ?? '—' }}</td>
                                <td>
                                    @if($info?->empEmail)
                                        <span class="cell-text-trunc" title="{{ $info->empEmail }}">{{ $info->empEmail }}</span>
                                    @else
                                        <span class="cell-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $addr = trim(implode(' ', array_filter([
                                            $info?->empAddStreet ?? '',
                                            $info?->empAddBrgyDesc ?? '',
                                            $info?->empAddCityDesc ?? '',
                                        ])));
                                    @endphp
                                    @if($addr)
                                        <span class="cell-text-trunc" title="{{ $addr }}">{{ $addr }}</span>
                                    @else
                                        <span class="cell-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $employee->company?->comp_name ?? '—' }}</td>
                                <td>{{ $employee->classification?->class_desc ?? '—' }}</td>
                                <td>{{ $employee->department?->dep_name ?? '—' }}</td>
                                <td>{{ $employee->position?->pos_desc ?? '—' }}</td>
                                <td>{{ trim(($employee->immediateSupervisor?->fname ?? '') . ' ' . ($employee->immediateSupervisor?->lname ?? '')) ?: '—' }}</td>
                                <td class="cell-center">
                                    @if($employee->empStatus == '1')
                                        <span class="badge-status badge-employed">Employed</span>
                                    @else
                                        <span class="badge-status badge-resigned">Resigned</span>
                                    @endif
                                </td>
                                <td>{{ $employee->empDateHired ? $employee->empDateHired->format('M d, Y') : '' }}</td>
                                <td>{{ $employee->empDateRegular ? $employee->empDateRegular->format('M d, Y') : '' }}</td>
                                <td class="cell-salary">{{ number_format($employee->empBasic ?? 0, 2) }}</td>
                                <td class="cell-salary">{{ number_format($employee->empAllowance ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
             
            @php
                $currentPage = $employees->currentPage();
                $lastPage = $employees->lastPage();
            @endphp
            <div class="pagination-wrap d-flex justify-content-between align-items-center py-3 px-3 border-top flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small fw-semibold">Page</span>
                    <input type="number" id="pageJumpInput" class="form-control form-control-sm"
                           style="width: 70px; text-align: center; font-weight: 600;"
                           min="1" max="{{ $lastPage }}" value="{{ $currentPage }}"
                           onkeydown="if(event.key==='Enter') jumpToPage()">
                    <span class="text-muted small">of {{ $lastPage }}</span>
                    <button class="btn btn-sm btn-outline-teal rounded-pill px-3 fw-semibold" onclick="jumpToPage()">
                        Go
                    </button>
                </div>
                <div>
                    {{ $employees->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
<script id="3m6r9h">
    function getParams() {
        let form = document.getElementById('frmSearch');
        return new URLSearchParams(new FormData(form)).toString();
    }

    function clearFilters() {
        let form = document.getElementById('frmSearch');
        form.reset();
        // Reset selects to 'all'
        form.querySelectorAll('select').forEach(s => { s.value = 'all'; });
        // Trigger search with clean filters
        form.submit();
    }

    function printData() {
        window.open("{{ route('employee.report.print') }}?" + getParams(), '_blank');
    }

    // Allow Enter key in search field to trigger filter
    document.getElementById('fltSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            this.form.submit();
        }
    });

    function jumpToPage() {
        const input = document.getElementById('pageJumpInput');
        const page = parseInt(input.value, 10);
        const last = {{ $lastPage }};
        if (isNaN(page) || page < 1 || page > last) {
            alert('Please enter a page number between 1 and ' + last + '.');
            input.value = {{ $currentPage }};
            return;
        }
        const params = new URLSearchParams(window.location.search);
        params.set('page', page);
        window.location.search = params.toString();
    }

    function exportData() {
        window.location.href = "{{ route('employee.report.export') }}?" + getParams();
    }
</script>
@endsection