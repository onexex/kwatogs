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

    /* ── Filter bar ── */
    .pa-filterbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:14px 22px;
        border-bottom:1px solid var(--border); background:#fbfdfd; }
    .pa-filter-search { position:relative; flex:1 1 220px; min-width:180px; }
    .pa-filter-search i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:.8rem; }
    .pa-filter-search .form-control { padding-left:34px; }
    .pa-filter-select { flex:0 0 auto; width:auto; min-width:150px; }
    .pa-filter-date { flex:0 0 auto; width:auto; min-width:160px; }
    .btn-filter-apply { background:var(--teal); color:#fff; border:none; border-radius:var(--radius-input);
        padding:.55rem 18px; font-size:.78rem; font-weight:700; letter-spacing:.3px; cursor:pointer; transition:background .2s; }
    .btn-filter-apply:hover { background:var(--teal-dark); }
    .btn-filter-clear { background:var(--surface); color:var(--slate-light); border:1.5px solid var(--border);
        border-radius:var(--radius-input); padding:.55rem 16px; font-size:.78rem; font-weight:700; letter-spacing:.3px;
        text-decoration:none; transition:all .2s; }
    .btn-filter-clear:hover { background:var(--bg); color:var(--slate); }

    /* ── Pagination ── */
    .pa-pagination { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
        padding:14px 22px; border-top:1px solid var(--border); }
    .pa-pagination-info { font-size:.78rem; color:var(--slate-light); }
    .pa-pagination .pagination { margin:0; }
    .pa-pagination .page-link { color:var(--teal); border-color:var(--border); font-size:.82rem; }
    .pa-pagination .page-item.active .page-link { background:var(--teal); border-color:var(--teal); color:#fff; }
    .pa-pagination .page-link:focus { box-shadow:0 0 0 3px rgba(0,128,128,.1); }

    /* ── Employee multi-picker ── */
    .emp-picker { border:1.5px solid var(--border); border-radius:var(--radius-input); background:#fafbfc; overflow:hidden; }
    .emp-picker-toolbar { display:flex; align-items:center; gap:12px; flex-wrap:wrap; padding:10px 12px;
        border-bottom:1px solid var(--border); background:var(--surface); }
    .emp-picker-search { position:relative; flex:1 1 200px; }
    .emp-picker-search i { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:.78rem; }
    .emp-picker-search .form-control { padding-left:32px; }
    .emp-picker-all { font-size:.76rem; font-weight:700; color:var(--slate-light); display:inline-flex; align-items:center;
        gap:6px; cursor:pointer; margin:0; white-space:nowrap; }
    .emp-picker-count { font-size:.74rem; color:var(--teal-dark); font-weight:700; white-space:nowrap; }
    .emp-picker-count #empSelectedCount { font-size:.8rem; }
    .emp-picker-list { max-height:200px; overflow-y:auto; padding:4px; }
    .emp-picker-item { display:flex; align-items:center; gap:9px; padding:8px 10px; border-radius:6px; cursor:pointer;
        margin:0; font-size:.82rem; color:var(--slate); }
    .emp-picker-item:hover { background:var(--teal-light); }
    .emp-picker-item input { width:15px; height:15px; accent-color:var(--teal); }
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

        {{-- ── Filter bar ── --}}
        <form method="GET" action="{{ route('payadjustments.index') }}" class="pa-filterbar">
            <div class="pa-filter-search">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ $search }}" class="form-control"
                       placeholder="Search employee name…" autocomplete="off">
            </div>
            <select name="kind" class="form-select pa-filter-select">
                <option value="">All Types</option>
                <option value="addition"  {{ $kind === 'addition'  ? 'selected' : '' }}>+ Addition</option>
                <option value="deduction" {{ $kind === 'deduction' ? 'selected' : '' }}>− Deduction</option>
            </select>
            <select name="apply_to" class="form-select pa-filter-select">
                <option value="">All Apply To</option>
                <option value="gross" {{ $applyTo === 'gross' ? 'selected' : '' }}>Gross (taxed)</option>
                <option value="net"   {{ $applyTo === 'net'   ? 'selected' : '' }}>Take-home</option>
            </select>
            <input type="date" name="pay_date" value="{{ $payDate }}" class="form-control pa-filter-date" title="Pay date">
            <button type="submit" class="btn-filter-apply"><i class="fas fa-filter me-1"></i> Filter</button>
            @if($search !== '' || $kind !== '' || $applyTo !== '' || $payDate !== '')
                <a href="{{ route('payadjustments.index') }}" class="btn-filter-clear"><i class="fas fa-times me-1"></i> Clear</a>
            @endif
        </form>

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
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        No adjustments found{{ ($search !== '' || $kind !== '' || $applyTo !== '' || $payDate !== '') ? ' for the selected filters' : ' yet' }}.
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($adjustments->hasPages())
        <div class="pa-pagination">
            <span class="pa-pagination-info">
                Showing {{ $adjustments->firstItem() }}–{{ $adjustments->lastItem() }} of {{ $adjustments->total() }}
            </span>
            {{ $adjustments->links() }}
        </div>
        @endif
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
                        {{-- Edit mode: single employee (locked to the record being edited) --}}
                        <div class="col-md-6" id="employeeSingleWrap" style="display:none;">
                            <label class="field-label" for="employee_id">Employee <span class="req">*</span></label>
                            <select class="form-select" name="employee_id" id="employee_id" disabled>
                                <option value="" selected disabled>Select Employee</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->empID }}">{{ strtoupper($emp->lname) }}, {{ strtoupper($emp->fname) }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Add mode: multi-select employees — same adjustment applied to each --}}
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

    // ── Switch the modal between bulk-add and single-edit employee inputs ──
    function setEmployeeMode(mode) {
        const isEdit = mode === 'edit';
        $('#employeeSingleWrap').toggle(isEdit);
        $('#employeeMultiWrap').toggle(!isEdit);
        // Only the visible input should submit
        $('#employee_id').prop('disabled', !isEdit);
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

    $('#addAdjBtn').click(function () {
        $('#adjForm')[0].reset();
        $('#adjustment_id').val('');
        $('#adjModalTitle').text('Add Adjustment');
        setEmployeeMode('add');
        resetEmpPicker();
        updateHelp();
        new bootstrap.Modal(document.getElementById('payAdjModal')).show();
    });

    $('.editAdjBtn').click(function () {
        const d = $(this).data();
        $('#adjModalTitle').text('Edit Adjustment');
        setEmployeeMode('edit');
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
        const isEdit = !!$('#adjustment_id').val();
        const url = isEdit ? "{{ route('payadjustments.update') }}" : "{{ route('payadjustments.store') }}";

        // Bulk-add must have at least one employee selected
        if (!isEdit && $('.emp-checkbox:checked').length === 0) {
            Swal.fire('No employees selected', 'Please select at least one employee.', 'warning');
            return;
        }

        Swal.fire({ title: 'Saving...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        axios.post(url, new FormData(this))
            .then(res => {
                const count = res.data && res.data.count ? res.data.count : 0;
                const text = (!isEdit && count > 1) ? count + ' adjustment entries created.' : 'Entry saved successfully.';
                Swal.fire({ icon:'success', title:'Saved!', text:text, timer:1400, showConfirmButton:false }).then(() => location.reload());
            })
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
