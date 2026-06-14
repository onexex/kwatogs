@extends('layout.app', ['title' => 'Overtime Report'])
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
        padding:.5rem 1rem; font-size:.8rem; font-weight:700; cursor:pointer; transition:all .2s; white-space:nowrap; }
    .btn-ghost:hover { background:var(--teal-light); border-color:var(--teal-mid); color:var(--teal); }
    .rpt-table thead th { position:sticky; top:0; z-index:5; background:#f8fafc; font-size:.68rem; font-weight:700;
        color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; border-bottom:2px solid var(--border); white-space:nowrap; padding:11px 14px; }
    .rpt-table tbody td { font-size:.82rem; color:var(--slate); vertical-align:middle; padding:10px 14px; }
    .rpt-table tbody tr:hover { background:var(--teal-light); }
    .rpt-table tfoot td { background:var(--teal-light); font-weight:700; color:var(--teal-dark); padding:11px 14px; border-top:2px solid var(--teal); }
    .pagination .page-item.active .page-link { background:var(--teal); border-color:var(--teal); }
    .pagination .page-link { color:var(--teal); }
</style>

<div class="rpt-shell">
    <div class="rpt-topbar">
        <p class="page-title">Overtime Report</p>
        <p class="page-sub">Filter and review employee overtime records</p>
    </div>

    <div class="sc">
        <div class="sc-body" style="padding:16px 22px;">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="field-label">Date From</label>
                    <input type="date" id="fltFrom" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Date To</label>
                    <input type="date" id="fltTo" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Department</label>
                    <select id="fltDept" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->dep_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Status</label>
                    <select id="fltStatus" class="form-select form-select-sm">
                        <option value="all">All</option>
                        <option value="FORAPPROVAL">For Approval</option>
                        <option value="APPROVED">Approved</option>
                        <option value="APPROVEDBYCFO">Approved by CFO</option>
                        <option value="DISAPPROVED">Disapproved</option>
                        <option value="CANCELED">Canceled</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="field-label">Search</label>
                    <input type="text" id="fltSearch" class="form-control form-control-sm" placeholder="Name or ID...">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button class="btn-filter flex-fill" id="btnFilter"><i class="fa fa-search me-1"></i> Filter</button>
                    <button class="btn-ghost" id="btnPrint"><i class="fa fa-print"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-user-clock"></i></div>
            <h5 class="sc-title">Overtime Records</h5>
        </div>
        <div class="table-responsive" style="max-height:72vh; overflow:auto;">
            <table class="table table-hover align-middle mb-0 rpt-table">
                <thead>
                    <tr>
                        <th class="ps-3">Employee</th>
                        <th>Dept</th>
                        <th>Date From</th>
                        <th>Date To</th>
                        <th class="text-end">Hours</th>
                        <th class="text-end">Pay</th>
                        <th>Purpose</th>
                        <th class="text-center pe-3">Status</th>
                    </tr>
                </thead>
                <tbody id="tbl"><tr><td colspan="8" class="text-center text-muted py-4">Set filters and click Filter.</td></tr></tbody>
                <tfoot id="tfoot"></tfoot>
            </table>
        </div>
        <div id="pager" class="p-2"></div>
    </div>
</div>

<script>
$(function () {
    const peso = n => Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const badge = s => {
        const m = { APPROVED:'success', APPROVEDBYCFO:'primary', DISAPPROVED:'danger', FORAPPROVAL:'warning text-dark', CANCELED:'secondary' };
        return `<span class="badge bg-${m[s] || 'light text-dark'}">${(s||'').replace(/([A-Z])/g,' $1').trim()}</span>`;
    };
    const params = () => ({
        date_from: $('#fltFrom').val(), date_to: $('#fltTo').val(),
        department_id: $('#fltDept').val() || 'all', status: $('#fltStatus').val() || 'all',
        search: $('#fltSearch').val(),
    });

    function load(page = 1) {
        $('#tbl').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        $('#tfoot').html('');
        axios.get('/reports/overtime/fetch', { params: { ...params(), page } }).then(res => {
            const d = res.data, rows = d.data || [];
            if (!rows.length) { $('#tbl').html('<tr><td colspan="8" class="text-center text-muted py-4">No overtime records found.</td></tr>'); $('#pager').html(''); return; }
            let h = '', th = 0, tp = 0;
            rows.forEach(r => {
                th += Number(r.total_hrs || 0); tp += Number(r.total_pay || 0);
                h += `<tr>
                    <td class="ps-3"><span class="fw-bold text-uppercase">${r.employee_name || ''}</span><br><span class="text-muted">${r.employee_id}</span></td>
                    <td>${r.department_name ?? '—'}</td>
                    <td>${(r.date_from||'').substring(0,10)}</td>
                    <td>${(r.date_to||'').substring(0,10)}</td>
                    <td class="text-end">${Number(r.total_hrs||0).toFixed(2)}</td>
                    <td class="text-end">${peso(r.total_pay)}</td>
                    <td class="text-truncate" style="max-width:180px;">${r.purpose ?? ''}</td>
                    <td class="text-center pe-3">${badge(r.status)}</td>
                </tr>`;
            });
            $('#tbl').html(h);
            $('#tfoot').html(`<tr><td colspan="4" class="text-end">Page Totals:</td><td class="text-end">${th.toFixed(2)}</td><td class="text-end">${peso(tp)}</td><td colspan="2"></td></tr>`);
            pager(d.last_page || 1, d.current_page || 1);
        }).catch(() => $('#tbl').html('<tr><td colspan="8" class="text-center text-danger py-4">Failed to load.</td></tr>'));
    }

    function pager(last, cur) {
        const c = document.getElementById('pager');
        if (last <= 1) { c.innerHTML = ''; return; }
        const it = (l, p, o) => (o && o.disabled) ? `<li class="page-item disabled"><span class="page-link">${l}</span></li>` : `<li class="page-item ${o && o.active ? 'active' : ''}"><a href="#" class="page-link pg" data-page="${p}">${l}</a></li>`;
        const win = 1, pages = [];
        for (let i = 1; i <= last; i++) if (i === 1 || i === last || (i >= cur - win && i <= cur + win)) pages.push(i);
        let h = '<nav><ul class="pagination pagination-sm justify-content-end mb-0 gap-1">' + it('&lsaquo;', cur - 1, { disabled: cur <= 1 });
        let prev = 0; pages.forEach(i => { if (prev && i - prev > 1) h += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>'; h += it(i, i, { active: i === cur }); prev = i; });
        h += it('&rsaquo;', cur + 1, { disabled: cur >= last }) + '</ul></nav>';
        c.innerHTML = h;
    }

    $('#btnFilter').on('click', () => load(1));
    $('#fltSearch').on('keyup', e => { if (e.key === 'Enter') load(1); });
    $('#fltFrom,#fltTo,#fltDept,#fltStatus').on('change', () => load(1));
    $(document).on('click', '.pg', function (e) { e.preventDefault(); if ($(this).closest('.page-item').hasClass('disabled')) return; const p = parseInt($(this).data('page'), 10); if (p) load(p); });
    $('#btnPrint').on('click', () => window.open('/reports/overtime/print?' + new URLSearchParams(params()).toString(), '_blank'));
});
</script>
@endsection
