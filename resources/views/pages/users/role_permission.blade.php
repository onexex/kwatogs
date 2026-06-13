@extends('layout.app', ['title' => 'Roles Permission'])

@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8; --bg:#f1f5f9;
        --surface:#ffffff; --border:#e2e8f0; --danger:#ef4444; --success:#10b981; --warning:#f59e0b;
        --radius-card:14px; --radius-input:8px; --shadow-card:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);
    }

    .perm-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }

    .perm-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:20px; display:flex;
        align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
    .perm-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; letter-spacing:-.2px; }
    .perm-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .btn-back-perm { background:var(--surface); color:var(--slate-light); border:1.5px solid var(--border);
        border-radius:10px; padding:9px 18px; font-size:.82rem; font-weight:700; cursor:pointer; transition:all .2s;
        text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
    .btn-back-perm:hover { background:var(--teal-light); color:var(--teal); border-color:var(--teal-mid); }

    /* ── Pill navigation ── */
    .perm-nav { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; }
    .perm-nav a { border-radius:50px; font-weight:700; font-size:.8rem; padding:9px 20px; color:var(--slate-light);
        background:var(--surface); border:1px solid var(--border); text-decoration:none; transition:all .15s;
        display:inline-flex; align-items:center; }
    .perm-nav a:hover { color:var(--teal); border-color:var(--teal-mid); }
    .perm-nav a.active { background:var(--teal); border-color:var(--teal); color:#fff; box-shadow:0 4px 14px rgba(0,128,128,.25); }

    /* ── Toolbar ── */
    .perm-toolbar { display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:18px; }
    .perm-search { position:relative; flex:1 1 280px; max-width:420px; }
    .perm-search i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:.85rem; }
    .perm-search input { width:100%; border:1.5px solid var(--border); border-radius:50px; background:var(--surface);
        padding:.6rem .9rem .6rem 2.4rem; font-size:.85rem; color:var(--slate); transition:border-color .15s, box-shadow .15s; }
    .perm-search input:focus { outline:none; border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); }
    .perm-summary { font-size:.8rem; color:var(--slate-light); font-weight:600; white-space:nowrap; }
    .perm-summary b { color:var(--teal); font-weight:800; }

    /* ── Permission group card ── */
    .perm-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); margin-bottom:18px; overflow:hidden; }
    .perm-card-head { display:flex; align-items:center; justify-content:space-between; gap:12px;
        padding:14px 20px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .perm-card-title { display:flex; align-items:center; gap:12px; }
    .perm-card-title .sc-icon { width:34px; height:34px; border-radius:9px; background:var(--teal-light); color:var(--teal);
        display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0; }
    .perm-card-title h6 { margin:0; font-size:.82rem; font-weight:800; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; }
    .perm-count { font-size:.7rem; color:var(--muted); font-weight:600; }
    .perm-count .grp-on { color:var(--teal); font-weight:800; }
    .perm-allswitch { display:flex; align-items:center; gap:10px; cursor:pointer; }
    .perm-all-label { font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; }

    /* ── Permission grid ── */
    .perm-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:10px; padding:16px 20px; }
    .perm-item { display:flex; align-items:center; justify-content:space-between; gap:12px;
        border:1px solid var(--border); border-radius:10px; padding:11px 14px; transition:all .15s; background:#fcfdfe; }
    .perm-item:hover { border-color:var(--teal-mid); background:var(--teal-light); }
    .perm-item-info { min-width:0; }
    .perm-item-name { font-size:.84rem; font-weight:600; color:var(--slate); }
    .perm-item-key { font-size:.66rem; color:var(--slate-light); background:#eef2f6; border-radius:5px;
        padding:1px 7px; font-family:ui-monospace,Menlo,Consolas,monospace; display:inline-block; margin-top:3px; }
    .perm-empty { padding:30px; text-align:center; color:var(--muted); font-size:.85rem; }

    /* ── Toggle switch ── */
    .switch { position:relative; display:inline-block; width:40px; height:20px; flex-shrink:0; }
    .switch input { opacity:0; width:0; height:0; }
    .slider { position:absolute; cursor:pointer; inset:0; background-color:var(--border); transition:.3s; border-radius:20px; }
    .slider:before { position:absolute; content:""; height:14px; width:14px; left:3px; bottom:3px; background:#fff;
        transition:.3s; border-radius:50%; box-shadow:0 1px 3px rgba(0,0,0,.2); }
    input:checked + .slider { background-color:var(--teal); }
    input:checked + .slider:before { transform:translateX(20px); }
    input:indeterminate + .slider { background-color:var(--teal-mid); }
</style>

<div class="perm-shell">

    {{-- ── Top header ── --}}
    <div class="perm-topbar">
        <div>
            <p class="page-title">Role Permissions &mdash; {{ $role->name }}</p>
            <p class="page-sub">Toggle what this role can access. Changes save instantly.</p>
        </div>
        <a href="{{ route('user-roles.index') }}" class="btn-back-perm">
            <i class="fa fa-arrow-left"></i> Back to Roles
        </a>
    </div>

    {{-- ── Permission tabs ── --}}
    <div class="perm-nav">
        <a href="{{ route('user-roles.show', ['user_role' => $role->id, 'permission' => 'page']) }}"
           class="{{ $permissiontab === 'page' ? 'active' : '' }}"><i class="fas fa-file-lines me-2"></i> Page</a>
        <a href="{{ route('user-roles.show', ['user_role' => $role->id, 'permission' => 'leave']) }}"
           class="{{ $permissiontab === 'leave' ? 'active' : '' }}"><i class="fas fa-calendar-day me-2"></i> Leave</a>
        <a href="{{ route('user-roles.show', ['user_role' => $role->id, 'permission' => 'overtime']) }}"
           class="{{ $permissiontab === 'overtime' ? 'active' : '' }}"><i class="fas fa-user-clock me-2"></i> Overtime</a>
        <a href="{{ route('user-roles.show', ['user_role' => $role->id, 'permission' => 'report']) }}"
           class="{{ $permissiontab === 'report' ? 'active' : '' }}"><i class="fas fa-chart-column me-2"></i> Report</a>
    </div>

    {{-- ── Toolbar ── --}}
    <div class="perm-toolbar">
        <div class="perm-search">
            <i class="fa fa-magnifying-glass"></i>
            <input type="text" id="permSearch" placeholder="Search permission name or key...">
        </div>
        <div class="perm-summary"><b id="permEnabledCount">0</b> of <span id="permTotalCount">0</span> enabled</div>
    </div>

    {{-- ── Permission group cards ── --}}
    <div id="permWrap">
        @foreach($permissions as $group)
            <div class="perm-card">
                <div class="perm-card-head">
                    <div class="perm-card-title">
                        <div class="sc-icon"><i class="fa-solid fa-layer-group"></i></div>
                        <div>
                            <h6>{{ $group['title'] }}</h6>
                            <span class="perm-count"><span class="grp-on">0</span>/<span class="grp-total">0</span> enabled</span>
                        </div>
                    </div>
                    <label class="perm-allswitch" title="Toggle all in this group">
                        <span class="perm-all-label">Enable all</span>
                        <span class="switch"><input type="checkbox" class="perm-group-toggle"><span class="slider"></span></span>
                    </label>
                </div>
                <div class="perm-grid">
                    @foreach($group['permissions'] as $key => $name)
                        <div class="perm-item" data-search="{{ strtolower($name . ' ' . $key) }}">
                            <div class="perm-item-info">
                                <div class="perm-item-name">{{ $name }}</div>
                                <code class="perm-item-key">{{ $key }}</code>
                            </div>
                            <label class="switch">
                                <input type="checkbox" class="permission-checkbox"
                                    data-role-id="{{ $role->id }}"
                                    data-permission="{{ $key }}"
                                    {{ $role->hasPermissionTo($key) ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const Toast = Swal.mixin({ toast:true, position:'top-end', showConfirmButton:false, timer:1800, timerProgressBar:true });

    // ── Persist a single permission change ──
    async function persist(roleId, permission, grant) {
        const res = await fetch(`/roles/${roleId}/permissions`, {
            method: grant ? 'POST' : 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ permission })
        });
        if (!res.ok) throw new Error(await res.text());
    }

    // ── Live counts + group master state ──
    function refreshCounts() {
        let totalOn = 0, total = 0;
        document.querySelectorAll('.perm-card').forEach(card => {
            const boxes = card.querySelectorAll('.permission-checkbox');
            const on = card.querySelectorAll('.permission-checkbox:checked').length;
            card.querySelector('.grp-on').textContent = on;
            card.querySelector('.grp-total').textContent = boxes.length;
            const master = card.querySelector('.perm-group-toggle');
            master.checked = boxes.length > 0 && on === boxes.length;
            master.indeterminate = on > 0 && on < boxes.length;
            totalOn += on; total += boxes.length;
        });
        document.getElementById('permEnabledCount').textContent = totalOn;
        document.getElementById('permTotalCount').textContent = total;
    }

    // ── Individual toggle ──
    document.querySelectorAll('.permission-checkbox').forEach(cb => {
        cb.addEventListener('change', async e => {
            const el = e.target, grant = el.checked;
            try {
                await persist(el.dataset.roleId, el.dataset.permission, grant);
                refreshCounts();
                Toast.fire({ icon:'success', title:`Permission ${grant ? 'granted' : 'revoked'}` });
            } catch (err) {
                el.checked = !grant;
                refreshCounts();
                Swal.fire({ icon:'error', title:'Update Failed', text:'Unable to change permission status.' });
            }
        });
    });

    // ── Group "Enable all" ──
    document.querySelectorAll('.perm-group-toggle').forEach(master => {
        master.addEventListener('change', async e => {
            const grant = e.target.checked;
            const card = e.target.closest('.perm-card');
            // only affect items currently visible (respect search filter)
            const boxes = [...card.querySelectorAll('.perm-item')]
                .filter(it => it.style.display !== 'none')
                .map(it => it.querySelector('.permission-checkbox'))
                .filter(cb => cb.checked !== grant);
            if (!boxes.length) { refreshCounts(); return; }
            try {
                await Promise.all(boxes.map(cb => {
                    cb.checked = grant;
                    return persist(cb.dataset.roleId, cb.dataset.permission, grant);
                }));
                refreshCounts();
                Toast.fire({ icon:'success', title:`Group ${grant ? 'enabled' : 'disabled'}` });
            } catch (err) {
                Swal.fire({ icon:'error', title:'Update Failed', text:'Some permissions could not be updated.' });
                location.reload();
            }
        });
    });

    // ── Search filter ──
    const search = document.getElementById('permSearch');
    search.addEventListener('input', () => {
        const q = search.value.trim().toLowerCase();
        document.querySelectorAll('.perm-card').forEach(card => {
            let visible = 0;
            card.querySelectorAll('.perm-item').forEach(it => {
                const match = !q || it.dataset.search.includes(q);
                it.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            card.style.display = visible ? '' : 'none';
        });
    });

    refreshCounts();
});
</script>
@endsection
