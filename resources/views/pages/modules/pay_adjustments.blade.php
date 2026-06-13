@extends('layout.app', ['title' => 'Pay Adjustments'])
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8; --bg:#f1f5f9;
        --surface:#fff; --border:#e2e8f0; --danger:#ef4444; --success:#10b981;
        --radius-card:14px; --radius-input:8px; --shadow-card:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);
    }
    .pa-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .pa-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:20px; display:flex;
        align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
    .pa-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .pa-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .btn-add { background:var(--teal); color:#fff; border:none; border-radius:8px; padding:10px 20px;
        font-size:.82rem; font-weight:700; letter-spacing:.3px; cursor:pointer; box-shadow:0 4px 14px rgba(0,128,128,.25);
        transition:all .2s; display:inline-flex; align-items:center; gap:8px; }
    .btn-add:hover { background:var(--teal-dark); transform:translateY(-1px); color:#fff; }
    .sc { background:var(--surface); border-radius:var(--radius-card); border:1px solid var(--border);
        box-shadow:var(--shadow-card); margin-bottom:20px; overflow:hidden; }
    .sc-head { display:flex; align-items:center; gap:10px; padding:14px 22px; border-bottom:1px solid var(--border);
        background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .sc-icon { width:30px; height:30px; border-radius:8px; background:var(--teal-light); color:var(--teal);
        display:flex; align-items:center; justify-content:center; font-size:.78rem; }
    .sc-title { font-size:.78rem; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; margin:0; }
    .pa-table thead th { font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase;
        letter-spacing:.4px; border-bottom:2px solid var(--border); background:#f8fafc; white-space:nowrap; padding:12px 16px; }
    .pa-table tbody td { font-size:.83rem; color:var(--slate); vertical-align:middle; padding:11px 16px; }
    .pa-table tbody tr:hover { background:var(--teal-light); }
    .field-label { font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase;
        letter-spacing:.4px; margin-bottom:5px; display:block; }
    .field-label .req { color:var(--danger); }
    .form-control, .form-select { border:1.5px solid var(--border); border-radius:var(--radius-input);
        font-size:.875rem; color:var(--slate); background:#fafbfc; padding:.55rem .85rem; }
    .form-control:focus, .form-select:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; }
    .badge-kind { font-size:.68rem; font-weight:700; padding:4px 9px; border-radius:999px; }
    .badge-add { background:#dcfce7; color:#166534; }
    .badge-ded { background:#fee2e2; color:#991b1b; }
    .badge-apply { font-size:.62rem; font-weight:700; padding:3px 8px; border-radius:999px; background:#e0f2f1; color:#006666; text-transform:uppercase; }
    .pa-help { font-size:.72rem; color:var(--slate-light); background:var(--teal-light); border:1px solid #bfe3df;
        border-radius:8px; padding:8px 12px; margin-top:4px; }
    #payAdjModal .modal-header { background:var(--teal); color:#fff; border:0; }
    #payAdjModal .modal-content { border-radius:var(--radius-card); border:0; overflow:hidden; }
    .icon-action-btn { width:32px; height:32px; border-radius:7px; border:1.5px solid var(--border);
        background:#fff; color:var(--slate-light); display:inline-flex; align-items:center; justify-content:center; cursor:pointer; }
    .icon-action-btn:hover { background:var(--teal-light); color:var(--teal); border-color:var(--teal-mid); }
    .icon-action-btn.del:hover { background:#fee2e2; color:var(--danger); border-color:#fca5a5; }
</style>

<div class="pa-shell">
    <div class="pa-topbar">
        <div>
            <p class="page-title">Pay Adjustments</p>
            <p class="page-sub">One-time additions or deductions applied to a specific payroll run</p>
        </div>
        <button class="btn-add" id="addAdjBtn"><i class="fa fa-plus"></i> Add Adjustment</button>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-sliders"></i></div>
            <h5 class="sc-title">Adjustment Entries</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle pa-table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Employee</th>
                        <th>Pay Date</th>
                        <th>Label</th>
                        <th>Type</th>
                        <th>Applied To</th>
                        <th class="text-end">Amount</th>
                        <th>Remarks</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $a)
                    <tr>
                        <td class="ps-4 fw-bold text-uppercase small">{{ $a->employee->lname ?? '' }}, {{ $a->employee->fname ?? '' }}</td>
                        <td class="small">{{ \Carbon\Carbon::parse($a->pay_date)->format('M d, Y') }}</td>
                        <td class="small">{{ $a->label }}</td>
                        <td>
                            <span class="badge-kind {{ $a->kind === 'addition' ? 'badge-add' : 'badge-ded' }}">
                                {{ $a->kind === 'addition' ? '+ Addition' : '− Deduction' }}
                            </span>
                        </td>
                        <td><span class="badge-apply">{{ $a->apply_to === 'gross' ? 'Gross (taxed)' : 'Take-home' }}</span></td>
                        <td class="text-end fw-bold {{ $a->kind === 'addition' ? 'text-success' : 'text-danger' }}">
                            {{ $a->kind === 'addition' ? '+' : '−' }}₱{{ number_format($a->amount, 2) }}
                        </td>
                        <td class="small text-muted">{{ $a->remarks ?: '—' }}</td>
                        <td class="pe-4 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <button class="icon-action-btn editAdjBtn"
                                    data-id="{{ $a->id }}" data-employee="{{ $a->employee_id }}"
                                    data-paydate="{{ \Carbon\Carbon::parse($a->pay_date)->format('Y-m-d') }}"
                                    data-label="{{ $a->label }}" data-kind="{{ $a->kind }}"
                                    data-apply="{{ $a->apply_to }}" data-amount="{{ $a->amount }}"
                                    data-remarks="{{ $a->remarks }}" title="Edit"><i class="fa fa-pen"></i></button>
                                <button class="icon-action-btn del deleteAdjBtn" data-id="{{ $a->id }}" title="Delete"><i class="fa fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No adjustments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="payAdjModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="adjModalTitle">Add Adjustment</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="adjForm">
                <div class="modal-body">
                    <input type="hidden" id="adjustment_id" name="adjustment_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="field-label" for="employee_id">Employee <span class="req">*</span></label>
                            <select class="form-select" name="employee_id" id="employee_id" required>
                                <option value="" selected disabled>Select Employee</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->empID }}">{{ strtoupper($emp->lname) }}, {{ strtoupper($emp->fname) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label" for="pay_date">Pay Date <span class="req">*</span></label>
                            <input type="date" class="form-control" name="pay_date" id="pay_date" required>
                        </div>
                        <div class="col-md-12">
                            <label class="field-label" for="label">Label <span class="req">*</span></label>
                            <input type="text" class="form-control" name="label" id="label" maxlength="255"
                                placeholder="e.g. Unpaid OT (May 26 cutoff), Overpayment recovery" required>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label" for="kind">Type <span class="req">*</span></label>
                            <select class="form-select" name="kind" id="kind" required>
                                <option value="addition">+ Addition</option>
                                <option value="deduction">− Deduction</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label" for="apply_to">Apply To <span class="req">*</span></label>
                            <select class="form-select" name="apply_to" id="apply_to" required>
                                <option value="gross">Gross (taxed)</option>
                                <option value="net" selected>Take-home (after tax)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label" for="amount">Amount <span class="req">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:var(--teal-light);color:var(--teal-dark);font-weight:700;border:1.5px solid var(--border);">₱</span>
                                <input type="number" step="0.01" min="0.01" class="form-control" name="amount" id="amount" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="pa-help" id="adjHelp"></div>
                        </div>
                        <div class="col-md-12">
                            <label class="field-label" for="remarks">Remarks / Authorization</label>
                            <input type="text" class="form-control" name="remarks" id="remarks" maxlength="255"
                                placeholder="Reason or approval reference (optional)">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-add" id="adjSaveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    function updateHelp() {
        const kind = $('#kind').val(), apply = $('#apply_to').val();
        let msg = '';
        if (kind === 'addition' && apply === 'gross') msg = 'Added to gross pay and taxed — use for earned wages like unpaid OT or back pay.';
        else if (kind === 'addition' && apply === 'net') msg = 'Added straight to take-home, not taxed — use for reimbursements or correcting an underpayment.';
        else if (kind === 'deduction' && apply === 'gross') msg = 'Removed from gross before tax (lowers taxable income) — use for voluntary pre-tax items (e.g. extra HMO).';
        else msg = 'Removed from take-home after tax — use for recovering an overpayment or company charges.';
        $('#adjHelp').html('<i class="fa fa-circle-info me-1"></i>' + msg);
    }
    $('#kind, #apply_to').on('change', updateHelp);

    $('#addAdjBtn').click(function () {
        $('#adjForm')[0].reset();
        $('#adjustment_id').val('');
        $('#adjModalTitle').text('Add Adjustment');
        updateHelp();
        new bootstrap.Modal(document.getElementById('payAdjModal')).show();
    });

    $('.editAdjBtn').click(function () {
        const d = $(this).data();
        $('#adjModalTitle').text('Edit Adjustment');
        $('#adjustment_id').val(d.id);
        $('#employee_id').val(d.employee);
        $('#pay_date').val(d.paydate);
        $('#label').val(d.label);
        $('#kind').val(d.kind);
        $('#apply_to').val(d.apply);
        $('#amount').val(d.amount);
        $('#remarks').val(d.remarks);
        updateHelp();
        new bootstrap.Modal(document.getElementById('payAdjModal')).show();
    });

    $('#adjForm').submit(function (e) {
        e.preventDefault();
        const url = $('#adjustment_id').val() ? "{{ route('payadjustments.update') }}" : "{{ route('payadjustments.store') }}";
        Swal.fire({ title: 'Saving...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        axios.post(url, new FormData(this))
            .then(() => Swal.fire({ icon:'success', title:'Saved!', timer:1200, showConfirmButton:false }).then(() => location.reload()))
            .catch(err => {
                const m = err.response?.data?.message || 'Unable to save.';
                Swal.fire('Error', m, 'error');
            });
    });

    $('.deleteAdjBtn').click(function () {
        const id = $(this).data('id');
        Swal.fire({ title:'Delete entry?', icon:'warning', showCancelButton:true, confirmButtonColor:'#ef4444' })
            .then(res => {
                if (res.isConfirmed) {
                    axios.delete(`/payadjustments/delete/${id}`)
                        .then(() => Swal.fire({ icon:'success', title:'Deleted', timer:1000, showConfirmButton:false }).then(() => location.reload()))
                        .catch(() => Swal.fire('Error', 'Unable to delete.', 'error'));
                }
            });
    });
});
</script>
@endsection
