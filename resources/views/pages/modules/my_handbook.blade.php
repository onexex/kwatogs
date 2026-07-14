@extends('layout.app')
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8;
        --bg:#f1f5f9; --surface:#ffffff; --border:#e2e8f0;
        --danger:#ef4444; --warning:#f59e0b; --success:#15803d;
        --radius-card:14px; --shadow-card:0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }
    .hb-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .hb-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .hb-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .hb-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .empty-state { text-align:center; padding:60px 20px; color:var(--muted); }
    .empty-state i { font-size:2.4rem; color:var(--teal-light); margin-bottom:12px; }

    /* Stat chips */
    .hb-stats { display:flex; gap:10px; flex-wrap:wrap; }
    .hb-stat { display:flex; align-items:center; gap:9px; background:#fafbfc; border:1px solid var(--border); border-radius:10px; padding:8px 14px; }
    .hb-stat .ic { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.8rem; }
    .hb-stat .ic.t { background:var(--teal-light); color:var(--teal); } .hb-stat .ic.a { background:#dcfce7; color:var(--success); } .hb-stat .ic.p { background:#fef3c7; color:#b45309; }
    .hb-stat .n { font-size:1.05rem; font-weight:800; color:var(--slate); line-height:1; }
    .hb-stat .l { font-size:.64rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-top:2px; }

    /* Workspace: list rail + reading pane */
    .hb-workspace { display:grid; grid-template-columns:340px 1fr; gap:16px; align-items:start; }
    @media (max-width:820px){ .hb-workspace { grid-template-columns:1fr; } }
    .hb-pane { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); overflow:hidden; }
    .hb-list-pane { display:flex; flex-direction:column; max-height:calc(100vh - 190px); }
    .hb-list-head { padding:12px 14px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .hb-search { width:100%; border:1.5px solid var(--border); border-radius:8px; padding:8px 12px; font-size:.82rem; color:var(--slate); background:#fafbfc; }
    .hb-search:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
    .hb-progress { margin-top:10px; font-size:.7rem; color:var(--slate-light); font-weight:700; display:flex; align-items:center; gap:8px; }
    .hb-bar { flex:1; height:6px; border-radius:6px; background:var(--border); overflow:hidden; }
    .hb-bar span { display:block; height:100%; background:var(--teal); width:0; transition:width .3s; }
    .hb-list { overflow-y:auto; flex:1; counter-reset:sec; }

    /* List rows */
    .srow { display:flex; gap:11px; padding:13px 15px; border-bottom:1px solid var(--border); cursor:pointer; transition:background .12s; border-left:3px solid transparent; }
    .srow:hover { background:var(--teal-light); }
    .srow.active { background:var(--teal-light); border-left-color:var(--teal); }
    .srow.master { background:linear-gradient(to right,#f0fdfa,#fff); border-bottom:2px solid var(--teal-light); }
    .srow.master .rtitle { color:var(--teal-dark); }
    .srow.master.active { background:var(--teal-light); }
    .srow .num { width:30px; height:30px; border-radius:8px; background:var(--teal-light); color:var(--teal); display:flex; align-items:center; justify-content:center; font-size:.8rem; font-weight:800; flex-shrink:0; }
    .srow .rmain { min-width:0; flex:1; }
    .srow .rtitle { font-size:.83rem; font-weight:700; color:var(--slate); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .srow .rmeta { font-size:.7rem; color:var(--muted); margin-top:3px; display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
    .srow .clip { color:var(--teal); }
    .srow .pill-mini { border-radius:20px; padding:1px 8px; font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.3px; }
    .pm-ack { background:#dcfce7; color:var(--success); } .pm-need { background:#fef3c7; color:#b45309; } .pm-re { background:#fee2e2; color:#b91c1c; }

    /* Reading pane */
    .hb-detail-pane { min-height:calc(100vh - 190px); }
    .hd-head { padding:24px 30px 18px; border-bottom:1px solid var(--border); }
    .hd-kicker { font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:var(--teal); }
    .hd-title { font-size:1.5rem; font-weight:800; color:var(--slate); margin:6px 0 0; line-height:1.25; }
    .hd-meta { font-size:.74rem; color:var(--slate-light); margin-top:12px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
    .hd-meta i { color:var(--muted); margin-right:5px; }
    .hd-body { padding:24px 30px; font-size:.94rem; color:var(--slate); line-height:1.75; }
    .hd-body h1,.hd-body h2,.hd-body h3 { color:var(--slate); font-weight:800; margin:1.2em 0 .5em; }
    .hd-body ul,.hd-body ol { padding-left:1.4em; margin:.6em 0; }
    .hd-body li { margin:.3em 0; }
    .hd-body p { margin:.7em 0; }
    .hd-body a { color:var(--teal-dark); }
    .hd-body img { max-width:100%; border-radius:8px; }

    .hd-doc { margin:0 30px 24px; padding:16px 18px; background:var(--teal-light); border:1px solid var(--teal-mid); border-radius:12px; display:flex; align-items:center; gap:14px; }
    .hd-doc .ai { width:40px; height:40px; border-radius:10px; background:#fff; color:var(--teal); display:flex; align-items:center; justify-content:center; font-size:1.05rem; flex-shrink:0; }
    .hd-doc .an { font-size:.82rem; font-weight:700; color:var(--teal-dark); }
    .hd-doc .as { font-size:.7rem; color:var(--slate-light); margin-top:2px; }
    .btn-view-doc { display:inline-flex; align-items:center; gap:8px; background:var(--teal); color:#fff; border:none; border-radius:8px; padding:9px 18px; font-size:.8rem; font-weight:700; cursor:pointer; transition:all .15s; white-space:nowrap; margin-left:auto; }
    .btn-view-doc:hover { background:var(--teal-dark); }

    /* Acknowledge box */
    .hb-ack { margin:0 30px 30px; border:1px solid #fed7aa; background:#fff7ed; border-radius:12px; padding:18px 20px; }
    .hb-ack.done { border-color:#bbf7d0; background:#f0fdf4; }
    .hb-ack .ah { font-size:.9rem; font-weight:800; color:#b45309; display:flex; align-items:center; gap:8px; }
    .hb-ack.done .ah { color:var(--success); }
    .hb-ack .asub { font-size:.78rem; color:var(--slate-light); margin:5px 0 12px; }
    .btn-ack { background:var(--teal); color:#fff; border:none; border-radius:8px; padding:10px 22px; font-size:.82rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
    .btn-ack:hover { background:var(--teal-dark); }
    .btn-ack:disabled { opacity:.6; cursor:default; }

    .hd-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; min-height:420px; text-align:center; color:var(--muted); padding:30px; }
    .hd-empty i { font-size:2.8rem; color:var(--teal-light); margin-bottom:14px; }

    /* Preview modal (view-only, no download) — shared pattern with My Notices */
    #docViewer { position:fixed; inset:0; z-index:20000; background:rgba(15,23,42,.9); display:none; align-items:center; justify-content:center; padding:24px; }
    #docViewer.open { display:flex; }
    .dv-shell { width:min(920px,96vw); height:min(90vh,1000px); background:#fff; border-radius:14px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,.5); }
    .dv-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 18px; background:var(--teal); color:#fff; flex-shrink:0; }
    .dv-head .t { font-size:.9rem; font-weight:700; display:flex; align-items:center; gap:8px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .dv-head .no-dl { font-size:.68rem; font-weight:700; background:rgba(255,255,255,.2); border-radius:20px; padding:3px 10px; white-space:nowrap; }
    .dv-close { background:none; border:none; color:#fff; font-size:1.1rem; cursor:pointer; line-height:1; padding:4px 8px; }
    .dv-stage { position:relative; flex:1; background:#334155; overflow:hidden; }
    .dv-doc { position:absolute; inset:0; overflow:auto; padding:16px; display:flex; flex-direction:column; align-items:center; gap:14px; }
    .dv-doc canvas { max-width:100%; height:auto; background:#fff; box-shadow:0 2px 10px rgba(0,0,0,.4); border-radius:2px; user-select:none; -webkit-user-drag:none; }
    .dv-loading { color:#cbd5e1; font-size:.85rem; display:flex; align-items:center; gap:8px; margin:auto; }
    .dv-watermark { position:absolute; inset:0; pointer-events:none; z-index:5; opacity:.9; background-repeat:repeat; }
    .dv-stage.blurred .dv-doc { filter:blur(22px); }
    .dv-stage.blurred::after { content:"Preview hidden while this window is not focused"; position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#fff; font-size:.9rem; font-weight:700; background:rgba(51,65,85,.65); z-index:10; text-align:center; padding:20px; }
    .dv-foot { padding:8px 18px; background:#f8fafc; border-top:1px solid var(--border); font-size:.72rem; color:var(--slate-light); text-align:center; flex-shrink:0; }
</style>

<div class="hb-shell" data-watermark="{{ $watermark ?? 'Confidential' }}">
    <div class="hb-topbar">
        <div>
            <p class="page-title"><i class="fa-solid fa-book-open me-2" style="color:var(--teal);"></i> Employee Handbook</p>
            <p class="page-sub">Company policies and guidelines. Read each section; some may require your acknowledgement.</p>
        </div>
        <div class="hb-stats">
            <div class="hb-stat"><div class="ic t"><i class="fa-solid fa-list"></i></div><div><div class="n" id="sTotal">0</div><div class="l">Sections</div></div></div>
            <div class="hb-stat"><div class="ic a"><i class="fa-solid fa-circle-check"></i></div><div><div class="n" id="sAck">0</div><div class="l">Acknowledged</div></div></div>
            <div class="hb-stat"><div class="ic p"><i class="fa-solid fa-hourglass-half"></i></div><div><div class="n" id="sPend">0</div><div class="l">To Acknowledge</div></div></div>
        </div>
    </div>

    <div class="hb-workspace">
        {{-- List rail --}}
        <aside class="hb-pane hb-list-pane">
            <div class="hb-list-head">
                <input type="text" class="hb-search" id="hbSearch" placeholder="Search the handbook…">
                <div class="hb-progress">
                    <div class="hb-bar"><span id="hbBar"></span></div>
                    <span id="hbPct">0%</span>
                </div>
            </div>
            <div class="hb-list" id="handbookList">
                <div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><div>Loading…</div></div>
            </div>
        </aside>

        {{-- Reading pane --}}
        <section class="hb-pane hb-detail-pane" id="hbDetail">
            <div class="hd-empty"><i class="fa-solid fa-book-open-reader"></i><div>Select a section from the list to start reading.</div></div>
        </section>
    </div>
</div>

{{-- Supporting-document preview (view-only, no download, watermarked) --}}
<div id="docViewer">
    <div class="dv-shell" oncontextmenu="return false;">
        <div class="dv-head">
            <div class="t"><i class="fa-solid fa-file-lines"></i><span id="dvTitle">Document</span></div>
            <div class="d-flex align-items-center gap-2">
                <span class="no-dl"><i class="fa-solid fa-ban me-1"></i>View only · no download</span>
                <button class="dv-close" id="dvClose" title="Close">&times;</button>
            </div>
        </div>
        <div class="dv-stage" id="dvStage" oncontextmenu="return false;">
            <div class="dv-doc" id="dvDoc"></div>
            <div class="dv-watermark" id="dvWatermark"></div>
        </div>
        <div class="dv-foot"><i class="fa-solid fa-shield-halved me-1"></i>This document is confidential. Downloading, copying or sharing is not permitted.</div>
    </div>
</div>

<script src="{{ asset('js/vendor/pdf.min.js') }}"></script>
<script>
    if (window.pdfjsLib) { pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('js/vendor/pdf.worker.min.js') }}"; }
</script>
<script src="{{ asset('js/modules/my_handbook.js') }}?v={{ @filemtime(public_path('js/modules/my_handbook.js')) ?: time() }}" defer></script>
@endsection
