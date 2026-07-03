@extends('layout.app', ['title' => 'Loan Ledger'])
@section('content')

@include('pages.reports.partials.styles')

<div class="rpt-shell">
    <div class="rpt-topbar">
        <p class="page-title"><i class="fa fa-hand-holding-dollar me-2" style="color:var(--teal)"></i>Loan Ledger / Outstanding Balances</p>
        <p class="page-sub">Every company &amp; government loan and advance with principal, amount paid, and remaining balance.</p>
    </div>

    <div class="sc">
        <div class="sc-body" style="padding:16px 22px;">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="field-label">Status</label>
                    <select id="fltStatus" class="form-select form-select-sm">
                        <option value="active" selected>Active</option>
                        <option value="closed">Closed</option>
                        <option value="all">All</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Loan Type</label>
                    <select id="fltType" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach ($loanTypes as $t)<option value="{{ $t }}">{{ ucwords(str_replace(['_','/'],[' ',' / '],$t)) }}</option>@endforeach
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
        <div class="stat"><div class="l">Loans</div><div class="v" id="kCount">0</div></div>
        <div class="stat"><div class="l">Total Principal</div><div class="v" id="kPrincipal">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Total Paid</div><div class="v" id="kPaid">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Outstanding</div><div class="v" id="kOut">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Monthly (Active)</div><div class="v" id="kMonthly">&#8369;0.00</div></div>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-hand-holding-dollar"></i></div>
            <h5 class="sc-title">Loan Ledger</h5>
        </div>
        <div class="table-responsive" style="max-height:64vh; overflow:auto;">
            <table class="table table-hover align-middle mb-0 rpt-table">
                <thead>
                    <tr>
                        <th class="ps-3">Employee</th>
                        <th>Loan Type</th>
                        <th class="text-end">Principal</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th class="text-end">Monthly</th>
                        <th class="text-center">Recurring</th>
                        <th>Term</th>
                        <th class="text-center pe-3">Status</th>
                    </tr>
                </thead>
                <tbody id="tbl"><tr><td colspan="9" class="text-center text-muted py-4">Click Filter to load loans.</td></tr></tbody>
                <tfoot id="tfoot"></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
$(function () {
    const peso = n => Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const P = n => '₱' + peso(n);
    const fmtDate = s => s ? new Date(s).toLocaleDateString('en-PH', { year: '2-digit', month: 'short', day: 'numeric' }) : '—';
    const params = () => ({
        status: $('#fltStatus').val(),
        loan_type: $('#fltType').val() || 'all',
        company_id: $('#fltCompany').val() || 'all',
        department_id: $('#fltDept').val() || 'all',
        search: $('#fltSearch').val(),
    });

    function load() {
        $('#tbl').html('<tr><td colspan="9" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        $('#tfoot').html('');
        axios.get('/reports/loan-ledger/fetch', { params: params() }).then(res => {
            const d = res.data, rows = d.data || [], s = d.stats || {};
            $('#kCount').text(s.count || 0);
            $('#kPrincipal').text(P(s.principal)); $('#kPaid').text(P(s.paid));
            $('#kOut').text(P(s.outstanding)); $('#kMonthly').text(P(s.monthly));
            if (!rows.length) { $('#tbl').html('<tr><td colspan="9" class="text-center text-muted py-4">No loans match this filter.</td></tr>'); return; }
            let h = '';
            rows.forEach(r => {
                const badge = r.status === 'active'
                    ? '<span class="pill" style="background:#dcfce7;color:#166534">Active</span>'
                    : '<span class="pill">' + (r.status || '—') + '</span>';
                h += `<tr>
                    <td class="ps-3"><span class="fw-bold text-uppercase">${r.employee_name || ''}</span><br><span class="text-muted">${r.employee_id} · ${r.department_name || ''}</span></td>
                    <td>${r.type_label || ''}</td>
                    <td class="text-end mono">${peso(r.loan_amount)}</td>
                    <td class="text-end mono">${peso(r.total_paid)}</td>
                    <td class="text-end mono fw-bold" style="color:var(--teal-dark)">${peso(r.balance)}</td>
                    <td class="text-end mono">${peso(r.monthly_amortization)}</td>
                    <td class="text-center">${r.is_recurring ? '<i class="fa fa-repeat" style="color:var(--teal)"></i>' : '—'}</td>
                    <td><span class="text-muted">${fmtDate(r.start_date)} – ${fmtDate(r.end_date)}</span></td>
                    <td class="text-center pe-3">${badge}</td>
                </tr>`;
            });
            $('#tbl').html(h);
            $('#tfoot').html(`<tr><td class="text-end" colspan="2">TOTAL (${s.count})</td><td class="text-end mono">${peso(s.principal)}</td><td class="text-end mono">${peso(s.paid)}</td><td class="text-end mono">${peso(s.outstanding)}</td><td class="text-end mono">${peso(s.monthly)}</td><td colspan="3"></td></tr>`);
        }).catch(() => $('#tbl').html('<tr><td colspan="9" class="text-center text-danger py-4">Failed to load.</td></tr>'));
    }

    $('#btnFilter').on('click', load);
    $('#fltSearch').on('keyup', e => { if (e.key === 'Enter') load(); });
    $('#fltStatus,#fltType,#fltCompany,#fltDept').on('change', load);
    $('#btnExport').on('click', () => window.location = '/reports/loan-ledger/export?' + new URLSearchParams(params()).toString());
    $('#btnPrint').on('click', () => window.open('/reports/loan-ledger/print?' + new URLSearchParams(params()).toString(), '_blank'));
    load();
});
</script>
@endsection
