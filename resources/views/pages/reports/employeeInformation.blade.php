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

                    <div class="col-lg-5 col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase tracking-wider">Date Range</label>
                        <div class="input-group">
                            <input type="date" id="txtDateFrom" name="txtDateFrom" value="{{ date('Y-m-d', strtotime('-7 days')) }}" class="form-control bg-light border-0 rptdatefrom">
                            <span class="input-group-text bg-light border-0 text-muted">to</span>
                            <input type="date" id="txtDateTo" name="txtDateto" value="{{ date('Y-m-d') }}" class="form-control bg-light border-0 rptdateto">
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-12 text-end">
                        <div class="d-flex gap-2 justify-content-lg-end">
                            <button type="button" id="btn_rptrefresh" class="btn btn-outline-primary rounded-pill px-4 fw-bold flex-fill flex-lg-grow-0 rptbtnref">
                                <i class="fa-solid fa-arrows-rotate me-2"></i>Refresh
                            </button>
                            <button type="button" id="btn_rptprint" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm flex-fill flex-lg-grow-0 rptbtnprint">
                                <i class="fa-solid fa-print me-2"></i>Print
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
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection