@extends('layout.app', ['title' => 'Pag-IBIG Report'])
@section('content')

@include('pages.reports.partials.styles')

<div class="rpt-shell">
    <div class="rpt-topbar">
        <p class="page-title"><i class="fa fa-house-chimney me-2" style="color:var(--teal)"></i>Pag-IBIG Contribution Remittance (MCRF)</p>
        <p class="page-sub">Monthly Pag-IBIG membership contributions &amp; short-term loan (STL) amortizations per member.</p>
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
                    <input type="text" id="fltSearch" class="form-control form-control-sm" placeholder="Name / ID / MID...">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button class="btn-filter flex-fill" id="btnFilter"><i class="fa fa-search me-1"></i> Filter</button>
                    <button class="btn-ghost" id="btnExport" title="Export to Excel"><i class="fa fa-file-excel"></i></button>
                    <button class="btn-ghost" id="btnPrint" title="Print"><i class="fa fa-print"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-3 flex-wrap">
        <div class="stat"><div class="l">Members</div><div class="v" id="kEmp">0</div></div>
        <div class="stat"><div class="l">Employee Share</div><div class="v" id="kEe">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Employer Share</div><div class="v" id="kEr">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Total Contributions</div><div class="v" id="kAll">&#8369;0.00</div></div>
        <div class="stat"><div class="l">STL / Loans</div><div class="v" id="kLoan">&#8369;0.00</div></div>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-house-chimney"></i></div>
            <h5 class="sc-title">Pag-IBIG MCRF &mdash; <span id="lblPeriod">—</span></h5>
            <span class="pill ms-auto" id="lblEmployer">Pag-IBIG Employer No.: —</span>
        </div>
        <div class="table-responsive" style="max-height:64vh; overflow:auto;">
            <table class="table table-hover align-middle mb-0 rpt-table">
                <thead>
                    <tr>
                        <th class="ps-3">Pag-IBIG MID</th>
                        <th>Employee</th>
                        <th class="text-end">EE Share</th>
                        <th class="text-end">ER Share</th>
                        <th class="text-end">Total</th>
                        <th class="text-end pe-3">STL / Loan</th>
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

    const params = () => ({
        mode: 'monthly',
        month: $('#fltMonth').val(),
        year: $('#fltYear').val(),
        company_id: $('#fltCompany').val() || 'all',
        department_id: $('#fltDept').val() || 'all',
        search: $('#fltSearch').val(),
    });

    function load() {
        $('#tbl').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        $('#tfoot').html('');
        axios.get('/reports/pagibig/fetch', { params: params() }).then(res => {
            const d = res.data, rows = d.data || [];
            $('#kEmp').text(d.count || 0);
            $('#kEe').text(peso(d.total_ee)); $('#kEr').text(peso(d.total_er));
            $('#kAll').text(peso(d.total_all)); $('#kLoan').text(peso(d.total_loan));
            $('#lblPeriod').text(d.label || '—');
            $('#lblEmployer').text('Pag-IBIG Employer No.: ' + (d.employer_no || '—'));
            if (!rows.length) { $('#tbl').html('<tr><td colspan="6" class="text-center text-muted py-4">No Pag-IBIG contributions found for this period.</td></tr>'); return; }
            let h = '';
            rows.forEach(r => {
                h += `<tr>
                    <td class="ps-3 mono">${r.gov_id || '—'}</td>
                    <td><span class="fw-bold text-uppercase">${r.employee_name || ''}</span><br><span class="text-muted">${r.employee_id}</span></td>
                    <td class="text-end mono">${peso(r.ee)}</td>
                    <td class="text-end mono">${peso(r.er)}</td>
                    <td class="text-end fw-bold mono" style="color:var(--teal-dark)">${peso(r.total)}</td>
                    <td class="text-end pe-3 mono">${peso(r.loan)}</td>
                </tr>`;
            });
            $('#tbl').html(h);
            $('#tfoot').html(`<tr><td colspan="2" class="text-end">TOTAL (${d.count})</td><td class="text-end mono">${peso(d.total_ee)}</td><td class="text-end mono">${peso(d.total_er)}</td><td class="text-end mono">${peso(d.total_all)}</td><td class="text-end pe-3 mono">${peso(d.total_loan)}</td></tr>`);
        }).catch(() => $('#tbl').html('<tr><td colspan="6" class="text-center text-danger py-4">Failed to load.</td></tr>'));
    }

    $('#btnFilter').on('click', load);
    $('#fltSearch').on('keyup', e => { if (e.key === 'Enter') load(); });
    $('#fltMonth,#fltYear,#fltCompany,#fltDept').on('change', load);
    $('#btnExport').on('click', () => window.location = '/reports/pagibig/export?' + new URLSearchParams(params()).toString());
    $('#btnPrint').on('click', () => window.open('/reports/pagibig/print?' + new URLSearchParams(params()).toString(), '_blank'));
    load();
});
</script>
@endsection
