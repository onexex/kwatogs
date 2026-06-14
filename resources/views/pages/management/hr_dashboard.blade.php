@extends('layout.app', ['title' => 'HR Control Center'])
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8; --bg:#eef2f6;
        --surface:#fff; --border:#e2e8f0; --danger:#ef4444; --success:#10b981; --warning:#f59e0b; --info:#3b82f6;
        --radius:14px; --shadow:0 1px 3px rgba(0,0,0,.06),0 6px 18px rgba(0,0,0,.05);
    }
    .cc-shell { background:var(--bg); min-height:100vh; padding:22px 26px 60px; margin:-1.5rem -1.5rem 0; }
    .cc-top { background:linear-gradient(135deg,var(--teal) 0%,var(--teal-dark) 100%); color:#fff; border-radius:var(--radius);
        box-shadow:var(--shadow); padding:18px 24px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
    .cc-top h4 { margin:0; font-weight:800; letter-spacing:-.3px; font-size:1.25rem; }
    .cc-top .sub { font-size:.8rem; opacity:.9; }
    .cc-clock .t { font-size:1.5rem; font-weight:800; line-height:1; text-align:right; }
    .cc-clock .dte { font-size:.75rem; opacity:.9; text-align:right; }

    .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; margin-bottom:18px; }
    .kpi { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); padding:16px 18px; display:flex; align-items:center; gap:14px; text-decoration:none; transition:.15s; }
    .kpi:hover { transform:translateY(-2px); box-shadow:0 10px 24px rgba(0,0,0,.09); }
    .kpi .ic { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .kpi .val { font-size:1.7rem; font-weight:800; color:var(--slate); line-height:1; }
    .kpi .lbl { font-size:.68rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-top:3px; }

    .grid { display:grid; grid-template-columns:repeat(12,1fr); gap:16px; }
    .cc-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; }
    .cc-h { display:flex; align-items:center; gap:9px; padding:13px 18px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .cc-h .i { width:28px; height:28px; border-radius:8px; background:var(--teal-light); color:var(--teal); display:flex; align-items:center; justify-content:center; font-size:.75rem; }
    .cc-h h6 { margin:0; font-size:.74rem; font-weight:800; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; }
    .cc-h .right { margin-left:auto; }
    .cc-b { padding:18px; }
    .cc-3{grid-column:span 3}.cc-4{grid-column:span 4}.cc-5{grid-column:span 5}.cc-6{grid-column:span 6}.cc-7{grid-column:span 7}.cc-8{grid-column:span 8}.cc-12{grid-column:span 12}
    @media(max-width:992px){.cc-3,.cc-4,.cc-5,.cc-6,.cc-7,.cc-8{grid-column:span 12}}

    .seg { display:inline-flex; border:1px solid var(--border); border-radius:8px; overflow:hidden; }
    .seg button { border:none; background:#fff; color:var(--slate-light); font-size:.7rem; font-weight:700; padding:4px 12px; cursor:pointer; }
    .seg button.on { background:var(--teal); color:#fff; }

    .appr { display:flex; align-items:center; justify-content:space-between; padding:11px 0; border-bottom:1px solid #f1f5f9; }
    .appr:last-child{border-bottom:0}
    .appr .n { font-weight:700; color:var(--slate); font-size:.9rem; }
    .appr .pill { font-size:1.1rem; font-weight:800; min-width:34px; text-align:center; padding:2px 10px; border-radius:9px; background:var(--teal-light); color:var(--teal-dark); }
    .appr a { font-size:.72rem; font-weight:700; color:var(--teal); text-decoration:none; }

    .att-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; }
    .att { border:1px solid var(--border); border-radius:10px; padding:12px; text-align:center; }
    .att .v { font-size:1.5rem; font-weight:800; } .att .l { font-size:.66rem; font-weight:700; text-transform:uppercase; color:var(--slate-light); letter-spacing:.3px; }

    .chart { width:100%; height:150px; }
    .chart .area-p { fill:rgba(0,128,128,.12); } 
    .chart .line-p { fill:none; stroke:var(--teal); stroke-width:2; vector-effect:non-scaling-stroke; }
    .chart .line-l { fill:none; stroke:var(--warning); stroke-width:2; stroke-dasharray:4 3; vector-effect:non-scaling-stroke; }

    .bar-row { display:flex; align-items:center; gap:10px; margin-bottom:9px; font-size:.8rem; }
    .bar-row .nm { width:110px; color:var(--slate); font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .bar-track { flex:1; background:#f1f5f9; border-radius:6px; height:14px; overflow:hidden; }
    .bar-fill { height:100%; border-radius:6px; }
    .bar-val { width:46px; text-align:right; font-weight:700; color:var(--slate-light); font-size:.75rem; }

    .donut { width:120px; height:120px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .donut .hole { width:74px; height:74px; border-radius:50%; background:var(--surface); display:flex; flex-direction:column; align-items:center; justify-content:center; }
    .donut .hole b { font-size:1.1rem; font-weight:800; color:var(--slate); } .donut .hole span { font-size:.6rem; color:var(--muted); text-transform:uppercase; }
    .lgnd { font-size:.76rem; color:var(--slate); display:flex; align-items:center; gap:7px; margin-bottom:6px; }
    .dot { width:10px; height:10px; border-radius:3px; display:inline-block; }

    .alert-row { display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f1f5f9; font-size:.82rem; }
    .alert-row:last-child{border-bottom:0}
    .alert-row .nm { color:var(--slate); font-weight:600; }
    .alert-row .meta { color:var(--slate-light); font-size:.72rem; }
    .empty { color:var(--muted); font-size:.8rem; text-align:center; padding:14px 0; }
    .in-dot { width:8px; height:8px; border-radius:50%; background:var(--success); box-shadow:0 0 0 3px rgba(16,185,129,.2); display:inline-block; margin-right:8px; }

    .qa { display:flex; flex-wrap:wrap; gap:10px; }
    .qa a { display:inline-flex; align-items:center; gap:8px; padding:9px 16px; border-radius:10px; border:1.5px solid var(--border); background:var(--surface); color:var(--slate); font-weight:700; font-size:.8rem; text-decoration:none; transition:.15s; }
    .qa a:hover { background:var(--teal-light); border-color:var(--teal-mid); color:var(--teal-dark); }
    .badge-status { font-size:.66rem; font-weight:800; padding:3px 10px; border-radius:999px; text-transform:uppercase; }
    .dept-bar { cursor:pointer; }
    .dept-bar:hover .bar-track { outline:2px solid var(--teal-mid); }
    @media print {
        aside, header, nav, .sidebar, .main-sidebar, .navbar, .topbar, .main-header, #sidebar, .app-sidebar { display:none !important; }
        .content-wrapper, .main-content, main, #main-wrapper, .content { margin:0 !important; padding:0 !important; width:100% !important; }
        .cc-shell { margin:0 !important; padding:0 !important; background:#fff !important; }
        .cc-top { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .no-print, .seg, #ccPrint { display:none !important; }
        .cc-card, .kpi { box-shadow:none !important; break-inside:avoid; }
        .grid { display:block !important; } .grid > .cc-card { margin-bottom:12px; }
    }
</style>

@php
    $maxDept  = max(1, optional($d['byDept']->first())->c ?? 1);
    $ptTotal  = max(1, $d['cash'] + $d['card']);
    $cashPct  = round($d['cash'] / $ptTotal * 100);
    $payBadge = ['pending'=>'background:#fef3c7;color:#92400e;','processed'=>'background:#dbeafe;color:#1e40af;','paid'=>'background:#dcfce7;color:#166534;'][$d['payStatus']] ?? 'background:#e2e8f0;color:#334155;';

    // SVG line-chart point builder (viewBox 0 0 100 38)
    $lp = function($data,$key,$max,$w=100,$h=34){
        $n=count($data); $p=[];
        foreach(array_values($data) as $i=>$r){
            $x = $n>1 ? round($i/($n-1)*$w,2) : 0;
            $y = round(2 + $h - ($max>0 ? ($r[$key]/$max*$h) : 0), 2);
            $p[]="$x,$y";
        }
        return implode(' ',$p);
    };
    $mk = function($data) use ($lp){
        $max = max(1, collect($data)->max('present'));
        $pl = $lp($data,'present',$max);
        $ll = $lp($data,'late',$max);
        $area = $pl ? "0,38 ".$pl." 100,38" : "";
        return ['present'=>$pl,'late'=>$ll,'area'=>$area];
    };
    $c14 = $mk($d['trend']); $c30 = $mk($d['trend30']);
@endphp

<div class="cc-shell">
    <div class="cc-top">
        <div>
            <h4><i class="fa fa-gauge-high me-2"></i>HR Control Center</h4>
            <div class="sub">Live workforce, attendance &amp; payroll command center</div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button id="ccPrint" onclick="window.print()" style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:10px;padding:8px 16px;font-size:.78rem;font-weight:700;cursor:pointer;">
                <i class="fa fa-file-pdf me-1"></i> Export PDF
            </button>
            <div class="cc-clock">
                <div class="t" id="ccClock">--:--</div>
                <div class="dte">{{ now()->format('l, F d, Y') }}</div>
                <div class="dte" id="ccUpdated" style="opacity:.7;"></div>
            </div>
        </div>
    </div>

    {{-- KPI ROW (clickable) --}}
    <div class="kpi-grid">
        <a class="kpi" href="/pages/management/e201"><div class="ic" style="background:var(--teal-light);color:var(--teal);"><i class="fa fa-users"></i></div><div><div class="val" data-kpi="active">{{ number_format($d['active']) }}</div><div class="lbl">Active Employees</div></div></a>
        <a class="kpi" href="#whoInCard"><div class="ic" style="background:#dcfce7;color:var(--success);"><i class="fa fa-user-check"></i></div><div><div class="val" data-kpi="present">{{ number_format($d['present']) }}</div><div class="lbl">Present Today</div></div></a>
        <a class="kpi" href="#absCard"><div class="ic" style="background:#fee2e2;color:var(--danger);"><i class="fa fa-user-xmark"></i></div><div><div class="val" data-kpi="absent">{{ number_format($d['absent']) }}</div><div class="lbl">Absent Today</div></div></a>
        <a class="kpi" href="/pages/modules/leaverequests"><div class="ic" style="background:#e0f2fe;color:var(--info);"><i class="fa fa-plane-departure"></i></div><div><div class="val" data-kpi="leaveob">{{ number_format($d['onLeave'] + $d['onOb']) }}</div><div class="lbl">On Leave / OB</div></div></a>
        <a class="kpi" href="#apprCard"><div class="ic" style="background:#fef3c7;color:var(--warning);"><i class="fa fa-bell"></i></div><div><div class="val" data-kpi="pendTotal">{{ number_format($d['pendTotal']) }}</div><div class="lbl">Pending Approvals</div></div></a>
        <a class="kpi" href="#trendCard"><div class="ic" style="background:var(--teal-light);color:var(--teal);"><i class="fa fa-clock"></i></div><div><div class="val" data-kpi="ontime">{{ $d['onTimeRate'] }}%</div><div class="lbl">On-time (30d)</div></div></a>
    </div>

    <div class="grid">
        {{-- Attendance patterns — line chart with toggle --}}
        <div class="cc-card cc-8" id="trendCard">
            <div class="cc-h"><div class="i"><i class="fa fa-chart-line"></i></div><h6>Attendance Patterns</h6>
                <div class="right seg">
                    <button class="on" data-range="14">14 days</button>
                    <button data-range="30">30 days</button>
                </div>
            </div>
            <div class="cc-b">
                <div class="d-flex gap-4 mb-1" style="font-size:.72rem;color:var(--slate-light);">
                    <span><span class="dot" style="background:var(--teal);"></span> Present</span>
                    <span><span class="dot" style="background:var(--warning);"></span> Late</span>
                </div>
                <svg class="chart trend-svg" data-range="14" viewBox="0 0 100 38" preserveAspectRatio="none">
                    <polygon class="area-p" points="{{ $c14['area'] }}"></polygon>
                    <polyline class="line-p" points="{{ $c14['present'] }}"></polyline>
                    <polyline class="line-l" points="{{ $c14['late'] }}"></polyline>
                </svg>
                <svg class="chart trend-svg d-none" data-range="30" viewBox="0 0 100 38" preserveAspectRatio="none">
                    <polygon class="area-p" points="{{ $c30['area'] }}"></polygon>
                    <polyline class="line-p" points="{{ $c30['present'] }}"></polyline>
                    <polyline class="line-l" points="{{ $c30['late'] }}"></polyline>
                </svg>
                <div class="d-flex justify-content-between" style="font-size:.6rem;color:var(--muted);margin-top:4px;">
                    <span>{{ $d['trend'][0]['label'] }}</span><span>today</span>
                </div>
            </div>
        </div>

        {{-- Pending approvals --}}
        <div class="cc-card cc-4" id="apprCard">
            <div class="cc-h"><div class="i"><i class="fa fa-list-check"></i></div><h6>Pending Approvals</h6></div>
            <div class="cc-b">
                <div class="appr"><span class="n">Leave Requests</span><span class="d-flex align-items-center gap-2"><span class="pill" data-pend="pendLeave">{{ $d['pendLeave'] }}</span><a href="/pages/modules/leaverequests">Open</a></span></div>
                <div class="appr"><span class="n">Overtime Requests</span><span class="d-flex align-items-center gap-2"><span class="pill" data-pend="pendOt">{{ $d['pendOt'] }}</span><a href="/pages/modules/overtimerequests">Open</a></span></div>
                <div class="appr"><span class="n">Schedule Changes</span><span class="d-flex align-items-center gap-2"><span class="pill" data-pend="pendSched">{{ $d['pendSched'] }}</span><a href="/pages/modules/schedulerequests">Open</a></span></div>
            </div>
        </div>

        {{-- Who's in now --}}
        <div class="cc-card cc-4" id="whoInCard">
            <div class="cc-h"><div class="i"><i class="fa fa-circle-dot"></i></div><h6>Who's In Now</h6><span class="right badge-status" id="whoInBadge" style="background:#dcfce7;color:#166534;">{{ $d['whoInCount'] }} in</span></div>
            <div class="cc-b" style="max-height:220px;overflow:auto;" id="whoInList">
                @forelse($d['whoIn'] as $w)
                    <div class="alert-row"><span class="nm text-uppercase"><span class="in-dot"></span>{{ $w->name }}</span><span class="meta">{{ \Carbon\Carbon::parse($w->time_in)->format('h:i A') }}</span></div>
                @empty<div class="empty">Nobody is currently clocked in.</div>@endforelse
            </div>
        </div>

        {{-- Today's attendance --}}
        <div class="cc-card cc-4">
            <div class="cc-h"><div class="i"><i class="fa fa-user-clock"></i></div><h6>Today's Attendance</h6></div>
            <div class="cc-b">
                <div class="att-grid">
                    <div class="att"><div class="v text-success" data-today="present">{{ $d['present'] }}</div><div class="l">Present</div></div>
                    <div class="att"><div class="v text-danger" data-today="absent">{{ $d['absent'] }}</div><div class="l">Absent</div></div>
                    <div class="att"><div class="v" style="color:var(--info)" data-today="onLeave">{{ $d['onLeave'] }}</div><div class="l">On Leave</div></div>
                    <div class="att"><div class="v" style="color:var(--teal)" data-today="onOb">{{ $d['onOb'] }}</div><div class="l">On OB</div></div>
                </div>
                <div class="mt-3 text-center" style="font-size:.78rem;color:var(--slate-light);">
                    <i class="fa fa-hourglass-half text-warning me-1"></i> <span data-today="late">{{ $d['late'] }}</span> late today &middot; <span data-today="scheduled">{{ $d['scheduled'] }}</span> scheduled
                </div>
            </div>
        </div>

        {{-- Payroll snapshot --}}
        <div class="cc-card cc-4">
            <div class="cc-h"><div class="i"><i class="fa fa-file-invoice-dollar"></i></div><h6>Payroll Snapshot</h6></div>
            <div class="cc-b">
                @if($d['payDate'])
                    <div class="d-flex justify-content-between align-items-center mb-2"><span style="font-size:.8rem;color:var(--slate-light);">Latest pay date</span><b style="font-size:.85rem;">{{ \Carbon\Carbon::parse($d['payDate'])->format('M d, Y') }}</b></div>
                    <div class="d-flex justify-content-between align-items-center mb-2"><span style="font-size:.8rem;color:var(--slate-light);">Status</span><span class="badge-status" style="{{ $payBadge }}">{{ strtoupper($d['payStatus'] ?? 'n/a') }}{{ $d['payLocked'] ? ' · LOCKED' : '' }}</span></div>
                    <div class="d-flex justify-content-between align-items-center mb-2"><span style="font-size:.8rem;color:var(--slate-light);">Employees paid</span><b>{{ number_format($d['payHeadcount']) }}</b></div>
                    <div class="d-flex justify-content-between align-items-center"><span style="font-size:.8rem;color:var(--slate-light);">Net total</span><b style="color:var(--teal);">&#8369;{{ number_format($d['payNet'],2) }}</b></div>
                @else<div class="empty">No payroll generated yet.</div>@endif
            </div>
        </div>

        {{-- Workforce donut --}}
        <div class="cc-card cc-4">
            <div class="cc-h"><div class="i"><i class="fa fa-id-card"></i></div><h6>Workforce</h6></div>
            <div class="cc-b">
                <div class="d-flex align-items-center gap-3">
                    <div class="donut" style="background:conic-gradient(var(--teal) 0% {{ $cashPct }}%, var(--teal-mid) {{ $cashPct }}% 100%);"><div class="hole"><b>{{ number_format($d['total']) }}</b><span>Total</span></div></div>
                    <div class="flex-fill">
                        <div class="lgnd"><span class="dot" style="background:var(--teal);"></span> Cash payroll <b class="ms-auto">{{ $d['cash'] }}</b></div>
                        <div class="lgnd"><span class="dot" style="background:var(--teal-mid);"></span> Card payroll <b class="ms-auto">{{ $d['card'] }}</b></div>
                        <hr class="my-2" style="opacity:.3;">
                        <div class="lgnd"><span class="dot" style="background:var(--success);"></span> Active <b class="ms-auto">{{ $d['active'] }}</b></div>
                        <div class="lgnd"><span class="dot" style="background:var(--danger);"></span> Resigned <b class="ms-auto">{{ $d['resigned'] }}</b></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Headcount by department --}}
        <div class="cc-card cc-4">
            <div class="cc-h"><div class="i"><i class="fa fa-sitemap"></i></div><h6>Headcount by Department</h6></div>
            <div class="cc-b">
                @forelse($d['byDept'] as $r)
                    <div class="bar-row dept-bar" data-id="{{ $r->id }}" data-name="{{ $r->name }}"><div class="nm" title="{{ $r->name }}">{{ $r->name }}</div><div class="bar-track"><div class="bar-fill" style="width:{{ round($r->c/$maxDept*100) }}%;background:linear-gradient(90deg,var(--teal-mid),var(--teal));"></div></div><div class="bar-val">{{ $r->c }}</div></div>
                @empty<div class="empty">No data.</div>@endforelse
            </div>
        </div>

        {{-- Absenteeism by department --}}
        <div class="cc-card cc-4" id="absCard">
            <div class="cc-h"><div class="i"><i class="fa fa-triangle-exclamation"></i></div><h6>Absenteeism by Dept (30d)</h6></div>
            <div class="cc-b">
                @forelse($d['absentByDept'] as $r)
                    @php $rate = $r->scheduled > 0 ? round(($r->scheduled - $r->present)/$r->scheduled*100) : 0;
                         $col = $rate >= 25 ? 'var(--danger)' : ($rate >= 10 ? 'var(--warning)' : 'var(--success)'); @endphp
                    <div class="bar-row"><div class="nm" title="{{ $r->name }}">{{ $r->name }}</div><div class="bar-track"><div class="bar-fill" style="width:{{ $rate }}%;background:{{ $col }};"></div></div><div class="bar-val">{{ $rate }}%</div></div>
                @empty<div class="empty">Not enough schedule data.</div>@endforelse
            </div>
        </div>

        {{-- Top late --}}
        <div class="cc-card cc-4">
            <div class="cc-h"><div class="i"><i class="fa fa-hourglass-half"></i></div><h6>Top Late (30 days)</h6></div>
            <div class="cc-b">
                @forelse($d['topLate'] as $r)
                    <div class="alert-row"><span class="nm text-uppercase">{{ $r->name }}</span><span class="meta">{{ $r->late }} min &middot; {{ $r->occ }}x</span></div>
                @empty<div class="empty">No late records.</div>@endforelse
            </div>
        </div>

        {{-- People alerts --}}
        <div class="cc-card cc-8">
            <div class="cc-h"><div class="i"><i class="fa fa-bell"></i></div><h6>People Alerts</h6></div>
            <div class="cc-b">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="lgnd mb-2"><i class="fa fa-cake-candles text-warning"></i> <b>Birthdays this week</b></div>
                        @forelse($d['birthdays'] as $b)<div class="alert-row"><span class="nm text-capitalize">{{ $b->name }}</span><span class="meta">{{ $b->bday }}</span></div>@empty<div class="empty">None this week.</div>@endforelse
                    </div>
                    <div class="col-md-4">
                        <div class="lgnd mb-2"><i class="fa fa-user-shield" style="color:var(--info)"></i> <b>Regularization due</b></div>
                        @forelse($d['regularize'] as $r)<div class="alert-row"><span class="nm text-capitalize">{{ $r->name }}</span><span class="meta">{{ \Carbon\Carbon::parse($r->due)->format('M d') }}</span></div>@empty<div class="empty">None upcoming.</div>@endforelse
                    </div>
                    <div class="col-md-4">
                        <div class="lgnd mb-2"><i class="fa fa-calendar-xmark text-danger"></i> <b>No schedule today</b></div>
                        <div class="att" style="margin-top:6px;"><div class="v text-danger">{{ $d['noSchedule'] }}</div><div class="l">active staff unscheduled</div></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="cc-card cc-12">
            <div class="cc-h"><div class="i"><i class="fa fa-bolt"></i></div><h6>Quick Actions</h6></div>
            <div class="cc-b">
                <div class="qa">
                    <a href="/pages/modules/payroll"><i class="fa fa-file-invoice-dollar"></i> Payroll</a>
                    <a href="/pages/modules/registration"><i class="fa fa-user-plus"></i> Enroll Employee</a>
                    <a href="/pages/modules/leaverequests"><i class="fa fa-calendar-check"></i> Leave Requests</a>
                    <a href="/pages/modules/overtimerequests"><i class="fa fa-user-clock"></i> OT Requests</a>
                    <a href="/pages/modules/schedulerequests"><i class="fa fa-calendar-plus"></i> Schedule Requests</a>
                    <a href="/attendance-import"><i class="fa fa-file-import"></i> Imports</a>
                    <a href="/pages/management/e201"><i class="fa fa-id-card-alt"></i> Admin E-201</a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Department drill-down modal --}}
<div class="modal fade" id="deptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content" style="border:0;border-radius:14px;overflow:hidden;">
            <div class="modal-header" style="background:var(--teal);color:#fff;border:0;">
                <h6 class="modal-title" id="deptModalTitle">Department</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="deptModalBody" style="background:var(--bg);"></div>
        </div>
    </div>
</div>

<script>
    (function () {
        const el = document.getElementById('ccClock');
        function tick() { const n = new Date(); el.textContent = n.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' }); }
        tick(); setInterval(tick, 20000);

        document.querySelectorAll('.seg button').forEach(b => b.addEventListener('click', function () {
            document.querySelectorAll('.seg button').forEach(x => x.classList.remove('on'));
            this.classList.add('on');
            const r = this.dataset.range;
            document.querySelectorAll('.trend-svg').forEach(s => s.classList.toggle('d-none', s.dataset.range !== r));
        }));

        function setStat(attr, obj) { for (const k in obj) { const el = document.querySelector(`[data-${attr}="${k}"]`); if (el) el.textContent = obj[k]; } }
        function refreshLive() {
            axios.get('/pages/management/hr-dashboard/live').then(({ data }) => {
                setStat('kpi', data.kpi); setStat('today', data.today); setStat('pend', data.pending);
                document.getElementById('whoInBadge').textContent = data.whoIn.count + ' in';
                const box = document.getElementById('whoInList');
                box.innerHTML = data.whoIn.list.length
                    ? data.whoIn.list.map(w => `<div class="alert-row"><span class="nm text-uppercase"><span class="in-dot"></span>${w.name}</span><span class="meta">${w.time_in}</span></div>`).join('')
                    : '<div class="empty">Nobody is currently clocked in.</div>';
                document.getElementById('ccUpdated').textContent = 'Updated ' + new Date().toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
            }).catch(() => {});
        }
        refreshLive();
        setInterval(refreshLive, 120000);

        let deptModal = null;
        document.querySelectorAll('.dept-bar').forEach(bar => bar.addEventListener('click', function () {
            const id = this.dataset.id || '', name = this.dataset.name || 'Department';
            document.getElementById('deptModalTitle').textContent = name + ' — employees';
            const body = document.getElementById('deptModalBody');
            body.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm"></div> Loading…</div>';
            if (!deptModal) deptModal = new bootstrap.Modal(document.getElementById('deptModal'));
            deptModal.show();
            axios.get('/pages/management/hr-dashboard/dept', { params: { id } }).then(res => {
                const rows = res.data || [];
                body.innerHTML = rows.length
                    ? '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">' + rows.map((r,i) => `<div style="display:flex;justify-content:space-between;padding:9px 14px;${i?'border-top:1px solid #f1f5f9;':''}"><span style="font-weight:600;color:#334155;text-transform:uppercase;font-size:.82rem;">${r.name}</span><span style="color:#94a3b8;font-size:.74rem;">${r.empid}</span></div>`).join('') + '</div><div class="text-muted mt-2" style="font-size:.72rem;">' + rows.length + ' active employee(s)</div>'
                    : '<div class="text-center text-muted py-4">No active employees.</div>';
            }).catch(() => { body.innerHTML = '<div class="text-center text-danger py-4">Failed to load.</div>'; });
        }));
    })();
</script>
@endsection
