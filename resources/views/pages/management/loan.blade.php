@extends('layout.app')

@section('content')

<style>
    /* ── Design tokens (shared with Edit Employee / Leave / Overtime) ── */
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
    .loan-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    /* ── Top header bar ──────────────────────────────────────── */
    .loan-topbar {
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
    .loan-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .loan-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }

    .btn-add-loan {
        background: var(--teal);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: .3px;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(0,128,128,.25);
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-add-loan:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

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
    .sc-body { padding: 0; }

    /* ── Field helpers ───────────────────────────────────────── */
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
        padding: 0.55rem 0.85rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--teal);
        box-shadow: 0 0 0 3px rgba(0,128,128,.1);
        background-color: #fff;
        outline: none;
    }

    /* ── Sub-section divider ─────────────────────────────────── */
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

    /* ── Table styling ───────────────────────────────────────── */
    .loan-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: var(--surface);
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--slate-light);
        text-transform: uppercase;
        letter-spacing: .4px;
        border-bottom: 2px solid var(--border);
        white-space: nowrap;
        padding: 12px 16px;
    }
    .loan-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .loan-table tbody tr:hover { background: var(--teal-light); }

    /* ── Type / status badges ────────────────────────────────── */
    .badge-loan-type {
        background: var(--teal-light);
        color: var(--teal-dark);
        border: 1px solid var(--teal-mid);
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: .3px;
        padding: 5px 12px;
        border-radius: 20px;
    }
    .badge-status {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: .3px;
        padding: 5px 12px;
        border-radius: 20px;
        border: 1px solid transparent;
    }
    .badge-status.active {
        background: rgba(16,185,129,.1);
        color: var(--success);
        border-color: var(--success);
    }
    .badge-status.inactive {
        background: var(--bg);
        color: var(--slate-light);
        border-color: var(--border);
    }

    .icon-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1.5px solid var(--border);
        background: var(--surface);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all .15s;
    }
    .icon-action-btn:hover { border-color: var(--teal-mid); background: var(--teal-light); }
    .icon-action-btn.danger:hover { border-color: var(--danger); background: #fff5f5; }

    /* ── Modal styling ───────────────────────────────────────── */
    #loanModal .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #loanModal .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #loanModal .modal-header .modal-title { color: #fff; }
    #loanModal .modal-header .modal-title i { color: #fff; }
    #loanModal .btn-close { filter: brightness(0) invert(1); }
    #loanModal .modal-body { background: var(--bg); padding: 22px; }
    #loanModal .modal-footer {
        background: var(--surface);
        border-top: 1px solid var(--border);
    }

    .btn-submit-loan {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 10px 26px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(245,158,11,.3);
        transition: all .2s;
    }
    .btn-submit-loan:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,.4); color: #fff; }

    .btn-cancel-loan {
        background: var(--surface);
        color: var(--slate-light);
        border: 1.5px solid var(--border);
        border-radius: 10px;
        padding: 10px 22px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        cursor: pointer;
        transition: all .2s;
    }
    .btn-cancel-loan:hover { background: var(--bg); }

    /* ── Filter bar ──────────────────────────────────────────── */
    .loan-filterbar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        padding: 14px 22px;
        border-bottom: 1px solid var(--border);
        background: #fbfdfd;
    }
    .loan-filter-search {
        position: relative;
        flex: 1 1 240px;
        min-width: 200px;
    }
    .loan-filter-search i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--muted);
        font-size: .8rem;
    }
    .loan-filter-search .form-control { padding-left: 34px; }
    .loan-filter-select { flex: 0 0 auto; width: auto; min-width: 150px; }
    .btn-filter-apply {
        background: var(--teal);
        color: #fff;
        border: none;
        border-radius: var(--radius-input);
        padding: .55rem 18px;
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .3px;
        cursor: pointer;
        transition: background .2s;
    }
    .btn-filter-apply:hover { background: var(--teal-dark); }
    .btn-filter-clear {
        background: var(--surface);
        color: var(--slate-light);
        border: 1.5px solid var(--border);
        border-radius: var(--radius-input);
        padding: .55rem 16px;
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .3px;
        text-decoration: none;
        transition: all .2s;
    }
    .btn-filter-clear:hover { background: var(--bg); color: var(--slate); }

    /* ── Pagination ──────────────────────────────────────────── */
    .loan-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        padding: 14px 22px;
        border-top: 1px solid var(--border);
    }
    .loan-pagination-info { font-size: .78rem; color: var(--slate-light); }
    .loan-pagination .pagination { margin: 0; }
    .loan-pagination .page-link {
        color: var(--teal);
        border-color: var(--border);
        font-size: .82rem;
    }
    .loan-pagination .page-item.active .page-link {
        background: var(--teal);
        border-color: var(--teal);
        color: #fff;
    }
    .loan-pagination .page-link:focus { box-shadow: 0 0 0 3px rgba(0,128,128,.1); }

    /* ── Employee multi-picker ───────────────────────────────── */
    .emp-picker {
        border: 1.5px solid var(--border);
        border-radius: var(--radius-input);
        background: #fafbfc;
        overflow: hidden;
    }
    .emp-picker-toolbar {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        padding: 10px 12px;
        border-bottom: 1px solid var(--border);
        background: var(--surface);
    }
    .emp-picker-search { position: relative; flex: 1 1 200px; }
    .emp-picker-search i {
        position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
        color: var(--muted); font-size: .78rem;
    }
    .emp-picker-search .form-control { padding-left: 32px; }
    .emp-picker-all {
        font-size: .76rem; font-weight: 700; color: var(--slate-light);
        display: inline-flex; align-items: center; gap: 6px; cursor: pointer; margin: 0;
        white-space: nowrap;
    }
    .emp-picker-count { font-size: .74rem; color: var(--teal-dark); font-weight: 700; white-space: nowrap; }
    .emp-picker-count #empSelectedCount { font-size: .8rem; }
    .emp-picker-list { max-height: 200px; overflow-y: auto; padding: 4px; }
    .emp-picker-item {
        display: flex; align-items: center; gap: 9px;
        padding: 8px 10px; border-radius: 6px; cursor: pointer; margin: 0;
        font-size: .82rem; color: var(--slate);
    }
    .emp-picker-item:hover { background: var(--teal-light); }
    .emp-picker-item input { width: 15px; height: 15px; accent-color: var(--teal); }

    /* ── Recurring charge ────────────────────────────────────── */
    .badge-recurring {
        display: inline-block;
        margin-left: 6px;
        background: rgba(245,158,11,.12);
        color: var(--warning);
        border: 1px solid var(--warning);
        font-size: .62rem;
        font-weight: 800;
        letter-spacing: .4px;
        padding: 4px 9px;
        border-radius: 20px;
        vertical-align: middle;
    }
    .recurring-card {
        border: 1.5px dashed var(--teal-mid);
        background: var(--teal-light);
        border-radius: var(--radius-input);
        padding: 12px 14px;
    }
    .recurring-card .form-check-input { accent-color: var(--teal); cursor: pointer; }
    .recurring-card .form-check-input:checked { background-color: var(--teal); border-color: var(--teal); }
    .recurring-card .form-check-label { color: var(--slate); font-size: .85rem; cursor: pointer; }
    .recurring-hint { font-size: .74rem; color: var(--slate-light); margin: 8px 0 0; line-height: 1.4; }

    .loan-toggle-wrap { display: inline-flex; align-items: center; gap: 8px; padding-left: 2.4em; }
    .loan-toggle-wrap .form-check-input { cursor: pointer; }
    .loan-toggle-wrap .form-check-input:checked { background-color: var(--success); border-color: var(--success); }
    .loan-toggle-label { font-size: .72rem; font-weight: 700; color: var(--slate-light); }
</style>

<div class="loan-shell">

    {{-- ── Top header ── --}}
    <div class="loan-topbar">
        <div>
            <p class="page-title">Charges &amp; Loans</p>
            <p class="page-sub">Manage employee loans, salary advances, and payroll charges</p>
        </div>
        <button type="button" class="btn-add-loan" id="addLoanBtn">
            <i class="fas fa-plus"></i> Add Record
        </button>
    </div>

    {{-- ── Loan Records ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fas fa-hand-holding-usd"></i></div>
                <h5 class="sc-title">Loan & Adjustment Records</h5>
                <span class="text-muted small">({{ $loans->total() }})</span>
            </div>
        </div>

        {{-- ── Filter bar ── --}}
        <form method="GET" action="{{ route('loans.index') }}" class="loan-filterbar">
            <div class="loan-filter-search">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ $search }}" class="form-control"
                       placeholder="Search employee name…" autocomplete="off">
            </div>

            <select name="type" class="form-select loan-filter-select">
                <option value="">All Types</option>
                <option value="pagibig"          {{ $type === 'pagibig'          ? 'selected' : '' }}>Pag-IBIG Loan</option>
                <option value="sss"              {{ $type === 'sss'              ? 'selected' : '' }}>SSS Loan</option>
                <option value="salary"           {{ $type === 'salary'           ? 'selected' : '' }}>Salary Loan</option>
                <option value="cash_adv"         {{ $type === 'cash_adv'         ? 'selected' : '' }}>Cash Advance</option>
                <option value="charges/penalty"  {{ $type === 'charges/penalty'  ? 'selected' : '' }}>Charges/Penalty</option>
                <option value="other"            {{ $type === 'other'            ? 'selected' : '' }}>Other</option>
            </select>

            <select name="status" class="form-select loan-filter-select">
                <option value="">All Status</option>
                <option value="active"    {{ $status === 'active'    ? 'selected' : '' }}>Active</option>
                <option value="paid"      {{ $status === 'paid'      ? 'selected' : '' }}>Paid</option>
                <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>

            <select name="recurring" class="form-select loan-filter-select">
                <option value="">All Charges</option>
                <option value="1" {{ $recurring === '1' ? 'selected' : '' }}>Recurring only</option>
                <option value="0" {{ $recurring === '0' ? 'selected' : '' }}>One-time only</option>
            </select>

            <button type="submit" class="btn-filter-apply"><i class="fas fa-filter me-1"></i> Filter</button>
            @if($search !== '' || $type !== '' || $status !== '' || $recurring !== '')
                <a href="{{ route('loans.index') }}" class="btn-filter-clear"><i class="fas fa-times me-1"></i> Clear</a>
            @endif
        </form>

        <div class="sc-body">
            <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover align-middle loan-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Type</th>
                            <th>Principal</th>
                            <th>Current Balance</th>
                            <th>Amortization</th>
                            <th>Validity Period</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">

                        @forelse($loans as $loan)
                        <tr>
                            <td class="ps-4 fw-bold text-dark text-uppercase small">
                                {{ $loan->employee->lname }}, {{ $loan->employee->fname }}
                            </td>
                            <td>
                                <span class="badge-loan-type">{{ strtoupper(str_replace('_', ' ', $loan->loan_type)) }}</span>
                                @if($loan->is_recurring)
                                    <span class="badge-recurring"><i class="fas fa-rotate me-1"></i>RECURRING</span>
                                @endif
                                @if($loan->loan_type === 'other' && $loan->other_description)
                                    <div class="small text-muted mt-1">{{ $loan->other_description }}</div>
                                @endif
                            </td>
                            @if($loan->is_recurring)
                                <td class="text-muted text-center">—</td>
                                <td class="text-muted text-center">— <span class="d-block small">monthly</span></td>
                            @else
                                <td class="fw-semibold text-dark">₱{{ number_format($loan->loan_amount, 2) }}</td>
                                <td class="fw-bold" style="color: var(--danger);">₱{{ number_format($loan->balance, 2) }}</td>
                            @endif
                            <td class="text-muted">₱{{ number_format($loan->monthly_amortization, 2) }}</td>
                            <td class="small">
                                <i class="far fa-calendar-alt text-muted me-1"></i> {{ $loan->start_date }} <br>
                                @if($loan->is_recurring)
                                    <i class="fas fa-infinity text-muted me-1"></i> Continuous
                                @else
                                    <i class="far fa-calendar-check text-muted me-1"></i> {{ $loan->end_date ?? 'N/A' }}
                                @endif
                            </td>
                            <td>
                                @if($loan->is_recurring)
                                    {{-- Inline on/off switch — pause/resume the monthly deduction --}}
                                    <div class="form-check form-switch loan-toggle-wrap m-0">
                                        <input class="form-check-input recurringToggle" type="checkbox"
                                               role="switch" data-id="{{ $loan->id }}"
                                               {{ $loan->status === 'active' ? 'checked' : '' }}>
                                        <span class="loan-toggle-label">{{ $loan->status === 'active' ? 'On' : 'Off' }}</span>
                                    </div>
                                @else
                                    @php $statusClass = $loan->status == 'active' ? 'active' : 'inactive'; @endphp
                                    <span class="badge-status {{ $statusClass }}">
                                        {{ strtoupper($loan->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="icon-action-btn editLoanBtn"
                                        data-id="{{ $loan->id }}"
                                        data-employee="{{ $loan->employee_id }}"
                                        data-type="{{ $loan->loan_type }}"
                                        data-other="{{ $loan->other_description }}"
                                        data-amount="{{ $loan->loan_amount }}"
                                        data-amort="{{ $loan->monthly_amortization }}"
                                        data-start="{{ $loan->start_date }}"
                                        data-end="{{ $loan->end_date }}"
                                        data-recurring="{{ $loan->is_recurring ? 1 : 0 }}"
                                        title="Edit">
                                        <i class="fa-solid fa-pencil" style="color: var(--teal);"></i>
                                    </button>
                                    <button class="icon-action-btn danger deleteLoanBtn" data-id="{{ $loan->id }}" title="Delete">
                                        <i class="fa-solid fa-trash text-danger"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                No loan records found{{ ($search !== '' || $type !== '' || $status !== '' || $recurring !== '') ? ' for the selected filters' : '' }}.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($loans->hasPages())
            <div class="loan-pagination">
                <span class="loan-pagination-info">
                    Showing {{ $loans->firstItem() }}–{{ $loans->lastItem() }} of {{ $loans->total() }}
                </span>
                {{ $loans->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal LOAN / ADJUSTMENT Form --}}
<div class="modal fade" id="loanModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-hand-holding-usd me-2"></i>
                    <span id="modalTitle">Adjustment Entry</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="loanForm">
                @csrf
                <input type="hidden" id="loan_id" name="loan_id">
                <div class="modal-body">

                    <div class="sub-divider"><span>Loan Details</span></div>
                    <div class="row g-3">
                        {{-- Edit mode: single employee (locked to the record being edited) --}}
                        <div class="col-md-6" id="employeeSingleWrap" style="display:none;">
                            <label class="field-label" for="employee_id">Employee <span class="req">*</span></label>
                            <select class="form-select text-uppercase" name="employee_id" id="employee_id" disabled>
                                <option value="" selected disabled>Select Employee</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->empID }}">{{ strtoupper($emp->lname) }}, {{ strtoupper($emp->fname) }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Add mode: multi-select employees — same amount details applied to all --}}
                        <div class="col-12" id="employeeMultiWrap">
                            <label class="field-label">
                                Employees <span class="req">*</span>
                                <span class="text-muted ms-1" style="font-weight:600; text-transform:none; letter-spacing:0;">— pick one or more; the details below apply to each</span>
                            </label>
                            <div class="emp-picker">
                                <div class="emp-picker-toolbar">
                                    <div class="emp-picker-search">
                                        <i class="fas fa-search"></i>
                                        <input type="text" id="empSearch" class="form-control" placeholder="Search employee…" autocomplete="off">
                                    </div>
                                    <label class="emp-picker-all">
                                        <input type="checkbox" id="empSelectAll"> Select all
                                    </label>
                                    <span class="emp-picker-count"><span id="empSelectedCount">0</span> selected</span>
                                </div>
                                <div class="emp-picker-list" id="empPickerList">
                                    @foreach($employees as $emp)
                                        <label class="emp-picker-item" data-name="{{ strtolower($emp->lname.' '.$emp->fname) }}">
                                            <input type="checkbox" class="emp-checkbox" name="employee_ids[]" value="{{ $emp->empID }}">
                                            <span>{{ strtoupper($emp->lname) }}, {{ strtoupper($emp->fname) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="field-label" for="loan_type">Adjustment Type <span class="req">*</span></label>
                            <select class="form-select" name="loan_type" id="loan_type" required>
                                <option value="" selected disabled>Select Type</option>
                                <option value="pagibig">Pag-IBIG Loan</option>
                                <option value="sss">SSS Loan</option>
                                <option value="salary">Salary Loan</option>
                                <option value="cash_adv">Cash Advance</option>
                                <option value="charges/penalty">Charges/Penalty</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-6" id="otherSpecifyWrap" style="display:none;">
                            <label class="field-label" for="other_description">Specify (Other) <span class="req">*</span></label>
                            <input type="text" class="form-control" name="other_description" id="other_description" placeholder="Describe the charge / adjustment" maxlength="255">
                        </div>

                        {{-- Continuous monthly charge toggle (not available for gov-type loans) --}}
                        <div class="col-12" id="recurringWrap">
                            <div class="recurring-card">
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" role="switch" name="is_recurring" id="is_recurring" value="1">
                                    <label class="form-check-label" for="is_recurring">
                                        <strong>Continuous monthly charge</strong>
                                    </label>
                                </div>
                                <p class="recurring-hint" id="recurringHint">
                                    Deducts the monthly amount every payroll month automatically (e.g. rent) —
                                    no principal, no end date. Switch it off later from the list to stop.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6" id="loanAmountWrap">
                            <label class="field-label" for="loan_amount">Total Amount (Principal)</label>
                            <div class="input-group">
                                <span class="input-group-text border-0" style="background: var(--teal-light); color: var(--teal-dark); font-weight: 700;">₱</span>
                                <input type="number" step="0.01" class="form-control" name="loan_amount" id="loan_amount" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="field-label" for="monthly_amortization"><span id="amortLabel">Monthly Amortization</span></label>
                            <div class="input-group">
                                <span class="input-group-text border-0" style="background: var(--teal-light); color: var(--teal-dark); font-weight: 700;">₱</span>
                                <input type="number" step="0.01" class="form-control" name="monthly_amortization" id="monthly_amortization" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="field-label" for="start_date">Effectivity Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="start_date" required>
                        </div>

                        <div class="col-md-6" id="endDateWrap">
                            <label class="field-label" for="end_date">Maturity/End Date</label>
                            <input type="date" class="form-control" name="end_date" id="end_date">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel-loan" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-submit-loan" id="saveBtn">Save Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {

    // ── Switch the modal between bulk-add and single-edit employee inputs ──
    function setEmployeeMode(mode) {
        const isEdit = mode === 'edit';
        $('#employeeSingleWrap').toggle(isEdit);
        $('#employeeMultiWrap').toggle(!isEdit);
        // Only the visible input should submit
        $('#employee_id').prop('disabled', !isEdit).prop('required', isEdit);
        $('.emp-checkbox').prop('disabled', isEdit);
    }

    function updateEmpCount() {
        $('#empSelectedCount').text($('.emp-checkbox:checked').length);
    }

    function resetEmpPicker() {
        $('.emp-checkbox').prop('checked', false);
        $('#empSelectAll').prop('checked', false);
        $('#empSearch').val('');
        $('.emp-picker-item').show();
        updateEmpCount();
    }

    // Search within the picker
    $('#empSearch').on('input', function () {
        const q = $(this).val().toLowerCase().trim();
        $('.emp-picker-item').each(function () {
            $(this).toggle($(this).data('name').indexOf(q) !== -1);
        });
    });

    // Select-all toggles only the currently visible (filtered) rows
    $('#empSelectAll').on('change', function () {
        const checked = $(this).is(':checked');
        $('.emp-picker-item:visible .emp-checkbox').prop('checked', checked);
        updateEmpCount();
    });

    $(document).on('change', '.emp-checkbox', updateEmpCount);

    $('#addLoanBtn').click(function () {
        $('#loanForm')[0].reset();
        $('#loan_id').val('');
        $('#employee_id').val('');
        $('#modalTitle').text('Add Adjustment Entry');
        setEmployeeMode('add');
        resetEmpPicker();
        $('#is_recurring').prop('checked', false);
        toggleOtherSpecify();
        applyRecurringGating();
        $('#loanModal').modal('show');
    });

    // Show the "Specify" field only when the type is "Other"
    function toggleOtherSpecify() {
        const isOther = $('#loan_type').val() === 'other';
        $('#otherSpecifyWrap').toggle(isOther);
        $('#other_description').prop('required', isOther);
        if (!isOther) { $('#other_description').val(''); }
    }

    // Government-type loans are inherently finite — recurring is disabled for them.
    const GOV_TYPES = ['sss', 'pagibig', 'philhealth'];
    function applyRecurringGating() {
        const isGov = GOV_TYPES.includes($('#loan_type').val());
        $('#is_recurring').prop('disabled', isGov);
        $('#recurringWrap').css('opacity', isGov ? 0.5 : 1);
        if (isGov && $('#is_recurring').is(':checked')) {
            $('#is_recurring').prop('checked', false);
        }
        $('#recurringHint').toggle(!isGov);
        toggleRecurring();
    }

    // When "continuous monthly charge" is on, hide principal & end date and
    // relabel the amount; the monthly amount becomes the only money field.
    function toggleRecurring() {
        const on = $('#is_recurring').is(':checked') && !$('#is_recurring').prop('disabled');
        $('#loanAmountWrap').toggle(!on);
        $('#endDateWrap').toggle(!on);
        $('#loan_amount').prop('required', !on);
        if (on) { $('#loan_amount').val(''); $('#end_date').val(''); }
        $('#amortLabel').text(on ? 'Monthly Amount' : 'Monthly Amortization');
    }

    $('#loan_type').on('change', function () { toggleOtherSpecify(); applyRecurringGating(); });
    $('#is_recurring').on('change', toggleRecurring);

    $('.editLoanBtn').click(function () {
        $('#modalTitle').text('Update Adjustment');
        setEmployeeMode('edit');
        $('#loan_id').val($(this).data('id'));
        $('#employee_id').val($(this).data('employee'));
        $('#loan_type').val($(this).data('type'));
        $('#other_description').val($(this).data('other'));
        $('#is_recurring').prop('checked', Number($(this).data('recurring')) === 1);
        toggleOtherSpecify();
        applyRecurringGating();
        $('#loan_amount').val($(this).data('amount'));
        $('#monthly_amortization').val($(this).data('amort'));
        $('#start_date').val($(this).data('start'));
        $('#end_date').val($(this).data('end'));
        $('#loanModal').modal('show');
    });

    $('#loanForm').submit(function (e) {
        e.preventDefault();
        const isEdit = !!$('#loan_id').val();
        let url = isEdit ? "{{ route('loans.update') }}" : "{{ route('loans.store') }}";

        // Bulk-add must have at least one employee selected
        if (!isEdit && $('.emp-checkbox:checked').length === 0) {
            Swal.fire('No employees selected', 'Please select at least one employee.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Saving Record...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        axios.post(url, new FormData(this))
        .then(res => {
            const count = res.data && res.data.count ? res.data.count : 0;
            const text = (!isEdit && count > 1)
                ? count + ' loan records created successfully.'
                : 'Record saved successfully.';
            Swal.fire({ icon: 'success', title: 'Saved!', text: text, timer: 1600, showConfirmButton: false })
            .then(() => location.reload());
        })
        .catch(err => {
            Swal.fire('Error', 'Unable to save record.', 'error');
        });
    });

    $('.deleteLoanBtn').click(function () {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Delete Record?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it',
            reverseButtons: true
        }).then(result => {
            if (result.isConfirmed) {
                axios.delete("/loans/delete/" + id).then(() => {
                    Swal.fire('Deleted!', 'Record removed.', 'success').then(() => location.reload());
                });
            }
        });
    });

    // ── Inline on/off switch: pause / resume a recurring charge ──
    $('.recurringToggle').on('change', function () {
        const $sw = $(this);
        const id = $sw.data('id');
        const $label = $sw.closest('.loan-toggle-wrap').find('.loan-toggle-label');

        $sw.prop('disabled', true);
        axios.post("/loans/" + id + "/toggle")
            .then(res => {
                const isOn = res.data.status === 'active';
                $sw.prop('checked', isOn);
                $label.text(isOn ? 'On' : 'Off');
            })
            .catch(() => {
                // Revert the switch on failure
                $sw.prop('checked', !$sw.prop('checked'));
                Swal.fire('Error', 'Unable to update the charge.', 'error');
            })
            .finally(() => $sw.prop('disabled', false));
    });
});
</script>
@endsection
