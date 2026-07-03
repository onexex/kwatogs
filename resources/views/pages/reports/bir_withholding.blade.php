@extends('layout.app', ['title' => 'BIR Withholding Report'])
@section('content')

@include('pages.reports.partials.styles')

<div class="rpt-shell">
    <div class="rpt-topbar">
        <p class="page-title"><i class="fa fa-receipt me-2" style="color:var(--teal)"></i>BIR Withholding Tax on Compensation</p>
        <p class="page-sub">Monthly remittance (1601-C) or Annual Alphalist (1604-C &mdash; the basis for each employee's Form 2316).</p>
    </div>

    <div class="sc">
        <div class="sc-body" style="padding:16px 22px;">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="field-label">Report Type</label>
                    <select id="fltMode" class="form-select form-select-sm">
                        <option value="monthly">Monthly (1601-C)</option>
                        <option value="annual">Annual Alphalist (1604-C)</option>
                    </select>
                </div>
                <div class="col-6 col-md-2" id="wrapMonth">
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
                    <input type="text" id="fltSearch" class="form-control form-control-sm" placeholder="Name / ID / TIN...">
                </div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-12 d-flex gap-2 justify-content-end">
                    <button class="btn-filter" id="btnFilter"><i class="fa fa-search me-1"></i> Filter</button>
                    <button class="btn-ghost" id="btnExport" title="Export to Excel"><i class="fa fa-file-excel me-1"></i> Excel</button>
                    <button class="btn-ghost" id="btnPrint" title="Print"><i class="fa fa-print me-1"></i> Print</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-3 flex-wrap">
        <div class="stat"><div class="l">Employees</div><div class="v" id="kEmp">0</div></div>
        <div class="stat"><div class="l">Gross Compensation</div><div class="v" id="kGross">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Taxable</div><div class="v" id="kTaxable">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Tax Withheld</div><div class="v" id="kWtax">&#8369;0.00</div></div>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-receipt"></i></div>
            <h5 class="sc-title" id="lblForm">Withholding Tax &mdash; <span id="lblPeriod">—</span></h5>
            <span class="pill ms-auto" id="lblEmployer">Employer TIN: —</span>
        </div>
        <div class="table-responsive" style="max-height:62vh; overflow:auto;">
            <table class="table table-hover align-middle mb-0 rpt-table">
                <thead>
                    <tr>
                        <th class="ps-3">TIN</th>
                        <th>Employee</th>
                        <th class="text-end">Gross Comp</th>
                        <th class="text-end">Non-Taxable</th>
                        <th class="text-end">Taxable</th>
                        <th class="text-end pe-3">Tax Withheld</th>
                    </tr>
                </thead>
                <tbody id="tbl"><tr><td colspan="6" class="text-center text-muted py-4">Pick a period and click Filter.</td></tr></tbody>
                <tfoot id="tfoot"></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
$(function () {
    const peso = n => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const now = new Date();
    MONTHS.forEach((m, i) => $('#fltMonth').append(`<option value="${i + 1}" ${i === now.getMonth() ? 'selected' : ''}>${m}</option>`));

    function syncMode() {
        const annual = $('#fltMode').val() === 'annual';
        $('#wrapMonth').toggle(!annual);
        $('#lblForm').html((annual ? 'Alphalist (1604-C)' : 'Monthly Remittance (1601-C)') + ' &mdash; <span id="lblPeriod">' + ($('#lblPeriod').text() || '—') + '</span>');
    }

    const params = () => ({
        mode: $('#fltMode').val(),
        month: $('#fltMonth').val(),
        year: $('#fltYear').val(),
        company_id: $('#fltCompany').val() || 'all',
        department_id: $('#fltDept').val() || 'all',
        search: $('#fltSearch').val(),
    });

    function load() {
        $('#tbl').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        $('#tfoot').html('');
        axios.get('/reports/bir/fetch', { params: params() }).then(res => {
            const d = res.data, rows = d.data || [];
            $('#kEmp').text(d.count || 0);
            $('#kGross').text(peso(d.total_gross)); $('#kTaxable').text(peso(d.total_tax)); $('#kWtax').text(peso(d.total_wtax));
            $('#lblPeriod').text(d.label || '—');
            $('#lblEmployer').text('Employer TIN: ' + (d.employer_tin || '—'));
            if (!rows.length) { $('#tbl').html('<tr><td colspan="6" class="text-center text-muted py-4">No payroll records with withholding data for this period.</td></tr>'); return; }
            let h = '';
            rows.forEach(r => {
                h += `<tr>
                    <td class="ps-3 mono">${r.gov_id || '—'}</td>
                    <td><span class="fw-bold text-uppercase">${r.employee_name || ''}</span><br><span class="text-muted">${r.employee_id}</span></td>
                    <td class="text-end mono">${peso(r.gross)}</td>
                    <td class="text-end mono">${peso(r.non_taxable)}</td>
                    <td class="text-end mono">${peso(r.taxable)}</td>
                    <td class="text-end pe-3 fw-bold mono" style="color:var(--teal-dark)">${peso(r.tax)}</td>
                </tr>`;
            });
            $('#tbl').html(h);
            $('#tfoot').html(`<tr><td colspan="2" class="text-end">TOTAL (${d.count})</td><td class="text-end mono">${peso(d.total_gross)}</td><td class="text-end mono">${peso(d.total_nontax)}</td><td class="text-end mono">${peso(d.total_tax)}</td><td class="text-end pe-3 mono">${peso(d.total_wtax)}</td></tr>`);
        }).catch(() => $('#tbl').html('<tr><td colspan="6" class="text-center text-danger py-4">Failed to load.</td></tr>'));
    }

    $('#fltMode').on('change', () => { syncMode(); load(); });
    $('#btnFilter').on('click', load);
    $('#fltSearch').on('keyup', e => { if (e.key === 'Enter') load(); });
    $('#fltMonth,#fltYear,#fltCompany,#fltDept').on('change', load);
    $('#btnExport').on('click', () => window.location = '/reports/bir/export?' + new URLSearchParams(params()).toString());
    $('#btnPrint').on('click', () => window.open('/reports/bir/print?' + new URLSearchParams(params()).toString(), '_blank'));
    syncMode();
    load();
});
</script>
@endsection
