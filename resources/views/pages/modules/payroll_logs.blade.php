@extends('layout.app', ['title' => 'Payroll Logs'])
@section('content')

<style>
    /* ── Design tokens (shared with Overtime Filing / Leave Application) ── */
    :root {
        --teal:         #008080;
        --teal-dark:    #006666;
        --teal-mid:     #4db6ac;
        --teal-light:   #e0f2f1;
        --slate:        #334155;
        --slate-light:  #64748b;
        --muted:        #94a3b8;
        --bg:           #f1f5f9;
        --surface:      #ffffff;
        --border:       #e2e8f0;
        --danger:       #ef4444;
        --success:      #10b981;
        --warning:      #f59e0b;
        --radius-card:  14px;
        --radius-input: 8px;
        --shadow-card:  0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }

    /* ── Page shell ──────────────────────────────────────────── */
    .pl-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    /* ── Top header bar ──────────────────────────────────────── */
    .pl-topbar {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        padding: 16px 22px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .pl-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .pl-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }

    /* ── Section card ────────────────────────────────────────── */
    .sc {
        background: var(--surface);
        border-radius: var(--radius-card);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-card);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .sc-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 22px;
        border-bottom: 1px solid var(--border);
        background: linear-gradient(to right, #fafcff, #f8fbfa);
    }
    .sc-head-left { display: flex; align-items: center; gap: 10px; }
    .sc-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: var(--teal-light);
        color: var(--teal);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        flex-shrink: 0;
    }
    .sc-title {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--slate);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin: 0;
    }
    .sc-body { padding: 22px; }

    .btn-refresh {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1.5px solid var(--border);
        background: var(--surface);
        color: var(--teal);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .15s;
    }
    .btn-refresh:hover { background: var(--teal-light); border-color: var(--teal-mid); }

    /* ── Filter bar ──────────────────────────────────────────── */
    .filter-bar {
        display: flex;
        align-items: flex-end;
        gap: 14px;
        flex-wrap: wrap;
    }
    .filter-bar .fb-field { display: flex; flex-direction: column; }
    .filter-bar .fb-grow { flex: 1 1 220px; min-width: 180px; }

    /* ── Field helpers ───────────────────────────────────────── */
    .field-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--slate-light);
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: 5px;
        display: block;
    }
    .form-control, .form-select {
        border: 1.5px solid var(--border);
        border-radius: var(--radius-input);
        font-size: 0.875rem;
        color: var(--slate);
        background: #fafbfc;
        transition: border-color .15s, box-shadow .15s;
        padding: 0.55rem 0.85rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--teal);
        box-shadow: 0 0 0 3px rgba(0,128,128,.1);
        background-color: #fff;
        outline: none;
    }

    /* ── Action buttons ──────────────────────────────────────── */
    .btn-filter {
        background: var(--teal);
        color: #fff;
        border: none;
        border-radius: var(--radius-input);
        padding: 0.55rem 1.1rem;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: .3px;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(0,128,128,.25);
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        white-space: nowrap;
    }
    .btn-filter:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

    .btn-ghost {
        background: var(--surface);
        color: var(--slate-light);
        border: 1.5px solid var(--border);
        border-radius: var(--radius-input);
        padding: 0.55rem 1.1rem;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: .3px;
        cursor: pointer;
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        white-space: nowrap;
    }
    .btn-ghost:hover { background: var(--teal-light); border-color: var(--teal-mid); color: var(--teal); }

    /* ── Table styling ───────────────────────────────────────── */
    .pl-table { margin-bottom: 0; }
    .pl-table thead th {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--slate-light);
        text-transform: uppercase;
        letter-spacing: .4px;
        border-bottom: 2px solid var(--border);
        background: #f8fafc;
        white-space: nowrap;
        padding: 12px 10px;
    }
    .pl-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 11px 10px;
    }
    .pl-table tbody tr:hover { background: var(--teal-light); }

    .btnView {
        border: 1.5px solid var(--teal);
        color: var(--teal);
        background: var(--surface);
        border-radius: var(--radius-input);
        padding: 5px 11px;
        transition: all .15s;
    }
    .btnView:hover { background: var(--teal); color: #fff; }

    .pagination .page-item.active .page-link {
        background: var(--teal);
        border-color: var(--teal);
    }
    .pagination .page-link { color: var(--teal); }

    /* ── Modal styling ───────────────────────────────────────── */
    #mdlLogDetail .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #mdlLogDetail .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #mdlLogDetail .modal-body { background: var(--bg); padding: 22px; }

    /* ── Computation waterfall (matches the print report) ── */
    #mdlLogBody .calc { width:100%; border-collapse:collapse; background:#fff; border:1px solid var(--border); border-radius:10px; overflow:hidden; }
    #mdlLogBody .calc td { padding:6px 12px; font-size:.8rem; border-bottom:1px solid #f1f5f9; vertical-align:baseline; }
    #mdlLogBody .calc td.lbl  { color:var(--slate); }
    #mdlLogBody .calc td.note { color:var(--muted); font-size:.68rem; text-align:right; white-space:nowrap; }
    #mdlLogBody .calc td.amt  { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; width:128px; font-weight:600; }
    #mdlLogBody .calc td.amt.add { color:#0f766e; }
    #mdlLogBody .calc td.amt.sub { color:#b91c1c; }
    #mdlLogBody .calc tr.sec td { background:#f8fafc; font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:var(--slate-light); padding-top:9px; padding-bottom:4px; }
    #mdlLogBody .calc tr.info td  { color:var(--slate-light); }
    #mdlLogBody .calc tr.info td.amt { font-weight:500; }
    #mdlLogBody .calc tr.mile td { background:var(--teal-light); font-weight:800; border-top:2px solid var(--teal); border-bottom:2px solid var(--teal); }
    #mdlLogBody .calc tr.mile td.lbl { color:var(--teal-dark); text-transform:uppercase; font-size:.72rem; letter-spacing:.3px; }
    #mdlLogBody .calc tr.mile td.amt { color:var(--teal-dark); font-size:.9rem; }
    #mdlLogBody .calc tr.grand td { background:var(--teal); color:#fff; font-weight:800; }
    #mdlLogBody .calc tr.grand td.lbl { text-transform:uppercase; letter-spacing:.4px; }
    #mdlLogBody .calc tr.grand td.amt { color:#fff; font-size:.95rem; }
    #mdlLogBody .calc-foot { margin-top:12px; font-size:.72rem; color:var(--muted); line-height:1.6; }
    #mdlLogBody .calc-foot .lbl  { font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:.3px; }
    #mdlLogBody .calc-foot .warn { color:var(--danger); font-weight:700; }
</style>

<div class="pl-shell">

    {{-- ── Top header ── --}}
    <div class="pl-topbar">
        <div>
            <p class="page-title">Payroll Computation Logs</p>
            <p class="page-sub">Review the technical breakdown behind each employee's payroll</p>
        </div>
    </div>

    {{-- ── Filters ── --}}
    <div class="sc">
        <div class="sc-body" style="padding: 18px 22px;">
            <div class="filter-bar">
                <div class="fb-field">
                    <label class="field-label" for="fltPayDate">Pay Date</label>
                    <input type="date" id="fltPayDate" class="form-control">
                </div>
                <div class="fb-field">
                    <label class="field-label" for="fltDepartment">Department</label>
                    <select id="fltDepartment" class="form-select">
                        <option value="all">All Departments</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->dep_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fb-field fb-grow">
                    <label class="field-label" for="fltSearch">Search (name or ID)</label>
                    <input type="text" id="fltSearch" class="form-control" placeholder="Employee name or ID...">
                </div>
                <button class="btn-filter" id="btnFilter">
                    <i class="fa fa-search"></i> Filter
                </button>
                <button class="btn-ghost" id="btnPrintAll">
                    <i class="fa fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>

    {{-- ── Computation Records ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa fa-list-ul"></i></div>
                <h5 class="sc-title">Computation Records</h5>
            </div>
            <button class="btn-refresh" id="btnRefreshLogs" title="Refresh">
                <i class="fa fa-refresh fa-sm"></i>
            </button>
        </div>
        <div class="sc-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle pl-table">
                    <thead>
                        <tr>
                            <th class="ps-3">Employee</th>
                            <th>Dept</th>
                            <th>Class</th>
                            <th>Pay Date</th>
                            <th class="text-end">Gross</th>
                            <th class="text-end">Net</th>
                            <th class="text-end">Pay Rec.</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tblLogs">
                        <tr><td colspan="8" class="text-center text-muted py-4">Use the filters above and click Filter.</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="logsPagination" class="p-2"></div>
        </div>
    </div>
</div>

{{-- Detail modal --}}
<div class="modal fade" id="mdlLogDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="mdlLogTitle">Computation Breakdown</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="mdlLogBody"></div>
            <div class="modal-footer">
                <a href="#" id="mdlPrintLink" target="_blank" class="btn-ghost"><i class="fa fa-print"></i> Print this</a>
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    let rows = [];      // current page data
    let curPage = 1;

    const peso = (n) => Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc  = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    function loadLogs(page = 1) {
        const params = {
            pay_date: $('#fltPayDate').val(),
            department_id: $('#fltDepartment').val() || 'all',
            search: $('#fltSearch').val(),
            page,
        };
        $('#tblLogs').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-secondary spinner-border-sm"></div></td></tr>');
        axios.get('/payroll-logs/fetch', { params }).then(res => {
            const d = res.data;
            rows = d.data || [];
            curPage = d.current_page || 1;
            if (!rows.length) {
                $('#tblLogs').html('<tr><td colspan="8" class="text-center text-muted py-4">No payroll logs found.</td></tr>');
                $('#logsPagination').html('');
                return;
            }
            let html = '';
            rows.forEach((r, i) => {
                html += `<tr>
                    <td class="ps-3"><span class="fw-bold text-uppercase small">${r.employee_name || ''}</span><br><span class="text-muted small">${r.employee_id}</span></td>
                    <td class="small">${r.department_name ?? r.department_id ?? '—'}</td>
                    <td class="small">${r.classification ?? '—'}</td>
                    <td class="small">${r.pay_date ? r.pay_date.substring(0,10) : ''}</td>
                    <td class="text-end">${peso(r.gross_pay)}</td>
                    <td class="text-end">${peso(r.net_pay)}</td>
                    <td class="text-end fw-bold">${peso(r.pay_rec)}</td>
                    <td class="text-end pe-3">
                        <button class="btn btn-sm btnView" data-i="${i}"><i class="fa fa-eye"></i></button>
                    </td>
                </tr>`;
            });
            $('#tblLogs').html(html);
            renderPagination(d.last_page || 1, curPage);
        }).catch(() => {
            $('#tblLogs').html('<tr><td colspan="8" class="text-center text-danger py-4">Failed to load logs.</td></tr>');
        });
    }

    function renderPagination(lastPage, cur) {
        const c = document.getElementById('logsPagination');
        if (lastPage <= 1) { c.innerHTML = ''; return; }
        const item = (lbl, pg, o) => (o && o.disabled)
            ? `<li class="page-item disabled"><span class="page-link">${lbl}</span></li>`
            : `<li class="page-item ${o && o.active ? 'active' : ''}"><a href="#" class="page-link logPage" data-page="${pg}">${lbl}</a></li>`;
        const win = 1, pages = [];
        for (let i = 1; i <= lastPage; i++) if (i === 1 || i === lastPage || (i >= cur - win && i <= cur + win)) pages.push(i);
        let h = '<nav><ul class="pagination pagination-sm justify-content-end mb-0 gap-1">';
        h += item('&lsaquo;', cur - 1, { disabled: cur <= 1 });
        let prev = 0;
        pages.forEach(i => { if (prev && i - prev > 1) h += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>'; h += item(i, i, { active: i === cur }); prev = i; });
        h += item('&rsaquo;', cur + 1, { disabled: cur >= lastPage });
        h += '</ul></nav>';
        c.innerHTML = h;
    }

    // Render the computation breakdown as a top-down waterfall that mirrors the
    // engine: earnings → less attendance deductions = Gross → less gov dues = Net
    // → less loans / plus allowance & adjustments = Pay Receivable. Milestones use
    // the stored authoritative figures, so it always foots (RGLR or daily).
    // Kept in sync with pages/modules/payroll_logs_print.blade.php.
    function renderBreakdown(bd) {
        if (!bd || typeof bd !== 'object') return '<span class="text-muted">No breakdown available.</span>';

        const g = (o, path, d = 0) => {
            let cur = o;
            for (const seg of path.split('.')) {
                if (cur && typeof cur === 'object' && seg in cur) cur = cur[seg];
                else return d;
            }
            return cur;
        };
        const num = (v) => { const n = Number(v); return isNaN(n) ? 0 : n; };
        const nz  = (v) => Math.abs(num(v)) > 0.005;
        const h2  = (v) => num(v).toFixed(2).replace(/\.?0+$/, '');

        const row = (label, amount, sign = 'add', note = '') => {
            const cls = sign === 'sub' ? 'sub' : 'add';
            const pre = sign === 'sub' ? '− ' : '+ ';
            return `<tr><td class="lbl">${esc(label)}</td><td class="note">${esc(note)}</td><td class="amt ${cls}">${pre}${peso(amount)}</td></tr>`;
        };
        const mile = (label, amount, grand = false, note = '') =>
            `<tr class="${grand ? 'grand' : 'mile'}"><td class="lbl">${esc(label)}</td><td class="note">${esc(note)}</td><td class="amt">${peso(amount)}</td></tr>`;
        const sec  = (label) => `<tr class="sec"><td colspan="3">${esc(label)}</td></tr>`;
        const info = (label, amount, note = '') => {
            const val = (amount !== '' && !isNaN(Number(amount))) ? peso(amount) : esc(amount);
            return `<tr class="info"><td class="lbl">${esc(label)}</td><td class="note">${esc(note)}</td><td class="amt">${val}</td></tr>`;
        };

        const isRglr   = String(g(bd, 'classification', '')).toUpperCase() === 'RGLR';
        const mRate    = num(g(bd, 'rates.basic_monthly', 0));
        const dRate    = num(g(bd, 'rates.daily_rate', 0));
        const hRate    = num(g(bd, 'rates.hourly_rate', 0));
        const aDaily   = num(g(bd, 'rates.daily_allowance', 0));
        const aHourly  = num(g(bd, 'rates.allowance_hourly', 0));
        const sched    = num(g(bd, 'attendance.scheduled_days', 0));
        const daysPres = num(g(bd, 'attendance.days_present', 0));
        const earnBase = isRglr ? num(g(bd, 'totals.basic_pay', 0)) : daysPres * dRate;
        const grossAdj = num(g(bd, 'adjustments.gross_taxed', 0));
        const netAdj   = num(g(bd, 'adjustments.net_after_tax', 0));

        let h = '<table class="calc"><tbody>';

        // ── PAY BASIS ──
        h += sec('Pay Basis');
        if (nz(mRate)) h += info('Monthly Basic', mRate);
        h += info('Daily Rate',  dRate, nz(mRate) ? `${h2(mRate)} ÷ 26 days` : '');
        h += info('Hourly Rate', hRate, nz(dRate) ? `${h2(dRate)} ÷ 8 hrs` : '');
        if (nz(aDaily))  h += info('Daily Allowance',  aDaily);
        if (nz(aHourly)) h += info('Allowance / Hour', aHourly, nz(aDaily) ? `${h2(aDaily)} ÷ 8 hrs` : '');
        h += info('Days Present', `${h2(daysPres)} / ${h2(sched)}`, 'present / scheduled');

        // ── EARNINGS ──
        h += sec('Earnings');
        h += row(isRglr ? 'Basic Pay (semi-monthly)' : 'Regular Pay', earnBase, 'add',
            isRglr ? `${h2(mRate)} ÷ 2` : `${h2(daysPres)} day(s) × ${peso(dRate)}`);
        if (nz(g(bd, 'overtime.total_pay', 0))) {
            const otDates = (g(bd, 'overtime.rest_day_ot_dates', []) || []).length;
            h += row('Overtime Pay', g(bd, 'overtime.total_pay', 0), 'add', otDates ? `${otDates} rest-day OT date(s)` : '');
        }
        if (nz(g(bd, 'holiday_pay', 0)))
            h += row('Holiday Pay', g(bd, 'holiday_pay', 0), 'add', `${g(bd, 'holiday.count', 0)} holiday(s)`);
        if (nz(g(bd, 'night_diff.pay', 0)))
            h += row('Night Differential', g(bd, 'night_diff.pay', 0), 'add', `${h2(g(bd, 'night_diff.minutes', 0))}min ÷ 60 × (${peso(hRate)} × 10%)`);
        if (nz(grossAdj))
            h += row('Gross Adjustment', Math.abs(grossAdj), grossAdj < 0 ? 'sub' : 'add', 'taxable');

        // ── LESS: ATTENDANCE DEDUCTIONS ──
        const att = [
            ['Tardiness',        g(bd,'tardiness.deduction',0),      `${h2(g(bd,'tardiness.total_minutes',0))}min → ${h2(g(bd,'tardiness.bracket_hours',0))}h × ${peso(g(bd,'tardiness.x_hourly_rate',hRate))}`],
            ['Undertime',        g(bd,'undertime.deduction',0),      `${h2(g(bd,'undertime.total_minutes',0))}min → ${h2(g(bd,'undertime.bracket_hours',0))}h × ${peso(g(bd,'undertime.x_hourly_rate',hRate))}`],
            ['Absences',         g(bd,'absences.deduction',0),       `${h2(g(bd,'absences.days',0))} day(s) × ${peso(g(bd,'absences.x_daily',dRate))}`],
            ['Over-break',       g(bd,'over_break.deduction',0),     `${h2(g(bd,'over_break.minutes',0))}min ÷ 60 × ${peso(hRate)}`],
            ['Outpass',          g(bd,'outpass.deduction',0),        `${h2(g(bd,'outpass.minutes',0))}min ÷ 60 × ${peso(hRate)}`],
            ['Custom Deduction', g(bd,'custom_deduction.amount',0),  `${h2(g(bd,'custom_deduction.minutes',0))}min ÷ 60 × ${peso(hRate)}`],
        ];
        if (att.some(r => nz(r[1]))) {
            h += sec('Less: Attendance Deductions');
            att.forEach(([lbl, amt, note]) => { if (nz(amt)) h += row(lbl, amt, 'sub', note); });
            h += info('Total Attendance Deductions', g(bd,'totals.total_deductions',0), 'subtotal');
        }
        h += mile('Gross Pay', g(bd,'totals.gross_pay',0));

        // ── LESS: GOVERNMENT DUES ── (contributions only; tax is its own step below)
        const gov = [
            ['SSS',        g(bd,'contributions.sss',0)],
            ['PhilHealth', g(bd,'contributions.philhealth',0)],
            ['Pag-IBIG',   g(bd,'contributions.pagibig',0)],
        ];
        h += sec('Less: Government Dues');
        if (gov.some(r => nz(r[1]))) {
            gov.forEach(([lbl, amt]) => { if (nz(amt)) h += row(lbl, amt, 'sub'); });
        } else {
            h += `<tr><td class="lbl" colspan="3" style="color:#94a3b8;font-style:italic;">No statutory deductions this cut-off (deducted end-of-month).</td></tr>`;
        }

        // ── TAXABLE INCOME (subtotal / basis for tax) ──
        h += mile('Taxable Income', g(bd,'contributions.taxable',0), false, 'basis for tax');

        // ── LESS: TAX ── (always shown; 0.00 when none)
        const wtax = g(bd,'contributions.tax',0);
        h += sec('Less: Tax');
        if (nz(wtax)) {
            h += row('Withholding Tax', wtax, 'sub');
        } else {
            h += `<tr><td class="lbl">Withholding Tax</td><td class="note"></td><td class="amt">${peso(0)}</td></tr>`;
        }

        h += mile('Net Pay', g(bd,'net_pay',0));

        // ── LESS: LOANS / PLUS: ALLOWANCE & ADJUSTMENTS ──
        const loans = [
            ['Company Loan',    g(bd,'loans.company',0)],
            ['Charges/Penalty', g(bd,'loans.charges',0)],
            ['Cash Advance',    g(bd,'loans.cash_adv',0)],
            ['Other',           g(bd,'loans.other',0)],
            ['SSS Loan',        g(bd,'loans.sss_loan',0)],
            ['Pag-IBIG Loan',   g(bd,'loans.pagibig_loan',0)],
        ];
        const aGross     = num(g(bd,'allowance.gross',0));
        const aLateUt    = num(g(bd,'allowance.late_ut_deduction',0));
        const aOverBreak = num(g(bd,'allowance.over_break_deduction',0));
        const allowNet   = num(g(bd,'allowance.net',0));
        const allowDecomposes = Math.abs((aGross - aLateUt - aOverBreak) - allowNet) < 0.02;
        const hasTail = loans.some(r => nz(r[1])) || nz(aGross) || nz(allowNet) || nz(netAdj);
        if (hasTail) {
            h += sec('Less: Loans / Plus: Allowance & Adjustments');
            loans.forEach(([lbl, amt]) => { if (nz(amt)) h += row(lbl, amt, 'sub'); });
            if (nz(aGross) && allowDecomposes) {
                h += row('Allowance (gross)', aGross, 'add', `${h2(g(bd,'allowance.days_paid',0))} day(s) × ${peso(aDaily)}`);
                if (nz(aLateUt))    h += row('Allowance Late/UT', aLateUt, 'sub', `${h2(g(bd,'allowance.late_ut_hours',0))}h × ${peso(aHourly)}`);
                if (nz(aOverBreak)) h += row('Allowance Over-break', aOverBreak, 'sub', `${h2(g(bd,'allowance.over_break_minutes',0))}min ÷ 60 × ${peso(aHourly)}`);
            } else if (nz(allowNet)) {
                h += row('Allowance (net)', allowNet, 'add');
            }
            if (nz(netAdj)) h += row('Net Adjustment', Math.abs(netAdj), netAdj < 0 ? 'sub' : 'add', 'after tax');
        }
        h += mile('Pay Receivable', g(bd,'pay_receivable',0), true);
        h += '</tbody></table>';

        // ── Footnotes ──
        const hols = g(bd,'holiday.applied',[]) || [];
        const adjs = g(bd,'adjustments.entries',[]) || [];
        const loansSkipped = g(bd,'loans.can_afford',true) === false;
        if (hols.length || adjs.length || loansSkipped) {
            h += '<div class="calc-foot">';
            if (hols.length) {
                const list = hols.map(x => `${esc(x.date ? String(x.date).substring(0,10) : '')} (${esc(x.type || '')})`).join(', ');
                h += `<div><span class="lbl">Holidays applied:</span> ${list}</div>`;
            }
            if (adjs.length) {
                const list = adjs.map(a => `${esc(a.label || 'Adj')} ${esc(a.kind || '')} ${peso(a.amount || 0)} (${esc(a.apply_to || '')})`).join(' · ');
                h += `<div><span class="lbl">Adjustments:</span> ${list}</div>`;
            }
            if (loansSkipped) h += `<div class="warn">⚠ Loan deductions skipped this cut-off — pay receivable would be negative.</div>`;
            h += '</div>';
        }
        return h;
    }

    // events
    $('#btnFilter').on('click', () => loadLogs(1));
    $('#btnRefreshLogs').on('click', () => loadLogs(curPage));
    $('#fltSearch').on('keyup', e => { if (e.key === 'Enter') loadLogs(1); });
    $('#fltPayDate, #fltDepartment').on('change', () => loadLogs(1));

    $(document).on('click', '.logPage', function (e) {
        e.preventDefault();
        if ($(this).closest('.page-item').hasClass('disabled')) return;
        const p = parseInt($(this).data('page'), 10);
        if (p) loadLogs(p);
    });

    $(document).on('click', '.btnView', function () {
        const r = rows[$(this).data('i')];
        if (!r) return;
        $('#mdlLogTitle').text(`${r.employee_name || ''} — ${r.pay_date ? r.pay_date.substring(0,10) : ''}`);
        $('#mdlLogBody').html(renderBreakdown(r.breakdown || {}));
        $('#mdlPrintLink').attr('href', `/payroll-logs/print?id=${encodeURIComponent(r.id)}`);
        new bootstrap.Modal(document.getElementById('mdlLogDetail')).show();
    });

    $('#btnPrintAll').on('click', function () {
        const params = new URLSearchParams({
            pay_date: $('#fltPayDate').val() || '',
            department_id: $('#fltDepartment').val() || 'all',
            search: $('#fltSearch').val() || '',
        });
        window.open(`/payroll-logs/print?${params.toString()}`, '_blank');
    });
});
</script>
@endsection
