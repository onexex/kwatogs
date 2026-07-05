@extends('layout.app', ['title' => '13th Month Pay'])
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8; --bg:#f1f5f9;
        --surface:#fff; --border:#e2e8f0; --radius-card:14px; --radius-input:8px;
        --shadow-card:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);
    }
    .rpt-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .rpt-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:20px; }
    .rpt-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; letter-spacing:-.2px; }
    .rpt-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .sc { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); margin-bottom:20px; overflow:hidden; }
    .sc-head { display:flex; align-items:center; gap:10px; padding:14px 22px; border-bottom:1px solid var(--border);
        background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .sc-icon { width:30px; height:30px; border-radius:8px; background:var(--teal-light); color:var(--teal);
        display:flex; align-items:center; justify-content:center; font-size:.78rem; }
    .sc-title { font-size:.78rem; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; margin:0; }
    .field-label { font-size:.68rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px; display:block; }
    .form-control, .form-select { border:1.5px solid var(--border); border-radius:var(--radius-input); font-size:.83rem;
        color:var(--slate); background:#fafbfc; padding:.45rem .7rem; }
    .form-control:focus, .form-select:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
    .btn-filter { background:var(--teal); color:#fff; border:none; border-radius:var(--radius-input); padding:.5rem 1rem;
        font-size:.8rem; font-weight:700; cursor:pointer; box-shadow:0 4px 14px rgba(0,128,128,.25); transition:all .2s; white-space:nowrap; }
    .btn-filter:hover { background:var(--teal-dark); color:#fff; transform:translateY(-1px); }
    .btn-ghost { background:var(--surface); color:var(--slate-light); border:1.5px solid var(--border); border-radius:var(--radius-input);
        padding:.5rem .9rem; font-size:.8rem; font-weight:700; cursor:pointer; transition:all .2s; white-space:nowrap; }
    .btn-ghost:hover { background:var(--teal-light); border-color:var(--teal-mid); color:var(--teal); }
    .stat { flex:1; min-width:140px; background:linear-gradient(135deg,#f0fdfa,#f8fbfa); border:1px solid var(--border);
        border-radius:12px; padding:13px 16px; }
    .stat .l { font-size:.62rem; font-weight:800; color:var(--slate-light); text-transform:uppercase; letter-spacing:.5px; }
    .stat .v { font-size:1.25rem; font-weight:800; color:var(--teal-dark); margin-top:2px; }
    .rpt-table thead th { position:sticky; top:0; z-index:5; background:#f8fafc; font-size:.68rem; font-weight:700;
        color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; border-bottom:2px solid var(--border); white-space:nowrap; padding:11px 14px; }
    .rpt-table tbody td { font-size:.82rem; color:var(--slate); vertical-align:middle; padding:10px 14px; }
    .rpt-table tbody tr:hover { background:var(--teal-light); }
    .rpt-table tfoot td { background:var(--teal-light); font-weight:800; color:var(--teal-dark); padding:12px 14px; border-top:2px solid var(--teal); }
    .pill { font-size:.66rem; font-weight:700; background:#eef2f6; color:var(--slate); border-radius:6px; padding:2px 8px; }
</style>

<div class="rpt-shell">
    <div class="rpt-topbar">
        <p class="page-title"><i class="fa fa-gift me-2" style="color:var(--teal)"></i>13th Month Pay</p>
        <p class="page-sub">Total basic salary earned within the coverage period &divide; 12 &mdash; pro-rated automatically for partial-year employees. Set the coverage first (defaults to the calendar year; adjust it e.g. to Dec&ndash;Nov when paying out before Dec 24).</p>
    </div>

    <div class="sc">
        <div class="sc-body" style="padding:16px 22px;">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="field-label">Year</label>
                    <select id="fltYear" class="form-select form-select-sm">
                        @foreach ($years as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Coverage From</label>
                    <input type="date" id="fltFrom" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Coverage To</label>
                    <input type="date" id="fltTo" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3">
                    <label class="field-label">Company</label>
                    <select id="fltCompany" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach ($companies as $c)
                            <option value="{{ $c->comp_id }}">{{ $c->comp_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="field-label">Department</label>
                    <select id="fltDept" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->dep_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="field-label">Search</label>
                    <input type="text" id="fltSearch" class="form-control form-control-sm" placeholder="Name or ID...">
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button class="btn-filter flex-fill" id="btnFilter"><i class="fa fa-search me-1"></i> Filter</button>
                    <button class="btn-ghost" id="btnExport" title="Export to Excel"><i class="fa fa-file-excel"></i></button>
                    <button class="btn-ghost" id="btnPrint" title="Print"><i class="fa fa-print"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-3 flex-wrap">
        <div class="stat"><div class="l">Employees</div><div class="v" id="kEmp">0</div></div>
        <div class="stat"><div class="l">Total Basic Earned</div><div class="v" id="kBasic">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Total 13th Month</div><div class="v" id="k13">&#8369;0.00</div></div>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-gift"></i></div>
            <h5 class="sc-title">13th Month Computation</h5>
            <span class="pill ms-auto" id="covLabel" title="Coverage period"><i class="fa fa-calendar me-1"></i>&mdash;</span>
        </div>
        <div class="table-responsive" style="max-height:66vh; overflow:auto;">
            <table class="table table-hover align-middle mb-0 rpt-table">
                <thead>
                    <tr>
                        <th class="ps-3">Employee</th>
                        <th>Dept</th>
                        <th>Company</th>
                        <th class="text-center">Months</th>
                        <th class="text-end">Total Basic Earned</th>
                        <th class="text-end pe-3">13th Month Pay</th>
                    </tr>
                </thead>
                <tbody id="tbl"><tr><td colspan="6" class="text-center text-muted py-4">Pick a year and click Filter.</td></tr></tbody>
                <tfoot id="tfoot"></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
$(function () {
    const peso = n => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const params = () => ({
        year: $('#fltYear').val(),
        coverage_from: $('#fltFrom').val(),
        coverage_to: $('#fltTo').val(),
        company_id: $('#fltCompany').val() || 'all',
        department_id: $('#fltDept').val() || 'all',
        search: $('#fltSearch').val(),
    });

    // Picking a year resets the coverage to that calendar year; the dates
    // stay editable for a custom window (e.g. Dec 1 prev yr – Nov 30).
    function setCoverageFromYear() {
        const y = $('#fltYear').val();
        $('#fltFrom').val(`${y}-01-01`);
        $('#fltTo').val(`${y}-12-31`);
    }

    function load() {
        $('#tbl').html('<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        $('#tfoot').html('');
        axios.get('/reports/thirteenth-month/fetch', { params: params() }).then(res => {
            const d = res.data, rows = d.data || [];
            $('#kEmp').text(d.count || 0);
            $('#kBasic').text(peso(d.total_basic));
            $('#k13').text(peso(d.total_13th));
            $('#covLabel').html(`<i class="fa fa-calendar me-1"></i>${d.coverage_label || ''}`);
            // The server may normalize a bad/reversed/over-long window — reflect the effective one.
            if (d.coverage_from) $('#fltFrom').val(d.coverage_from);
            if (d.coverage_to) $('#fltTo').val(d.coverage_to);
            if (!rows.length) { $('#tbl').html('<tr><td colspan="6" class="text-center text-muted py-4">No payroll records found within this coverage.</td></tr>'); return; }
            let h = '';
            rows.forEach(r => {
                h += `<tr>
                    <td class="ps-3"><span class="fw-bold text-uppercase">${r.employee_name || ''}</span><br><span class="text-muted">${r.employee_id}</span></td>
                    <td>${r.department_name ?? '—'}</td>
                    <td>${r.company_name ?? '—'}</td>
                    <td class="text-center"><span class="pill">${r.months}/12</span></td>
                    <td class="text-end">${peso(r.total_basic)}</td>
                    <td class="text-end pe-3 fw-bold" style="color:var(--teal-dark)">${peso(r.thirteenth)}</td>
                </tr>`;
            });
            $('#tbl').html(h);
            $('#tfoot').html(`<tr><td colspan="4" class="text-end">GRAND TOTAL (${d.count} employees):</td><td class="text-end">${peso(d.total_basic)}</td><td class="text-end pe-3">${peso(d.total_13th)}</td></tr>`);
        }).catch(() => $('#tbl').html('<tr><td colspan="6" class="text-center text-danger py-4">Failed to load.</td></tr>'));
    }

    $('#btnFilter').on('click', load);
    $('#fltSearch').on('keyup', e => { if (e.key === 'Enter') load(); });
    $('#fltYear').on('change', () => { setCoverageFromYear(); load(); });
    $('#fltFrom,#fltTo,#fltCompany,#fltDept').on('change', load);
    $('#btnExport').on('click', () => window.location = '/reports/thirteenth-month/export?' + new URLSearchParams(params()).toString());
    $('#btnPrint').on('click', () => window.open('/reports/thirteenth-month/print?' + new URLSearchParams(params()).toString(), '_blank'));
    setCoverageFromYear();
    load();
});
</script>
@endsection
