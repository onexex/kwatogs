@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Consistent Sticky Header and Table Design */
        .payroll-table thead th {
            position: sticky !important;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
            border-bottom: 2px solid #dee2e6;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 5px;
        }

        .payroll-table tbody td {
            font-size: 0.7rem;
            padding: 8px 5px;
            white-space: nowrap;
        }

        .payroll-container .card {
            transition: transform 0.2s ease;
        }

        .payroll-container .card:hover {
            transform: translateY(-3px);
        }

        /* Filter Card Refinements */
        .filter-card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 1rem;
            transition: transform 0.2s;
            height: 100%;
        }

        .filter-card:hover {
            transform: translateY(-3px);
        }

        .filter-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }

        .filter-icon {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 128, 128, 0.1);
            color: #008080;
            border-radius: 6px;
            margin-right: 10px;
        }

        /* Column Grouping Colors for better scannability */
        .bg-earnings {
            background-color: rgba(0, 128, 128, 0.03);
        }

        .bg-deductions {
            background-color: rgba(220, 53, 69, 0.03);
        }

        .fw-bold-total {
            font-weight: 800;
            color: #008080;
        }
    </style>

    <div class="container-fluid payroll-container px-4 py-3">

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold text-dark m-0">Payroll System</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item text-muted">Financials</li>
                        <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Payroll Processing
                        </li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" id="btnRelease">
                    <i class="fas fa-check-double me-2"></i> Approve Payroll
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">

            <div class="col-xl-4 col-lg-6">
                <div class="card filter-card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="filter-label">
                                <div class="filter-icon"><i class="fa fa-calendar"></i></div> Payroll Date
                            </label>
                            <div class="input-group">
                                <input type="date" id="pay_date" class="form-control bg-light border-0">
                                <button class="btn btn-primary fw-bold px-3" id="btnGenerate">Generate</button>
                            </div>
                        </div>
                        <div>
                            <label class="filter-label">
                                <div class="filter-icon"><i class="fa fa-building"></i></div> Company
                            </label>
                            <select id="selCompany" class="form-select bg-light border-0">
                                <option value="all">All Organizations</option>
                                {{-- @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                            @endforeach --}}
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-6">
                <div class="card filter-card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="filter-label">
                                <div class="filter-icon"><i class="fa fa-clock"></i></div> Cut-off Period
                            </label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="date" id="date_from"
                                        class="form-control form-control-sm bg-light border-0 text-center"
                                        placeholder="From" readonly>
                                </div>
                                <div class="col-6">
                                    <input type="date" id="date_to"
                                        class="form-control form-control-sm bg-light border-0 text-center" placeholder="To"
                                        readonly>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="filter-label">
                                <div class="filter-icon"><i class="fa fa-filter"></i></div> Status Filter
                            </label>
                            <select id="selFilter" class="form-select bg-light border-0">
                                <option value="all">View All Employees</option>
                                <option value="released">Released Only</option>
                                <option value="pending">Pending Only</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-12">
                <div class="card border-0 shadow-sm h-100"
                    style="background: linear-gradient(135deg, #008080 0%, #005a5a 100%); border-radius: 1rem;">
                    <div class="card-body d-flex flex-column p-4">

                        <div class="mb-auto">
                            <label
                                class="d-flex align-items-center text-white opacity-75 small fw-bold text-uppercase tracking-wider mb-3">
                                <div class="bg-white bg-opacity-20 rounded-3 p-2 me-2 d-flex align-items-center justify-content-center"
                                    style="width: 32px; height: 32px;">
                                    <i class="fa fa-file-export small"></i>
                                </div>
                                Quick Reports
                            </label>

                            <div class="d-flex gap-2 mb-4">
                                <button class="btn btn-glass-light flex-fill" id="btnPayroll">
                                    <i class="fa-solid fa-list-ol me-1 small"></i> Register
                                </button>
                                <button class="btn btn-glass-light flex-fill" id="btnSummary">
                                    <i class="fa fa-chart-pie me-1 small"></i> Summary
                                </button>
                                 <button class="btn btn-glass-light flex-fill" id="btnSummary">
                                    <i class="fa fa-file-export"></i> Export
                                </button>
                            </div>
                        </div>

                        <button class="btn btn-white-primary w-100 shadow-sm" data-bs-toggle="modal"
                            data-bs-target="#mdlAdjustment" id="btnAdjustment">
                            <i class="fa fa-plus-circle me-2"></i>CREATE ADJUSTMENT
                        </button>

                    </div>
                </div>
            </div>

            <style>
                /* Glass Effect for Secondary Buttons */
                .btn-glass-light {
                    background: rgba(255, 255, 255, 0.15);
                    backdrop-filter: blur(4px);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    color: white;
                    font-weight: 600;
                    font-size: 0.75rem;
                    border-radius: 50px;
                    padding: 8px 15px;
                    transition: all 0.2s ease;
                }

                .btn-glass-light:hover {
                    background: rgba(255, 255, 255, 0.25);
                    color: white;
                    transform: translateY(-1px);
                }

                /* High Contrast Primary Button */
                .btn-white-primary {
                    background-color: white;
                    color: #008080 !important;
                    border-radius: 50px;
                    font-weight: 800;
                    font-size: 0.8rem;
                    padding: 12px;
                    letter-spacing: 0.5px;
                    border: none;
                    transition: all 0.2s ease;
                }

                .btn-white-primary:hover {
                    background-color: #f8f9fa;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
                }
            </style>

        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 px-4 border-bottom-0 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-secondary">PAYROLL REGISTER</h6>
                <button class="btn btn-light btn-sm rounded-pill px-3 fw-bold shadow-sm" id="btnPrint">
                    <i class="fa fa-print me-2 text-primary"></i> Print Register
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center payroll-table mb-0">
                        <thead>
                            <tr>
                                <th rowspan="2" class="ps-4">#</th>
                                <th rowspan="2">Employee</th>
                                <th rowspan="2">Basic Salary</th>
                                <th rowspan="2">Bi-Monthly</th>
                                <th rowspan="2">Abs/Trd/Ut</th>
                                <th colspan="3" class="bg-earnings text-primary">Earnings</th>
                                <th rowspan="2" class="bg-light fw-bold">Gross Pay</th>
                                <th colspan="5" class="bg-deductions text-danger">Govt Premiums & Loans</th>
                                <th rowspan="2">Tax</th>
                                <th rowspan="2">Charges</th>
                                <th rowspan="2">Cash Adv</th>
                                <th rowspan="2" class="pe-4 fw-bold-total">Pay Receivable</th>
                            </tr>
                            <tr>
                                <th class="bg-earnings">HD Pay</th>
                                <th class="bg-earnings">OT Pay</th>
                                <th class="bg-earnings">ND Pay</th>
                                <th class="bg-deductions small">SSS</th>
                                <th class="bg-deductions small">SSS Loan</th>
                                <th class="bg-deductions small">Pag-ibig</th>
                                <th class="bg-deductions small">PIB Loan</th>
                                <th class="bg-deductions small">PhilHealth</th>
                            </tr>
                        </thead>
                        <tbody id="payrollTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mdlAdjustment" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pt-4 px-4 bg-white">
                    <h5 class="modal-title fw-bold text-secondary text-uppercase tracking-wide">
                        <i class="fas fa-sliders-h me-2 text-primary"></i> Payroll Adjustment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="bg-light rounded-4 p-3 mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-5">
                                <label class="form-label small fw-semibold text-muted">Employee <span
                                        class="text-danger">*</span></label>
                                <select id="selEmployee" class="form-select border-0 shadow-sm">
                                    <option value="">Search Employee...</option>
                                </select>
                            </div>
                            <div class="col-lg-4">
                                <label class="form-label small fw-semibold text-muted">Amount <span
                                        class="text-danger">*</span></label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text border-0 bg-white">₱</span>
                                    <input type="number" step="0.01" id="txtAmount" class="form-control border-0">
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <button class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm"
                                    id="btnSaveAdjustment">
                                    <i class="fa fa-plus me-1"></i> Add Entry
                                </button>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-secondary mb-3 small text-uppercase">Pending Adjustments</h6>
                    <div class="table-responsive" style="max-height: 250px;">
                        <table class="table table-hover align-middle small">
                            <thead class="bg-white">
                                <tr class="text-muted">
                                    <th>No</th>
                                    <th>Employee</th>
                                    <th>Amount</th>
                                    <th>Date Created</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="tblAdjustment">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer border-0 pb-4 px-4 bg-white">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted me-2"
                        data-bs-dismiss="modal">Close</button>
                    <button type="button" id="btnSaveClassification"
                        class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Apply All Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/modules/payroll.js') }}"></script>
@endsection
