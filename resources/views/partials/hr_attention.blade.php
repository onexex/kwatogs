{{--
  HR Attention Center — floating panel + once-per-login welcome banner.
  Included once in layout/app.blade.php, gated by @can('hrdashboard'). The topbar bell trigger
  (#hrAttnBell / #hrAttnBadge) lives in the layout's navbar. Data comes from
  hr-dashboard.attention (AJAX); nothing here is server-rendered per row, so non-HR page loads
  are untouched. Colours are hard-coded brand tokens because the dashboard's :root vars are
  scoped to that one view and aren't available globally.
--}}
<style>
    .hr-attn-panel{position:fixed;top:62px;right:14px;width:360px;max-width:calc(100vw - 28px);background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 10px 40px rgba(15,23,42,.18);z-index:1060;display:none;flex-direction:column;overflow:hidden}
    .hr-attn-panel.open{display:flex}
    .hr-attn-head{display:flex;align-items:center;gap:9px;padding:13px 14px;border-bottom:1px solid #e2e8f0}
    .hr-attn-head-ic{width:28px;height:28px;border-radius:8px;background:#e0f2f1;color:#006666;display:flex;align-items:center;justify-content:center;font-size:14px}
    .hr-attn-head-tx{display:flex;flex-direction:column;line-height:1.3}
    .hr-attn-title{font-size:14px;font-weight:600;color:#334155}
    .hr-attn-sub{font-size:11.5px;color:#94a3b8}
    .hr-attn-x{margin-left:auto;background:none;border:none;color:#94a3b8;cursor:pointer;font-size:15px;padding:2px 4px;line-height:1}
    .hr-attn-body{max-height:min(60vh,460px);overflow:auto;padding:4px 6px 6px}
    .hr-attn-glabel{font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;padding:10px 10px 3px}
    .hr-attn-row{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:9px;text-decoration:none;color:inherit}
    .hr-attn-row:hover{background:#f1f5f9}
    .hr-attn-chip{flex:none;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px}
    .hr-attn-rtext{flex:1;min-width:0;display:flex;flex-direction:column}
    .hr-attn-rlabel{font-size:13.5px;color:#334155;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .hr-attn-rsub{font-size:11.5px;color:#94a3b8}
    .hr-attn-pill{flex:none;font-size:12px;font-weight:600;border-radius:20px;padding:1px 9px}
    .hr-attn-chev{color:#cbd5e1;font-size:13px}
    .hr-attn-foot{display:flex;align-items:center;justify-content:center;gap:6px;padding:11px;border-top:1px solid #e2e8f0;font-size:12.5px;color:#006666;text-decoration:none;font-weight:600}
    .hr-attn-foot:hover{background:#f0fdfa}
    .hr-attn-empty{padding:26px 18px;text-align:center;color:#94a3b8;font-size:12.5px}
    .hr-attn-banner{position:fixed;top:14px;right:14px;width:320px;max-width:calc(100vw - 28px);background:#fff;border:1px solid #e2e8f0;border-left:4px solid #008080;border-radius:12px;box-shadow:0 10px 40px rgba(15,23,42,.18);z-index:1080;padding:13px 15px;animation:hrAttnIn .25s ease}
    @keyframes hrAttnIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}
    .hr-attn-banner-top{display:flex;align-items:center;gap:8px;margin-bottom:6px}
    .hr-attn-banner-ic{width:24px;height:24px;border-radius:7px;background:#e0f2f1;color:#006666;display:flex;align-items:center;justify-content:center;font-size:13px}
    .hr-attn-banner-hi{font-size:12.5px;color:#64748b;font-weight:600}
    .hr-attn-banner-title{font-size:15px;color:#334155;font-weight:600;margin-bottom:11px}
    .hr-attn-banner-actions{display:flex;gap:8px}
    .hr-attn-btn-primary{font-size:12.5px;color:#fff;background:#008080;border:none;border-radius:8px;padding:6px 16px;cursor:pointer;font-weight:600}
    .hr-attn-btn-primary:hover{background:#006666}
    .hr-attn-btn-ghost{font-size:12.5px;color:#64748b;background:none;border:1px solid #e2e8f0;border-radius:8px;padding:6px 14px;cursor:pointer}
</style>

<div id="hrAttnPanel" class="hr-attn-panel" role="dialog" aria-label="Needs your attention">
    <div class="hr-attn-head">
        <span class="hr-attn-head-ic"><i class="fa-solid fa-list-check"></i></span>
        <span class="hr-attn-head-tx">
            <span class="hr-attn-title">Needs your attention</span>
            <span class="hr-attn-sub"><span id="hrAttnCount">0 items</span> &middot; updated just now</span>
        </span>
        <button type="button" id="hrAttnClose" class="hr-attn-x" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="hrAttnBody" class="hr-attn-body">
        <div class="hr-attn-empty">Loading&hellip;</div>
    </div>
    <a href="{{ route('hr-dashboard.index') }}" class="hr-attn-foot">Open HR Dashboard <i class="fa-solid fa-arrow-right"></i></a>
</div>

@if(session('hr_attention_greet'))
    <div id="hrAttnBanner" class="hr-attn-banner">
        <div class="hr-attn-banner-top">
            <span class="hr-attn-banner-ic"><i class="fa-solid fa-hand"></i></span>
            <span class="hr-attn-banner-hi">Welcome back</span>
            <button type="button" id="hrAttnBannerX" class="hr-attn-x" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="hr-attn-banner-title"><span id="hrAttnBannerCount">&hellip;</span> items need your attention</div>
        <div class="hr-attn-banner-actions">
            <button type="button" id="hrAttnBannerReview" class="hr-attn-btn-primary">Review</button>
            <button type="button" id="hrAttnBannerLater" class="hr-attn-btn-ghost">Later</button>
        </div>
    </div>
    @php session()->forget('hr_attention_greet'); @endphp
@endif

<script>
(function () {
    var SEV = {
        danger:  { bg:'#fee2e2', fg:'#b91c1c' },
        info:    { bg:'#e0edff', fg:'#1d4ed8' },
        warning: { bg:'#fef3c7', fg:'#b45309' },
        purple:  { bg:'#ede9fe', fg:'#6d28d9' },
        muted:   { bg:'#f1f5f9', fg:'#64748b' }
    };
    var URL = "{{ route('hr-dashboard.attention') }}";
    var bell   = document.getElementById('hrAttnBell');
    var badge  = document.getElementById('hrAttnBadge');
    var panel  = document.getElementById('hrAttnPanel');
    var body   = document.getElementById('hrAttnBody');
    var banner = document.getElementById('hrAttnBanner');
    if (!panel) return;

    function esc(s){ return String(s == null ? '' : s).replace(/[&<>"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }

    function render(data){
        var total = (data && data.total) || 0;
        if (badge){
            if (total > 0){ badge.textContent = total > 99 ? '99+' : total; badge.style.display = ''; }
            else { badge.style.display = 'none'; }
        }
        var count = document.getElementById('hrAttnCount');
        if (count) count.textContent = total + (total === 1 ? ' item' : ' items');

        if (banner){
            if (total > 0){ var bc = document.getElementById('hrAttnBannerCount'); if (bc) bc.textContent = total; }
            else { banner.remove(); banner = null; }
        }

        var groups = (data && data.groups) || [];
        if (!groups.length){
            body.innerHTML = '<div class="hr-attn-empty">You\'re all caught up. Nothing needs your attention right now.</div>';
            return;
        }
        body.innerHTML = groups.map(function (g){
            var rows = g.rows.map(function (r){
                var s = SEV[r.severity] || SEV.muted;
                return '<a class="hr-attn-row" href="' + esc(r.url) + '">'
                    + '<span class="hr-attn-chip" style="background:' + s.bg + ';color:' + s.fg + '"><i class="fa-solid ' + esc(r.icon) + '"></i></span>'
                    + '<span class="hr-attn-rtext"><span class="hr-attn-rlabel">' + esc(r.label) + '</span><span class="hr-attn-rsub">' + esc(r.sub) + '</span></span>'
                    + '<span class="hr-attn-pill" style="background:' + s.bg + ';color:' + s.fg + '">' + (r.count > 99 ? '99+' : r.count) + '</span>'
                    + '<i class="fa-solid fa-chevron-right hr-attn-chev"></i></a>';
            }).join('');
            return '<div class="hr-attn-glabel">' + esc(g.label) + '</div>' + rows;
        }).join('');
    }

    function load(){ axios.get(URL).then(function (res){ render(res.data); }).catch(function(){}); }

    function toggle(show){ panel.classList.toggle('open', typeof show === 'boolean' ? show : !panel.classList.contains('open')); }
    if (bell) bell.addEventListener('click', function (e){ e.preventDefault(); toggle(); });
    var close = document.getElementById('hrAttnClose');
    if (close) close.addEventListener('click', function (){ toggle(false); });
    document.addEventListener('click', function (e){
        if (panel.classList.contains('open') && !panel.contains(e.target) && (!bell || !bell.contains(e.target))) toggle(false);
    });

    if (banner){
        var bx = document.getElementById('hrAttnBannerX');
        var bl = document.getElementById('hrAttnBannerLater');
        var br = document.getElementById('hrAttnBannerReview');
        if (bx) bx.addEventListener('click', function (){ banner.remove(); });
        if (bl) bl.addEventListener('click', function (){ banner.remove(); });
        if (br) br.addEventListener('click', function (){ if (banner) banner.remove(); toggle(true); });
    }

    load();
    setInterval(load, 120000);
})();
</script>
