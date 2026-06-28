@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
        .payroll-shell {
            background: var(--bg);
            min-height: 100vh;
            padding: 24px 28px 60px;
            margin: -1.5rem -1.5rem 0;
        }

        /* ── Top header bar ──────────────────────────────────────── */
        .payroll-topbar {
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
        .payroll-topbar .page-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--slate);
            margin: 0;
            letter-spacing: .3px;
            text-transform: uppercase; /* matches Attendance Viewer's header treatment */
        }
        .payroll-topbar .breadcrumb {
            font-size: 0.75rem;
            margin: 2px 0 0;
            padding: 0;
            background: none;
        }
        .payroll-topbar .breadcrumb-item.active.text-teal { color: var(--teal) !important; font-weight: 600; }

        /* ── Soft status badges (same pattern as Attendance Viewer's badge-soft-primary) ── */
        .badge-soft-success {
            background-color: rgba(16, 185, 129, .12);
            color: #047857;
            border: 1px solid rgba(16, 185, 129, .25);
        }
        .badge-soft-warning {
            background-color: rgba(245, 158, 11, .12);
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, .25);
        }
        .badge-soft-primary {
            background-color: rgba(0, 128, 128, .1);
            color: var(--teal);
            border: 1px solid rgba(0, 128, 128, .2);
        }

        /* ── Buttons ─────────────────────────────────────────────── */
        .btn-teal {
            background-color: var(--teal);
            border-color: var(--teal);
            color: #fff;
        }
        .btn-teal:hover, .btn-teal:focus, .btn-teal:active {
            background-color: var(--teal-dark) !important;
            border-color: var(--teal-dark) !important;
            color: #fff !important;
        }
        .text-teal { color: var(--teal) !important; }

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
            color: var(--slate-light);
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        .field-icon {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--teal-light);
            color: var(--teal);
            border-radius: 6px;
            margin-right: 10px;
        }
        .form-control, .form-select {
            border: 1.5px solid var(--border);
            border-radius: var(--radius-input);
            font-size: 0.875rem;
            color: var(--slate);
            background: #fafbfc;
            transition: border-color .15s, box-shadow .15s;
            padding: 0.55rem 0.85rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--teal);
            box-shadow: 0 0 0 3px rgba(0,128,128,.1);
            background-color: #fff;
            outline: none;
        }

        /* ── Filter cards ────────────────────────────────────────── */
        .filter-card {
            border: 1px solid var(--border);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
            background: var(--surface);
            transition: transform 0.2s;
            height: 100%;
        }
        .filter-card:hover { transform: translateY(-3px); }

        /* ── Compact toolbar (filters in one slim row) ───────────── */
        .filter-toolbar .field-label {
            font-size: 0.62rem;
            margin-bottom: 4px;
        }
        .filter-toolbar .field-icon {
            width: 20px;
            height: 20px;
            font-size: 0.65rem;
            margin-right: 6px;
            border-radius: 5px;
        }
        .filter-toolbar .form-control,
        .filter-toolbar .form-select {
            font-size: 0.8rem;
            padding: 0.4rem 0.65rem;
        }
        .filter-toolbar .form-control-sm {
            font-size: 0.78rem;
            padding: 0.4rem 0.5rem;
        }
        .filter-toolbar .btn-teal {
            padding: 0.4rem 0.85rem;
            font-size: 0.8rem;
        }

        /* ── Quick reports panel ─────────────────────────────────── */
        .quick-reports-card {
            background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dark) 100%);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
            border: none;
        }
        .quick-reports-card .btn-glass-light,
        .quick-reports-card .btn-white-primary {
            white-space: nowrap;
        }
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
        .btn-white-primary {
            background-color: white;
            color: var(--teal) !important;
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

        /* ── Payroll table ───────────────────────────────────────── */
        .payroll-table { border-collapse: separate; border-spacing: 0; }
        .payroll-table thead th {
            position: sticky !important;
            top: 0;
            background-color: var(--surface);
            z-index: 10;
            border-bottom: 2px solid var(--border);
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--slate-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 5px;
        }
        .payroll-table tbody td {
            font-size: 0.7rem;
            padding: 8px 5px;
            white-space: nowrap;
            color: var(--slate);
            border-bottom: 1px solid #f1f5f9;
        }
        /* Zebra striping for easier row tracking across the wide table */
        .payroll-table tbody tr:nth-child(even):not(.payroll-totals) td { background-color: #fbfdfd; }
        .payroll-table tbody tr:hover:not(.payroll-totals) td { background: var(--teal-light) !important; }

        .bg-earnings {
            background-color: rgba(0, 128, 128, 0.03);
        }
        .bg-deductions {
            background-color: rgba(220, 53, 69, 0.03);
        }
        .fw-bold-total {
            font-weight: 800;
            color: var(--teal);
        }

        /* ── Frozen first columns (#, Employee) so identity stays visible
              while scrolling the 20+ money columns horizontally ───────── */
        .payroll-table th.col-num,
        .payroll-table td.col-num {
            position: sticky;
            left: 0;
            width: 40px;
            min-width: 40px;
            background-color: var(--surface);
            z-index: 9;
        }
        .payroll-table th.col-emp,
        .payroll-table td.col-emp {
            position: sticky;
            left: 40px;
            min-width: 150px;
            text-align: left;
            background-color: var(--surface);
            z-index: 9;
            box-shadow: 6px 0 8px -6px rgba(15, 23, 42, .18);
        }
        .payroll-table thead th.col-num,
        .payroll-table thead th.col-emp { z-index: 12; }
        /* Keep frozen cells opaque against zebra + hover tints */
        .payroll-table tbody tr:nth-child(even):not(.payroll-totals) td.col-num,
        .payroll-table tbody tr:nth-child(even):not(.payroll-totals) td.col-emp { background-color: #fbfdfd; }
        .payroll-table tbody tr:hover:not(.payroll-totals) td.col-num,
        .payroll-table tbody tr:hover:not(.payroll-totals) td.col-emp { background: var(--teal-light) !important; }

        /* Muted zero values — drop the visual weight of 0.00 so real numbers pop */
        .payroll-table tbody td.cell-zero { color: var(--muted); font-weight: 400; }

        /* Totals row: frozen label cell + emphasis */
        .payroll-table tr.payroll-totals td { border-top: 2px solid var(--teal); }
        .payroll-table tr.payroll-totals td.col-totals {
            position: sticky;
            left: 0;
            z-index: 9;
            background: #e0f2f1;
            text-align: left;
        }

        /* Loading + empty states */
        .payroll-state td { padding: 38px 12px !important; }
        .payroll-state .spinner-border { width: 1.4rem; height: 1.4rem; }
        .payroll-empty-icon {
            width: 46px; height: 46px; border-radius: 50%;
            background: var(--teal-light); color: var(--teal);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.1rem; margin-bottom: 10px;
        }

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

        /* ── Modal styling ───────────────────────────────────────── */
        #mdlAdjustment .modal-content {
            border-radius: var(--radius-card);
            border: none;
            overflow: hidden;
        }
        #mdlAdjustment .modal-header {
            background: var(--teal);
            color: #fff;
            border-bottom: none;
            padding: 16px 22px;
        }
        #mdlAdjustment .modal-header .modal-title { color: #fff; }
        #mdlAdjustment .modal-header .modal-title i { color: #fff; }
        #mdlAdjustment .btn-close { filter: brightness(0) invert(1); }
        #mdlAdjustment .modal-body { background: var(--bg); padding: 22px; }
        #mdlAdjustment .modal-footer {
            background: var(--surface);
            border-top: 1px solid var(--border);
        }

        .adj-table thead th {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--slate-light);
            text-transform: uppercase;
            letter-spacing: .4px;
            border-bottom: 2px solid var(--border);
        }
        .adj-table tbody td {
            font-size: 0.83rem;
            color: var(--slate);
            vertical-align: middle;
        }
    </style>

    <div class="payroll-shell">

        <div class="payroll-topbar">
            <div>
                <h4 class="page-title">Payroll System</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item text-muted">Financials</li>
                        <li class="breadcrumb-item active fw-semibold text-teal" aria-current="page">Payroll Processing
                        </li>
                    </ol>
                </nav>
            </div>
            {{-- <div class="d-flex gap-2">
                <button class="btn btn-teal rounded-pill px-4 shadow-sm fw-bold" id="btnRelease">
                    <i class="fas fa-check-double me-2"></i> Approve Payroll
                </button>
            </div> --}}
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="card filter-card filter-toolbar">
                    <div class="card-body py-3 px-3">
                        <div class="row g-2 align-items-end">

                            <div class="col-6 col-md-3 col-xl-2">
                                <label class="field-label mb-1">
                                    <div class="field-icon"><i class="fa fa-calendar"></i></div> Payroll Date
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="date" id="pay_date" class="form-control bg-light border-0">
                                </div>
                            </div>

                            <div class="col-6 col-md-2 col-xl-1 d-flex align-items-end">
                                <button class="btn btn-teal fw-bold w-100" id="btnGenerate">Generate</button>
                            </div>

                            <div class="col-6 col-md-3 col-xl-2">
                                <label class="field-label mb-1">
                                    <div class="field-icon"><i class="fa fa-clock"></i></div> Cut-off Period
                                </label>
                                <div class="row g-1">
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

                            <div class="col-6 col-md-2 col-xl-2">
                                <label class="field-label mb-1">
                                    <div class="field-icon"><i class="fa fa-building"></i></div> Company
                                </label>
                                <select id="selCompany" class="form-select form-select-sm bg-light border-0">
                                    <option value="all">All Organizations</option>
                                    @foreach ($companies as $company)
                                        {{-- Using $company->comp_id and $company->comp_name based on your model's fillable fields --}}
                                        <option value="{{ $company->comp_id }}">{{ $company->comp_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-6 col-md-2 col-xl-2">
                                <label class="field-label mb-1">
                                    <div class="field-icon"><i class="fa fa-filter"></i></div> Status Filter
                                </label>
                                <select id="selFilter" class="form-select form-select-sm bg-light border-0">
                                    <option value="all" @selected($selectedClassification === 'all')>View All </option>
                                    @foreach ($classifications as $classification)
                                        <option value="{{ $classification->class_code }}" @selected($selectedClassification == $classification->class_code)>
                                            {{ $classification->class_desc }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-6 col-md-2 col-xl-2">
                                <label class="field-label mb-1">
                                    <div class="field-icon"><i class="fa fa-sitemap"></i></div> Department
                                </label>
                                <select id="selDepartment" class="form-select form-select-sm bg-light border-0">
                                    <option value="all">All Departments</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->dep_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="card quick-reports-card">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2 py-2 px-3">

                        <label class="d-flex align-items-center text-white opacity-75 small fw-bold text-uppercase tracking-wider mb-0">
                            <div class="bg-white bg-opacity-20 rounded-3 p-2 me-2 d-flex align-items-center justify-content-center"
                                style="width: 28px; height: 28px;">
                                <i class="fa fa-file-export small"></i>
                            </div>
                            Quick Reports
                        </label>

                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-glass-light" id="btnPayroll">
                                <i class="fa-solid fa-list-ol me-1 small"></i> View Payroll
                            </button>
                            <button class="btn btn-glass-light" id="btnSummary">
                                <i class="fa fa-chart-pie me-1 small"></i> Summary
                            </button>
                            {{-- <button class="btn btn-glass-light" id="btnSummary1">
                                <i class="fa fa-file-export"></i> Export
                            </button> --}}
                            {{-- <button class="btn btn-white-primary" data-bs-toggle="modal"
                                data-bs-target="#mdlAdjustment" id="btnAdjustment">
                                <i class="fa fa-plus-circle me-2"></i>CREATE ADJUSTMENT
                            </button> --}}
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="sc">
            <div class="sc-head">
                <div class="sc-head-left">
                    <div class="sc-icon"><i class="fa fa-list-ol"></i></div>
                    <h5 class="sc-title">Payroll Register</h5>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span id="approvalBadge" class="badge badge-soft-success rounded-pill px-3 py-2 d-none" style="font-size:.72rem;font-weight:700;">
                        <i class="fa fa-lock me-1"></i><span id="approvalBadgeText">APPROVED · FINAL</span>
                    </span>
                    <button class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-sm d-none" id="btnApprovePayroll">
                        <i class="fa fa-circle-check me-2"></i> Approve Payroll
                    </button>
                    <button class="btn btn-warning btn-sm rounded-pill px-3 fw-bold shadow-sm d-none" id="btnReopenPayroll">
                        <i class="fa fa-unlock me-2"></i> Reopen
                    </button>
                    @can('regeneratepayroll')
                    <button class="btn btn-danger btn-sm rounded-pill px-3 fw-bold shadow-sm d-none" id="btnDeletePayroll">
                        <i class="fa fa-trash me-2"></i> Delete Payroll
                    </button>
                    @endcan
                    <button class="btn btn-light btn-sm rounded-pill px-3 fw-bold shadow-sm" id="btnPrint">
                        <i class="fa fa-print me-2 text-teal"></i> Print Report
                    </button>
                    <button class="btn btn-teal btn-sm rounded-pill px-3 fw-bold shadow-sm" id="btnPrintPayslips">
                        <i class="fa fa-file-invoice me-2"></i> Print Payslips
                    </button>
                    @can('payslipemail')
                        <button class="btn btn-light btn-sm rounded-pill px-3 fw-bold shadow-sm" id="btnEmailPayslips" data-bs-toggle="modal" data-bs-target="#payslipEmailModal">
                            <i class="fa fa-paper-plane me-2 text-teal"></i> Email Payslips
                        </button>
                    @endcan
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm rounded-pill px-3 fw-bold shadow-sm dropdown-toggle" type="button" id="btnExportMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa fa-file-excel me-2 text-success"></i> Export
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="btnExportMenu">
                            <li><a class="dropdown-item" href="#" id="btnExportCash"><i class="fa fa-money-bill-wave me-2 text-secondary"></i>Cash list (name &amp; net pay)</a></li>
                            <li><a class="dropdown-item" href="#" id="btnExportCard"><i class="fa fa-credit-card me-2 text-teal"></i>ATM / Card (bank upload)</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" id="btnExportGovDues"><i class="fa fa-building-columns me-2 text-success"></i>Gov dues (EE/ER)</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="sc-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center payroll-table mb-0">
                        <thead>
                            <tr>
                                <th rowspan="2" class="ps-4 col-num">#</th>
                                <th rowspan="2" class="col-emp">Employee</th>
                                <th rowspan="2">Basic Salary</th>
                                <th rowspan="2">Bi-Monthly</th>
                                <th rowspan="2">Abs/Trd/Ut</th>

                                <th colspan="4" class="bg-earnings text-teal text-center">Earnings</th>

                                <th rowspan="2" class="bg-light fw-bold">Gross Pay</th>

                                <th colspan="5" class="bg-deductions text-danger text-center">Govt Premiums & Loans
                                </th>

                                <th rowspan="2">Taxable Inc.</th>
                                <th rowspan="2">Tax</th>
                                <th rowspan="2">Allowances</th>
                                <th rowspan="2">Adjustments</th>
                                <th rowspan="2">Charges/Others</th>
                                <th rowspan="2">Company Loan</th>
                                <th rowspan="2">Cash Adv</th>
                                <th rowspan="2" class="bg-light fw-bold">Net Pay</th>
                                <th rowspan="2" class="pe-4 fw-bold-total">Pay Receivable</th>
                            </tr>
                            <tr>
                                <th class="bg-earnings">HD Pay</th>
                                <th class="bg-earnings">OT Hrs</th>
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
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-sliders-h me-2"></i> Payroll Adjustment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <div class="sub-divider"><span>New Adjustment</span></div>
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-lg-5">
                            <label class="field-label">Employee <span class="text-danger">*</span></label>
                            <select id="selEmployee" class="form-select">
                                <option value="">Search Employee...</option>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label class="field-label">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text border-0" style="background: var(--teal-light); color: var(--teal-dark); font-weight: 700;">₱</span>
                                <input type="number" step="0.01" id="txtAmount" class="form-control">
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <button class="btn btn-teal w-100 rounded-pill fw-bold shadow-sm"
                                id="btnSaveAdjustment">
                                <i class="fa fa-plus me-1"></i> Add Entry
                            </button>
                        </div>
                    </div>

                    <div class="sub-divider"><span>Pending Adjustments</span></div>
                    <div class="table-responsive" style="max-height: 250px;">
                        <table class="table table-hover align-middle small adj-table">
                            <thead>
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

                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted me-2"
                        data-bs-dismiss="modal">Close</button>
                    <button type="button" id="btnSaveClassification"
                        class="btn btn-teal rounded-pill px-5 fw-bold shadow-sm">Apply All Changes</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== Email Payslips Modal ===================== --}}
    <div class="modal fade" id="payslipEmailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-paper-plane me-2 text-teal"></i>Email Payslips</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="payslipEmailAlert"></div>

                    <div class="border rounded-3 p-3 mb-3" style="background:#f8fafc;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong style="font-size:.85rem;">Payslip Email Settings</strong>
                            <button type="button" class="btn btn-link btn-sm p-0" id="btnTogglePayslipEmailSettings">Edit</button>
                        </div>
                        <div id="payslipEmailSettingsView" style="font-size:.82rem; color:#64748b;">Loading...</div>
                        <div id="payslipEmailSettingsForm" class="d-none mt-2">
                            <div class="mb-2">
                                <label class="form-label" style="font-size:.8rem;">PDF Password</label>
                                <select class="form-select form-select-sm" id="selPayslipPasswordSource"></select>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="chkAutoSendOnApproval">
                                <label class="form-check-label" style="font-size:.8rem;" for="chkAutoSendOnApproval">
                                    Automatically email payslips when a payroll run is approved
                                </label>
                            </div>
                            <button type="button" class="btn btn-teal btn-sm" id="btnSavePayslipEmailSettings">Save Settings</button>
                        </div>
                    </div>

                    <p style="font-size:.85rem; color:#64748b;">
                        Sends a password-protected PDF payslip to the employee's email on file, for the pay date and filters currently selected on this screen.
                    </p>

                    <div class="table-responsive" style="max-height: 320px; overflow:auto;">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="payslipEmailStatusBody">
                                <tr><td colspan="4" class="text-center text-muted">Open this dialog to load status…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-teal rounded-pill px-4 fw-bold shadow-sm" id="btnSendPayslipEmails">
                        <i class="fa fa-paper-plane me-2"></i> Send Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>window.companyPayrollPeriods = @json($companyPeriods ?? []);</script>
    <script>window.canViewPayrollLogs = @can('payrolllogs') true @else false @endcan;</script>
    <script>window.payrollLogoUrl = "{{ asset('img/kwatogslogo.jpg') }}";</script>
    <script>
        window.canApprovePayroll = @can('approvepayroll') true @else false @endcan;
        window.canRegeneratePayroll = @can('regeneratepayroll') true @else false @endcan;
        window.canSendPayslipEmail = @can('payslipemail') true @else false @endcan;
    </script>
    <script src="{{ asset('js/modules/payroll.js') }}"></script>
@endsection
