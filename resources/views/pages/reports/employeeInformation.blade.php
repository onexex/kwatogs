@extends('layout.app', [
    'title' => 'Employee Information Report'
])

@section('content')
<style>
    /* Professional Table Refinements */
    .table-sticky-header thead th {
        position: sticky !important;
        top: 0;
        background-color: #f8f9fa;
        z-index: 10;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: 2px solid #dee2e6;
    }

    .table tbody td {
        font-size: 0.8rem;
        vertical-align: middle;
    }

    /* Modern Badge for Duration/Status */
    .badge-soft-primary {
        background-color: rgba(0, 128, 128, 0.1);
        color: #008080;
        border: 1px solid rgba(0, 128, 128, 0.2);
    }

    .search-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
    }
</style>

<div class="container-fluid px-4 py-3">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">Employee Information</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted">Reports</li>
                    <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Employee Information</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card search-card mb-4">
        <div class="card-body p-4">
            <form action='' id="frmSearch">
                <div class="row g-3 align-items-end">

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase tracking-wider">Date Hired Range</label>
                        <div class="input-group ">
                            <input value="{{ request('date_from') ? date('Y-m-d', strtotime(request('date_from'))) : '' }}" type="date" id="date_from" name="date_from" class="form-control bg-light border-0 rptdatefrom">
                            <span class="input-group-text bg-light border-0 text-muted">to</span>
                            <input value="{{ request('date_to') ? date('Y-m-d', strtotime(request('date_to'))) : '' }}" type="date" id="date_to" name="date_to" class="form-control bg-light border-0 rptdateto">
                        </div>
                    </div>
                        
                    <div class="col-lg-3 col-md-6 ">
                        <label for="selClassification" class="form-label small fw-semibold text-muted">Classification <span class="text-danger">*</span></label>
                        <select class="form-select form-control-lg bg-light border-0 fs-6" name="classification_id" id="selClassification">
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
                        <label for="selCompany" class="form-label small fw-semibold text-muted">Company <span class="text-danger">*</span></label>
                        <select class="form-select form-control-lg bg-light border-0 fs-6" name="company_id" id="selCompany">
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
                        <label for="selCompany" class="form-label small fw-semibold text-muted">Department <span class="text-danger">*</span></label>
                        <select class="form-select form-control-lg bg-light border-0 fs-6" name="department_id" id="selCompany">
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
                        <label for="selCompany" class="form-label small fw-semibold text-muted">Position <span class="text-danger">*</span></label>
                        <select class="form-select form-control-lg bg-light border-0 fs-6" name="position_id" id="selCompany">
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
                            <button type="submit" id="btn_rptrefresh" class="btn btn-outline-primary rounded-pill px-4 fw-bold flex-fill flex-lg-grow-0 rptbtnref">
                                <i class="fa-solid fa-arrows-rotate me-2"></i>Search
                            </button>
                            <button type="button" id="btn_rptprint" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm flex-fill flex-lg-grow-0 rptbtnprint">
                                <i class="fa-solid fa-print me-2"></i>Export
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0" id="Report_thisPrint">
            
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
             
            <nav>
                <ul id="pagination" class="pagination pagination-sm justify-content-center mt-2">
                </ul>
                <div class="mt-3 p-2">
                    {{ $employees->links('pagination::bootstrap-5') }}
                </div>
            </nav>
        </div>
    </div>
</div>

@endsection