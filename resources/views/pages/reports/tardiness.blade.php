@extends('layout.app', ['title' => 'Tardiness & Absences'])
@section('content')

@include('pages.reports.partials.styles')

<div class="rpt-shell">
    <div class="rpt-topbar">
        <p class="page-title"><i class="fa fa-user-xmark me-2" style="color:var(--teal)"></i>Tardiness &amp; Absences Summary</p>
        <p class="page-sub">Per-employee late minutes, undertime, absences, over-break and out-pass for a month &mdash; ranked worst-first.</p>
    </div>

    <div class="sc">
        <div class="sc-body" style="padding:16px 22px;">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="field-label">Month</label>
                    <select id="fltMonth" class="form-select form-select-sm"></select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Year</label>
                    <select id="fltYear" class="form-select form-select-sm">
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
        <div class="stat"><div class="l">Employees Flagged</div><div class="v" id="kEmp">0</div></div>
        <div class="stat"><div class="l">Total Late (min)</div><div class="v" id="kLate">0</div></div>
        <div class="stat"><div class="l">Total UT (min)</div><div class="v" id="kUT">0</div></div>
        <div class="stat"><div class="l">Total Absences</div><div class="v" id="kAbs">0</div></div>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-user-xmark"></i></div>
            <h5 class="sc-title">Tardiness &amp; Absences &mdash; <span id="lblPeriod">—</span></h5>
        </div>
        <div class="table-responsive" style="max-height:64vh; overflow:auto;">
            <table class="table table-hover align-middle mb-0 rpt-table">
                <thead>
                    <tr>
                        <th class="ps-3">Employee</th>
                        <th>Department</th>
                        <th class="text-end">Late (min)</th>
                        <th class="text-end">Late Days</th>
                        <th class="text-end">UT (min)</th>
                        <th class="text-end">UT Days</th>
                        <th class="text-end">Absences</th>
                        <th class="text-end">Over-Break</th>
                        <th class="text-end pe-3">Out-Pass</th>
                    </tr>
                </thead>
                <tbody id="tbl"><tr><td colspan="9" class="text-center text-muted py-4">Pick a period and click Filter.</td></tr></tbody>
                <tfoot id="tfoot"></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
$(function () {
    const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const now = new Date();
    MONTHS.forEach((m, i) => $('#fltMonth').append(`<option value="${i + 1}" ${i === now.getMonth() ? 'selected' : ''}>${m}</option>`));

    const params = () => ({
        month: $('#fltMonth').val(), year: $('#fltYear').val(),
        company_id: $('#fltCompany').val() || 'all', department_id: $('#fltDept').val() || 'all', search: $('#fltSearch').val(),
    });

    function load() {
        $('#tbl').html('<tr><td colspan="9" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        $('#tfoot').html('');
        axios.get('/reports/tardiness/fetch', { params: params() }).then(res => {
            const d = res.data, rows = d.data || [], s = d.stats || {};
            $('#kEmp').text(s.employees || 0); $('#kLate').text(s.late_mins || 0); $('#kUT').text(s.ut_mins || 0); $('#kAbs').text(s.absences || 0);
            $('#lblPeriod').text(d.label || '—');
            if (!rows.length) { $('#tbl').html('<tr><td colspan="9" class="text-center text-muted py-4">No tardiness or absences for this period. 🎉</td></tr>'); return; }
            let h = '';
            rows.forEach(r => {
                h += `<tr>
                    <td class="ps-3"><span class="fw-bold text-uppercase">${r.employee_name || ''}</span><br><span class="text-muted">${r.employee_id}</span></td>
                    <td>${r.department_name || '—'}</td>
                    <td class="text-end mono fw-bold" style="color:${r.late_mins > 0 ? 'var(--teal-dark)' : 'inherit'}">${r.late_mins || 0}</td>
                    <td class="text-end mono">${r.late_days || 0}</td>
                    <td class="text-end mono">${r.ut_mins || 0}</td>
                    <td class="text-end mono">${r.ut_days || 0}</td>
                    <td class="text-end mono">${r.absent_days ? '<span class="pill" style="background:#fee2e2;color:#991b1b">'+r.absent_days+'</span>' : 0}</td>
                    <td class="text-end mono">${r.over_break || 0}</td>
                    <td class="text-end pe-3 mono">${r.outpass || 0}</td>
                </tr>`;
            });
            $('#tbl').html(h);
            $('#tfoot').html(`<tr><td class="text-end" colspan="2">TOTAL (${s.employees})</td><td class="text-end mono">${s.late_mins}</td><td></td><td class="text-end mono">${s.ut_mins}</td><td></td><td class="text-end mono">${s.absences}</td><td class="text-end mono">${s.over_break}</td><td class="text-end pe-3 mono">${s.outpass}</td></tr>`);
        }).catch(() => $('#tbl').html('<tr><td colspan="9" class="text-center text-danger py-4">Failed to load.</td></tr>'));
    }

    $('#btnFilter').on('click', load);
    $('#fltSearch').on('keyup', e => { if (e.key === 'Enter') load(); });
    $('#fltMonth,#fltYear,#fltCompany,#fltDept').on('change', load);
    $('#btnExport').on('click', () => window.location = '/reports/tardiness/export?' + new URLSearchParams(params()).toString());
    $('#btnPrint').on('click', () => window.open('/reports/tardiness/print?' + new URLSearchParams(params()).toString(), '_blank'));
    load();
});
</script>
@endsection
