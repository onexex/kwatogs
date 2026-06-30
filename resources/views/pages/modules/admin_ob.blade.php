@extends('layout.app', ['title' => 'Apply Employee OB'])
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8; --bg:#f1f5f9;
        --border:#e2e8f0; --radius-card:14px; --shadow-card:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);
    }
    .aob-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .aob-topbar { background:#fff; border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:20px;
        display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
    .aob-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .aob-topbar .page-sub   { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .sc { background:#fff; border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); margin-bottom:20px; overflow:hidden; }
    .sc-head { display:flex; align-items:center; gap:10px; padding:14px 20px; border-bottom:1px solid var(--border); }
    .sc-icon { width:32px; height:32px; border-radius:8px; background:var(--teal-light); color:var(--teal);
        display:flex; align-items:center; justify-content:center; font-size:.85rem; }
    .sc-title { font-size:.88rem; font-weight:700; color:var(--slate); margin:0; }
    .sc-body { padding:22px; }
    .field-label { font-size:.75rem; font-weight:700; color:var(--slate-light); text-transform:uppercase;
        letter-spacing:.4px; margin-bottom:5px; display:block; }
    .form-control, .form-select { border-radius:8px; border:1.5px solid var(--border); font-size:.85rem; color:var(--slate); }
    .form-control:focus, .form-select:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.12); }
    .sub-divider { display:flex; align-items:center; gap:10px; margin:18px 0 14px; }
    .sub-divider span { font-size:.72rem; font-weight:700; color:var(--teal); text-transform:uppercase;
        letter-spacing:.4px; white-space:nowrap; }
    .sub-divider::after { content:''; flex-grow:1; height:1px; background:var(--border); }
    .btn-submit { background:var(--teal); color:#fff; border:none; border-radius:10px;
        padding:10px 28px; font-size:.85rem; font-weight:700; letter-spacing:.3px;
        box-shadow:0 4px 14px rgba(0,128,128,.25); transition:all .2s; cursor:pointer; }
    .btn-submit:hover { background:var(--teal-dark); transform:translateY(-1px); color:#fff; }
    .ob-table thead th { font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase;
        letter-spacing:.4px; border-bottom:2px solid var(--border); white-space:nowrap; }
    .ob-table tbody td { font-size:.83rem; color:var(--slate); vertical-align:middle; }
    .ob-table tbody tr:hover { background:var(--teal-light); }
    .badge-pending    { background:#fef9c3; color:#854d0e; }
    .badge-approved   { background:#dcfce7; color:#166534; }
    .badge-rejected   { background:#fee2e2; color:#991b1b; }
</style>

<div class="aob-shell">

    <div class="aob-topbar">
        <div>
            <p class="page-title">Apply Employee OB</p>
            <p class="page-sub">Modules &middot; File official business on behalf of an employee</p>
        </div>
    </div>

    {{-- Form --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa-solid fa-briefcase"></i></div>
            <h5 class="sc-title">Official Business Filing Form</h5>
        </div>
        <div class="sc-body">
            <form id="frmAdminOB">
                @csrf

                <div class="sub-divider"><span>Employee</span></div>
                <div class="row g-3 mb-3">
                    <div class="col-lg-6">
                        <label class="field-label">Employee <span class="text-danger">*</span></label>
                        <select class="form-select" name="employee_id" required>
                            <option value="">— Select Employee —</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->empID }}">
                                    {{ strtoupper($emp->lname) }}, {{ ucwords(strtolower($emp->fname)) }}
                                    &mdash; {{ $emp->empDetail->position->pos_desc ?? 'No Position' }}
                                </option>
                            @endforeach
                        </select>
                        <span class="text-danger small" id="err_employee_id"></span>
                    </div>
                </div>

                <div class="sub-divider"><span>OB Details</span></div>
                <div class="row g-3 mb-3">
                    <div class="col-lg-3">
                        <label class="field-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="start_date" id="txtStartDate" required>
                        <span class="text-danger small" id="err_start_date"></span>
                    </div>
                    <div class="col-lg-3">
                        <label class="field-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="end_date" id="txtEndDate" required>
                        <span class="text-danger small" id="err_end_date"></span>
                    </div>
                    <div class="col-lg-3">
                        <label class="field-label">Total Hours <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="total_hrs" id="txtTotalHrs"
                               step="0.5" min="0.5" placeholder="8" required>
                        <span class="text-danger small" id="err_total_hrs"></span>
                    </div>
                    <div class="col-lg-3">
                        <label class="field-label">Destination <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="destination" placeholder="Location / place" required>
                        <span class="text-danger small" id="err_destination"></span>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-lg-8">
                        <label class="field-label">Purpose <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="purpose" rows="2"
                                  placeholder="Purpose of official business..." required></textarea>
                        <span class="text-danger small" id="err_purpose"></span>
                    </div>
                    <div class="col-lg-4">
                        <label class="field-label">Remarks <span class="text-muted fw-normal" style="text-transform:none;">(optional)</span></label>
                        <textarea class="form-control" name="remarks" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn-submit" id="btnSubmitOB">
                        <i class="fa-solid fa-paper-plane me-2"></i>Submit OB
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Records Table --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-history"></i></div>
            <h5 class="sc-title">OB Records</h5>
        </div>
        <div class="sc-body" style="padding:0;">
            <div class="table-responsive">
                <table class="table table-hover ob-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Destination</th>
                            <th>Purpose</th>
                            <th>Hours</th>
                            <th>Approved By</th>
                            <th class="pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($obs as $ob)
                        @php
                            $statusClass = match($ob->status) {
                                'Approved' => 'badge-approved',
                                'Rejected' => 'badge-rejected',
                                default    => 'badge-pending',
                            };
                        @endphp
                        <tr>
                            <td class="ps-4 fw-semibold">
                                {{ strtoupper($ob->lname) }}, {{ ucwords(strtolower($ob->fname)) }}
                            </td>
                            <td>{{ \Carbon\Carbon::parse($ob->start_date)->format('M d, Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($ob->end_date)->format('M d, Y') }}</td>
                            <td>{{ $ob->destination }}</td>
                            <td>{{ $ob->purpose }}</td>
                            <td>{{ $ob->total_hrs }} hr(s)</td>
                            <td>{{ $ob->approved_by ?? '—' }}</td>
                            <td class="pe-4">
                                <span class="badge {{ $statusClass }} rounded-pill px-3 py-2 fw-semibold">
                                    {{ $ob->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No OB records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($obs->hasPages())
            <div class="d-flex justify-content-end px-4 py-3">
                {{ $obs->links() }}
            </div>
            @endif
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('frmAdminOB');
    const btn  = document.getElementById('btnSubmitOB');

    const errIds = ['employee_id', 'start_date', 'end_date', 'destination', 'purpose', 'total_hrs'];

    function clearErrors() {
        errIds.forEach(k => {
            const el = document.getElementById('err_' + k);
            if (el) el.textContent = '';
        });
    }

    // Auto-fill total_hrs when start date changes (1 day = 8 hrs)
    document.getElementById('txtStartDate').addEventListener('change', function () {
        const end = document.getElementById('txtEndDate');
        if (!end.value) end.value = this.value;
        recalcHrs();
    });
    document.getElementById('txtEndDate').addEventListener('change', recalcHrs);

    function recalcHrs() {
        const s = document.getElementById('txtStartDate').value;
        const e = document.getElementById('txtEndDate').value;
        if (s && e) {
            const days = Math.max(1, Math.round((new Date(e) - new Date(s)) / 86400000) + 1);
            document.getElementById('txtTotalHrs').value = days * 8;
        }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

        axios.post('{{ route("admin.ob.store") }}', new FormData(form))
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
                    errIds.forEach(k => {
                        if (errs[k]) {
                            const el = document.getElementById('err_' + k);
                            if (el) el.textContent = errs[k][0];
                        }
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
                }
            })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i>Submit OB';
            });
    });
});
</script>
@endsection
