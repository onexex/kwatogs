@extends('layout.app')
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8;
        --bg:#f1f5f9; --surface:#ffffff; --border:#e2e8f0;
        --danger:#ef4444; --warning:#f59e0b;
        --radius-card:14px; --shadow-card:0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }
    .mn-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .mn-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .mn-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .mn-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .badge-soft { display:inline-block; border-radius:20px; padding:4px 12px; font-size:.72rem; font-weight:700; white-space:nowrap; }
    .b-memo { background:#e0e7ff; color:#4338ca; } .b-disc { background:#fee2e2; color:#b91c1c; } .b-cat { background:#fef3c7; color:#b45309; }
    .empty-state { text-align:center; padding:60px 20px; color:var(--muted); }
    .empty-state i { font-size:2.4rem; color:var(--teal-light); margin-bottom:12px; }

    /* Stat chips in the topbar */
    .mn-stats { display:flex; gap:10px; flex-wrap:wrap; }
    .mn-stat { display:flex; align-items:center; gap:9px; background:#fafbfc; border:1px solid var(--border); border-radius:10px; padding:8px 14px; }
    .mn-stat .ic { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.8rem; }
    .mn-stat .ic.t { background:var(--teal-light); color:var(--teal); } .mn-stat .ic.d { background:#fee2e2; color:#b91c1c; } .mn-stat .ic.b { background:#e0e7ff; color:#4338ca; } .mn-stat .ic.a { background:#dcfce7; color:#15803d; }
    .mn-stat .n { font-size:1.05rem; font-weight:800; color:var(--slate); line-height:1; }
    .mn-stat .l { font-size:.64rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-top:2px; }

    /* ── Workspace: list rail + reading pane ── */
    .mn-workspace { display:grid; grid-template-columns:340px 1fr; gap:16px; align-items:start; }
    @media (max-width:820px){ .mn-workspace { grid-template-columns:1fr; } }
    .mn-pane { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); overflow:hidden; }
    .mn-list-pane { display:flex; flex-direction:column; max-height:calc(100vh - 190px); }
    .mn-list-head { padding:12px 14px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .mn-pills { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px; }
    .pill { border:1.5px solid var(--border); background:var(--surface); color:var(--slate-light); border-radius:20px; padding:5px 12px; font-size:.72rem; font-weight:700; cursor:pointer; transition:all .15s; }
    .pill:hover { border-color:var(--teal-mid); }
    .pill.active { background:var(--teal); color:#fff; border-color:var(--teal); }
    .pill .pc { background:rgba(0,0,0,.12); border-radius:10px; padding:0 7px; margin-left:5px; font-size:.66rem; }
    .pill.active .pc { background:rgba(255,255,255,.25); }
    .mn-search { width:100%; border:1.5px solid var(--border); border-radius:8px; padding:8px 12px; font-size:.82rem; color:var(--slate); background:#fafbfc; }
    .mn-search:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
    .mn-list { overflow-y:auto; flex:1; }

    /* List rows */
    .nrow { display:flex; gap:11px; padding:13px 15px; border-bottom:1px solid var(--border); cursor:pointer; transition:background .12s; border-left:3px solid transparent; }
    .nrow:hover { background:var(--teal-light); }
    .nrow.active { background:var(--teal-light); border-left-color:var(--teal); }
    .nrow .dot { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:.82rem; flex-shrink:0; }
    .nrow.memo .dot { background:var(--teal-light); color:var(--teal); } .nrow.disc .dot { background:#fee2e2; color:#b91c1c; }
    .nrow .rmain { min-width:0; flex:1; }
    .nrow .rtitle { font-size:.83rem; font-weight:700; color:var(--slate); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .nrow .rmeta { font-size:.7rem; color:var(--muted); margin-top:2px; display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
    .nrow .clip { color:var(--teal); }

    /* Reading pane */
    .mn-detail-pane { min-height:calc(100vh - 190px); }
    .nd-head { padding:22px 26px 16px; border-bottom:1px solid var(--border); }
    .nd-badges { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
    .nd-title { font-size:1.25rem; font-weight:800; color:var(--slate); margin:0; line-height:1.3; }
    .nd-meta { font-size:.76rem; color:var(--slate-light); margin-top:10px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
    .nd-meta i { color:var(--muted); margin-right:5px; }
    .nd-body { padding:22px 26px; font-size:.9rem; color:var(--slate); white-space:pre-wrap; line-height:1.65; }
    .nd-attach { margin:0 26px 24px; padding:16px 18px; background:var(--teal-light); border:1px solid var(--teal-mid); border-radius:12px; display:flex; align-items:center; gap:14px; }
    .nd-attach .ai { width:40px; height:40px; border-radius:10px; background:#fff; color:var(--teal); display:flex; align-items:center; justify-content:center; font-size:1.05rem; flex-shrink:0; }
    .nd-attach .an { font-size:.82rem; font-weight:700; color:var(--teal-dark); }
    .nd-attach .as { font-size:.7rem; color:var(--slate-light); margin-top:2px; }
    .btn-view-memo { display:inline-flex; align-items:center; gap:8px; background:var(--teal); color:#fff; border:none; border-radius:8px; padding:9px 18px; font-size:.8rem; font-weight:700; cursor:pointer; transition:all .15s; white-space:nowrap; margin-left:auto; }
    .btn-view-memo:hover { background:var(--teal-dark); }
    .nd-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; min-height:420px; text-align:center; color:var(--muted); padding:30px; }
    .nd-empty i { font-size:2.8rem; color:var(--teal-light); margin-bottom:14px; }

    /* ── Notice to Explain (NTE) ── */
    .nte-box { margin:0 26px 24px; border:1px solid #fed7aa; background:#fff7ed; border-radius:12px; padding:18px 20px; }
    .nte-box.od { border-color:#fca5a5; background:#fef2f2; }
    .nte-box.submitted { border-color:var(--border); background:#f8fafc; }
    .nte-h { font-size:.9rem; font-weight:800; color:#b45309; display:flex; align-items:center; gap:8px; }
    .nte-box.od .nte-h { color:#b91c1c; }
    .nte-box.submitted .nte-h { color:var(--slate); }
    .nte-sub { font-size:.76rem; color:var(--slate-light); margin:4px 0 12px; }
    .nte-sub .od { color:#b91c1c; font-weight:700; }
    .nte-input { width:100%; border:1.5px solid var(--border); border-radius:8px; padding:10px 12px; font-size:.86rem; color:var(--slate); background:#fff; resize:vertical; }
    .nte-input:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); outline:none; }
    .nte-file { margin-top:10px; font-size:.78rem; color:var(--slate-light); }
    .nte-file label { display:block; font-weight:700; margin-bottom:4px; }
    .nte-file input { font-size:.78rem; }
    .nte-err { color:var(--danger); font-size:.76rem; margin-top:8px; min-height:1em; }
    .nte-submit { margin-top:12px; background:var(--teal); color:#fff; border:none; border-radius:8px; padding:10px 20px; font-size:.82rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
    .nte-submit:hover { background:var(--teal-dark); }
    .nte-submit:disabled { opacity:.6; cursor:default; }
    .nte-body { font-size:.86rem; color:var(--slate); white-space:pre-wrap; line-height:1.6; margin-top:8px; padding:12px 14px; background:#fff; border:1px solid var(--border); border-radius:8px; }
    .nte-doc { display:inline-flex; align-items:center; gap:6px; margin-top:10px; font-size:.78rem; font-weight:700; color:var(--teal-dark); text-decoration:none; }
    .nte-decision { margin-top:12px; padding:10px 14px; border-radius:8px; font-size:.8rem; font-weight:700; display:flex; align-items:center; gap:8px; }
    .nte-decision.ok { background:#dcfce7; color:#15803d; }
    .nte-decision.warn { background:#fee2e2; color:#b91c1c; }
    .nte-decision.pend { background:#e0e7ff; color:#4338ca; }

    /* ── Acknowledge receipt (disciplinary) ── */
    .ack-box { margin:0 26px 24px; border:1px solid #bfdbfe; background:#eff6ff; border-radius:12px; padding:18px 20px; }
    .ack-h { font-size:.9rem; font-weight:800; color:#1d4ed8; display:flex; align-items:center; gap:8px; }
    .ack-s { font-size:.82rem; color:var(--slate); line-height:1.55; margin-top:6px; }
    .ack-btn { margin-top:14px; background:var(--teal); color:#fff; border:none; border-radius:8px; padding:10px 20px; font-size:.82rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
    .ack-btn:hover { background:var(--teal-dark); }
    .ack-btn:disabled { opacity:.6; cursor:default; }
    .ack-box.done { display:flex; align-items:center; gap:12px; border-color:#bbf7d0; background:#f0fdf4; }
    .ack-box.done > i { color:#16a34a; font-size:1.3rem; }
    .ack-box.done .ack-t { font-size:.86rem; font-weight:800; color:#15803d; }
    .ack-box.done .ack-s { margin-top:2px; }

    /* ── Preview modal (view-only, no download) ── */
    #memoViewer { position:fixed; inset:0; z-index:20000; background:rgba(15,23,42,.9); display:none; align-items:center; justify-content:center; padding:24px; }
    #memoViewer.open { display:flex; }
    .mv-shell { width:min(920px,96vw); height:min(90vh,1000px); background:#fff; border-radius:14px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,.5); }
    .mv-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 18px; background:var(--teal); color:#fff; flex-shrink:0; }
    .mv-head .t { font-size:.9rem; font-weight:700; display:flex; align-items:center; gap:8px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .mv-head .no-dl { font-size:.68rem; font-weight:700; background:rgba(255,255,255,.2); border-radius:20px; padding:3px 10px; white-space:nowrap; }
    .mv-close { background:none; border:none; color:#fff; font-size:1.1rem; cursor:pointer; line-height:1; padding:4px 8px; }
    .mv-stage { position:relative; flex:1; background:#334155; overflow:hidden; }
    /* Documents render to <canvas> — no native PDF viewer, no downloadable file, right-click is ours to block. */
    .mv-doc { position:absolute; inset:0; overflow:auto; padding:16px; display:flex; flex-direction:column; align-items:center; gap:14px; }
    .mv-doc canvas { max-width:100%; height:auto; background:#fff; box-shadow:0 2px 10px rgba(0,0,0,.4); border-radius:2px; user-select:none; -webkit-user-drag:none; }
    .mv-loading { color:#cbd5e1; font-size:.85rem; display:flex; align-items:center; gap:8px; margin:auto; }
    /* Screenshot-deterrent watermark, tiled over the document. Never intercepts scroll. */
    .mv-watermark { position:absolute; inset:0; pointer-events:none; z-index:5; opacity:.9; background-repeat:repeat; }
    .mv-stage.blurred .mv-doc { filter:blur(22px); }
    .mv-stage.blurred::after { content:"Preview hidden while this window is not focused"; position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#fff; font-size:.9rem; font-weight:700; background:rgba(51,65,85,.65); z-index:10; text-align:center; padding:20px; }
    .mv-foot { padding:8px 18px; background:#f8fafc; border-top:1px solid var(--border); font-size:.72rem; color:var(--slate-light); text-align:center; flex-shrink:0; }
</style>

<div class="mn-shell" data-watermark="{{ $watermark ?? 'Confidential' }}">
    <div class="mn-topbar">
        <div>
            <p class="page-title"><i class="fa-solid fa-bell me-2" style="color:var(--teal);"></i> My Notices</p>
            <p class="page-sub">Memos and notices issued to you by HR. Opening this page marks them as read.</p>
        </div>
        <div class="mn-stats">
            <div class="mn-stat"><div class="ic t"><i class="fa-solid fa-inbox"></i></div><div><div class="n" id="sTotal">0</div><div class="l">Total</div></div></div>
            <div class="mn-stat"><div class="ic b"><i class="fa-solid fa-file-lines"></i></div><div><div class="n" id="sMemo">0</div><div class="l">Memos</div></div></div>
            <div class="mn-stat"><div class="ic d"><i class="fa-solid fa-gavel"></i></div><div><div class="n" id="sDisc">0</div><div class="l">Disciplinary</div></div></div>
            <div class="mn-stat"><div class="ic a"><i class="fa-solid fa-file-signature"></i></div><div><div class="n" id="sDocs">0</div><div class="l">Signed Docs</div></div></div>
        </div>
    </div>

    <div class="mn-workspace">
        {{-- List rail --}}
        <aside class="mn-pane mn-list-pane">
            <div class="mn-list-head">
                <div class="mn-pills">
                    <button class="pill active" data-filter="all">All <span class="pc" id="cAll">0</span></button>
                    <button class="pill" data-filter="memo">Memos <span class="pc" id="cMemo">0</span></button>
                    <button class="pill" data-filter="disciplinary">Disciplinary <span class="pc" id="cDisc">0</span></button>
                </div>
                <input type="text" class="mn-search" id="mnSearch" placeholder="Search title or content…">
            </div>
            <div class="mn-list" id="myNoticeList">
                <div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><div>Loading…</div></div>
            </div>
        </aside>

        {{-- Reading pane --}}
        <section class="mn-pane mn-detail-pane" id="mnDetail">
            <div class="nd-empty"><i class="fa-solid fa-envelope-open-text"></i><div>Select a notice from the list to read it.</div></div>
        </section>
    </div>
</div>

{{-- Signed-memo preview (view-only, no download, watermarked) --}}
<div id="memoViewer">
    <div class="mv-shell" oncontextmenu="return false;">
        <div class="mv-head">
            <div class="t"><i class="fa-solid fa-file-signature"></i><span id="mvTitle">Signed Memo</span></div>
            <div class="d-flex align-items-center gap-2">
                <span class="no-dl"><i class="fa-solid fa-ban me-1"></i>View only · no download</span>
                <button class="mv-close" id="mvClose" title="Close">&times;</button>
            </div>
        </div>
        <div class="mv-stage" id="mvStage" oncontextmenu="return false;">
            <div class="mv-doc" id="mvDoc"></div>
            <div class="mv-watermark" id="mvWatermark"></div>
        </div>
        <div class="mv-foot"><i class="fa-solid fa-shield-halved me-1"></i>This document is confidential. Downloading, copying or sharing is not permitted.</div>
    </div>
</div>

<script src="{{ asset('js/vendor/pdf.min.js') }}"></script>
<script>
    // Render PDFs to canvas (no native viewer / no download / no toolbar).
    if (window.pdfjsLib) { pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('js/vendor/pdf.worker.min.js') }}"; }
</script>
<script src="{{ asset('js/modules/my_notices.js') }}?v={{ @filemtime(public_path('js/modules/my_notices.js')) ?: time() }}" defer></script>
@endsection
