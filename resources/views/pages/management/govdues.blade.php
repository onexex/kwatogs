@extends('layout.app')
@section('content')

<style>
    /* ── Design tokens (shared across all settings pages) ── */
    :root {
        --teal:        #008080;
        --teal-dark:   #006666;
        --teal-mid:    #4db6ac;
        --teal-light:  #e0f2f1;
        --slate:       #334155;
        --slate-light: #64748b;
        --muted:       #94a3b8;
        --bg:          #f1f5f9;
        --surface:     #ffffff;
        --border:      #e2e8f0;
        --danger:      #ef4444;
        --success:     #10b981;
        --radius-card: 14px;
        --radius-input:8px;
        --shadow-card: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }

    .gd-shell { background: var(--bg); min-height: 100vh; padding: 24px 28px 60px; margin: -1.5rem -1.5rem 0; }

    .gd-topbar {
        background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-card);
        box-shadow: var(--shadow-card); padding: 16px 22px; margin-bottom: 20px;
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
    }
    .gd-topbar .page-title { font-size: 1.1rem; font-weight: 700; color: var(--slate); margin: 0; letter-spacing: -.2px; }
    .gd-topbar .page-sub   { font-size: .78rem; color: var(--muted); margin: 2px 0 0; }

    .gd-filters { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .gd-filters .form-control, .gd-filters .form-select {
        border: 1.5px solid var(--border); border-radius: var(--radius-input); font-size: .82rem;
        color: var(--slate); background: #fafbfc; padding: .5rem .8rem; min-width: 200px;
    }
    .gd-filters .form-control:focus, .gd-filters .form-select:focus {
        border-color: var(--teal); box-shadow: 0 0 0 3px rgba(0,128,128,.1); background: #fff; outline: none;
    }

    .sc { background: var(--surface); border-radius: var(--radius-card); border: 1px solid var(--border); box-shadow: var(--shadow-card); margin-bottom: 20px; overflow: hidden; }
    .sc-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 22px; border-bottom: 1px solid var(--border); background: linear-gradient(to right, #fafcff, #f8fbfa); }
    .sc-head-left { display: flex; align-items: center; gap: 10px; }
    .sc-icon { width: 30px; height: 30px; border-radius: 8px; background: var(--teal-light); color: var(--teal); display: flex; align-items: center; justify-content: center; font-size: .78rem; flex-shrink: 0; }
    .sc-title { font-size: .78rem; font-weight: 700; color: var(--slate); text-transform: uppercase; letter-spacing: .5px; margin: 0; }
    .sc-count { font-size: .72rem; color: var(--muted); font-weight: 600; }

    .gd-table thead th {
        position: sticky; top: 0; z-index: 10; background: var(--surface);
        font-size: .7rem; font-weight: 700; color: var(--slate-light); text-transform: uppercase;
        letter-spacing: .4px; border-bottom: 2px solid var(--border); white-space: nowrap; padding: 12px 16px;
    }
    .gd-table tbody td { font-size: .83rem; color: var(--slate); vertical-align: middle; padding: 11px 16px; }
    .gd-table tbody tr:hover { background: var(--teal-light); }
    .gd-emp-name { font-weight: 700; color: var(--slate); }
    .gd-emp-id   { font-size: .72rem; color: var(--muted); }
    .gd-chip { display: inline-block; font-size: .68rem; font-weight: 700; padding: 3px 9px; border-radius: 20px; background: var(--bg); color: var(--slate-light); border: 1px solid var(--border); }

    /* ── Toggle switch (teal) ── */
    .gd-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
    .gd-switch input { opacity: 0; width: 0; height: 0; }
    .gd-slider { position: absolute; cursor: pointer; inset: 0; background: #cbd5e1; transition: .25s; border-radius: 24px; }
    .gd-slider::before { content: ''; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background: #fff; transition: .25s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
    .gd-switch input:checked + .gd-slider { background: var(--teal); }
    .gd-switch input:checked + .gd-slider::before { transform: translateX(20px); }
    .gd-switch input:disabled + .gd-slider { opacity: .5; cursor: not-allowed; }
    .gd-switch.busy .gd-slider { opacity: .5; }

    .gd-rowbtn { font-size: .68rem; font-weight: 700; letter-spacing: .3px; border-radius: 7px; padding: 5px 10px; border: 1.5px solid var(--border); background: var(--surface); color: var(--slate-light); cursor: pointer; transition: all .15s; }
    .gd-rowbtn:hover { border-color: var(--teal-mid); background: var(--teal-light); color: var(--teal-dark); }

    .gd-legend { font-size: .72rem; color: var(--muted); margin: 0 0 14px; }
    .gd-legend b { color: var(--slate-light); }
</style>

<div class="gd-shell">

    <div class="gd-topbar">
        <div>
            <p class="page-title">Government Dues</p>
            <p class="page-sub">Choose which employees are subject to SSS, PhilHealth and Pag-IBIG contributions</p>
        </div>
        <div class="gd-filters">
            <select class="form-select" id="gdCompanyFilter"><option value="">All Companies</option></select>
            <input type="text" class="form-control" id="gdSearch" placeholder="Search name or ID…" />
        </div>
    </div>

    <p class="gd-legend">
        <b>ON</b> = the contribution is deducted during payroll. <b>OFF</b> = the employee is excluded (employee &amp; employer share both set to 0). Changes take effect the next time payroll is computed.
    </p>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-landmark"></i></div>
                <h5 class="sc-title">Employee Enrolment</h5>
            </div>
            <span class="sc-count" id="gdCount"></span>
        </div>
        <div class="sc-body">
            <div class="table-responsive" style="max-height: 72vh; overflow-y: auto;">
                <table class="table table-hover align-middle gd-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Company</th>
                            <th>Classification</th>
                            <th class="text-center" style="width:90px;">SSS</th>
                            <th class="text-center" style="width:110px;">PhilHealth</th>
                            <th class="text-center" style="width:100px;">Pag-IBIG</th>
                            <th class="text-center pe-4" style="width:140px;">Quick set</th>
                        </tr>
                    </thead>
                    <tbody id="gdBody">
                        <tr><td colspan="7" class="text-center py-5 text-muted">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                            Loading employees…
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/govdues.js') }}" defer></script>
@endsection
