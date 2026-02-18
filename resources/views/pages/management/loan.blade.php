@extends('layout.app')

@section('content')
<style>
    /* Consistent Sticky Header */
    .table-sticky-header thead th {
        position: sticky !important;
        top: 0;
        background-color: #ffffff;
        z-index: 10;
        border-bottom: 2px solid #f8f9fa;
    }

    .table-hover tbody tr:hover {
        background-color: #fcfcfc;
        transition: background-color 0.2s ease;
    }
</style>

<div class="container-fluid px-4 py-3">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">Payroll Adjustments</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted">Payroll</li>
                    <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Charges & Loans</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" id="addLoanBtn">
            <i class="fas fa-plus me-2"></i> Add Record
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover align-middle table-sticky-header mb-0">
                    <thead class="bg-light">
                        <tr class="text-secondary small fw-bold text-uppercase tracking-wider">
                            <th class="ps-4 py-3">Employee</th>
                            <th class="py-3">Type</th>
                            <th class="py-3">Principal</th>
                            <th class="py-3">Current Balance</th>
                            <th class="py-3">Amortization</th>
                            <th class="py-3">Validity Period</th>
                            <th class="py-3">Status</th>
                            <th class="pe-4 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        
                        @foreach($loans as $loan)
                        <tr>
                            <td class="ps-4 fw-bold text-dark text-uppercase small">
                                {{ $loan->employee->lname }}, {{ $loan->employee->fname }}
                            </td>
                            <td>
                                <span class="badge bg-light text-primary border rounded-pill px-3">{{ strtoupper(str_replace('_', ' ', $loan->loan_type)) }}</span>
                            </td>
                            <td class="fw-semibold text-dark">₱{{ number_format($loan->loan_amount, 2) }}</td>
                            <td class="text-danger fw-bold">₱{{ number_format($loan->balance, 2) }}</td>
                            <td class="text-muted">₱{{ number_format($loan->monthly_amortization, 2) }}</td>
                            <td class="small">
                                <i class="far fa-calendar-alt text-muted me-1"></i> {{ $loan->start_date }} <br>
                                <i class="far fa-calendar-check text-muted me-1"></i> {{ $loan->end_date ?? 'N/A' }}
                            </td>
                            <td class="text-center">
                                @php $statusColor = $loan->status == 'active' ? 'success' : 'secondary'; @endphp
                                <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-white border border-{{ $statusColor }} rounded-pill px-2">
                                    {{ strtoupper($loan->status) }}
                                </span>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-light btn-sm rounded-circle shadow-sm p-2 editLoanBtn"
                                        data-id="{{ $loan->id }}"
                                        data-employee="{{ $loan->employee_id }}"
                                        data-type="{{ $loan->loan_type }}"
                                        data-amount="{{ $loan->loan_amount }}"
                                        data-amort="{{ $loan->monthly_amortization }}"
                                        data-start="{{ $loan->start_date }}"
                                        data-end="{{ $loan->end_date }}"
                                        title="Edit">
                                        <i class="fa-solid fa-pencil text-primary"></i>
                                    </button>
                                    <button class="btn btn-light btn-sm rounded-circle shadow-sm p-2 deleteLoanBtn" data-id="{{ $loan->id }}" title="Delete">
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

<div class="modal fade" id="loanModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-secondary text-uppercase tracking-wide">
                    <i class="fas fa-hand-holding-usd me-2 text-primary"></i> 
                    <span id="modalTitle">Adjustment Entry</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="loanForm">
                @csrf
                <input type="hidden" id="loan_id" name="loan_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Employee <span class="text-danger">*</span></label>
                            <select class="form-select form-control-lg bg-light border-0 fs-6 text-uppercase" name="employee_id" id="employee_id" required>
                                <option value="" selected disabled>Select Employee</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->empID }}">{{ $emp->lname }}, {{ $emp->fname }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Adjustment Type <span class="text-danger">*</span></label>
                            <select class="form-select form-control-lg bg-light border-0 fs-6" name="loan_type" id="loan_type" required>
                                <option value="" selected disabled>Select Type</option>
                                <option value="pagibig">Pag-IBIG Loan</option>
                                <option value="sss">SSS Loan</option>
                                <option value="salary">Salary Loan</option>
                                <option value="cash_adv">Cash Advance</option>
                                <option value="charges/penalty">Charges/Penalty</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Total Amount (Principal)</label>
                            <div class="input-group">
                                <span class="input-group-text border-0 bg-light">₱</span>
                                <input type="number" step="0.01" class="form-control form-control-lg bg-light border-0 fs-6" name="loan_amount" id="loan_amount" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Monthly Amortization</label>
                            <div class="input-group">
                                <span class="input-group-text border-0 bg-light">₱</span>
                                <input type="number" step="0.01" class="form-control form-control-lg bg-light border-0 fs-6" name="monthly_amortization" id="monthly_amortization" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Effectivity Start Date</label>
                            <input type="date" class="form-control form-control-lg bg-light border-0 fs-6" name="start_date" id="start_date" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Maturity/End Date</label>
                            <input type="date" class="form-control form-control-lg bg-light border-0 fs-6" name="end_date" id="end_date">
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm" id="saveBtn">Save Entry</button>
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
        $('#loanModal').modal('show');
    });

    $('.editLoanBtn').click(function () {
        $('#modalTitle').text('Update Adjustment');
        $('#loan_id').val($(this).data('id'));
        $('#employee_id').val($(this).data('employee'));
        $('#loan_type').val($(this).data('type'));
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