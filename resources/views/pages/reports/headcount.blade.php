@extends('layout.app', ['title' => 'Headcount & Turnover'])
@section('content')

@include('pages.reports.partials.styles')

<div class="rpt-shell">
    <div class="rpt-topbar">
        <p class="page-title"><i class="fa fa-users me-2" style="color:var(--teal)"></i>Headcount / Manpower &amp; Turnover</p>
        <p class="page-sub">Active-headcount snapshot by department &amp; classification, plus new hires and separations for the year.</p>
    </div>

    <div class="sc">
        <div class="sc-body" style="padding:16px 22px;">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="field-label">Year</label>
                    <select id="fltYear" class="form-select form-select-sm">
                        @foreach ($years as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="field-label">Company</label>
                    <select id="fltCompany" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach ($companies as $c)<option value="{{ $c->comp_id }}">{{ $c->comp_name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 d-flex gap-2 justify-content-end">
                    <button class="btn-filter" id="btnFilter"><i class="fa fa-search me-1"></i> Filter</button>
                    <button class="btn-ghost" id="btnExport" title="Export to Excel"><i class="fa fa-file-excel me-1"></i> Excel</button>
                    <button class="btn-ghost" id="btnPrint" title="Print"><i class="fa fa-print me-1"></i> Print</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-3 flex-wrap">
        <div class="stat"><div class="l">Active Headcount</div><div class="v" id="kActive">0</div></div>
        <div class="stat"><div class="l">New Hires (year)</div><div class="v" id="kHires">0</div></div>
        <div class="stat"><div class="l">Separations (year)</div><div class="v" id="kSep">0</div></div>
        <div class="stat"><div class="l">Turnover Rate</div><div class="v" id="kTurn">0%</div></div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="sc mb-3">
                <div class="sc-head"><div class="sc-icon"><i class="fa fa-sitemap"></i></div><h5 class="sc-title">Headcount by Department</h5></div>
                <div class="table-responsive"><table class="table align-middle mb-0 rpt-table">
                    <thead><tr><th class="ps-3">Department</th><th class="text-end pe-3">Active Heads</th></tr></thead>
                    <tbody id="tblDept"><tr><td colspan="2" class="text-center text-muted py-4">—</td></tr></tbody>
                    <tfoot id="footDept"></tfoot>
                </table></div>
            </div>
        </div>
        <div class="col-12 col-lg-5">
            <div class="sc mb-3">
                <div class="sc-head"><div class="sc-icon"><i class="fa fa-layer-group"></i></div><h5 class="sc-title">By Classification</h5></div>
                <div class="table-responsive"><table class="table align-middle mb-0 rpt-table">
                    <thead><tr><th class="ps-3">Classification</th><th class="text-end pe-3">Heads</th></tr></thead>
                    <tbody id="tblClass"><tr><td colspan="2" class="text-center text-muted py-4">—</td></tr></tbody>
                </table></div>
            </div>
        </div>
    </div>

    <div class="sc">
        <div class="sc-head"><div class="sc-icon"><i class="fa fa-user-plus"></i></div><h5 class="sc-title">New Hires &mdash; <span id="lblY1">—</span></h5></div>
        <div class="table-responsive" style="max-height:40vh; overflow:auto;"><table class="table table-hover align-middle mb-0 rpt-table">
            <thead><tr><th class="ps-3">Employee</th><th>Department</th><th>Position</th><th>Date Hired</th><th class="pe-3">Status</th></tr></thead>
            <tbody id="tblHires"><tr><td colspan="5" class="text-center text-muted py-4">—</td></tr></tbody>
        </table></div>
    </div>

    <div class="sc">
        <div class="sc-head"><div class="sc-icon"><i class="fa fa-user-minus"></i></div><h5 class="sc-title">Separations &mdash; <span id="lblY2">—</span></h5></div>
        <div class="table-responsive" style="max-height:40vh; overflow:auto;"><table class="table table-hover align-middle mb-0 rpt-table">
            <thead><tr><th class="ps-3">Employee</th><th>Department</th><th>Separation Date</th><th>Reason</th><th>Type</th><th class="text-end pe-3">Years</th></tr></thead>
            <tbody id="tblSep"><tr><td colspan="6" class="text-center text-muted py-4">—</td></tr></tbody>
        </table></div>
    </div>
</div>

<script>
$(function () {
    const params = () => ({ year: $('#fltYear').val(), company_id: $('#fltCompany').val() || 'all' });

    function load() {
        $('#tblDept,#tblClass,#tblHires,#tblSep').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        $('#footDept').html('');
        axios.get('/reports/headcount/fetch', { params: params() }).then(res => {
            const d = res.data, s = d.stats || {};
            $('#kActive').text(s.active || 0); $('#kHires').text(s.new_hires || 0); $('#kSep').text(s.separations || 0); $('#kTurn').text((s.turnover || 0) + '%');
            $('#lblY1,#lblY2').text(d.year);

            $('#tblDept').html((d.byDept || []).map(r => `<tr><td class="ps-3 fw-bold">${r.department_name}</td><td class="text-end pe-3 mono">${r.headcount}</td></tr>`).join('') || '<tr><td colspan="2" class="text-center text-muted py-4">No active employees.</td></tr>');
            $('#footDept').html(`<tr><td class="text-end">TOTAL ACTIVE</td><td class="text-end pe-3 mono">${s.active || 0}</td></tr>`);

            $('#tblClass').html((d.byClass || []).map(r => `<tr><td class="ps-3">${r.classification}</td><td class="text-end pe-3 mono">${r.headcount}</td></tr>`).join('') || '<tr><td colspan="2" class="text-center text-muted py-4">—</td></tr>');

            $('#tblHires').html((d.newHires || []).map(r => `<tr>
                <td class="ps-3"><span class="fw-bold text-uppercase">${r.name}</span><br><span class="text-muted">${r.employee_id}</span></td>
                <td>${r.department}</td><td>${r.position}</td><td>${r.hired_fmt}</td>
                <td class="pe-3"><span class="pill">${r.status_lbl}</span></td></tr>`).join('') || '<tr><td colspan="5" class="text-center text-muted py-4">No new hires this year.</td></tr>');

            $('#tblSep').html((d.separations || []).map(r => `<tr>
                <td class="ps-3"><span class="fw-bold text-uppercase">${r.name}</span><br><span class="text-muted">${r.employee_id}</span></td>
                <td>${r.department}</td><td>${r.sep_fmt}</td><td>${r.reason || '—'}</td>
                <td><span class="pill" style="background:#fee2e2;color:#991b1b">${r.status_lbl}</span></td>
                <td class="text-end pe-3 mono">${r.years_rendered != null ? Number(r.years_rendered).toFixed(2) : '—'}</td></tr>`).join('') || '<tr><td colspan="6" class="text-center text-muted py-4">No separations recorded this year.</td></tr>');
        }).catch(() => $('#tblDept').html('<tr><td colspan="2" class="text-center text-danger py-4">Failed to load.</td></tr>'));
    }

    $('#btnFilter').on('click', load);
    $('#fltYear,#fltCompany').on('change', load);
    $('#btnExport').on('click', () => window.location = '/reports/headcount/export?' + new URLSearchParams(params()).toString());
    $('#btnPrint').on('click', () => window.open('/reports/headcount/print?' + new URLSearchParams(params()).toString(), '_blank'));
    load();
});
</script>
@endsection
