@extends('layout.app', ['title' => 'Payroll Logs'])
@section('content')

<div class="container-fluid">

    <div class="mb-2 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-gray-800">Payroll Computation Logs</h4>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Pay Date</label>
                    <input type="date" id="fltPayDate" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Department</label>
                    <select id="fltDepartment" class="form-select form-select-sm">
                        <option value="all">All Departments</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->dep_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">Search (name or ID)</label>
                    <input type="text" id="fltSearch" class="form-control form-control-sm" placeholder="Employee name or ID...">
                </div>
                <div class="col-12 col-md-2 d-grid gap-2">
                    <button class="btn btn-sm btn-teal fw-bold" id="btnFilter" style="background:#008080;color:#fff;">
                        <i class="fa fa-search me-1"></i> Filter
                    </button>
                    <button class="btn btn-sm btn-outline-secondary fw-bold" id="btnPrintAll">
                        <i class="fa fa-print me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-secondary small text-uppercase">
                            <th class="ps-3">Employee</th>
                            <th>Dept</th>
                            <th>Class</th>
                            <th>Pay Date</th>
                            <th class="text-end">Gross</th>
                            <th class="text-end">Net</th>
                            <th class="text-end">Pay Rec.</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tblLogs">
                        <tr><td colspan="8" class="text-center text-muted py-4">Use the filters above and click Filter.</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="logsPagination" class="p-2"></div>
        </div>
    </div>
</div>

{{-- Detail modal --}}
<div class="modal fade" id="mdlLogDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:#008080;color:#fff;">
                <h6 class="modal-title" id="mdlLogTitle">Computation Breakdown</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="mdlLogBody"></div>
            <div class="modal-footer">
                <a href="#" id="mdlPrintLink" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa fa-print me-1"></i> Print this</a>
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    let rows = [];      // current page data
    let curPage = 1;

    const peso = (n) => Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function loadLogs(page = 1) {
        const params = {
            pay_date: $('#fltPayDate').val(),
            department_id: $('#fltDepartment').val() || 'all',
            search: $('#fltSearch').val(),
            page,
        };
        $('#tblLogs').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-secondary spinner-border-sm"></div></td></tr>');
        axios.get('/payroll-logs/fetch', { params }).then(res => {
            const d = res.data;
            rows = d.data || [];
            curPage = d.current_page || 1;
            if (!rows.length) {
                $('#tblLogs').html('<tr><td colspan="8" class="text-center text-muted py-4">No payroll logs found.</td></tr>');
                $('#logsPagination').html('');
                return;
            }
            let html = '';
            rows.forEach((r, i) => {
                html += `<tr>
                    <td class="ps-3"><span class="fw-bold text-uppercase small">${r.employee_name || ''}</span><br><span class="text-muted small">${r.employee_id}</span></td>
                    <td class="small">${r.department_name ?? r.department_id ?? '—'}</td>
                    <td class="small">${r.classification ?? '—'}</td>
                    <td class="small">${r.pay_date ? r.pay_date.substring(0,10) : ''}</td>
                    <td class="text-end">${peso(r.gross_pay)}</td>
                    <td class="text-end">${peso(r.net_pay)}</td>
                    <td class="text-end fw-bold">${peso(r.pay_rec)}</td>
                    <td class="text-end pe-3">
                        <button class="btn btn-sm btn-outline-primary btnView" data-i="${i}"><i class="fa fa-eye"></i></button>
                    </td>
                </tr>`;
            });
            $('#tblLogs').html(html);
            renderPagination(d.last_page || 1, curPage);
        }).catch(() => {
            $('#tblLogs').html('<tr><td colspan="8" class="text-center text-danger py-4">Failed to load logs.</td></tr>');
        });
    }

    function renderPagination(lastPage, cur) {
        const c = document.getElementById('logsPagination');
        if (lastPage <= 1) { c.innerHTML = ''; return; }
        const item = (lbl, pg, o) => (o && o.disabled)
            ? `<li class="page-item disabled"><span class="page-link">${lbl}</span></li>`
            : `<li class="page-item ${o && o.active ? 'active' : ''}"><a href="#" class="page-link logPage" data-page="${pg}">${lbl}</a></li>`;
        const win = 1, pages = [];
        for (let i = 1; i <= lastPage; i++) if (i === 1 || i === lastPage || (i >= cur - win && i <= cur + win)) pages.push(i);
        let h = '<nav><ul class="pagination pagination-sm justify-content-end mb-0 gap-1">';
        h += item('&lsaquo;', cur - 1, { disabled: cur <= 1 });
        let prev = 0;
        pages.forEach(i => { if (prev && i - prev > 1) h += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>'; h += item(i, i, { active: i === cur }); prev = i; });
        h += item('&rsaquo;', cur + 1, { disabled: cur >= lastPage });
        h += '</ul></nav>';
        c.innerHTML = h;
    }

    // Render a breakdown object as nested tables
    function renderBreakdown(obj) {
        if (obj === null || obj === undefined) return '<span class="text-muted">—</span>';
        if (typeof obj !== 'object') return `<span>${obj}</span>`;
        let h = '<table class="table table-sm mb-0"><tbody>';
        Object.keys(obj).forEach(k => {
            const v = obj[k];
            const label = k.replace(/_/g, ' ');
            if (v !== null && typeof v === 'object') {
                h += `<tr><td class="fw-bold text-capitalize align-top" style="width:200px;">${label}</td><td>${renderBreakdown(v)}</td></tr>`;
            } else {
                h += `<tr><td class="text-capitalize" style="width:200px;">${label}</td><td>${v ?? '—'}</td></tr>`;
            }
        });
        h += '</tbody></table>';
        return h;
    }

    // events
    $('#btnFilter').on('click', () => loadLogs(1));
    $('#fltSearch').on('keyup', e => { if (e.key === 'Enter') loadLogs(1); });
    $('#fltPayDate, #fltDepartment').on('change', () => loadLogs(1));

    $(document).on('click', '.logPage', function (e) {
        e.preventDefault();
        if ($(this).closest('.page-item').hasClass('disabled')) return;
        const p = parseInt($(this).data('page'), 10);
        if (p) loadLogs(p);
    });

    $(document).on('click', '.btnView', function () {
        const r = rows[$(this).data('i')];
        if (!r) return;
        $('#mdlLogTitle').text(`${r.employee_name || ''} — ${r.pay_date ? r.pay_date.substring(0,10) : ''}`);
        $('#mdlLogBody').html(renderBreakdown(r.breakdown || {}));
        $('#mdlPrintLink').attr('href', `/payroll-logs/print?id=${encodeURIComponent(r.id)}`);
        new bootstrap.Modal(document.getElementById('mdlLogDetail')).show();
    });

    $('#btnPrintAll').on('click', function () {
        const params = new URLSearchParams({
            pay_date: $('#fltPayDate').val() || '',
            department_id: $('#fltDepartment').val() || 'all',
            search: $('#fltSearch').val() || '',
        });
        window.open(`/payroll-logs/print?${params.toString()}`, '_blank');
    });
});
</script>
@endsection
