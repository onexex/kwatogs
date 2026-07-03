@extends('layout.app', ['title' => 'Disciplinary Notices'])
@section('content')

@include('pages.reports.partials.styles')

<div class="rpt-shell">
    <div class="rpt-topbar">
        <p class="page-title"><i class="fa fa-triangle-exclamation me-2" style="color:var(--teal)"></i>Disciplinary Notices Summary</p>
        <p class="page-sub">Per-employee memo &amp; disciplinary tally with escalation flags, plus pending suspension recommendations.</p>
    </div>

    <div class="sc">
        <div class="sc-body" style="padding:16px 22px;">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="field-label">Year</label>
                    <select id="fltYear" class="form-select form-select-sm">
                        <option value="all">All years</option>
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
                <div class="col-6 col-md-3">
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
        <div class="stat"><div class="l">Employees</div><div class="v" id="kEmp">0</div></div>
        <div class="stat"><div class="l">Disciplinary</div><div class="v" id="kDisc">0</div></div>
        <div class="stat"><div class="l">Memos</div><div class="v" id="kMemo">0</div></div>
        <div class="stat"><div class="l">Over-Limit</div><div class="v" id="kOver">0</div></div>
        <div class="stat"><div class="l">At Risk</div><div class="v" id="kRisk">0</div></div>
        <div class="stat"><div class="l">Pending Recs</div><div class="v" id="kRec">0</div></div>
    </div>

    <div class="sc" id="recsCard" style="display:none;">
        <div class="sc-head"><div class="sc-icon" style="background:#fee2e2;color:#991b1b"><i class="fa fa-gavel"></i></div><h5 class="sc-title">Pending Suspension Recommendations</h5></div>
        <div class="table-responsive"><table class="table align-middle mb-0 rpt-table">
            <thead><tr><th class="ps-3">Employee</th><th class="text-center">Notices</th><th>Reason</th><th class="pe-3">Recommended</th></tr></thead>
            <tbody id="tblRecs"></tbody>
        </table></div>
    </div>

    <div class="sc">
        <div class="sc-head"><div class="sc-icon"><i class="fa fa-triangle-exclamation"></i></div><h5 class="sc-title">Notices by Employee</h5></div>
        <div class="table-responsive" style="max-height:58vh; overflow:auto;">
            <table class="table table-hover align-middle mb-0 rpt-table">
                <thead>
                    <tr>
                        <th class="ps-3">Employee</th>
                        <th>Department</th>
                        <th class="text-end">Disciplinary</th>
                        <th class="text-end">Active</th>
                        <th class="text-end">Memos</th>
                        <th class="text-end">Void</th>
                        <th class="text-center pe-3">Status</th>
                    </tr>
                </thead>
                <tbody id="tbl"><tr><td colspan="7" class="text-center text-muted py-4">Click Filter to load.</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(function () {
    const params = () => ({
        year: $('#fltYear').val(), company_id: $('#fltCompany').val() || 'all',
        department_id: $('#fltDept').val() || 'all', search: $('#fltSearch').val(),
    });
    const escBadge = e => e === 'over' ? '<span class="pill" style="background:#fee2e2;color:#991b1b">Over-limit</span>'
        : (e === 'at_risk' ? '<span class="pill" style="background:#fef9c3;color:#854d0e">At risk</span>'
        : '<span class="pill" style="background:#dcfce7;color:#166534">OK</span>');

    function load() {
        $('#tbl').html('<tr><td colspan="7" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        axios.get('/reports/notices/fetch', { params: params() }).then(res => {
            const d = res.data, rows = d.data || [], recs = d.recommendations || [], s = d.stats || {};
            $('#kEmp').text(s.employees || 0); $('#kDisc').text(s.disciplinary || 0); $('#kMemo').text(s.memos || 0);
            $('#kOver').text(s.over || 0); $('#kRisk').text(s.at_risk || 0); $('#kRec').text(s.pending_recs || 0);

            if (recs.length) {
                $('#recsCard').show();
                $('#tblRecs').html(recs.map(r => `<tr>
                    <td class="ps-3"><span class="fw-bold text-uppercase">${r.employee_name}</span><br><span class="text-muted">${r.employee_id}</span></td>
                    <td class="text-center"><span class="pill">${r.notice_count}</span></td>
                    <td>${r.reason || '—'}</td>
                    <td class="pe-3 text-muted">${r.recommended_at ? new Date(r.recommended_at).toLocaleDateString('en-PH') : '—'}</td></tr>`).join(''));
            } else { $('#recsCard').hide(); }

            if (!rows.length) { $('#tbl').html('<tr><td colspan="7" class="text-center text-muted py-4">No notices found.</td></tr>'); return; }
            $('#tbl').html(rows.map(r => `<tr>
                <td class="ps-3"><span class="fw-bold text-uppercase">${r.employee_name}</span><br><span class="text-muted">${r.employee_id}</span></td>
                <td>${r.department_name || '—'}</td>
                <td class="text-end mono">${r.disciplinary}</td>
                <td class="text-end mono fw-bold">${r.active_disciplinary}</td>
                <td class="text-end mono">${r.memos}</td>
                <td class="text-end mono">${r.voided}</td>
                <td class="text-center pe-3">${escBadge(r.escalation)}</td></tr>`).join(''));
        }).catch(() => $('#tbl').html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load.</td></tr>'));
    }

    $('#btnFilter').on('click', load);
    $('#fltSearch').on('keyup', e => { if (e.key === 'Enter') load(); });
    $('#fltYear,#fltCompany,#fltDept').on('change', load);
    $('#btnExport').on('click', () => window.location = '/reports/notices/export?' + new URLSearchParams(params()).toString());
    $('#btnPrint').on('click', () => window.open('/reports/notices/print?' + new URLSearchParams(params()).toString(), '_blank'));
    load();
});
</script>
@endsection
