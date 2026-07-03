@extends('layout.app', ['title' => 'Payroll Journal'])
@section('content')

@include('pages.reports.partials.styles')

<div class="rpt-shell">
    <div class="rpt-topbar">
        <p class="page-title"><i class="fa fa-book me-2" style="color:var(--teal)"></i>Payroll Journal &mdash; GL Summary</p>
        <p class="page-sub">Per-department cost breakdown and the balanced journal entry for a pay run &mdash; for posting to the general ledger.</p>
    </div>

    <div class="sc">
        <div class="sc-body" style="padding:16px 22px;">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="field-label">Pay Date</label>
                    <select id="fltPayDate" class="form-select form-select-sm">
                        @forelse ($payDates as $pd)
                            <option value="{{ \Illuminate\Support\Carbon::parse($pd)->format('Y-m-d') }}">{{ \Illuminate\Support\Carbon::parse($pd)->format('M d, Y') }}</option>
                        @empty
                            <option value="">No payroll runs</option>
                        @endforelse
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
        <div class="stat"><div class="l">Departments</div><div class="v" id="kDept">0</div></div>
        <div class="stat"><div class="l">Gross Pay</div><div class="v" id="kGross">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Employer Share</div><div class="v" id="kEr">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Net Pay</div><div class="v" id="kNet">&#8369;0.00</div></div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="sc mb-0">
                <div class="sc-head">
                    <div class="sc-icon"><i class="fa fa-sitemap"></i></div>
                    <h5 class="sc-title">By Department &mdash; <span id="lblPeriod">—</span></h5>
                </div>
                <div class="table-responsive" style="max-height:60vh; overflow:auto;">
                    <table class="table table-hover align-middle mb-0 rpt-table">
                        <thead>
                            <tr>
                                <th class="ps-3">Department</th>
                                <th class="text-center">Heads</th>
                                <th class="text-end">Gross</th>
                                <th class="text-end">EE Ded.</th>
                                <th class="text-end">ER Share</th>
                                <th class="text-end pe-3">Net</th>
                            </tr>
                        </thead>
                        <tbody id="tbl"><tr><td colspan="6" class="text-center text-muted py-4">Pick a pay date and click Filter.</td></tr></tbody>
                        <tfoot id="tfoot"></tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-5">
            <div class="sc mb-0">
                <div class="sc-head">
                    <div class="sc-icon"><i class="fa fa-scale-balanced"></i></div>
                    <h5 class="sc-title">Journal Entry</h5>
                    <span class="pill ms-auto" id="lblBalance">—</span>
                </div>
                <div style="padding:6px 0;">
                    <table class="table align-middle mb-0 rpt-table">
                        <thead><tr><th class="ps-3">Account</th><th class="text-end">Debit</th><th class="text-end pe-3">Credit</th></tr></thead>
                        <tbody id="jrn"><tr><td colspan="3" class="text-center text-muted py-4">—</td></tr></tbody>
                        <tfoot id="jrnFoot"></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    const peso = n => Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const P = n => '₱' + peso(n);
    const params = () => ({ pay_date: $('#fltPayDate').val(), company_id: $('#fltCompany').val() || 'all' });

    function load() {
        $('#tbl').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        $('#tfoot,#jrn,#jrnFoot').html('');
        axios.get('/reports/payroll-journal/fetch', { params: params() }).then(res => {
            const d = res.data, deps = d.departments || [], g = d.grand || {}, j = d.journal || {};
            const er = (g.er_sss || 0) + (g.er_phic || 0) + (g.er_hdmf || 0);
            $('#kDept').text(deps.length); $('#kGross').text(P(g.gross)); $('#kEr').text(P(er)); $('#kNet').text(P(g.net));
            $('#lblPeriod').text(d.pay_date ? new Date(d.pay_date).toLocaleDateString('en-PH', {year:'numeric',month:'short',day:'numeric'}) : '—');

            if (!deps.length) { $('#tbl').html('<tr><td colspan="6" class="text-center text-muted py-4">No payroll for this run.</td></tr>'); return; }
            let h = '';
            deps.forEach(r => {
                const eeDed = (r.ee_sss||0)+(r.ee_phic||0)+(r.ee_hdmf||0)+(r.wtax||0)+(r.loans||0);
                const rEr = (r.er_sss||0)+(r.er_phic||0)+(r.er_hdmf||0);
                h += `<tr>
                    <td class="ps-3 fw-bold">${r.department_name}</td>
                    <td class="text-center"><span class="pill">${r.headcount}</span></td>
                    <td class="text-end mono">${peso(r.gross)}</td>
                    <td class="text-end mono">${peso(eeDed)}</td>
                    <td class="text-end mono">${peso(rEr)}</td>
                    <td class="text-end pe-3 mono fw-bold" style="color:var(--teal-dark)">${peso(r.net)}</td>
                </tr>`;
            });
            $('#tbl').html(h);
            const gEeDed = (g.ee_sss||0)+(g.ee_phic||0)+(g.ee_hdmf||0)+(g.wtax||0)+(g.loans||0);
            $('#tfoot').html(`<tr><td class="text-end">TOTAL</td><td class="text-center">${g.headcount||0}</td><td class="text-end mono">${peso(g.gross)}</td><td class="text-end mono">${peso(gEeDed)}</td><td class="text-end mono">${peso(er)}</td><td class="text-end pe-3 mono">${peso(g.net)}</td></tr>`);

            // Journal
            let jr = '';
            Object.entries(j.debit || {}).forEach(([a, v]) => {
                jr += `<tr><td class="ps-3">${a}</td><td class="text-end mono">${peso(v)}</td><td class="pe-3"></td></tr>`;
            });
            Object.entries(j.credit || {}).forEach(([a, v]) => {
                jr += `<tr><td class="ps-3" style="padding-left:2rem!important;color:var(--slate-light)">${a}</td><td></td><td class="text-end pe-3 mono">${peso(v)}</td></tr>`;
            });
            $('#jrn').html(jr);
            $('#jrnFoot').html(`<tr><td class="ps-3 text-end">TOTAL</td><td class="text-end mono">${peso(j.total_debit)}</td><td class="text-end pe-3 mono">${peso(j.total_credit)}</td></tr>`);
            const balanced = Math.abs((j.total_debit||0) - (j.total_credit||0)) < 0.01;
            $('#lblBalance').text(balanced ? 'Balanced ✓' : 'Out of balance').css('background', balanced ? '#dcfce7' : '#fee2e2').css('color', balanced ? '#166534' : '#991b1b');
        }).catch(() => $('#tbl').html('<tr><td colspan="6" class="text-center text-danger py-4">Failed to load.</td></tr>'));
    }

    $('#btnFilter').on('click', load);
    $('#fltPayDate,#fltCompany').on('change', load);
    $('#btnExport').on('click', () => window.location = '/reports/payroll-journal/export?' + new URLSearchParams(params()).toString());
    $('#btnPrint').on('click', () => window.open('/reports/payroll-journal/print?' + new URLSearchParams(params()).toString(), '_blank'));
    load();
});
</script>
@endsection
