@extends('layout.app', ['title' => 'COE Issuance Log'])
@section('content')

@include('pages.reports.partials.styles')

<div class="rpt-shell">
    <div class="rpt-topbar">
        <p class="page-title"><i class="fa fa-file-contract me-2" style="color:var(--teal)"></i>Certificate of Employment &mdash; Issuance Log</p>
        <p class="page-sub">Every COE request with status, certificate number, purpose, signatory and review trail.</p>
    </div>

    <div class="sc">
        <div class="sc-body" style="padding:16px 22px;">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="field-label">Status</label>
                    <select id="fltStatus" class="form-select form-select-sm">
                        <option value="all">All</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Year</label>
                    <select id="fltYear" class="form-select form-select-sm">
                        <option value="all">All years</option>
                        @foreach ($years as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach
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
                    <input type="text" id="fltSearch" class="form-control form-control-sm" placeholder="Name / ID / Cert#...">
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
        <div class="stat"><div class="l">Total Requests</div><div class="v" id="kTotal">0</div></div>
        <div class="stat"><div class="l">Pending</div><div class="v" id="kPend">0</div></div>
        <div class="stat"><div class="l">Approved</div><div class="v" id="kApp">0</div></div>
        <div class="stat"><div class="l">Rejected</div><div class="v" id="kRej">0</div></div>
    </div>

    <div class="sc">
        <div class="sc-head"><div class="sc-icon"><i class="fa fa-file-contract"></i></div><h5 class="sc-title">COE Requests</h5></div>
        <div class="table-responsive" style="max-height:64vh; overflow:auto;">
            <table class="table table-hover align-middle mb-0 rpt-table">
                <thead>
                    <tr>
                        <th class="ps-3">Cert No.</th>
                        <th>Employee</th>
                        <th>Purpose</th>
                        <th class="text-center">Copies</th>
                        <th class="text-center">Salary</th>
                        <th>Signatory</th>
                        <th>Reviewed</th>
                        <th class="text-center pe-3">Status</th>
                    </tr>
                </thead>
                <tbody id="tbl"><tr><td colspan="8" class="text-center text-muted py-4">Click Filter to load.</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(function () {
    const params = () => ({
        status: $('#fltStatus').val(), year: $('#fltYear').val(),
        company_id: $('#fltCompany').val() || 'all', department_id: $('#fltDept').val() || 'all', search: $('#fltSearch').val(),
    });
    const badge = s => s === 'approved' ? '<span class="pill" style="background:#dcfce7;color:#166534">Approved</span>'
        : (s === 'rejected' ? '<span class="pill" style="background:#fee2e2;color:#991b1b">Rejected</span>'
        : '<span class="pill" style="background:#fef9c3;color:#854d0e">Pending</span>');

    function load() {
        $('#tbl').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        axios.get('/reports/coe-log/fetch', { params: params() }).then(res => {
            const d = res.data, rows = d.data || [], s = d.stats || {};
            $('#kTotal').text(s.total || 0); $('#kPend').text(s.pending || 0); $('#kApp').text(s.approved || 0); $('#kRej').text(s.rejected || 0);
            if (!rows.length) { $('#tbl').html('<tr><td colspan="8" class="text-center text-muted py-4">No COE requests found.</td></tr>'); return; }
            $('#tbl').html(rows.map(r => `<tr>
                <td class="ps-3 mono">${r.certificate_no || '—'}</td>
                <td><span class="fw-bold text-uppercase">${r.employee_name}</span><br><span class="text-muted">${r.employee_id} · ${r.department_name || ''}</span></td>
                <td>${r.purpose || '—'}</td>
                <td class="text-center">${r.copies || 1}</td>
                <td class="text-center">${r.include_salary ? '<i class="fa fa-check" style="color:var(--teal)"></i>' : '—'}</td>
                <td>${r.signatory_name || '—'}</td>
                <td class="text-muted">${r.reviewed_at ? new Date(r.reviewed_at).toLocaleDateString('en-PH') : '—'}${r.reviewer ? '<br><span class="text-muted" style="font-size:.7rem">'+r.reviewer+'</span>' : ''}</td>
                <td class="text-center pe-3">${badge(r.status)}</td></tr>`).join(''));
        }).catch(() => $('#tbl').html('<tr><td colspan="8" class="text-center text-danger py-4">Failed to load.</td></tr>'));
    }

    $('#btnFilter').on('click', load);
    $('#fltSearch').on('keyup', e => { if (e.key === 'Enter') load(); });
    $('#fltStatus,#fltYear,#fltCompany,#fltDept').on('change', load);
    $('#btnExport').on('click', () => window.location = '/reports/coe-log/export?' + new URLSearchParams(params()).toString());
    $('#btnPrint').on('click', () => window.open('/reports/coe-log/print?' + new URLSearchParams(params()).toString(), '_blank'));
    load();
});
</script>
@endsection
