@extends('layout.app', [
    'title' => 'Pending Overtime Requests'
])
@section('content')

<style>
    /* ── Design tokens (shared with Pending Leave Requests) ── */
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
    .otreq-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    /* ── Top header bar ──────────────────────────────────────── */
    .otreq-topbar {
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
    .otreq-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .otreq-topbar .page-sub {
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
    .sc-body { padding: 0; }

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

    /* ── Info note ───────────────────────────────────────────── */
    .ot-note {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin: 18px 22px 4px;
        padding: 12px 14px;
        background: var(--teal-light);
        border: 1px solid #b8e0dc;
        border-radius: var(--radius-input);
        font-size: 0.8rem;
        color: var(--slate);
        line-height: 1.5;
    }
    .ot-note i { color: var(--teal); margin-top: 2px; }

    /* ── Table styling ───────────────────────────────────────── */
    .otreq-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: var(--surface);
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--slate-light);
        text-transform: uppercase;
        letter-spacing: .4px;
        border-bottom: 2px solid var(--border);
        white-space: nowrap;
        padding: 12px 16px;
    }
    .otreq-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .otreq-table tbody tr:hover { background: var(--teal-light); }

    /* ── Status pills ────────────────────────────────────────── */
    .st-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 11px 4px 9px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: .2px;
        line-height: 1.4;
        white-space: nowrap;
        border: 1px solid transparent;
    }
    .st-pill .dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex: 0 0 auto;
    }
    /* Waiting on HR */
    .st-pill.is-hr      { background:#fff7ed; color:#9a3412; border-color:#fed7aa; }
    .st-pill.is-hr .dot { background:#f97316; box-shadow:0 0 0 3px #f9731622; }
    /* Waiting on CFO */
    .st-pill.is-cfo      { background:#eff6ff; color:#1e40af; border-color:#bfdbfe; }
    .st-pill.is-cfo .dot { background:#3b82f6; box-shadow:0 0 0 3px #3b82f622; }
    /* Approved */
    .st-pill.is-approved      { background:#ecfdf5; color:#065f46; border-color:#a7f3d0; }
    .st-pill.is-approved .dot { background:#10b981; box-shadow:0 0 0 3px #10b98122; }
    /* Disapproved */
    .st-pill.is-rejected      { background:#fef2f2; color:#991b1b; border-color:#fecaca; }
    .st-pill.is-rejected .dot { background:#ef4444; box-shadow:0 0 0 3px #ef444422; }

    /* ── Action buttons ──────────────────────────────────────── */
    .otreq-actions {
        display: inline-flex;
        gap: 8px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
    .act-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: .3px;
        line-height: 1.4;
        color: #fff;
        cursor: pointer;
        white-space: nowrap;
        transition: all .2s;
    }
    .act-btn:active { transform: translateY(0); }
    .act-btn:focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }

    .act-approve       { background: var(--success); box-shadow: 0 4px 14px rgba(16,185,129,.25); }
    .act-approve:hover { background: #059669; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(16,185,129,.35); color:#fff; }

    .act-confirm       { background: var(--teal); box-shadow: 0 4px 14px rgba(0,128,128,.25); }
    .act-confirm:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color:#fff; }

    .act-reject        { background: var(--danger); box-shadow: 0 4px 14px rgba(239,68,68,.25); }
    .act-reject:hover  { background: #dc2626; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(239,68,68,.35); color:#fff; }
</style>

<div class="otreq-shell">

    {{-- ── Top header ── --}}
    <div class="otreq-topbar">
        <div>
            <p class="page-title">Pending Overtime Requests</p>
            <p class="page-sub">Review and act on employee overtime filings</p>
        </div>
    </div>

    {{-- ── Overtime History ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa fa-clock"></i></div>
                <h5 class="sc-title">Overtime History</h5>
            </div>
            <button class="btn-refresh" name="btnRefreshTbl" id="btnRefreshTbl" title="Refresh">
                <i class="fa fa-refresh fa-sm"></i>
            </button>
        </div>

        <div class="ot-note">
            <i class="fa fa-circle-info"></i>
            <div>
                <strong>OT pay rule (Regular &amp; Rest day):</strong>
                first 8 hrs &times; <strong>1.30</strong>, hours beyond 8 &times; <strong>1.25</strong>.
                A <strong>1-hour meal break</strong> is deducted when the filed span is <strong>&ge; 9 hours</strong>.
                Holiday day-types use the standard multipliers.
            </div>
        </div>

        <div class="sc-body">
            <div class="table-responsive" style="max-height: 72vh; overflow-y: auto;">
                <table class="table table-hover align-middle otreq-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Department</th>
                            <th>Filing Date</th>
                            <th>Date From</th>
                            <th>Date To</th>
                            <th>Duration</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tblOvertimeApp">

                    </tbody>
                </table>
                <div id="overtimePagination" class="mt-2 px-2 pb-2"></div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/modules/overtimerequest.js') }}" defer></script>
@endsection
