@extends('layout.app', ['title' => 'Leave Ledger'])
@section('content')

@include('pages.reports.partials.styles')

<div class="rpt-shell">
    <div class="rpt-topbar">
        <p class="page-title"><i class="fa fa-calendar-check me-2" style="color:var(--teal)"></i>Leave Ledger / Balance</p>
        <p class="page-sub">Per-employee leave credits allocated, used, and remaining &mdash; cross-referenced with leave actually filed.</p>
    </div>

    <div class="sc">
        <div class="sc-body" style="padding:16px 22px;">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="field-label">Year</label>
                    <select id="fltYear" class="form-select form-select-sm">
                        @foreach ($years as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Leave Type</label>
                    <select id="fltType" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach ($leaveTypes as $t)<option value="{{ $t->id }}">{{ $t->type_leave }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Company</label>
                    <select id="fltCompany" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach ($companies as $c)<option value="{{ $c->comp_id }}">{{ $c->comp_name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Department</label>
                    <select id="fltDept" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->dep_name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Search</label>
                    <input type="text" id="fltSearch" class="form-control form-control-sm" placeholder="Name / ID...">
                </div>
                <div class="col-6 col-md-2 d-flex gap-2">
                    <button class="btn-filter flex-fill" id="btnFilter"><i class="fa fa-search me-1"></i> Filter</button>
                    <button class="btn-ghost" id="btnExport" title="Export to Excel"><i class="fa fa-file-excel"></i></button>
                    <button class="btn-ghost" id="btnPrint" title="Print"><i class="fa fa-print"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-3 flex-wrap">
        <div class="stat"><div class="l">Ledger Lines</div><div class="v" id="kCount">0</div></div>
        <div class="stat"><div class="l">Total Allocated</div><div class="v" id="kAlloc">0</div></div>
        <div class="stat"><div class="l">Total Used</div><div class="v" id="kUsed">0</div></div>
        <div class="stat"><div class="l">Total Balance</div><div class="v" id="kBal">0</div></div>
        <div class="stat"><div class="l">Days Filed</div><div class="v" id="kFiled">0</div></div>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-calendar-check"></i></div>
            <h5 class="sc-title">Leave Ledger &mdash; <span id="lblYear">—</span></h5>
        </div>
        <div class="table-responsive" style="max-height:64vh; overflow:auto;">
            <table class="table table-hover align-middle mb-0 rpt-table">
                <thead>
                    <tr>
                        <th class="ps-3">Employee</th>
                        <th>Department</th>
                        <th>Leave Type</th>
                        <th class="text-end">Allocated</th>
                        <th class="text-end">Used</th>
                        <th class="text-end">Balance</th>
                        <th class="text-end">Filed (days)</th>
                        <th class="text-end pe-3">Filed (hrs)</th>
                    </tr>
                </thead>
                <tbody id="tbl"><tr><td colspan="8" class="text-center text-muted py-4">Pick a year and click Filter.</td></tr></tbody>
                <tfoot id="tfoot"></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
$(function () {
    const num = n => Number(n || 0).toLocaleString('en-PH', { maximumFractionDigits: 2 });
    const params = () => ({
        year: $('#fltYear').val(), leavetype_id: $('#fltType').val() || 'all',
        company_id: $('#fltCompany').val() || 'all', department_id: $('#fltDept').val() || 'all', search: $('#fltSearch').val(),
    });

    function load() {
        $('#tbl').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        $('#tfoot').html('');
        axios.get('/reports/leave-ledger/fetch', { params: params() }).then(res => {
            const d = res.data, rows = d.data || [], s = d.stats || {};
            $('#kCount').text(s.count || 0); $('#kAlloc').text(num(s.allocated)); $('#kUsed').text(num(s.used)); $('#kBal').text(num(s.balance)); $('#kFiled').text(num(s.filed));
            $('#lblYear').text(d.year || '—');
            if (!rows.length) { $('#tbl').html('<tr><td colspan="8" class="text-center text-muted py-4">No leave credits or filed leave for this year.</td></tr>'); return; }
            let h = '';
            rows.forEach(r => {
                h += `<tr>
                    <td class="ps-3"><span class="fw-bold text-uppercase">${r.employee_name || ''}</span><br><span class="text-muted">${r.employee_id}</span></td>
                    <td>${r.department_name || '—'}</td>
                    <td>${r.leave_type || '—'}</td>
                    <td class="text-end mono">${r.has_alloc ? num(r.allocated) : '—'}</td>
                    <td class="text-end mono">${r.has_alloc ? num(r.used) : '—'}</td>
                    <td class="text-end mono fw-bold" style="color:var(--teal-dark)">${r.has_alloc ? num(r.balance) : '—'}</td>
                    <td class="text-end mono">${num(r.filed_days)}</td>
                    <td class="text-end pe-3 mono">${num(r.filed_hours)}</td>
                </tr>`;
            });
            $('#tbl').html(h);
            $('#tfoot').html(`<tr><td class="text-end" colspan="3">TOTAL (${s.count})</td><td class="text-end mono">${num(s.allocated)}</td><td class="text-end mono">${num(s.used)}</td><td class="text-end mono">${num(s.balance)}</td><td class="text-end mono">${num(s.filed)}</td><td class="pe-3"></td></tr>`);
        }).catch(() => $('#tbl').html('<tr><td colspan="8" class="text-center text-danger py-4">Failed to load.</td></tr>'));
    }

    $('#btnFilter').on('click', load);
    $('#fltSearch').on('keyup', e => { if (e.key === 'Enter') load(); });
    $('#fltYear,#fltType,#fltCompany,#fltDept').on('change', load);
    $('#btnExport').on('click', () => window.location = '/reports/leave-ledger/export?' + new URLSearchParams(params()).toString());
    $('#btnPrint').on('click', () => window.open('/reports/leave-ledger/print?' + new URLSearchParams(params()).toString(), '_blank'));
    load();
});
</script>
@endsection
