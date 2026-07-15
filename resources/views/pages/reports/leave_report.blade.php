@extends('layout.app', ['title' => 'Leave Report'])
@section('content')

<style>
    /* ── Design tokens (shared with Attendance Viewer) ──────────── */
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

    /* ── Page shell (matches Attendance Viewer) ──────────────────── */
    .home-shell {
        background: var(--bg);
        min-height: 100vh;
        margin: -1rem -1.5rem;
        padding: 24px 28px 60px;
    }

    /* ── Top header bar (matches Attendance Viewer) ──────────────── */
    .home-topbar {
        background: var(--surface);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        padding: 16px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 20px;
    }
    .home-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
        text-transform: uppercase;
    }
    .home-topbar .breadcrumb {
        font-size: 0.75rem;
        margin: 2px 0 0;
        padding: 0;
        background: none;
    }
    .home-topbar .breadcrumb-item.active {
        color: var(--teal);
        font-weight: 600;
    }

    /* ── Section card ───────────────────────────────────────────── */
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
        gap: 10px;
        padding: 14px 22px;
        border-bottom: 1px solid var(--border);
        background: linear-gradient(to right, #fafcff, #f8fbfa);
    }
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
    .sc-body { padding: 0; }

    /* ── Standard button styles (matches Attendance Viewer) ─────── */
    .btn-teal {
        background: var(--teal);
        border-color: var(--teal);
        color: #fff;
    }
    .btn-teal:hover {
        background: var(--teal-dark);
        border-color: var(--teal-dark);
        color: #fff;
    }
    .btn-outline-teal {
        background: var(--surface);
        border: 1.5px solid var(--border);
        color: var(--slate);
    }
    .btn-outline-teal:hover {
        border-color: var(--teal);
        color: var(--teal);
        background: var(--teal-light);
    }

    /* ── Field helpers ──────────────────────────────────────────── */
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

    /* ── Table styling (matches Attendance Viewer) ──────────────── */
    .table-sticky-header thead th {
        position: sticky !important;
        top: 0;
        background-color: #fafbfc;
        z-index: 10;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--slate-light);
        border-bottom: 2px solid var(--border);
    }
    .table tbody td {
        font-size: 0.8rem;
        vertical-align: middle;
    }
    .table-hover tbody tr:hover {
        background-color: var(--teal-light);
        transition: background-color 0.2s ease;
    }
    .table tfoot td {
        background: var(--teal-light);
        font-weight: 700;
        color: var(--teal-dark);
        padding: 11px 14px;
        border-top: 2px solid var(--teal);
    }

    /* ── Soft badge (matches Attendance Viewer) ─────────────────── */
    .badge-soft-primary {
        background-color: rgba(0, 128, 128, 0.1);
        color: var(--teal);
        border: 1px solid rgba(0, 128, 128, 0.2);
    }
    .badge-soft-success {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    .badge-soft-danger {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    .badge-soft-warning {
        background-color: rgba(245, 158, 11, 0.1);
        color: #92400e;
        border: 1px solid rgba(245, 158, 11, 0.25);
    }
    .badge-soft-secondary {
        background-color: rgba(148, 163, 184, 0.12);
        color: var(--muted);
        border: 1px solid rgba(148, 163, 184, 0.2);
    }

    /* ── Pagination (matches Attendance Viewer) ─────────────────── */
    .pagination .page-item.active .page-link { background: var(--teal); border-color: var(--teal); }
    .pagination .page-link { color: var(--teal); }
</style>

<div class="home-shell">

    {{-- ── Top header with breadcrumb (matches Attendance Viewer) ── --}}
    <div class="home-topbar">
        <div>
            <h4 class="page-title">Leave Report</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted">Reports</li>
                    <li class="breadcrumb-item active fw-semibold" aria-current="page">Leave Report</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- ── Search / filter card (matches Attendance Viewer) ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
            <h5 class="sc-title">Search Filters</h5>
        </div>
        <div class="sc-body" style="padding: 18px 22px;">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="field-label">Date From</label>
                    <input type="date" id="fltFrom" class="form-control">
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Date To</label>
                    <input type="date" id="fltTo" class="form-control">
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Department</label>
                    <select id="fltDept" class="form-select">
                        <option value="all">All</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->dep_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Leave Type</label>
                    <select id="fltType" class="form-select">
                        <option value="all">All</option>
                        @foreach ($leavetypes as $lt)
                            <option value="{{ $lt->id }}">{{ $lt->type_leave }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Status</label>
                    <select id="fltStatus" class="form-select">
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
                    <input type="text" id="fltSearch" class="form-control" placeholder="Name or ID...">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button class="btn btn-teal rounded-pill px-4 fw-bold shadow-sm flex-fill" id="btnFilter">
                        <i class="fa fa-search me-1"></i> Filter
                    </button>
                    <button class="btn btn-outline-teal rounded-pill px-3 fw-bold" id="btnPrint" title="Print Report">
                        <i class="fa fa-print"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Results table ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-calendar-check"></i></div>
            <h5 class="sc-title">Leave Records</h5>
        </div>
        <div class="table-responsive" style="max-height: 72vh; overflow: auto;">
            <table class="table table-hover align-middle mb-0 table-sticky-header">
                <thead>
                    <tr>
                        <th class="ps-4">Employee</th>
                        <th>Dept</th>
                        <th>Leave Type</th>
                        <th>Start</th>
                        <th>End</th>
                        <th class="text-end">Days</th>
                        <th>Kind</th>
                        <th>Reason</th>
                        <th class="text-center pe-4">Status</th>
                    </tr>
                </thead>
                <tbody id="tbl"><tr><td colspan="9" class="text-center text-muted py-4">Set filters and click Filter.</td></tr></tbody>
                <tfoot id="tfoot"></tfoot>
            </table>
        </div>
        <div id="pager" class="p-2"></div>
    </div>
</div>

<script>
$(function () {
    const badge = s => {
        const m = {
            APPROVED: 'badge-soft-success',
            APPROVEDBYCFO: 'badge-soft-primary',
            DISAPPROVED: 'badge-soft-danger',
            FORAPPROVAL: 'badge-soft-warning',
            CANCELED: 'badge-soft-secondary'
        };
        const label = (s||'').replace(/([A-Z])/g,' $1').trim();
        return `<span class="badge ${m[s] || 'bg-light text-dark'} rounded-pill px-3 py-2 fw-semibold">${label}</span>`;
    };
    const params = () => ({
        date_from: $('#fltFrom').val(), date_to: $('#fltTo').val(),
        department_id: $('#fltDept').val() || 'all', status: $('#fltStatus').val() || 'all',
        leave_type: $('#fltType').val() || 'all',
        search: $('#fltSearch').val(),
    });

    function load(page = 1) {
        $('#tbl').html('<tr><td colspan="9" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        $('#tfoot').html('');
        axios.get('/reports/leave/fetch', { params: { ...params(), page } }).then(res => {
            const d = res.data, rows = d.data || [];
            if (!rows.length) { $('#tbl').html('<tr><td colspan="9" class="text-center text-muted py-4">No leave records found.</td></tr>'); $('#pager').html(''); return; }
            let h = '', td = 0;
            rows.forEach(r => {
                const days = Number(r.total_hrs || 0) / 8;
                td += days;
                h += `<tr>
                    <td class="ps-4"><span class="fw-bold text-uppercase">${r.employee_name || ''}</span><br><span class="text-muted small">${r.employee_id}</span></td>
                    <td>${r.department_name ?? '—'}</td>
                    <td>${r.leave_type ?? '—'}</td>
                    <td>${(r.start_date||'').substring(0,10)}</td>
                    <td>${(r.end_date||'').substring(0,10)}</td>
                    <td class="text-end fw-semibold">${days.toFixed(2)}</td>
                    <td>${Number(r.leave_kind) === 0 ? 'Paid' : 'Unpaid'}</td>
                    <td class="text-truncate" style="max-width:180px;">${r.reason ?? ''}</td>
                    <td class="text-center pe-4">${badge(r.status)}</td>
                </tr>`;
            });
            $('#tbl').html(h);
            $('#tfoot').html(`<tr><td colspan="5" class="text-end text-uppercase fw-bold small">Page Total Days:</td><td class="text-end fw-bold">${td.toFixed(2)}</td><td colspan="3"></td></tr>`);
            pager(d.last_page || 1, d.current_page || 1);
        }).catch(() => $('#tbl').html('<tr><td colspan="9" class="text-center text-danger py-4">Failed to load.</td></tr>'));
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
    $('#fltFrom,#fltTo,#fltDept,#fltStatus,#fltType').on('change', () => load(1));

    // Deep-link support: apply any filter passed via URL query and auto-run.
    (function applyUrlFilters() {
        const qp = new URLSearchParams(location.search);
        if (![...qp.keys()].length) return;
        if (qp.get('date_from'))     $('#fltFrom').val(qp.get('date_from'));
        if (qp.get('date_to'))       $('#fltTo').val(qp.get('date_to'));
        if (qp.get('department_id')) $('#fltDept').val(qp.get('department_id'));
        if (qp.get('status'))        $('#fltStatus').val(qp.get('status'));
        if (qp.get('leave_type'))    $('#fltType').val(qp.get('leave_type'));
        if (qp.get('search'))        $('#fltSearch').val(qp.get('search'));
        load(1);
    })();
    $(document).on('click', '.pg', function (e) { e.preventDefault(); if ($(this).closest('.page-item').hasClass('disabled')) return; const p = parseInt($(this).data('page'), 10); if (p) load(p); });
    $('#btnPrint').on('click', () => window.open('/reports/leave/print?' + new URLSearchParams(params()).toString(), '_blank'));
});
</script>
@endsection