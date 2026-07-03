@extends('layout.app', ['title' => 'Final Pay Computation'])
@section('content')

@include('pages.reports.partials.styles')

<div class="rpt-shell">
    <div class="rpt-topbar">
        <p class="page-title"><i class="fa fa-money-check-dollar me-2" style="color:var(--teal)"></i>Final Pay / Last Pay Computation</p>
        <p class="page-sub">Estimate for separated employees &mdash; pro-rated 13th month + unused-leave conversion. Last salary, tax refund &amp; other items are added manually per policy.</p>
    </div>

    <div class="sc">
        <div class="sc-body" style="padding:16px 22px;">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="field-label">Separation Year</label>
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
        <div class="stat"><div class="l">Separated Employees</div><div class="v" id="kCount">0</div></div>
        <div class="stat"><div class="l">Pro-rated 13th Month</div><div class="v" id="k13">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Leave Conversion</div><div class="v" id="kLeave">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Est. Final Pay</div><div class="v" id="kFinal">&#8369;0.00</div></div>
    </div>

    <div class="sc">
        <div class="sc-head"><div class="sc-icon"><i class="fa fa-money-check-dollar"></i></div><h5 class="sc-title">Final Pay Estimate</h5>
            <span class="pill ms-auto" style="background:#fef9c3;color:#854d0e">Estimate — verify per policy</span>
        </div>
        <div class="table-responsive" style="max-height:64vh; overflow:auto;">
            <table class="table table-hover align-middle mb-0 rpt-table" style="min-width:1100px;">
                <thead>
                    <tr>
                        <th class="ps-3">Employee</th>
                        <th>Separated</th>
                        <th class="text-end">Years</th>
                        <th class="text-end">Daily Rate</th>
                        <th class="text-end">Basic Earned</th>
                        <th class="text-end">13th (Pro-rated)</th>
                        <th class="text-end">Leave Bal</th>
                        <th class="text-end">Leave Conv.</th>
                        <th class="text-end pe-3">Est. Final Pay</th>
                    </tr>
                </thead>
                <tbody id="tbl"><tr><td colspan="9" class="text-center text-muted py-4">Click Filter to load.</td></tr></tbody>
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
        year: $('#fltYear').val(), company_id: $('#fltCompany').val() || 'all',
        department_id: $('#fltDept').val() || 'all', search: $('#fltSearch').val(),
    });

    function load() {
        $('#tbl').html('<tr><td colspan="9" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        $('#tfoot').html('');
        axios.get('/reports/final-pay/fetch', { params: params() }).then(res => {
            const d = res.data, rows = d.data || [], s = d.stats || {};
            $('#kCount').text(s.count || 0); $('#k13').text(P(s.th13)); $('#kLeave').text(P(s.leave_conv)); $('#kFinal').text(P(s.estimated));
            if (!rows.length) { $('#tbl').html('<tr><td colspan="9" class="text-center text-muted py-4">No separated employees for this filter.</td></tr>'); return; }
            $('#tbl').html(rows.map(r => `<tr>
                <td class="ps-3"><span class="fw-bold text-uppercase">${r.employee_name}</span><br><span class="text-muted">${r.employee_id} · ${r.department_name || ''}</span></td>
                <td>${r.separation_date ? new Date(r.separation_date).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'2-digit'}) : '—'}${r.separation_reason ? '<br><span class="text-muted" style="font-size:.7rem">'+r.separation_reason+'</span>' : ''}</td>
                <td class="text-end mono">${r.years_rendered != null ? Number(r.years_rendered).toFixed(2) : '—'}</td>
                <td class="text-end mono">${peso(r.daily_rate)}</td>
                <td class="text-end mono">${peso(r.basic_earned)}</td>
                <td class="text-end mono">${peso(r.prorated_13th)}</td>
                <td class="text-end mono">${peso(r.leave_balance)}</td>
                <td class="text-end mono">${peso(r.leave_conversion)}</td>
                <td class="text-end pe-3 mono fw-bold" style="color:var(--teal-dark)">${peso(r.estimated_final)}</td></tr>`).join(''));
            $('#tfoot').html(`<tr><td class="text-end" colspan="5">TOTAL (${s.count})</td><td class="text-end mono">${peso(s.th13)}</td><td></td><td class="text-end mono">${peso(s.leave_conv)}</td><td class="text-end pe-3 mono">${peso(s.estimated)}</td></tr>`);
        }).catch(() => $('#tbl').html('<tr><td colspan="9" class="text-center text-danger py-4">Failed to load.</td></tr>'));
    }

    $('#btnFilter').on('click', load);
    $('#fltSearch').on('keyup', e => { if (e.key === 'Enter') load(); });
    $('#fltYear,#fltCompany,#fltDept').on('change', load);
    $('#btnExport').on('click', () => window.location = '/reports/final-pay/export?' + new URLSearchParams(params()).toString());
    $('#btnPrint').on('click', () => window.open('/reports/final-pay/print?' + new URLSearchParams(params()).toString(), '_blank'));
    load();
});
</script>
@endsection
