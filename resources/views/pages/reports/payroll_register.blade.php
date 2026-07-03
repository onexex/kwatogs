@extends('layout.app', ['title' => 'Payroll Register'])
@section('content')

@include('pages.reports.partials.styles')

<div class="rpt-shell">
    <div class="rpt-topbar">
        <p class="page-title"><i class="fa fa-table-list me-2" style="color:var(--teal)"></i>Payroll Register</p>
        <p class="page-sub">Full earnings &rarr; deductions &rarr; net breakdown for every employee on a pay run. Figures come from the generated payroll.</p>
    </div>

    <div class="sc">
        <div class="sc-body" style="padding:16px 22px;">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="field-label">Pay Date</label>
                    <select id="fltPayDate" class="form-select form-select-sm">
                        @forelse ($payDates as $pd)
                            <option value="{{ \Illuminate\Support\Carbon::parse($pd)->format('Y-m-d') }}">{{ \Illuminate\Support\Carbon::parse($pd)->format('M d, Y') }}</option>
                        @empty
                            <option value="">No payroll runs</option>
                        @endforelse
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
                    <label class="field-label">Classification</label>
                    <select id="fltClass" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach ($classifications as $cl)<option value="{{ $cl->class_code }}">{{ $cl->class_desc }}</option>@endforeach
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
        <div class="stat"><div class="l">Gross Pay</div><div class="v" id="kGross">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Total Deductions</div><div class="v" id="kDed">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Net Pay</div><div class="v" id="kNet">&#8369;0.00</div></div>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-table-list"></i></div>
            <h5 class="sc-title">Payroll Register &mdash; <span id="lblPeriod">—</span></h5>
        </div>
        <div class="table-responsive" style="max-height:64vh; overflow:auto;">
            <table class="table table-hover align-middle mb-0 rpt-table" style="min-width:1400px;">
                <thead>
                    <tr>
                        <th class="ps-3">Employee</th>
                        <th class="text-end">Basic</th>
                        <th class="text-end">Allow.</th>
                        <th class="text-end">OT</th>
                        <th class="text-end">Holiday</th>
                        <th class="text-end">N.Diff</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Late/UT</th>
                        <th class="text-end">SSS</th>
                        <th class="text-end">PhIC</th>
                        <th class="text-end">HDMF</th>
                        <th class="text-end">Tax</th>
                        <th class="text-end">Loans</th>
                        <th class="text-end">Other</th>
                        <th class="text-end">Tot.Ded</th>
                        <th class="text-end pe-3">Net Pay</th>
                    </tr>
                </thead>
                <tbody id="tbl"><tr><td colspan="16" class="text-center text-muted py-4">Pick a pay date and click Filter.</td></tr></tbody>
                <tfoot id="tfoot"></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
$(function () {
    const peso = n => Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const P = n => '₱' + peso(n);
    const params = () => ({
        pay_date: $('#fltPayDate').val(),
        company_id: $('#fltCompany').val() || 'all',
        department_id: $('#fltDept').val() || 'all',
        classification_id: $('#fltClass').val() || 'all',
        search: $('#fltSearch').val(),
    });

    function load() {
        $('#tbl').html('<tr><td colspan="16" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        $('#tfoot').html('');
        axios.get('/reports/payroll-register/fetch', { params: params() }).then(res => {
            const d = res.data, rows = d.data || [], t = d.totals || {};
            $('#kEmp').text(d.count || 0);
            $('#kGross').text(P(t.gross_pay)); $('#kDed').text(P(t.total_ded)); $('#kNet').text(P(t.net));
            $('#lblPeriod').text(d.pay_date ? new Date(d.pay_date).toLocaleDateString('en-PH', {year:'numeric',month:'short',day:'numeric'}) : '—');
            if (!rows.length) { $('#tbl').html('<tr><td colspan="16" class="text-center text-muted py-4">No payroll rows for this run.</td></tr>'); return; }
            let h = '';
            rows.forEach(r => {
                h += `<tr>
                    <td class="ps-3"><span class="fw-bold text-uppercase">${r.employee_name || ''}</span><br><span class="text-muted">${r.employee_id} · ${r.classification || ''}</span></td>
                    <td class="text-end mono">${peso(r.basic_salary)}</td>
                    <td class="text-end mono">${peso(r.allowances)}</td>
                    <td class="text-end mono">${peso(r.overtime_pay)}</td>
                    <td class="text-end mono">${peso(r.holiday_pay)}</td>
                    <td class="text-end mono">${peso(r.night_diff_pay)}</td>
                    <td class="text-end mono fw-bold">${peso(r.gross_pay)}</td>
                    <td class="text-end mono">${peso(r.tardiness)}</td>
                    <td class="text-end mono">${peso(r.sss_contribution)}</td>
                    <td class="text-end mono">${peso(r.philhealth_contribution)}</td>
                    <td class="text-end mono">${peso(r.pagibig_contribution)}</td>
                    <td class="text-end mono">${peso(r.withholding_tax)}</td>
                    <td class="text-end mono">${peso(r.loans)}</td>
                    <td class="text-end mono">${peso(r.other_ded)}</td>
                    <td class="text-end mono">${peso(r.total_ded)}</td>
                    <td class="text-end pe-3 mono fw-bold" style="color:var(--teal-dark)">${peso(r.net)}</td>
                </tr>`;
            });
            $('#tbl').html(h);
            $('#tfoot').html(`<tr>
                <td class="text-end">TOTAL (${d.count})</td>
                <td class="text-end mono">${peso(t.basic_salary)}</td>
                <td class="text-end mono">${peso(t.allowances)}</td>
                <td class="text-end mono">${peso(t.overtime_pay)}</td>
                <td class="text-end mono">${peso(t.holiday_pay)}</td>
                <td class="text-end mono">${peso(t.night_diff_pay)}</td>
                <td class="text-end mono">${peso(t.gross_pay)}</td>
                <td class="text-end mono">${peso(t.tardiness)}</td>
                <td class="text-end mono">${peso(t.sss_contribution)}</td>
                <td class="text-end mono">${peso(t.philhealth_contribution)}</td>
                <td class="text-end mono">${peso(t.pagibig_contribution)}</td>
                <td class="text-end mono">${peso(t.withholding_tax)}</td>
                <td class="text-end mono">${peso(t.loans)}</td>
                <td class="text-end mono">${peso(t.other_ded)}</td>
                <td class="text-end mono">${peso(t.total_ded)}</td>
                <td class="text-end pe-3 mono">${peso(t.net)}</td>
            </tr>`);
        }).catch(() => $('#tbl').html('<tr><td colspan="16" class="text-center text-danger py-4">Failed to load.</td></tr>'));
    }

    $('#btnFilter').on('click', load);
    $('#fltSearch').on('keyup', e => { if (e.key === 'Enter') load(); });
    $('#fltPayDate,#fltCompany,#fltDept,#fltClass').on('change', load);
    $('#btnExport').on('click', () => window.location = '/reports/payroll-register/export?' + new URLSearchParams(params()).toString());
    $('#btnPrint').on('click', () => window.open('/reports/payroll-register/print?' + new URLSearchParams(params()).toString(), '_blank'));
    load();
});
</script>
@endsection
