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
    .mn-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:20px; }
    .mn-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .mn-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }

    .notice-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); margin-bottom:14px; overflow:hidden; }
    .notice-card.disc { border-left:5px solid var(--danger); }
    .notice-card.memo { border-left:5px solid var(--teal); }
    .nc-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:16px 20px 8px; }
    .nc-title { font-size:.98rem; font-weight:700; color:var(--slate); margin:0; }
    .nc-meta { font-size:.72rem; color:var(--muted); margin-top:4px; }
    .nc-body { padding:0 20px 16px; font-size:.86rem; color:var(--slate); white-space:pre-wrap; line-height:1.55; }
    .badge-soft { display:inline-block; border-radius:20px; padding:4px 12px; font-size:.72rem; font-weight:700; white-space:nowrap; }
    .b-memo { background:#e0e7ff; color:#4338ca; } .b-disc { background:#fee2e2; color:#b91c1c; } .b-cat { background:#fef3c7; color:#b45309; }
    .empty-state { text-align:center; padding:60px 20px; color:var(--muted); }
    .empty-state i { font-size:2.4rem; color:var(--teal-light); margin-bottom:12px; }

    /* Filter bar */
    .mn-filters { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:14px; }
    .mn-pills { display:flex; gap:8px; flex-wrap:wrap; }
    .pill { border:1.5px solid var(--border); background:var(--surface); color:var(--slate-light); border-radius:20px; padding:6px 15px; font-size:.76rem; font-weight:700; cursor:pointer; transition:all .15s; }
    .pill:hover { border-color:var(--teal-mid); }
    .pill.active { background:var(--teal); color:#fff; border-color:var(--teal); }
    .pill .pc { background:rgba(0,0,0,.12); border-radius:10px; padding:0 7px; margin-left:5px; font-size:.68rem; }
    .pill.active .pc { background:rgba(255,255,255,.25); }
    .mn-search { flex:1; min-width:200px; max-width:340px; border:1.5px solid var(--border); border-radius:8px; padding:7px 12px; font-size:.83rem; color:var(--slate); background:#fafbfc; }
    .mn-search:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
</style>

<div class="mn-shell">
    <div class="mn-topbar">
        <p class="page-title"><i class="fa-solid fa-bell me-2" style="color:var(--teal);"></i> My Notices</p>
        <p class="page-sub">Memos and notices issued to you by HR. Opening this page marks them as read.</p>

        <div class="mn-filters">
            <div class="mn-pills">
                <button class="pill active" data-filter="all">All <span class="pc" id="cAll">0</span></button>
                <button class="pill" data-filter="memo">Memos <span class="pc" id="cMemo">0</span></button>
                <button class="pill" data-filter="disciplinary">Disciplinary <span class="pc" id="cDisc">0</span></button>
            </div>
            <input type="text" class="mn-search" id="mnSearch" placeholder="Search title or content…">
        </div>
    </div>

    <div id="myNoticeList">
        <div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><div>Loading your notices…</div></div>
    </div>
</div>

<script src="{{ asset('js/modules/my_notices.js') }}" defer></script>
@endsection
