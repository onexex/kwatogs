<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8; --bg:#f1f5f9;
        --surface:#fff; --border:#e2e8f0; --radius-card:14px; --radius-input:8px;
        --shadow-card:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);
    }
    .rpt-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .rpt-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:20px; }
    .rpt-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; letter-spacing:-.2px; }
    .rpt-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .sc { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); margin-bottom:20px; overflow:hidden; }
    .sc-head { display:flex; align-items:center; gap:10px; padding:14px 22px; border-bottom:1px solid var(--border);
        background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .sc-icon { width:30px; height:30px; border-radius:8px; background:var(--teal-light); color:var(--teal);
        display:flex; align-items:center; justify-content:center; font-size:.78rem; }
    .sc-title { font-size:.78rem; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; margin:0; }
    .field-label { font-size:.68rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px; display:block; }
    .form-control, .form-select { border:1.5px solid var(--border); border-radius:var(--radius-input); font-size:.83rem;
        color:var(--slate); background:#fafbfc; padding:.45rem .7rem; }
    .form-control:focus, .form-select:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
    .btn-filter { background:var(--teal); color:#fff; border:none; border-radius:var(--radius-input); padding:.5rem 1rem;
        font-size:.8rem; font-weight:700; cursor:pointer; box-shadow:0 4px 14px rgba(0,128,128,.25); transition:all .2s; white-space:nowrap; }
    .btn-filter:hover { background:var(--teal-dark); color:#fff; transform:translateY(-1px); }
    .btn-ghost { background:var(--surface); color:var(--slate-light); border:1.5px solid var(--border); border-radius:var(--radius-input);
        padding:.5rem .9rem; font-size:.8rem; font-weight:700; cursor:pointer; transition:all .2s; white-space:nowrap; }
    .btn-ghost:hover { background:var(--teal-light); border-color:var(--teal-mid); color:var(--teal); }
    .stat { flex:1; min-width:140px; background:linear-gradient(135deg,#f0fdfa,#f8fbfa); border:1px solid var(--border);
        border-radius:12px; padding:13px 16px; }
    .stat .l { font-size:.62rem; font-weight:800; color:var(--slate-light); text-transform:uppercase; letter-spacing:.5px; }
    .stat .v { font-size:1.25rem; font-weight:800; color:var(--teal-dark); margin-top:2px; }
    .rpt-table thead th { position:sticky; top:0; z-index:5; background:#f8fafc; font-size:.68rem; font-weight:700;
        color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; border-bottom:2px solid var(--border); white-space:nowrap; padding:11px 14px; }
    .rpt-table tbody td { font-size:.82rem; color:var(--slate); vertical-align:middle; padding:10px 14px; }
    .rpt-table tbody tr:hover { background:var(--teal-light); }
    .rpt-table tfoot td { background:var(--teal-light); font-weight:800; color:var(--teal-dark); padding:12px 14px; border-top:2px solid var(--teal); }
    .pill { font-size:.66rem; font-weight:700; background:#eef2f6; color:var(--slate); border-radius:6px; padding:2px 8px; }
    .mono { font-variant-numeric:tabular-nums; }
</style>
