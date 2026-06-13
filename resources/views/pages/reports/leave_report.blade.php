@extends('layout.app', ['title' => 'Leave Report'])
@section('content')

<div class="container-fluid">
    <div class="mb-2"><h4 class="mb-0 text-gray-800">Leave Report</h4></div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Date From</label>
                    <input type="date" id="fltFrom" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Date To</label>
                    <input type="date" id="fltTo" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Department</label>
                    <select id="fltDept" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->dep_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select id="fltStatus" class="form-select form-select-sm">
                        <option value="all">All</option>
                        <option value="FORAPPROVAL">For Approval</option>
                        <option value="APPROVED">Approved</option>
                        <option value="APPROVEDBYCFO">Approved by CFO</option>
                        <option value="DISAPPROVED">Disapproved</option>
                        <option value="CANCELED">Canceled</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1">Search</label>
                    <input type="text" id="fltSearch" class="form-control form-control-sm" placeholder="Name or ID...">
                </div>
                <div class="col-12 col-md-2 d-grid gap-2">
                    <button class="btn btn-sm fw-bold text-white" id="btnFilter" style="background:#008080;"><i class="fa fa-search me-1"></i> Filter</button>
                    <button class="btn btn-sm btn-outline-secondary fw-bold" id="btnPrint"><i class="fa fa-print me-1"></i> Print</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead>
                        <tr class="text-secondary text-uppercase">
                            <th class="ps-3">Employee</th>
                            <th>Dept</th>
                            <th>Leave Type</th>
                            <th>Start</th>
                            <th>End</th>
                            <th class="text-end">Days</th>
                            <th>Kind</th>
                            <th>Reason</th>
                            <th class="text-center pe-3">Status</th>
                        </tr>
                    </thead>
                    <tbody id="tbl"><tr><td colspan="9" class="text-center text-muted py-4">Set filters and click Filter.</td></tr></tbody>
                    <tfoot id="tfoot"></tfoot>
                </table>
            </div>
            <div id="pager" class="p-2"></div>
        </div>
    </div>
</div>

<script>
$(function () {
    const badge = s => {
        const m = { APPROVED:'success', APPROVEDBYCFO:'primary', DISAPPROVED:'danger', FORAPPROVAL:'warning text-dark', CANCELED:'secondary' };
        return `<span class="badge bg-${m[s] || 'light text-dark'}">${(s||'').replace(/([A-Z])/g,' $1').trim()}</span>`;
    };
    const params = () => ({
        date_from: $('#fltFrom').val(), date_to: $('#fltTo').val(),
        department_id: $('#fltDept').val() || 'all', status: $('#fltStatus').val() || 'all',
        search: $('#fltSearch').val(),
    });

    function load(page = 1) {
        $('#tbl').html('<tr><td colspan="9" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        $('#tfoot').html('');
        axios.get('/reports/leave/fetch', { params: { ...params(), page } }).then(res => {
            const d = res.data, rows = d.data || [];
            if (!rows.length) { $('#tbl').html('<tr><td colspan="9" class="text-center text-muted py-4">No leave records found.</td></tr>'); $('#pager').html(''); return; }
            let h = '', td = 0;
            rows.forEach(r => {
                const days = Number(r.total_hrs || 0) / 8;
                td += days;
                h += `<tr>
                    <td class="ps-3"><span class="fw-bold text-uppercase">${r.employee_name || ''}</span><br><span class="text-muted">${r.employee_id}</span></td>
                    <td>${r.department_name ?? '—'}</td>
                    <td>${r.leave_type ?? '—'}</td>
                    <td>${(r.start_date||'').substring(0,10)}</td>
                    <td>${(r.end_date||'').substring(0,10)}</td>
                    <td class="text-end">${days.toFixed(2)}</td>
                    <td>${Number(r.leave_kind) === 0 ? 'Paid' : 'Unpaid'}</td>
                    <td class="text-truncate" style="max-width:180px;">${r.reason ?? ''}</td>
                    <td class="text-center pe-3">${badge(r.status)}</td>
                </tr>`;
            });
            $('#tbl').html(h);
            $('#tfoot').html(`<tr class="fw-bold"><td colspan="5" class="text-end">Page Total Days:</td><td class="text-end">${td.toFixed(2)}</td><td colspan="3"></td></tr>`);
            pager(d.last_page || 1, d.current_page || 1);
        }).catch(() => $('#tbl').html('<tr><td colspan="9" class="text-center text-danger py-4">Failed to load.</td></tr>'));
    }

    function pager(last, cur) {
        const c = document.getElementById('pager');
        if (last <= 1) { c.innerHTML = ''; return; }
        const it = (l, p, o) => (o && o.disabled) ? `<li class="page-item disabled"><span class="page-link">${l}</span></li>` : `<li class="page-item ${o && o.active ? 'active' : ''}"><a href="#" class="page-link pg" data-page="${p}">${l}</a></li>`;
        const win = 1, pages = [];
        for (let i = 1; i <= last; i++) if (i === 1 || i === last || (i >= cur - win && i <= cur + win)) pages.push(i);
        let h = '<nav><ul class="pagination pagination-sm justify-content-end mb-0 gap-1">' + it('&lsaquo;', cur - 1, { disabled: cur <= 1 });
        let prev = 0; pages.forEach(i => { if (prev && i - prev > 1) h += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>'; h += it(i, i, { active: i === cur }); prev = i; });
        h += it('&rsaquo;', cur + 1, { disabled: cur >= last }) + '</ul></nav>';
        c.innerHTML = h;
    }

    $('#btnFilter').on('click', () => load(1));
    $('#fltSearch').on('keyup', e => { if (e.key === 'Enter') load(1); });
    $('#fltFrom,#fltTo,#fltDept,#fltStatus').on('change', () => load(1));
    $(document).on('click', '.pg', function (e) { e.preventDefault(); if ($(this).closest('.page-item').hasClass('disabled')) return; const p = parseInt($(this).data('page'), 10); if (p) load(p); });
    $('#btnPrint').on('click', () => window.open('/reports/leave/print?' + new URLSearchParams(params()).toString(), '_blank'));
});
</script>
@endsection
