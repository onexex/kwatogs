@extends('layout.app', ['title' => 'Roles Permission'])

@section('content')

<style>
    /* ── Design tokens (shared with Edit Employee / Leave / Classification) ── */
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
    .perm-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    /* ── Top header bar ──────────────────────────────────────── */
    .perm-topbar {
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
    .perm-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .perm-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }

    .btn-back-perm {
        background: var(--surface);
        color: var(--slate-light);
        border: 1.5px solid var(--border);
        border-radius: 10px;
        padding: 10px 22px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: .4px;
        cursor: pointer;
        transition: all .2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-back-perm:hover { background: var(--bg); color: var(--slate); }

    /* ── Pill navigation ─────────────────────────────────────── */
    .perm-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }
    .perm-nav a {
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.8rem;
        padding: 9px 20px;
        color: var(--slate-light);
        background: var(--surface);
        border: 1px solid var(--border);
        text-decoration: none;
        transition: all .15s;
        display: inline-flex;
        align-items: center;
    }
    .perm-nav a:hover { color: var(--teal); border-color: var(--teal-mid); }
    .perm-nav a.active {
        background: var(--teal);
        border-color: var(--teal);
        color: #fff;
        box-shadow: 0 4px 14px rgba(0,128,128,.25);
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

    /* ── Table styling ───────────────────────────────────────── */
    .perm-table thead th {
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
    .perm-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .perm-table tbody tr:hover { background: var(--teal-light); }

    .perm-group-row td {
        background: var(--teal-light);
        color: var(--teal-dark);
        font-weight: 700;
        font-size: 0.73rem;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 10px 16px;
    }

    /* ── Toggle switch ────────────────────────────────────────── */
    .switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 20px;
    }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: var(--border);
        transition: .3s;
        border-radius: 20px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 14px;
        width: 14px;
        left: 3px;
        bottom: 3px;
        background-color: #fff;
        transition: .3s;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    input:checked + .slider { background-color: var(--teal); }
    input:checked + .slider:before { transform: translateX(20px); }
</style>

<div class="perm-shell">

    {{-- ── Top header ── --}}
    <div class="perm-topbar">
        <div>
            <p class="page-title">Roles Permission &mdash; {{ $role->name }}</p>
            <p class="page-sub">Manage page, leave, overtime, and report permissions for this role</p>
        </div>
        <a href="{{ route('user-roles.index') }}" class="btn-back-perm">
            <i class="fa fa-arrow-left"></i> Back to Roles
        </a>
    </div>

    {{-- ── Permission tabs ── --}}
    <div class="perm-nav">
        <a href="{{ route('user-roles.show', ['user_role' => $role->id, 'permission' => 'page']) }}"
           class="{{ $permissiontab === 'page' ? 'active' : '' }}">
           <i class="fas fa-file-alt me-2"></i> Page Permissions
        </a>
        <a href="{{ route('user-roles.show', ['user_role' => $role->id, 'permission' => 'leave']) }}"
           class="{{ $permissiontab === 'leave' ? 'active' : '' }}">
           <i class="fas fa-file-alt me-2"></i> Leave Permissions
        </a>
        <a href="{{ route('user-roles.show', ['user_role' => $role->id, 'permission' => 'overtime']) }}"
           class="{{ $permissiontab === 'overtime' ? 'active' : '' }}">
           <i class="fas fa-file-alt me-2"></i> Overtime Permissions
        </a>
        <a href="{{ route('user-roles.show', ['user_role' => $role->id, 'permission' => 'report']) }}"
           class="{{ $permissiontab === 'report' ? 'active' : '' }}">
           <i class="fas fa-file-alt me-2"></i> Report Permissions
        </a>
    </div>

    {{-- ── Permission Records ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <h5 class="sc-title">Permission List</h5>
            </div>
        </div>
        <div class="sc-body">
            <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover align-middle perm-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 100px;">Index</th>
                            <th>Permission Name</th>
                            <th class="pe-4 text-center" style="width: 150px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions as $group)
                            <tr class="perm-group-row">
                                <td colspan="3" class="ps-4">
                                    <i class="fas fa-folder-open me-2"></i> {{ strtoupper($group['title']) }}
                                </td>
                            </tr>

                            @foreach($group['permissions'] as $key => $name)
                                <tr>
                                    <td class="ps-4 text-muted small">
                                        {{ $loop->parent->iteration }}.{{ $loop->iteration }}
                                    </td>
                                    <td class="fw-medium text-dark">
                                        {{ $name }}
                                        <br><small class="text-muted fw-normal">{{ $key }}</small>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <label class="switch">
                                            <input
                                                type="checkbox"
                                                class="permission-checkbox"
                                                data-role-id="{{ $role->id }}"
                                                data-permission="{{ $key }}"
                                                {{ $role->hasPermissionTo($key) ? 'checked' : '' }}
                                            >
                                            <span class="slider"></span>
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', async (e) => {
            const checkboxEl = e.target;
            const roleId = checkboxEl.dataset.roleId;
            const permission = checkboxEl.dataset.permission;
            const checked = checkboxEl.checked;

            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });

            try {
                const response = await fetch(`/roles/${roleId}/permissions`, {
                    method: checked ? 'POST' : 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ permission })
                });

                if (!response.ok) throw new Error(await response.text());

                Toast.fire({
                    icon: 'success',
                    title: `Permission ${checked ? 'granted' : 'revoked'}`
                });

            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: 'Unable to change permission status.'
                });
                checkboxEl.checked = !checked; // Revert
            }
        });
    });
});
</script>
@endsection
