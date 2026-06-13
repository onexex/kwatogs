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

    // Render a breakdown object as nested tables
    function renderBreakdown(obj) {
        if (obj === null || obj === undefined) return '<span class="text-muted">—</span>';
        if (typeof obj !== 'object') return `<span>${obj}</span>`;
        let h = '<table class="table table-sm mb-0"><tbody>';
        Object.keys(obj).forEach(k => {
            const v = obj[k];
            const label = k.replace(/_/g, ' ');
            if (v !== null && typeof v === 'object') {
                h += `<tr><td class="fw-bold text-capitalize align-top" style="width:200px;">${label}</td><td>${renderBreakdown(v)}</td></tr>`;
            } else {
                h += `<tr><td class="text-capitalize" style="width:200px;">${label}</td><td>${v ?? '—'}</td></tr>`;
            }
        });
        h += '</tbody></table>';
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
