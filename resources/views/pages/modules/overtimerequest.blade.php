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
