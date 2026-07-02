@extends('layout.app', ['title' => 'Apply Employee Overtime'])
@section('content')

<style>
    :root {
        --teal:        #008080;
        --teal-dark:   #006666;
        --teal-mid:    #4db6ac;
        --teal-light:  #e0f2f1;
        --slate:       #334155;
        --slate-light: #64748b;
        --muted:       #94a3b8;
        --bg:          #f1f5f9;
        --border:      #e2e8f0;
        --radius-card: 14px;
        --shadow-card: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }

    .aot-shell { background: var(--bg); min-height: 100vh; padding: 24px 28px 60px; margin: -1.5rem -1.5rem 0; }

    .aot-topbar {
        background: #fff; border: 1px solid var(--border); border-radius: var(--radius-card);
        box-shadow: var(--shadow-card); padding: 16px 22px; margin-bottom: 20px;
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
    }
    .aot-topbar .page-title { font-size: 1.1rem; font-weight: 700; color: var(--slate); margin: 0; }
    .aot-topbar .page-sub   { font-size: .78rem; color: var(--muted); margin: 2px 0 0; }

    .sc { background: #fff; border: 1px solid var(--border); border-radius: var(--radius-card); box-shadow: var(--shadow-card); margin-bottom: 20px; overflow: hidden; }
    .sc-head { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid var(--border); }
    .sc-head-left { display: flex; align-items: center; gap: 10px; }
    .sc-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--teal-light); color: var(--teal); display: flex; align-items: center; justify-content: center; font-size: .85rem; }
    .sc-title { font-size: .88rem; font-weight: 700; color: var(--slate); margin: 0; }
    .sc-body { padding: 22px; }

    .field-label { font-size: .75rem; font-weight: 700; color: var(--slate-light); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 5px; display: block; }
    .form-control, .form-select { border-radius: 8px; border: 1.5px solid var(--border); font-size: .85rem; color: var(--slate); }
    .form-control:focus, .form-select:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(0,128,128,.12); }

    .sub-divider { display: flex; align-items: center; gap: 10px; margin: 18px 0 14px; }
    .sub-divider span { font-size: .72rem; font-weight: 700; color: var(--teal); text-transform: uppercase; letter-spacing: .4px; white-space: nowrap; }
    .sub-divider::after { content: ''; flex-grow: 1; height: 1px; background: var(--border); }

    .btn-submit {
        background: var(--teal); color: #fff; border: none; border-radius: 10px;
        padding: 10px 28px; font-size: .85rem; font-weight: 700; letter-spacing: .3px;
        box-shadow: 0 4px 14px rgba(0,128,128,.25); transition: all .2s; cursor: pointer;
    }
    .btn-submit:hover { background: var(--teal-dark); transform: translateY(-1px); color: #fff; }

    .ot-table thead th { font-size: .7rem; font-weight: 700; color: var(--slate-light); text-transform: uppercase; letter-spacing: .4px; border-bottom: 2px solid var(--border); white-space: nowrap; }
    .ot-table tbody td { font-size: .83rem; color: var(--slate); vertical-align: middle; }
    .ot-table tbody tr:hover { background: var(--teal-light); }

    .badge-forapproval  { background: #fef9c3; color: #854d0e; }
    .badge-approved     { background: #dcfce7; color: #166534; }
    .badge-approvedbycfo{ background: #dbeafe; color: #1e40af; }
    .badge-disapproved  { background: #fee2e2; color: #991b1b; }
    .badge-canceled     { background: #f1f5f9; color: #64748b; }
</style>

<div class="aot-shell">

    {{-- Top bar --}}
    <div class="aot-topbar">
        <div>
            <p class="page-title">Apply Employee Overtime</p>
            <p class="page-sub">Modules &middot; File overtime on behalf of an employee</p>
        </div>
    </div>

    {{-- Form --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-user-clock"></i></div>
                <h5 class="sc-title">Overtime Filing Form</h5>
            </div>
        </div>
        <div class="sc-body">
            <form id="frmAdminOT">
                @csrf

                <div class="sub-divider"><span>Employee</span></div>
                <div class="row g-3 mb-3">
                    <div class="col-lg-6">
                        <label class="field-label">Employee <span class="text-danger">*</span></label>
                        <select class="form-select" id="selEmployee" name="employee_id" required>
                            <option value="">— Select Employee —</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->empID }}">
                                    {{ strtoupper($emp->lname) }}, {{ ucwords(strtolower($emp->fname)) }}
                                    &mdash; {{ $emp->empDetail->position->pos_desc ?? 'No Position' }}
                                </option>
                            @endforeach
                        </select>
                        <span class="text-danger small error-text" id="err_employee"></span>
                    </div>
                </div>

                <div class="sub-divider"><span>Overtime Details</span></div>
                <div class="row g-3 mb-3">
                    <div class="col-lg-3">
                        <label class="field-label">Date From <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="txtDateFrom" name="dateFrom" required>
                        <span class="text-danger small error-text" id="err_dateFrom"></span>
                    </div>
                    <div class="col-lg-3">
                        <label class="field-label">Date To <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="txtDateTo" name="dateTo" required>
                        <span class="text-danger small error-text" id="err_dateTo"></span>
                    </div>
                    <div class="col-lg-3">
                        <label class="field-label">Time From <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="txtTimeFrom" name="timeFrom" required>
                        <span class="text-danger small error-text" id="err_timeFrom"></span>
                    </div>
                    <div class="col-lg-3">
                        <label class="field-label">Time To <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="txtTimeTo" name="timeTo" required>
                        <span class="text-danger small error-text" id="err_timeTo"></span>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-lg-12">
                        <label class="field-label">Purpose <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="txtPurpose" name="purpose" rows="2" placeholder="Reason for overtime..." required></textarea>
                        <span class="text-danger small error-text" id="err_purpose"></span>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn-submit" id="btnSubmitOT">
                        <i class="fa-solid fa-paper-plane me-2"></i>Submit Overtime
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Records Table --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa fa-history"></i></div>
                <h5 class="sc-title">Overtime Records</h5>
            </div>
        </div>
        <div class="sc-body" style="padding:0;">
            <div class="table-responsive">
                <table class="table table-hover ot-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Filing Date</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Hours</th>
                            <th>Day Type</th>
                            <th>Purpose</th>
                            <th class="pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody id="tblAdminOT">
                        @forelse($overtimes as $ot)
                        @php
                            $from = \Carbon\Carbon::parse($ot->date_from . ' ' . $ot->time_in);
                            $to   = \Carbon\Carbon::parse($ot->date_to   . ' ' . $ot->time_out);
                            $statusClass = match($ot->status) {
                                'APPROVED'      => 'badge-approved',
                                'APPROVEDBYCFO' => 'badge-approvedbycfo',
                                'DISAPPROVED'   => 'badge-disapproved',
                                'CANCELED'      => 'badge-canceled',
                                default         => 'badge-forapproval',
                            };
                        @endphp
                        <tr>
                            <td class="ps-4 fw-semibold">
                                {{ ucwords(strtolower(optional($ot->employee->user)->lname . ', ' . optional($ot->employee->user)->fname)) }}
                            </td>
                            <td>{{ \Carbon\Carbon::parse($ot->created_at)->format('M d, Y') }}</td>
                            <td>{{ $from->format('M d, Y h:i A') }}</td>
                            <td>{{ $to->format('M d, Y h:i A') }}</td>
                            <td>{{ $ot->total_hrs }} hr(s)</td>
                            <td><span class="badge bg-light text-secondary">{{ str_replace('_', ' ', $ot->day_type) }}</span></td>
                            <td>{{ $ot->purpose }}</td>
                            <td class="pe-4">
                                <span class="badge {{ $statusClass }} rounded-pill px-3 py-2 fw-semibold">
                                    {{ $ot->status === 'FORAPPROVAL' ? 'FOR APPROVAL' : $ot->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No overtime records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($overtimes->hasPages())
            <div class="d-flex justify-content-end px-4 py-3">
                {{ $overtimes->links() }}
            </div>
            @endif
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const form   = document.getElementById('frmAdminOT');
    const btn    = document.getElementById('btnSubmitOT');
    const errors = ['employee', 'dateFrom', 'dateTo', 'timeFrom', 'timeTo', 'purpose'];

    function clearErrors() {
        errors.forEach(k => document.getElementById('err_' + k).textContent = '');
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

        const formData = new FormData(form);

        axios.post('{{ route("admin.overtime.store") }}', formData)
            .then(function (response) {
                if (response.data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Filed!',
                        text: response.data.message,
                        timer: 2500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.data.message });
                }
            })
            .catch(function (err) {
                if (err.response && err.response.status === 422) {
                    const errs = err.response.data.errors;
                    if (errs.employee_id) document.getElementById('err_employee').textContent = errs.employee_id[0];
                    if (errs.dateFrom)    document.getElementById('err_dateFrom').textContent  = errs.dateFrom[0];
                    if (errs.dateTo)      document.getElementById('err_dateTo').textContent    = errs.dateTo[0];
                    if (errs.timeFrom)    document.getElementById('err_timeFrom').textContent  = errs.timeFrom[0];
                    if (errs.timeTo)      document.getElementById('err_timeTo').textContent    = errs.timeTo[0];
                    if (errs.purpose)     document.getElementById('err_purpose').textContent   = errs.purpose[0];
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
                }
            })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i>Submit Overtime';
            });
    });

    // Mirror date_from → date_to automatically
    document.getElementById('txtDateFrom').addEventListener('change', function () {
        document.getElementById('txtDateTo').value = this.value;
    });
});
</script>
@endsection
