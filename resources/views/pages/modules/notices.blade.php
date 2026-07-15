@extends('layout.app')
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8;
        --bg:#f1f5f9; --surface:#ffffff; --border:#e2e8f0;
        --danger:#ef4444; --success:#10b981; --warning:#f59e0b;
        --radius-card:14px; --radius-input:8px;
        --shadow-card:0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }
    .ntc-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .ntc-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; }
    .ntc-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .ntc-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .btn-teal { background:var(--teal); color:#fff; border:none; border-radius:8px; padding:10px 20px; font-size:.82rem; font-weight:700; cursor:pointer; box-shadow:0 4px 14px rgba(0,128,128,.25); transition:all .2s; display:inline-flex; align-items:center; gap:8px; }
    .btn-teal:hover { background:var(--teal-dark); transform:translateY(-1px); color:#fff; }

    /* Compact stat chips (topbar) */
    .ntc-stats { display:flex; gap:10px; flex-wrap:wrap; }
    .ntc-stat { display:flex; align-items:center; gap:9px; background:#fafbfc; border:1px solid var(--border); border-radius:10px; padding:8px 14px; }
    .ntc-stat .ic { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.8rem; flex-shrink:0; }
    .ntc-stat .ic.t{ background:var(--teal-light); color:var(--teal);} .ntc-stat .ic.w{ background:#fef3c7; color:#b45309;} .ntc-stat .ic.d{ background:#fee2e2; color:#b91c1c;} .ntc-stat .ic.b{ background:#e0e7ff; color:#4338ca;}
    .ntc-stat .n { font-size:1.05rem; font-weight:800; color:var(--slate); line-height:1; }
    .ntc-stat .l { font-size:.64rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-top:2px; }

    /* Alert flash */
    .flash-alert { background:linear-gradient(135deg,#fee2e2,#fff1f2); border:1px solid #fca5a5; border-left:5px solid var(--danger); border-radius:var(--radius-card); padding:16px 20px; margin-bottom:16px; box-shadow:var(--shadow-card); }
    .flash-alert .fa-title { color:#b91c1c; font-weight:800; font-size:.9rem; display:flex; align-items:center; gap:9px; }
    .flash-alert .names { margin-top:8px; display:flex; flex-wrap:wrap; gap:8px; }
    /* Amber variant for the at-risk banner (revealed by ?focus=atrisk). */
    .flash-alert.amber { background:linear-gradient(135deg,#fef3c7,#fffbeb); border-color:#fcd34d; border-left-color:var(--warning); }
    .flash-alert.amber .fa-title { color:#b45309; }
    /* Deep-link highlight pulse for a scrolled-to section. */
    .attn-pulse { animation:attnPulse 1.1s ease-in-out 2; }
    @keyframes attnPulse { 0%,100% { box-shadow:var(--shadow-card); } 50% { box-shadow:0 0 0 4px rgba(0,128,128,.35); } }
    /* NTE focus filter banner. */
    .focus-flash { display:flex; align-items:center; gap:10px; background:#eef2ff; border:1px solid #c7d2fe; border-left:4px solid #4338ca; border-radius:var(--radius-card); padding:12px 16px; margin-bottom:16px; font-size:.82rem; color:#3730a3; }
    .focus-flash i { color:#4338ca; }
    .focus-flash-x { margin-left:auto; background:#fff; border:1px solid #c7d2fe; color:#4338ca; border-radius:20px; padding:3px 12px; font-size:.72rem; font-weight:700; cursor:pointer; }
    .focus-flash-x:hover { background:#4338ca; color:#fff; }
    .flash-name { background:#fff; border:1px solid #fca5a5; color:#b91c1c; border-radius:20px; padding:5px 12px; font-size:.78rem; font-weight:700; }
    .flash-name .n { background:#b91c1c; color:#fff; border-radius:10px; padding:1px 7px; margin-left:6px; font-size:.68rem; }

    .sc { background:var(--surface); border-radius:var(--radius-card); border:1px solid var(--border); box-shadow:var(--shadow-card); margin-bottom:16px; overflow:hidden; }
    .sc-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 20px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); flex-wrap:wrap; cursor:pointer; }
    .sc-head-left { display:flex; align-items:center; gap:10px; }
    .sc-icon { width:30px; height:30px; border-radius:8px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:.78rem; flex-shrink:0; }
    .sc-title { font-size:.78rem; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; margin:0; }
    .sc-title .cnt { background:#fee2e2; color:#b91c1c; border-radius:10px; padding:1px 8px; margin-left:6px; font-size:.68rem; }
    .sc-caret { color:var(--muted); transition:transform .2s; }
    .sc.collapsed .sc-caret { transform:rotate(-90deg); }
    .sc.collapsed #recList { display:none; }

    .badge-soft { display:inline-block; border-radius:20px; padding:4px 12px; font-size:.72rem; font-weight:700; white-space:nowrap; }
    .b-memo { background:#e0e7ff; color:#4338ca; } .b-disc { background:#fee2e2; color:#b91c1c; }
    .b-active { background:#dcfce7; color:#15803d; } .b-void { background:#f1f5f9; color:#64748b; }
    .b-cat { background:#fef3c7; color:#b45309; }

    .btn-mini { border:1.5px solid var(--border); background:var(--surface); border-radius:8px; padding:5px 11px; font-size:.74rem; font-weight:700; cursor:pointer; transition:all .15s; display:inline-flex; align-items:center; gap:5px; color:var(--slate); }
    .btn-mini.edit:hover { background:var(--teal-light); border-color:var(--teal-mid); }
    .btn-mini.del { color:var(--danger); } .btn-mini.del:hover { background:#fff5f5; border-color:var(--danger); }
    .btn-mini.ok { color:var(--success); } .btn-mini.ok:hover { background:#f0fdf4; border-color:var(--success); }
    .btn-mini.warn { color:var(--warning); } .btn-mini.warn:hover { background:#fffbeb; border-color:var(--warning); }

    .rec-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 18px; border-bottom:1px solid var(--border); }
    .rec-row:last-child { border-bottom:0; }
    .rec-row .who { font-weight:700; color:var(--slate); }
    .rec-row .why { font-size:.76rem; color:var(--slate-light); margin-top:3px; max-width:560px; }

    /* ── Workspace: list rail + reading pane ── */
    .ntc-workspace { display:grid; grid-template-columns:360px 1fr; gap:16px; align-items:start; }
    @media (max-width:900px){ .ntc-workspace { grid-template-columns:1fr; } }
    .ntc-pane { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); overflow:hidden; }
    .ntc-list-pane { display:flex; flex-direction:column; max-height:calc(100vh - 210px); }
    .ntc-list-head { padding:12px 14px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .ntc-pills { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px; }
    .pill { border:1.5px solid var(--border); background:var(--surface); color:var(--slate-light); border-radius:20px; padding:5px 12px; font-size:.72rem; font-weight:700; cursor:pointer; transition:all .15s; }
    .pill:hover { border-color:var(--teal-mid); }
    .pill.active { background:var(--teal); color:#fff; border-color:var(--teal); }
    .pill .pc { background:rgba(0,0,0,.12); border-radius:10px; padding:0 7px; margin-left:5px; font-size:.66rem; }
    .pill.active .pc { background:rgba(255,255,255,.25); }
    .list-tools { display:flex; gap:8px; }
    .ntc-search { flex:1; border:1.5px solid var(--border); border-radius:8px; padding:8px 12px; font-size:.82rem; color:var(--slate); background:#fafbfc; }
    .ntc-search:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
    .ntc-statusfilter { border:1.5px solid var(--border); border-radius:8px; padding:8px 10px; font-size:.78rem; color:var(--slate); background:#fafbfc; flex:0 0 auto; }
    .ntc-statusfilter:focus { border-color:var(--teal); outline:none; }
    .ntc-list { overflow-y:auto; flex:1; }

    /* List rows */
    .nrow { display:flex; gap:11px; padding:13px 15px; border-bottom:1px solid var(--border); cursor:pointer; transition:background .12s; border-left:3px solid transparent; }
    .nrow:hover { background:var(--teal-light); }
    .nrow.active { background:var(--teal-light); border-left-color:var(--teal); }
    .nrow.voided { opacity:.62; }
    .nrow .dot { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:.82rem; flex-shrink:0; }
    .nrow.memo .dot { background:var(--teal-light); color:var(--teal); } .nrow.disc .dot { background:#fee2e2; color:#b91c1c; }
    .nrow .rmain { min-width:0; flex:1; }
    .nrow .rtop { display:flex; align-items:center; gap:6px; }
    .nrow .rname { font-size:.82rem; font-weight:700; color:var(--slate); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex:1; min-width:0; }
    .nrow .rdate { font-size:.66rem; color:var(--muted); flex-shrink:0; }
    .nrow .rtitle { font-size:.76rem; color:var(--slate-light); margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .nrow .rmeta { font-size:.66rem; color:var(--muted); margin-top:5px; display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
    .nrow .clip { color:var(--teal); }
    .mini-tag { display:inline-flex; align-items:center; gap:3px; border-radius:6px; padding:1px 6px; font-size:.62rem; font-weight:700; }
    .t-seen { background:#f1f5f9; color:#64748b; } .t-nte { background:#e0e7ff; color:#4338ca; } .t-nte-od { background:#fee2e2; color:#b91c1c; } .t-nte-rev { background:#dcfce7; color:#15803d; }
    .list-empty { text-align:center; padding:50px 20px; color:var(--muted); }
    .list-empty i { font-size:2rem; color:var(--teal-light); margin-bottom:10px; display:block; }

    /* Reading pane */
    .ntc-detail-pane { min-height:calc(100vh - 210px); display:flex; flex-direction:column; }
    .nd-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; flex:1; min-height:420px; text-align:center; color:var(--muted); padding:30px; }
    .nd-empty i { font-size:2.8rem; color:var(--teal-light); margin-bottom:14px; }
    .nd-head { padding:22px 26px 16px; border-bottom:1px solid var(--border); }
    .nd-badges { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; align-items:center; }
    .nd-title { font-size:1.25rem; font-weight:800; color:var(--slate); margin:0; line-height:1.3; }
    .nd-meta { font-size:.76rem; color:var(--slate-light); margin-top:12px; display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:8px 20px; }
    .nd-meta .mi { display:flex; align-items:center; gap:8px; }
    .nd-meta .mi i { color:var(--muted); width:14px; text-align:center; }
    .nd-meta .mi .mk { color:var(--muted); }
    .nd-body { padding:22px 26px; font-size:.9rem; color:var(--slate); white-space:pre-wrap; line-height:1.65; flex:1; }
    .nd-section-h { font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin:0 26px 8px; }
    .nd-attach { margin:0 26px 22px; padding:16px 18px; background:var(--teal-light); border:1px solid var(--teal-mid); border-radius:12px; display:flex; align-items:center; gap:14px; }
    .nd-attach .ai { width:40px; height:40px; border-radius:10px; background:#fff; color:var(--teal); display:flex; align-items:center; justify-content:center; font-size:1.05rem; flex-shrink:0; }
    .nd-attach .an { font-size:.82rem; font-weight:700; color:var(--teal-dark); }
    .nd-attach .as { font-size:.7rem; color:var(--slate-light); margin-top:2px; }
    .nd-attach a { display:inline-flex; align-items:center; gap:8px; background:var(--teal); color:#fff; border-radius:8px; padding:9px 18px; font-size:.8rem; font-weight:700; text-decoration:none; margin-left:auto; white-space:nowrap; }
    .nd-attach a:hover { background:var(--teal-dark); }

    /* NTE block inside the reading pane */
    .nte-box { margin:0 26px 22px; border:1px solid #fed7aa; background:#fff7ed; border-radius:12px; padding:16px 18px; }
    .nte-box.od { border-color:#fca5a5; background:#fef2f2; }
    .nte-box.submitted { border-color:var(--border); background:#f8fafc; }
    .nte-h { font-size:.82rem; font-weight:800; color:#b45309; display:flex; align-items:center; gap:8px; }
    .nte-box.od .nte-h { color:#b91c1c; } .nte-box.submitted .nte-h { color:var(--slate); }
    .nte-sub { font-size:.74rem; color:var(--slate-light); margin:4px 0 0; }
    .nte-resp { font-size:.86rem; color:var(--slate); white-space:pre-wrap; line-height:1.6; margin-top:10px; padding:12px 14px; background:#fff; border:1px solid var(--border); border-radius:8px; }
    .nte-doc { display:inline-flex; align-items:center; gap:6px; margin-top:10px; font-size:.78rem; font-weight:700; color:var(--teal-dark); text-decoration:none; }
    .nte-decision { margin-top:12px; padding:9px 14px; border-radius:8px; font-size:.8rem; font-weight:700; display:flex; align-items:center; gap:8px; }
    .nte-decision.ok { background:#dcfce7; color:#15803d; } .nte-decision.warn { background:#fee2e2; color:#b91c1c; }

    /* Action bar (sticky footer of the reading pane) */
    .nd-actions { margin-top:auto; padding:16px 26px; border-top:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .nd-actions .lock { display:inline-flex; align-items:center; gap:7px; font-size:.78rem; font-weight:700; color:var(--slate-light); }
    .nd-actions .spacer { flex:1; }
    .btn-act { border:none; border-radius:8px; padding:9px 18px; font-size:.8rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:7px; transition:all .15s; }
    .btn-act.primary { background:var(--teal); color:#fff; } .btn-act.primary:hover { background:var(--teal-dark); }
    .btn-act.ghost { background:var(--surface); border:1.5px solid var(--border); color:var(--slate); } .btn-act.ghost:hover { border-color:var(--teal-mid); background:var(--teal-light); }
    .btn-act.danger { background:var(--surface); border:1.5px solid #fecaca; color:var(--danger); } .btn-act.danger:hover { background:#fff5f5; border-color:var(--danger); }
    .btn-act.review { background:#4338ca; color:#fff; } .btn-act.review:hover { background:#3730a3; }

    /* Shared form styles (used by the modals) */
    .field-label { font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px; display:block; }
    .field-label .req { color:var(--danger); margin-left:2px; }
    .form-control, .form-select { border:1.5px solid var(--border); border-radius:var(--radius-input); font-size:.875rem; color:var(--slate); background:#fafbfc; padding:.55rem .85rem; }
    .form-control:focus, .form-select:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
    #mdlNotice .modal-content { border-radius:var(--radius-card); border:none; overflow:hidden; }
    #mdlNotice .modal-header { background:var(--teal); color:#fff; border-bottom:none; padding:16px 22px; }
    #mdlNotice .modal-title { color:#fff; } #mdlNotice .btn-close { filter:brightness(0) invert(1); }
    #mdlNotice .modal-body { background:var(--bg); padding:22px; }
    /* Uniform, all-caps dropdowns in the Issue Notice modal (display only) */
    #mdlNotice .form-select, #mdlNotice .form-select option { text-transform:uppercase; letter-spacing:.3px; }
    #mdlNotice .modal-footer { background:var(--surface); border-top:1px solid var(--border); }

    /* Bulk recipient pickers (Send To = multiple / department) */
    .recip-toolbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:8px; }
    .recip-toolbar .count { font-size:.72rem; font-weight:700; color:var(--slate-light); white-space:nowrap; }
    .recip-toolbar .form-control { flex:1; min-width:160px; text-transform:none; }
    .link-btn { background:none; border:none; color:var(--teal); font-size:.74rem; font-weight:700; cursor:pointer; padding:2px 6px; }
    .link-btn:hover { color:var(--teal-dark); text-decoration:underline; }
    .recip-list { max-height:200px; overflow-y:auto; background:var(--surface); border:1.5px solid var(--border); border-radius:var(--radius-input); text-transform:none; }
    .recip-row { display:flex; align-items:center; gap:10px; padding:8px 12px; border-bottom:1px solid var(--border); cursor:pointer; margin:0; font-size:.82rem; color:var(--slate); }
    .recip-row:last-child { border-bottom:0; }
    .recip-row:hover { background:var(--teal-light); }
    .recip-row input { width:15px; height:15px; accent-color:var(--teal); flex-shrink:0; cursor:pointer; }
    .recip-row .dept { font-size:.7rem; color:var(--muted); margin-left:auto; white-space:nowrap; }
    .recip-empty { padding:14px 12px; text-align:center; color:var(--muted); font-size:.8rem; }
    .recip-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(170px,1fr)); gap:8px; }
    .recip-chip { display:flex; align-items:center; gap:8px; background:var(--surface); border:1.5px solid var(--border); border-radius:var(--radius-input); padding:8px 12px; font-size:.8rem; font-weight:600; color:var(--slate); cursor:pointer; margin:0; transition:all .15s; }
    .recip-chip:hover { border-color:var(--teal-mid); }
    .recip-chip.checked { background:var(--teal-light); border-color:var(--teal); }
    .recip-chip input { width:15px; height:15px; accent-color:var(--teal); flex-shrink:0; cursor:pointer; }
    .recip-hint { font-size:.72rem; color:var(--muted); margin-top:4px; display:block; }
    .btn-submit { background:linear-gradient(135deg,#008080,#006666); color:#fff; border:none; border-radius:10px; padding:10px 26px; font-size:.82rem; font-weight:700; letter-spacing:.4px; text-transform:uppercase; cursor:pointer; }
    .btn-submit:hover { color:#fff; transform:translateY(-1px); }
</style>

<div class="ntc-shell">

    <div class="ntc-topbar">
        <div>
            <p class="page-title"><i class="fa-solid fa-file-circle-exclamation me-2" style="color:var(--teal);"></i> Notices &amp; Memos</p>
            <p class="page-sub">Issue memos and disciplinary notices. {{ $d['stats']['suspend'] }} active disciplinary notices auto-recommends suspension for HR review.</p>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="ntc-stats">
                <div class="ntc-stat"><div class="ic t"><i class="fa-solid fa-file-lines"></i></div><div><div class="n">{{ $d['stats']['issuedThisMonth'] }}</div><div class="l">This Month</div></div></div>
                <div class="ntc-stat"><div class="ic d"><i class="fa-solid fa-gavel"></i></div><div><div class="n">{{ $d['stats']['activeDisc'] }}</div><div class="l">Active Disc.</div></div></div>
                <div class="ntc-stat"><div class="ic w"><i class="fa-solid fa-user-clock"></i></div><div><div class="n">{{ $d['stats']['atRiskCount'] }}</div><div class="l">At Risk ({{ $d['stats']['warn'] }}+)</div></div></div>
                <div class="ntc-stat"><div class="ic b"><i class="fa-solid fa-ban"></i></div><div><div class="n">{{ $d['stats']['pendingRecs'] }}</div><div class="l">Pending Recs</div></div></div>
            </div>
            <button class="btn-teal" id="btnIssueNotice"><i class="fa-solid fa-plus"></i> Issue Notice</button>
        </div>
    </div>

    {{-- Alert flash: employees over the suspension threshold --}}
    @if (!empty($d['over']))
        <div class="flash-alert" id="ntcOverBanner">
            <div class="fa-title"><i class="fa-solid fa-triangle-exclamation"></i> {{ count($d['over']) }} employee(s) exceeded the disciplinary limit ({{ $d['stats']['suspend'] }}+ notices) &mdash; recommended for suspension review:</div>
            <div class="names">
                @foreach ($d['over'] as $o)
                    <span class="flash-name">{{ $o['name'] }} <span class="n">{{ $o['count'] }}</span></span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- At-risk banner: revealed by the HR Attention deep link (?focus=atrisk). There is no
         standalone at-risk list elsewhere, so this names them on demand. Hidden by default. --}}
    @if (!empty($d['atRisk']))
        <div class="flash-alert amber" id="ntcAtRiskBanner" style="display:none;">
            <div class="fa-title"><i class="fa-solid fa-user-clock"></i> {{ count($d['atRisk']) }} employee(s) approaching the disciplinary limit ({{ $d['stats']['warn'] }}+ notices):</div>
            <div class="names">
                @foreach ($d['atRisk'] as $a)
                    <span class="flash-name">{{ $a['name'] }} <span class="n">{{ $a['count'] }}</span></span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Suspension recommendations (collapsible) --}}
    <div class="sc" id="recCard">
        <div class="sc-head" id="recToggle">
            <div class="sc-head-left"><div class="sc-icon"><i class="fa-solid fa-ban"></i></div><h5 class="sc-title">Suspension Recommendations <span class="cnt" id="recCount">0</span></h5></div>
            <i class="fa-solid fa-chevron-down sc-caret"></i>
        </div>
        <div id="recList">
            <div class="rec-row"><span class="text-muted">Loading&hellip;</span></div>
        </div>
    </div>

    {{-- HR Attention deep-link banner (?focus=nte): NTE-only list filter, with "Show all". --}}
    <div id="ntcFocusFlash"></div>

    {{-- Workspace: notice list rail + reading pane --}}
    <div class="ntc-workspace">
        <aside class="ntc-pane ntc-list-pane">
            <div class="ntc-list-head">
                <div class="ntc-pills">
                    <button class="pill active" data-filter="">All <span class="pc" id="cAll">0</span></button>
                    <button class="pill" data-filter="memo">Memos <span class="pc" id="cMemo">0</span></button>
                    <button class="pill" data-filter="disciplinary">Disciplinary <span class="pc" id="cDisc">0</span></button>
                </div>
                <div class="list-tools">
                    <input type="text" class="ntc-search" id="fSearch" placeholder="Search name / title…">
                    <select class="ntc-statusfilter" id="fStatus"><option value="">All</option><option value="active">Active</option><option value="void">Void</option></select>
                </div>
            </div>
            <div class="ntc-list" id="noticeList">
                <div class="list-empty"><i class="fa-solid fa-spinner fa-spin"></i>Loading…</div>
            </div>
        </aside>

        <section class="ntc-pane ntc-detail-pane" id="noticeDetail">
            <div class="nd-empty"><i class="fa-solid fa-envelope-open-text"></i><div>Select a notice from the list to view it and take action.</div></div>
        </section>
    </div>
</div>

{{-- Issue / Edit Notice Modal --}}
<div class="modal fade" id="mdlNotice" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-file-circle-exclamation me-2"></i><span id="mdlTitle">Issue Notice</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="noticeId">
                <div class="row g-3">
                    <div class="col-lg-7" id="recipientModeWrap">
                        <label class="field-label" for="selRecipientMode">Send To <span class="req">*</span></label>
                        <select class="form-select" id="selRecipientMode">
                            <option value="single">Single employee</option>
                            <option value="employees">Multiple employees</option>
                            <option value="department">Department(s)</option>
                            <option value="all">All active employees</option>
                        </select>
                        <span class="recip-hint">Bulk sending is available for memos only.</span>
                        <span class="text-danger small d-block mt-1" id="err-recipient_mode"></span>
                    </div>
                    <div class="col-lg-5">
                        <label class="field-label" for="selType">Type <span class="req">*</span></label>
                        <select class="form-select" id="selType">
                            <option value="memo">Memo (informational)</option>
                            <option value="disciplinary">Disciplinary Notice (counts toward suspension)</option>
                        </select>
                    </div>
                    <div class="col-12" id="empSingleWrap">
                        <label class="field-label" for="selEmployee">Employee <span class="req">*</span></label>
                        <select class="form-select" id="selEmployee"><option value="">Select employee…</option></select>
                        <span class="text-danger small d-block mt-1" id="err-employee_id"></span>
                    </div>
                    <div class="col-12" id="empMultiWrap" style="display:none;">
                        <label class="field-label">Employees <span class="req">*</span></label>
                        <div class="recip-toolbar">
                            <input type="text" class="form-control form-control-sm" id="txtEmpSearch" placeholder="Search name / department…">
                            <span class="count"><span id="empPickCount">0</span> selected</span>
                            <button type="button" class="link-btn" id="btnEmpAll">Select all</button>
                            <button type="button" class="link-btn" id="btnEmpClear">Clear</button>
                        </div>
                        <div class="recip-list" id="empCheckList"></div>
                        <span class="text-danger small d-block mt-1" id="err-employee_ids"></span>
                    </div>
                    <div class="col-12" id="deptMultiWrap" style="display:none;">
                        <label class="field-label">Departments <span class="req">*</span></label>
                        <div class="recip-toolbar">
                            <span class="count"><span id="deptPickCount">0</span> of {{ $departments->count() }} selected</span>
                            <button type="button" class="link-btn" id="btnDeptAll">Select all</button>
                            <button type="button" class="link-btn" id="btnDeptClear">Clear</button>
                        </div>
                        <div class="recip-grid">
                            @forelse ($departments as $dept)
                                <label class="recip-chip"><input type="checkbox" class="chk-dept" value="{{ $dept->id }}"><span>{{ $dept->dep_name }}</span></label>
                            @empty
                                <div class="text-muted small">No departments found.</div>
                            @endforelse
                        </div>
                        <span class="text-danger small d-block mt-1" id="err-department_ids"></span>
                    </div>
                    <div class="col-12" id="allWrap" style="display:none;">
                        <div class="recip-hint" style="font-size:.8rem; color:var(--slate-light);"><i class="fa-solid fa-users me-1"></i> The memo will be sent to every active employee.</div>
                    </div>
                    <div class="col-lg-7" id="catWrap" style="display:none;">
                        <label class="field-label" for="selCategory">Category</label>
                        <select class="form-select" id="selCategory">
                            <option value="">— Select reason —</option>
                            <option>Tardiness</option>
                            <option>Absenteeism / AWOL</option>
                            <option>Misconduct</option>
                            <option>Insubordination</option>
                            <option>Policy Violation</option>
                            <option>Performance</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="col-lg-5">
                        <label class="field-label" for="txtIssuedAt">Date Issued</label>
                        <input type="date" class="form-control" id="txtIssuedAt">
                    </div>
                    <div class="col-12">
                        <label class="field-label" for="txtTitle">Title / Subject <span class="req">*</span></label>
                        <input type="text" class="form-control" id="txtTitle" placeholder="e.g. Notice to Explain — Repeated Tardiness">
                        <span class="text-danger small d-block mt-1" id="err-title"></span>
                    </div>
                    <div class="col-12">
                        <label class="field-label" for="txtBody">Details <span class="req">*</span></label>
                        <textarea class="form-control" id="txtBody" rows="5" placeholder="Describe the notice / memo…"></textarea>
                        <span class="text-danger small d-block mt-1" id="err-body"></span>
                    </div>
                    {{-- Signed-memo attachment: memo-only, preview-only for the employee (no download). --}}
                    <div class="col-12" id="attachWrap">
                        <label class="field-label" for="fileAttachment"><i class="fa-solid fa-file-signature me-1"></i> Signed Memo (scan) <span class="text-muted" style="text-transform:none;font-weight:600;">— optional, PDF or image</span></label>
                        <input type="file" class="form-control" id="fileAttachment" accept=".pdf,.jpg,.jpeg,.png">
                        <span class="recip-hint"><i class="fa-solid fa-eye me-1"></i> Employees can preview this but cannot download it. Max 10&nbsp;MB.</span>
                        <div id="attachCurrent" class="mt-2" style="display:none;font-size:.8rem;">
                            <span class="badge-soft b-memo"><i class="fa-solid fa-paperclip me-1"></i><span id="attachCurrentName"></span></span>
                            <a href="#" id="attachPreviewLink" target="_blank" class="ms-2" style="font-weight:700;color:var(--teal);">Preview</a>
                            <span class="text-muted ms-1" style="font-size:.72rem;">— upload a new file to replace</span>
                        </div>
                        <span class="text-danger small d-block mt-1" id="err-attachment"></span>
                    </div>
                    {{-- Notice to Explain: disciplinary only. --}}
                    <div class="col-12" id="nteWrap" style="display:none;">
                        <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:10px; padding:12px 14px;">
                            <label class="recip-chip" style="border:none; background:transparent; padding:0; font-weight:700; color:var(--slate);">
                                <input type="checkbox" id="chkRequiresResponse"> <span><i class="fa-solid fa-file-pen me-1" style="color:#b45309;"></i>Require a written explanation (Notice to Explain)</span>
                            </label>
                            <div id="respondByWrap" style="display:none; margin-top:10px; max-width:240px;">
                                <label class="field-label" for="txtRespondBy">Respond by (deadline)</label>
                                <input type="date" class="form-control" id="txtRespondBy">
                                <span class="recip-hint">The employee must submit their explanation on or before this date.</span>
                            </div>
                            <label class="recip-chip" style="border:none; background:transparent; padding:0; margin-top:12px; font-weight:700; color:var(--slate);">
                                <input type="checkbox" id="chkRequiresAck"> <span><i class="fa-solid fa-signature me-1" style="color:#1d4ed8;"></i>Require the employee to acknowledge receipt</span>
                            </label>
                            <div class="recip-hint" style="margin-top:2px;">Adds a one-click "I acknowledge receipt" button on the employee's copy (receipt only, not agreement) — timestamp + IP recorded.</div>
                        </div>
                    </div>
                    <div class="col-lg-5" id="statusWrap" style="display:none;">
                        <label class="field-label" for="selStatus">Status</label>
                        <select class="form-select" id="selStatus"><option value="active">Active</option><option value="void">Void (exclude from counts)</option></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-mini" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-submit" id="btnSaveNotice">Save Notice</button>
            </div>
        </div>
    </div>
</div>

{{-- Review Explanation (NTE) Modal --}}
<div class="modal fade" id="mdlReview" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--radius-card); border:none; overflow:hidden;">
            <div class="modal-header" style="background:var(--teal); color:#fff; border-bottom:none; padding:16px 22px;">
                <h5 class="modal-title" style="color:#fff;"><i class="fa-solid fa-file-pen me-2"></i>Review Explanation</h5>
                <button type="button" class="btn-close" style="filter:brightness(0) invert(1);" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background:var(--bg); padding:22px;">
                <input type="hidden" id="reviewId">
                <div style="background:#fff; border:1px solid var(--border); border-radius:12px; padding:16px 18px; margin-bottom:16px;">
                    <div style="font-size:.72rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px;">Notice</div>
                    <div id="revNoticeTitle" style="font-size:.95rem; font-weight:700; color:var(--slate); margin:4px 0 2px;"></div>
                    <div id="revNoticeMeta" style="font-size:.74rem; color:var(--muted);"></div>
                </div>
                <div style="background:#fff; border:1px solid var(--border); border-radius:12px; padding:16px 18px; margin-bottom:16px;">
                    <div style="font-size:.72rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px;">Employee's Explanation</div>
                    <div id="revResponseBody" style="font-size:.86rem; color:var(--slate); white-space:pre-wrap; line-height:1.6; margin-top:8px;"></div>
                    <div id="revResponseDoc" style="margin-top:12px; display:none;">
                        <a href="#" id="revDocLink" target="_blank" class="badge-soft b-memo" style="text-decoration:none;"><i class="fa-solid fa-paperclip me-1"></i><span id="revDocName">attachment</span></a>
                    </div>
                    <div id="revResponseAt" style="font-size:.72rem; color:var(--muted); margin-top:10px;"></div>
                </div>
                <div class="row g-3">
                    <div class="col-lg-5">
                        <label class="field-label" for="selDecision">Decision <span class="req">*</span></label>
                        <select class="form-select" id="selDecision">
                            <option value="">— Select —</option>
                            <option value="accepted">Explanation accepted</option>
                            <option value="further_action">For further action</option>
                        </select>
                        <span class="text-danger small d-block mt-1" id="err-decision"></span>
                    </div>
                    <div class="col-12">
                        <label class="field-label" for="txtReviewNote">Note (optional)</label>
                        <textarea class="form-control" id="txtReviewNote" rows="3" placeholder="Remarks on the decision…"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background:var(--surface); border-top:1px solid var(--border);">
                <button type="button" class="btn-mini" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-submit" id="btnSaveReview">Record Decision</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/modules/notices.js') }}?v={{ @filemtime(public_path('js/modules/notices.js')) ?: time() }}" defer></script>
@endsection
