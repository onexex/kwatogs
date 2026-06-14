@extends('layout.app', ['title' => 'Pending Schedule Requests'])
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8; --bg:#f1f5f9;
        --surface:#fff; --border:#e2e8f0; --danger:#ef4444; --success:#10b981;
        --radius-card:14px; --shadow-card:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);
    }
    .sr-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .sr-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:20px; }
    .sr-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .sr-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .sc { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); overflow:hidden; }
    .sc-head { display:flex; align-items:center; gap:10px; padding:14px 22px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .sc-icon { width:30px; height:30px; border-radius:8px; background:var(--teal-light); color:var(--teal); display:flex; align-items:center; justify-content:center; font-size:.78rem; }
    .sc-title { font-size:.78rem; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; margin:0; }
    .sr-table thead th { font-size:.68rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px;
        border-bottom:2px solid var(--border); background:#f8fafc; white-space:nowrap; padding:11px 16px; }
    .sr-table tbody td { font-size:.83rem; color:var(--slate); vertical-align:middle; padding:11px 16px; }
    .sr-table tbody tr:hover { background:var(--teal-light); }
    .chip { font-size:.78rem; font-weight:700; padding:3px 9px; border-radius:7px; background:#eef2f6; color:var(--slate); }
    .chip-new { background:var(--teal-light); color:var(--teal-dark); }
    .btn-ok { background:var(--success); color:#fff; border:none; border-radius:7px; padding:6px 12px; font-size:.75rem; font-weight:700; cursor:pointer; }
    .btn-no { background:#fff; color:var(--danger); border:1.5px solid #fca5a5; border-radius:7px; padding:6px 12px; font-size:.75rem; font-weight:700; cursor:pointer; }
    .btn-no:hover { background:#fef2f2; }
</style>

<div class="sr-shell">
    <div class="sr-topbar">
        <p class="page-title">Pending Schedule Requests</p>
        <p class="page-sub">These changes are already in effect (emergency mode). Approve to confirm, or disapprove to revert to the old schedule.</p>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-calendar-check"></i></div>
            <h5 class="sc-title">Awaiting Approval</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 sr-table">
                <thead>
                    <tr>
                        <th class="ps-4">Employee</th>
                        <th>Date</th>
                        <th>Current</th>
                        <th>Requested</th>
                        <th>Reason</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody id="srBody">
                    @forelse($requests as $r)
                    <tr data-id="{{ $r->id }}">
                        <td class="ps-4 fw-bold text-uppercase small">{{ $r->employee->lname ?? '' }}, {{ $r->employee->fname ?? '' }}<br><span class="text-muted fw-normal">{{ $r->employee_id }}</span></td>
                        <td class="small">{{ \Carbon\Carbon::parse($r->request_date)->format('M d, Y') }}</td>
                        <td><span class="chip">{{ $r->old_sched_in ? substr($r->old_sched_in,0,5).'–'.substr($r->old_sched_out,0,5) : '—' }}</span></td>
                        <td><span class="chip chip-new">{{ substr($r->new_sched_in,0,5) }}–{{ substr($r->new_sched_out,0,5) }}</span></td>
                        <td class="small text-muted">{{ $r->reason ?: '—' }}</td>
                        <td class="pe-4 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <button class="btn-ok srApprove" data-id="{{ $r->id }}"><i class="fa fa-check me-1"></i>Approve</button>
                                <button class="btn-no srDisapprove" data-id="{{ $r->id }}">Disapprove</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No pending schedule requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(function () {
    function post(id, status, remarks) {
        if (window.Swal) Swal.fire({ title: 'Saving…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        axios.post("{{ route('schedule-request.update') }}", { id, status, remarks: remarks || '' })
            .then(r => {
                if (window.Swal) Swal.fire({ icon: 'success', title: status === 'APPROVED' ? 'Approved' : 'Disapproved', text: r.data.message, timer: 1900, showConfirmButton: false });
                $(`tr[data-id="${id}"]`).fadeOut(250, function () { $(this).remove(); if (!$('#srBody tr').length) $('#srBody').html('<tr><td colspan="6" class="text-center text-muted py-4">No pending schedule requests.</td></tr>'); });
            })
            .catch(e => { const m = e.response?.data?.message || 'Failed.'; window.Swal ? Swal.fire('Error', m, 'error') : alert(m); });
    }
    $(document).on('click', '.srApprove', function () {
        const id = $(this).data('id');
        if (window.Swal) {
            Swal.fire({ icon: 'question', title: 'Approve this change?', text: "This confirms the change (it's already in effect).", showCancelButton: true, confirmButtonText: 'Yes, approve', confirmButtonColor: '#16a34a' })
                .then(res => { if (res.isConfirmed) post(id, 'APPROVED'); });
        } else if (confirm('Approve?')) post(id, 'APPROVED');
    });
    $(document).on('click', '.srDisapprove', function () {
        const id = $(this).data('id');
        if (window.Swal) {
            Swal.fire({ icon: 'warning', title: 'Disapprove & revert?', text: "This reverts the schedule to the original and recomputes the day.", input: 'text', inputPlaceholder: 'Reason (optional)', showCancelButton: true, confirmButtonText: 'Disapprove', confirmButtonColor: '#ef4444' })
                .then(res => { if (res.isConfirmed) post(id, 'DISAPPROVED', res.value); });
        } else post(id, 'DISAPPROVED', prompt('Reason?') || '');
    });
});
</script>
@endsection
