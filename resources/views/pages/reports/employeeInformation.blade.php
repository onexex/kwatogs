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

    /* Professional Table Refinements */
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
                <div class="row g-3 align-items-end">

                    <div class="col-lg-3 col-md-6">
                        <label class="field-label">Date Hired Range</label>
                        <div class="input-group ">
                            <input value="{{ request('date_from') ? date('Y-m-d', strtotime(request('date_from'))) : '' }}" type="date" id="date_from" name="date_from" class="form-control rptdatefrom">
                            <span class="input-group-text">to</span>
                            <input value="{{ request('date_to') ? date('Y-m-d', strtotime(request('date_to'))) : '' }}" type="date" id="date_to" name="date_to" class="form-control rptdateto">
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 ">
                        <label for="selClassification" class="field-label">Classification <span class="req">*</span></label>
                        <select class="form-select" name="classification_id" id="selClassification">
                            <option value="">Select Classification</option>
                            @if (count($classifications) > 0)
                                @foreach ($classifications as $employeeClassification)
                                    <option {{ request('classification_id') == $employeeClassification->class_code ? 'selected' : '' }} value='{{ $employeeClassification->class_code }}'>{{ $employeeClassification->class_desc }}</option>
                                @endforeach
                            @endif
                        </select>
                        <span class="text-danger small error-text classification_error"></span>
                    </div>

                    <div class="col-lg-2 col-md-6 ">
                        <label for="selCompany" class="field-label">Company <span class="req">*</span></label>
                        <select class="form-select" name="company_id" id="selCompany">
                            <option value="">Select Company</option>
                            @if (count($companies) > 0)
                                @foreach ($companies as $company)
                                    <option {{ request('company_id') == $company->comp_id ? 'selected' : '' }} value='{{ $company->comp_id }}'>{{ $company->comp_name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <span class="text-danger small error-text classification_error"></span>
                    </div>

                    <div class="col-lg-2 col-md-6 ">
                        <label for="selCompany" class="field-label">Department <span class="req">*</span></label>
                        <select class="form-select" name="department_id" id="selCompany">
                            <option value="">Select Department</option>
                            @if (count($departments) > 0)
                                @foreach ($departments as $department)
                                    <option {{ request('department_id') == $department->id ? 'selected' : '' }} value='{{ $department->id }}'>{{ $department->dep_name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <span class="text-danger small error-text classification_error"></span>
                    </div>

                    <div class="col-lg-2 col-md-6 ">
                        <label for="selCompany" class="field-label">Position <span class="req">*</span></label>
                        <select class="form-select" name="position_id" id="selCompany">
                            <option value="">Select Position</option>
                            @if (count($positions) > 0)
                                @foreach ($positions as $position)
                                    <option {{ request('position_id') == $position->id ? 'selected' : '' }} value='{{ $position->id }}'>{{ $position->pos_desc }}</option>
                                @endforeach
                            @endif
                        </select>
                        <span class="text-danger small error-text classification_error"></span>
                    </div>

                    <div class="col-lg-12 col-md-12 text-end">
                        <div class="d-flex gap-2 justify-content-lg-end">
                            <button type="submit" id="btn_rptrefresh" class="btn btn-outline-teal rounded-pill px-4 fw-bold flex-fill flex-lg-grow-0 rptbtnref">
                                <i class="fa-solid fa-arrows-rotate me-2"></i>Search
                            </button>
                            <button
                                onclick="exportData()"
                                type="button" id="btn_rptprint" class="btn btn-teal rounded-pill px-4 fw-bold shadow-sm flex-fill flex-lg-grow-0 rptbtnprint">
                                <i class="fa-solid fa-print me-2"></i>Export
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
                    <thead class="text-center">
                       <tr>
                        <th scope="col" class="ps-4">No</th>
                        <th scope="col">Employee Name</th>
                        <th scope="col">Suffix</th>
                        <th scope="col">Gender</th>
                        <th scope="col">Citizenship</th>
                        <th scope="col">Date of Birth</th>
                        <th scope="col">Civil Status</th>
                        <th scope="col">Phone Number</th>
                        <th scope="col">Email</th>
                        <th scope="col">Address</th>
                        <th scope="col">Company</th>
                        <th scope="col">Classification</th>
                        <th scope="col">Department</th>
                        <th scope="col">Position</th>
                        <th scope="col">Immediate Superior</th>
                        <th scope="col">Status</th>
                        <th scope="col">Date Hired</th>
                        <th scope="col">Date Regular</th>
                        <th scope="col">Basic Salary</th>
                        <th scope="col">Allowance</th>
                    </tr>
                    </thead>
                    <tbody id="tbl_rptattendance" name="tbl_rptattendance" class="text-center">
                        @foreach ($employees as $employee)
                            <tr>
                                <td>{{ $employee->empID }}</td>
                                <td>{{ $employee->user?->fname }} {{ $employee->user?->lname }}</td>
                                <td>{{ $employee->user?->suffix }}</td>
                                <td>{{ $employee->employeeInformation?->gender }}</td>
                                <td>{{ $employee->employeeInformation?->citizenship }}</td>
                                <td>{{ $employee->employeeInformation?->empBdate ? date('F d, Y', strtotime($employee->employeeInformation?->empBdate)) : '' }}</td>
                                <td>  {{
                                    $employee->employeeInformation?->empCStatus == '0' ? 'Single' :
                                    ($employee->employeeInformation?->empCStatus == '1' ? 'Married' :
                                    ($employee->employeeInformation?->empCStatus == '2' ? 'Divorced' : 'N/A'))
                                }}</td>
                                <td>{{ $employee->employeeInformation?->empPContact }}</td>
                                <td>{{ $employee->employeeInformation?->empEmail }}</td>
                                <td>{{ $employee->employeeInformation?->empAddStreet }} {{ $employee->employeeInformation?->empAddBrgyDesc }} {{ $employee->employeeInformation?->empAddCityDesc }}</td>
                                <td>{{ $employee->company?->comp_name }}</td>
                                <td>{{ $employee->classification?->class_desc }}</td>
                                <td>{{ $employee->department?->dep_name }}</td>
                                <td>{{ $employee->position?->pos_desc }}</td>
                                <td>{{ $employee->immediateSupervisor?->fname }} {{ $employee->immediateSupervisor?->lname }}</td>
                                <td>  {{
                                    $employee->empStatus == '1' ? 'Employed' : 'Resigned' 
                                }}</td>
                                <td>{{ $employee->empDateHired ? date('F d, Y', strtotime($employee->empDateHired)) : '' }}</td>
                                <td>{{ $employee->empDateRegular ? date('F d, Y', strtotime($employee->empDateRegular)) : '' }}</td>
                                <td>{{ $employee->empBasic }}</td>
                                <td>{{ $employee->empAllowance }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
             
            <div class="pagination-wrap d-flex justify-content-center align-items-center py-3 border-top">
                {{ $employees->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
<script id="3m6r9h">
    function exportData() {
        let form = document.getElementById('frmSearch');

        let params = new URLSearchParams(new FormData(form)).toString();

        window.location.href = "{{ route('employee.report.export') }}?" + params;
    }
</script>
@endsection