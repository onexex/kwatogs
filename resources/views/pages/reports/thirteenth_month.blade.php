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
    .badge-s { font-size:.62rem; font-weight:800; border-radius:6px; padding:2px 7px; text-transform:uppercase; letter-spacing:.3px; white-space:nowrap; }
    .b-active { background:#e0f2f1; color:#00695c; } .b-resigned { background:#fee2e2; color:#b91c1c; } .b-eoc { background:#fef3c7; color:#92400e; }
    .b-rel { background:#dcfce7; color:#15803d; } .b-pend { background:#eef2f6; color:#64748b; } .b-half { background:#fef3c7; color:#92400e; }
    .claim-line { font-size:.66rem; color:var(--slate-light); white-space:nowrap; } .claim-line b { color:var(--slate); }
    /* coverage-quality pill accents */
    .pill.q-newhire { background:#e0f2fe; color:#0369a1; } .pill.q-partial { background:#fef3c7; color:#92400e; } .pill.q-separated { background:#fee2e2; color:#b91c1c; }
    .rpt-table thead th.sortable { cursor:pointer; user-select:none; } .rpt-table thead th.sortable:hover { color:var(--teal); }
    .rpt-table thead th.sortable .fa { opacity:.35; font-size:.6rem; margin-left:3px; } .rpt-table thead th.sortable.asc .fa, .rpt-table thead th.sortable.desc .fa { opacity:1; color:var(--teal); }
    .btn-primary-teal { background:var(--teal-dark); color:#fff; border:none; border-radius:var(--radius-input); padding:.5rem .9rem; font-size:.8rem; font-weight:700; cursor:pointer; white-space:nowrap; }
    .btn-primary-teal:hover { background:#00524f; color:#fff; } .btn-primary-teal:disabled { opacity:.5; cursor:not-allowed; }
    /* Row locked for the chosen release portion (already advanced/paid) — not selectable. */
    tr.row-locked td:not(:last-child) { opacity:.5; } tr.row-locked .chkRow { cursor:not-allowed; }
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
                <div class="col-6 col-md-2">
                    <label class="field-label" title="Date printed on the payslip (release date)">Payout Date</label>
                    <input type="date" id="fltPayDate" class="form-control form-control-sm">
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
                <div class="col-6 col-md-2">
                    <label class="field-label" title="Separated staff are usually paid via final pay at separation">Status</label>
                    <select id="fltStatus" class="form-select form-select-sm">
                        <option value="all">All</option>
                        <option value="active">Active only</option>
                        <option value="separated">Separated only</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label" title="December view: who is owed the whole vs the remaining half">Claim</label>
                    <select id="fltClaim" class="form-select form-select-sm">
                        <option value="all">All</option>
                        <option value="unclaimed">Due Whole (no advance)</option>
                        <option value="half">Due Remaining (½ advanced)</option>
                        <option value="full">Fully Paid</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Search</label>
                    <input type="text" id="fltSearch" class="form-control form-control-sm" placeholder="Name or ID...">
                </div>
                <div class="col-12 col-md-4 d-flex gap-2 flex-wrap">
                    <button class="btn-filter flex-fill" id="btnFilter"><i class="fa fa-search me-1"></i> Filter</button>
                    <button class="btn-ghost" id="btnExport" title="Export register to Excel"><i class="fa fa-file-excel"></i></button>
                    <button class="btn-ghost" id="btnBank" title="Export bank/disbursement file"><i class="fa fa-building-columns"></i></button>
                    <button class="btn-ghost" id="btnPrint" title="Print"><i class="fa fa-print"></i></button>
                    <select id="relPortion" class="form-select form-select-sm" style="width:auto; flex:0 0 auto;" title="Which portion to record">
                        <option value="half">½ Half (advance)</option>
                        <option value="full" selected>Full / remaining</option>
                    </select>
                    <button class="btn-primary-teal" id="btnRelease" title="Record a claim for selected employees"><i class="fa fa-check-double me-1"></i> Release</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-3 flex-wrap">
        <div class="stat"><div class="l">Employees (selected)</div><div class="v" id="kEmp">0</div></div>
        <div class="stat"><div class="l">Total Basic Earned</div><div class="v" id="kBasic">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Total 13th Month</div><div class="v" id="k13">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Taxable Excess (&gt;&#8369;90k)</div><div class="v" id="kTax">&#8369;0.00</div></div>
        <div class="stat"><div class="l">Due Whole (no advance)</div><div class="v" id="kUnclaimed">0</div></div>
        <div class="stat"><div class="l">Due Remaining (&frac12;)</div><div class="v" id="kHalf">0</div></div>
        <div class="stat"><div class="l">Fully Paid</div><div class="v" id="kFull">0</div></div>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-gift"></i></div>
            <h5 class="sc-title">13th Month Computation</h5>
            <span class="pill ms-auto" id="covLabel" title="Coverage period"><i class="fa fa-calendar me-1"></i>&mdash;</span>
        </div>
        <div class="table-responsive" style="max-height:66vh; overflow:auto;">
            <table class="table table-hover align-middle mb-0 rpt-table" id="rptTable">
                <thead>
                    <tr>
                        <th class="text-center ps-3" style="width:36px"><input type="checkbox" id="chkAll" checked title="Select all"></th>
                        <th class="sortable" data-key="employee_name">Employee <i class="fa fa-sort"></i></th>
                        <th class="sortable" data-key="status_code">Status <i class="fa fa-sort"></i></th>
                        <th>Dept</th>
                        <th class="text-center sortable" data-key="months">Months <i class="fa fa-sort"></i></th>
                        <th class="text-center sortable" data-key="days_paid" title="Worked days + approved paid leave">Days Paid <i class="fa fa-sort"></i></th>
                        <th class="text-end sortable" data-key="total_basic">Total Basic Earned <i class="fa fa-sort"></i></th>
                        <th class="text-end sortable" data-key="thirteenth">13th Month Pay <i class="fa fa-sort"></i></th>
                        <th class="text-end sortable" data-key="taxable">Taxable Excess <i class="fa fa-sort"></i></th>
                        <th class="sortable" data-key="claim_status">Claim (½ / Full) <i class="fa fa-sort"></i></th>
                        <th class="text-end sortable" data-key="balance">Balance Due <i class="fa fa-sort"></i></th>
                        <th class="text-center pe-3">Slip</th>
                    </tr>
                </thead>
                <tbody id="tbl"><tr><td colspan="12" class="text-center text-muted py-4">Pick a year and click Filter.</td></tr></tbody>
                <tfoot id="tfoot"></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
$(function () {
    const peso = n => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const csrf = $('meta[name="csrf-token"]').attr('content') || '';
    const toast = (title, icon) => window.Swal
        ? Swal.fire({ toast: true, position: 'top-end', timer: 2400, timerProgressBar: true, showConfirmButton: false, icon: icon || 'success', title })
        : alert(title);
    const params = () => ({
        year: $('#fltYear').val(),
        coverage_from: $('#fltFrom').val(),
        coverage_to: $('#fltTo').val(),
        department_id: $('#fltDept').val() || 'all',
        search: $('#fltSearch').val(),
        pay_date: $('#fltPayDate').val(),
    });

    // Picking a year resets the coverage to that calendar year; the dates
    // stay editable for a custom window (e.g. Dec 1 prev yr – Nov 30).
    function setCoverageFromYear() {
        const y = $('#fltYear').val();
        $('#fltFrom').val(`${y}-01-01`);
        $('#fltTo').val(`${y}-12-31`);
    }

    let allRows = [];                 // last fetched rows (client-side sort source)
    const checked = new Set();        // employee_ids currently ticked
    let sort = { key: 'employee_name', dir: 'asc' };

    // Coverage-quality accents + tooltips (see controller enrichRows()).
    const COV = {
        full:      { cls: '',            t: 'Full coverage' },
        newhire:   { cls: 'q-newhire',   t: 'New hire — pro-rated (expected)' },
        partial:   { cls: 'q-partial',   t: 'Partial — possible payroll gap, please review' },
        separated: { cls: 'q-separated', t: 'Separated — pro-rated to exit date' },
    };
    const STA = { '1': { cls: 'b-active', t: 'Active' }, '0': { cls: 'b-resigned', t: 'Resigned' }, '2': { cls: 'b-eoc', t: 'EOC' } };

    const selectedIds = () => [...checked];

    // Whether a row can be released for the currently-chosen portion — mirrors
    // the server guard: a ½ advance is refused once already advanced/paid, and a
    // Full release is pointless once fully paid. Locked rows are grayed & can't
    // be ticked (revert first to redo). ½-claimed rows stay open for Full.
    function releasable(r) {
        return $('#relPortion').val() === 'half'
            ? r.claim_status === 'unclaimed'
            : r.claim_status !== 'full';
    }

    // Claim cell: a status badge + a who/when/amount line per portion claimed,
    // plus what is still DUE (the December view).
    function claimCell(r) {
        const line = (label, c) => c
            ? `<div class="claim-line"><b>${label}</b> ${peso(c.amount)}${c.at ? ` · ${c.at}` : ''}${c.by ? ` · ${c.by}` : ''}</div>`
            : '';
        if (r.claim_status === 'unclaimed') {
            return '<span class="badge-s b-pend">Unclaimed</span><div class="claim-line">Due: <b>WHOLE</b></div>';
        }
        const badge = r.claim_status === 'full'
            ? '<span class="badge-s b-rel">Fully paid</span>'
            : '<span class="badge-s b-half">½ claimed</span><div class="claim-line">Due: <b>remaining</b></div>';
        return `${badge}${line('½', r.claim_half)}${line('Full', r.claim_full)}`;
    }

    // Rows visible under the current status + claim filters (the December
    // "active vs separated" and "whole vs half" views).
    function baseRows() {
        const claim = $('#fltClaim').val();
        const stat = $('#fltStatus').val();
        return allRows.filter(r => {
            if (claim && claim !== 'all' && r.claim_status !== claim) return false;
            if (stat === 'active' && String(r.status_code) !== '1') return false;
            if (stat === 'separated' && String(r.status_code) === '1') return false;
            return true;
        });
    }

    // Selection always follows visibility, so Export/Print/Release act on the
    // filtered group only.
    function selectVisible() {
        checked.clear();
        baseRows().forEach(r => { if (releasable(r)) checked.add(String(r.employee_id)); });
    }

    const CLAIM_RANK = { unclaimed: 0, half: 1, full: 2 };
    function sortedRows() {
        const k = sort.key, dir = sort.dir === 'asc' ? 1 : -1;
        const numeric = ['months', 'days_paid', 'total_basic', 'thirteenth', 'taxable', 'balance', 'status_code'].includes(k);
        return baseRows().slice().sort((a, b) => {
            let x = a[k], y = b[k];
            if (k === 'claim_status') { return ((CLAIM_RANK[a.claim_status] ?? 0) - (CLAIM_RANK[b.claim_status] ?? 0)) * dir; }
            if (numeric) return ((parseFloat(x) || 0) - (parseFloat(y) || 0)) * dir;
            return String(x ?? '').localeCompare(String(y ?? '')) * dir;
        });
    }

    function render() {
        const rows = sortedRows();
        if (!rows.length) {
            const msg = allRows.length ? 'No employees match this claim filter.' : 'No payroll records found within this coverage.';
            $('#tbl').html(`<tr><td colspan="12" class="text-center text-muted py-4">${msg}</td></tr>`);
            $('#tfoot').html('');
            recomputeTotals();
            return;
        }
        let h = '';
        rows.forEach(r => {
            const slipQs = new URLSearchParams(Object.assign({}, params(), { employee_id: r.employee_id })).toString();
            const rel = releasable(r);
            if (!rel) checked.delete(String(r.employee_id));
            const isC = checked.has(String(r.employee_id));
            const cov = COV[r.coverage_flag] || COV.full;
            const sta = STA[String(r.status_code)] || { cls: 'b-pend', t: r.status_label || '—' };
            const claim = claimCell(r);
            const balDone = Math.abs(parseFloat(r.balance) || 0) < 0.005;
            const lockTitle = $('#relPortion').val() === 'half' ? 'Already advanced/paid — revert the ½ first to re-release' : 'Already fully paid';
            h += `<tr class="${rel ? '' : 'row-locked'}">
                <td class="text-center ps-3"><input type="checkbox" class="chkRow" value="${r.employee_id}" ${isC ? 'checked' : ''} ${rel ? '' : `disabled title="${lockTitle}"`}></td>
                <td><span class="fw-bold text-uppercase">${r.employee_name || ''}</span><br><span class="text-muted">${r.employee_id}</span></td>
                <td><span class="badge-s ${sta.cls}">${sta.t}</span></td>
                <td>${r.department_name ?? '—'}</td>
                <td class="text-center"><span class="pill ${cov.cls}" title="${cov.t}">${r.months}/12</span></td>
                <td class="text-center">${parseInt(r.days_paid) || 0}</td>
                <td class="text-end">${peso(r.total_basic)}</td>
                <td class="text-end fw-bold" style="color:var(--teal-dark)">${peso(r.thirteenth)}</td>
                <td class="text-end">${r.taxable > 0 ? `<span style="color:#b45309;font-weight:700">${peso(r.taxable)}</span>` : '<span class="text-muted">—</span>'}</td>
                <td>${claim}</td>
                <td class="text-end ${balDone ? '' : 'fw-bold'}" style="color:${balDone ? 'var(--teal)' : '#b45309'}">${peso(r.balance)}</td>
                <td class="text-center pe-3"><button class="btn-ghost btn-slip" data-qs="${slipQs}" title="Print 13th month payslip"><i class="fa fa-receipt"></i></button></td>
            </tr>`;
        });
        $('#tbl').html(h);
        recomputeTotals();
    }

    // Stat cards + grand-total footer reflect the TICKED subset (kReleased is a
    // whole-list count, set on load).
    function recomputeTotals() {
        const rows = baseRows();
        let basic = 0, thirteen = 0, tax = 0, bal = 0, days = 0, n = 0;
        // Claim-status breakdown reflects the FILTERED group (all visible rows),
        // not just the ticked subset — so it reacts to the Status/Claim filters.
        let whole = 0, remaining = 0, paid = 0;
        rows.forEach(r => {
            if (r.claim_status === 'full') paid++;
            else if (r.claim_status === 'half') remaining++;
            else whole++;
            if (!checked.has(String(r.employee_id))) return;
            basic += parseFloat(r.total_basic) || 0;
            thirteen += parseFloat(r.thirteenth) || 0;
            tax += parseFloat(r.taxable) || 0;
            bal += parseFloat(r.balance) || 0;
            days += parseInt(r.days_paid) || 0;
            n++;
        });
        $('#kEmp').text(n);
        $('#kBasic').text(peso(basic));
        $('#k13').text(peso(thirteen));
        $('#kTax').text(peso(tax));
        $('#kUnclaimed').text(whole);
        $('#kHalf').text(remaining);
        $('#kFull').text(paid);
        $('#tfoot').html(`<tr><td colspan="5" class="text-end">GRAND TOTAL (${n} selected):</td><td class="text-center">${days.toLocaleString('en-PH')}</td><td class="text-end">${peso(basic)}</td><td class="text-end">${peso(thirteen)}</td><td class="text-end">${peso(tax)}</td><td></td><td class="text-end">${peso(bal)}</td><td class="pe-3"></td></tr>`);
        const selectable = rows.filter(releasable).length;
        $('#chkAll').prop('checked', selectable > 0 && n === selectable).prop('indeterminate', n > 0 && n < selectable);
        $('#btnRelease').prop('disabled', n === 0);
    }

    function load() {
        $('#tbl').html('<tr><td colspan="12" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        $('#tfoot').html('');
        axios.get('/reports/thirteenth-month/fetch', { params: params() }).then(res => {
            const d = res.data;
            allRows = d.data || [];
            selectVisible(); // default: all rows in the current filter ticked
            $('#covLabel').html(`<i class="fa fa-calendar me-1"></i>${d.coverage_label || ''}`);
            // The server may normalize a bad/reversed/over-long window — reflect the effective one.
            if (d.coverage_from) $('#fltFrom').val(d.coverage_from);
            if (d.coverage_to) $('#fltTo').val(d.coverage_to);
            // Claim-status cards are computed reactively in recomputeTotals()
            // (called by render) so they track the Status/Claim filters.
            render();
        }).catch(() => {
            allRows = []; checked.clear();
            $('#tbl').html('<tr><td colspan="12" class="text-center text-danger py-4">Failed to load.</td></tr>');
            $('#tfoot').html('');
        });
    }

    // Build an Export/Print/Bank URL, appending ticked IDs when it's a real subset.
    function outUrl(base) {
        const ids = selectedIds();
        if (!ids.length) { toast('Select at least one employee.', 'warning'); return null; }
        const p = new URLSearchParams(params());
        if (ids.length !== allRows.length) ids.forEach(id => p.append('employee_ids[]', id));
        return base + '?' + p.toString();
    }

    $('#chkAll').on('change', function () {
        if (this.checked) selectVisible(); else baseRows().forEach(r => checked.delete(String(r.employee_id)));
        $('#tbl .chkRow:not(:disabled)').prop('checked', this.checked);
        recomputeTotals();
    });
    // Switching the release portion changes which rows are eligible — re-select
    // and repaint so locked rows gray out for the new portion.
    $('#relPortion').on('change', function () { selectVisible(); render(); });
    $('#tbl').on('change', '.chkRow', function () {
        if (this.checked) checked.add(String(this.value)); else checked.delete(String(this.value));
        recomputeTotals();
    });
    // Status / Claim filters — re-select the visible group so Release/Export/Print act on it.
    $('#fltStatus, #fltClaim').on('change', function () { selectVisible(); render(); });
    $('#rptTable thead').on('click', '.sortable', function () {
        const key = $(this).data('key');
        if (sort.key === key) sort.dir = sort.dir === 'asc' ? 'desc' : 'asc';
        else { sort.key = key; sort.dir = 'asc'; }
        $('#rptTable thead .sortable').removeClass('asc desc').find('.fa').attr('class', 'fa fa-sort');
        $(this).addClass(sort.dir).find('.fa').attr('class', 'fa fa-sort-' + (sort.dir === 'asc' ? 'up' : 'down'));
        render();
    });

    $('#btnFilter').on('click', load);
    $('#fltSearch').on('keyup', e => { if (e.key === 'Enter') load(); });
    $('#fltYear').on('change', () => { setCoverageFromYear(); load(); });
    $('#fltFrom,#fltTo,#fltDept').on('change', load);
    $('#btnExport').on('click', () => { const u = outUrl('/reports/thirteenth-month/export'); if (u) window.location = u; });
    $('#btnBank').on('click', () => { const u = outUrl('/reports/thirteenth-month/bank-export'); if (u) window.location = u; });
    $('#btnPrint').on('click', () => { const u = outUrl('/reports/thirteenth-month/print'); if (u) window.open(u, '_blank'); });
    $('#btnRelease').on('click', function () {
        const ids = selectedIds();
        if (!ids.length) { toast('Select at least one employee.', 'warning'); return; }
        const portion = $('#relPortion').val();
        const label = portion === 'half' ? 'the ½ HALF advance (50%)' : 'the FULL / remaining balance';
        Swal.fire({
            title: 'Record claim?',
            html: `Record <b>${label}</b> for <b>${ids.length}</b> employee(s) in this coverage year.` +
                  `<br><span style="font-size:.78rem;color:#94a3b8">Idempotent — safe to re-run.</span>`,
            icon: 'question',
            input: 'text',
            inputLabel: 'Optional batch label',
            inputPlaceholder: 'e.g. Dec 2026 or Mid-year',
            inputValue: portion === 'half' ? 'Mid-year' : '',
            showCancelButton: true,
            confirmButtonColor: '#008080',
            confirmButtonText: '<i class="fa fa-check-double me-1"></i> Record',
        }).then(result => {
            if (!result.isConfirmed) return;
            const batch = result.value || '';
            const body = new URLSearchParams(params());
            body.append('portion', portion);
            ids.forEach(id => body.append('employee_ids[]', id));
            if (batch) body.append('batch', batch);
            $('#btnRelease').prop('disabled', true);
            axios.post('/reports/thirteenth-month/release', body, { headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' } })
                .then(res => { toast(res.data.message || 'Recorded.', 'success'); load(); })
                .catch(err => {
                    Swal.fire({ icon: 'error', title: 'Failed', text: err.response?.data?.message || 'Failed to record claim.', confirmButtonColor: '#008080' });
                    $('#btnRelease').prop('disabled', false);
                });
        });
    });
    // Per-employee 13th-month payslip (opens the printable slip in a new tab).
    $('#tbl').on('click', '.btn-slip', function () {
        window.open('/reports/thirteenth-month/payslip?' + $(this).data('qs'), '_blank');
    });
    setCoverageFromYear();
    load();
});
</script>
@endsection
