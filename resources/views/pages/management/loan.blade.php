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
            </div>
        </div>
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

                        @foreach($loans as $loan)
                        <tr>
                            <td class="ps-4 fw-bold text-dark text-uppercase small">
                                {{ $loan->employee->lname }}, {{ $loan->employee->fname }}
                            </td>
                            <td>
                                <span class="badge-loan-type">{{ strtoupper(str_replace('_', ' ', $loan->loan_type)) }}</span>
                                @if($loan->loan_type === 'other' && $loan->other_description)
                                    <div class="small text-muted mt-1">{{ $loan->other_description }}</div>
                                @endif
                            </td>
                            <td class="fw-semibold text-dark">₱{{ number_format($loan->loan_amount, 2) }}</td>
                            <td class="fw-bold" style="color: var(--danger);">₱{{ number_format($loan->balance, 2) }}</td>
                            <td class="text-muted">₱{{ number_format($loan->monthly_amortization, 2) }}</td>
                            <td class="small">
                                <i class="far fa-calendar-alt text-muted me-1"></i> {{ $loan->start_date }} <br>
                                <i class="far fa-calendar-check text-muted me-1"></i> {{ $loan->end_date ?? 'N/A' }}
                            </td>
                            <td>
                                @php $statusClass = $loan->status == 'active' ? 'active' : 'inactive'; @endphp
                                <span class="badge-status {{ $statusClass }}">
                                    {{ strtoupper($loan->status) }}
                                </span>
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
                                        title="Edit">
                                        <i class="fa-solid fa-pencil" style="color: var(--teal);"></i>
                                    </button>
                                    <button class="icon-action-btn danger deleteLoanBtn" data-id="{{ $loan->id }}" title="Delete">
                                        <i class="fa-solid fa-trash text-danger"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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
                        <div class="col-md-6">
                            <label class="field-label" for="employee_id">Employee <span class="req">*</span></label>
                            <select class="form-select text-uppercase" name="employee_id" id="employee_id" required>
                                <option value="" selected disabled>Select Employee</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->empID }}">{{ strtoupper($emp->lname) }}, {{ strtoupper($emp->fname) }}</option>
                                @endforeach
                            </select>
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

                        <div class="col-md-6">
                            <label class="field-label" for="loan_amount">Total Amount (Principal)</label>
                            <div class="input-group">
                                <span class="input-group-text border-0" style="background: var(--teal-light); color: var(--teal-dark); font-weight: 700;">₱</span>
                                <input type="number" step="0.01" class="form-control" name="loan_amount" id="loan_amount" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="field-label" for="monthly_amortization">Monthly Amortization</label>
                            <div class="input-group">
                                <span class="input-group-text border-0" style="background: var(--teal-light); color: var(--teal-dark); font-weight: 700;">₱</span>
                                <input type="number" step="0.01" class="form-control" name="monthly_amortization" id="monthly_amortization" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="field-label" for="start_date">Effectivity Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="start_date" required>
                        </div>

                        <div class="col-md-6">
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
    $('#addLoanBtn').click(function () {
        $('#loanForm')[0].reset();
        $('#loan_id').val('');
        $('#modalTitle').text('Add Adjustment Entry');
        toggleOtherSpecify();
        $('#loanModal').modal('show');
    });

    // Show the "Specify" field only when the type is "Other"
    function toggleOtherSpecify() {
        const isOther = $('#loan_type').val() === 'other';
        $('#otherSpecifyWrap').toggle(isOther);
        $('#other_description').prop('required', isOther);
        if (!isOther) { $('#other_description').val(''); }
    }
    $('#loan_type').on('change', toggleOtherSpecify);

    $('.editLoanBtn').click(function () {
        $('#modalTitle').text('Update Adjustment');
        $('#loan_id').val($(this).data('id'));
        $('#employee_id').val($(this).data('employee'));
        $('#loan_type').val($(this).data('type'));
        $('#other_description').val($(this).data('other'));
        toggleOtherSpecify();
        $('#loan_amount').val($(this).data('amount'));
        $('#monthly_amortization').val($(this).data('amort'));
        $('#start_date').val($(this).data('start'));
        $('#end_date').val($(this).data('end'));
        $('#loanModal').modal('show');
    });

    $('#loanForm').submit(function (e) {
        e.preventDefault();
        let url = $('#loan_id').val() ? "{{ route('loans.update') }}" : "{{ route('loans.store') }}";

        Swal.fire({
            title: 'Saving Record...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        axios.post(url, new FormData(this))
        .then(res => {
            Swal.fire({ icon: 'success', title: 'Saved!', text: 'Record updated successfully.', timer: 1500, showConfirmButton: false })
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
});
</script>
@endsection
