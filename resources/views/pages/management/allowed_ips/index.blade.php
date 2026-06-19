@extends('layout.app', ['title' => 'Allowed IP Management'])
@section('content')

<style>
    /* ── Design tokens ──────────────────────────────────────────────────────── */
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

    .aip-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    /* ── Topbar ─────────────────────────────────────────────────────────────── */
    .aip-topbar {
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
    .aip-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .aip-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }

    .btn-add-aip {
        background: var(--teal);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: .3px;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(0,128,128,.25);
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }
    .btn-add-aip:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

    /* ── Section card ───────────────────────────────────────────────────────── */
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
        flex-wrap: wrap;
    }
    .sc-head-left { display: flex; align-items: center; gap: 10px; }
    .sc-icon {
        width: 30px; height: 30px;
        border-radius: 8px;
        background: var(--teal-light);
        color: var(--teal);
        display: flex; align-items: center; justify-content: center;
        font-size: 0.78rem; flex-shrink: 0;
    }
    .sc-title {
        font-size: 0.78rem; font-weight: 700;
        color: var(--slate); text-transform: uppercase;
        letter-spacing: .5px; margin: 0;
    }
    .sc-body { padding: 0; }

    /* ── Toolbar (search + actions) ─────────────────────────────────────────── */
    .search-wrap {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }
    .search-wrap .form-control {
        border: 1.5px solid var(--border);
        border-radius: var(--radius-input);
        font-size: 0.83rem;
        color: var(--slate);
        background: #fafbfc;
        padding: 0.45rem 0.8rem;
        width: 220px;
        transition: border-color .15s, box-shadow .15s;
    }
    .search-wrap .form-control:focus {
        border-color: var(--teal);
        box-shadow: 0 0 0 3px rgba(0,128,128,.1);
        background: #fff;
        outline: none;
    }
    .btn-search {
        background: var(--teal);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 7px 14px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-search:hover { background: var(--teal-dark); color: #fff; }
    .btn-outline-aip {
        background: var(--surface);
        color: var(--slate-light);
        border: 1.5px solid var(--border);
        border-radius: 8px;
        padding: 7px 12px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex; align-items: center; gap: 5px;
        transition: background .15s;
    }
    .btn-outline-aip:hover { background: var(--bg); color: var(--slate); }

    /* ── Bulk toolbar ───────────────────────────────────────────────────────── */
    #bulk-toolbar {
        display: none;
        background: #fff9e6;
        border: 1.5px solid #f59e0b;
        border-radius: 10px;
        padding: 10px 18px;
        margin-bottom: 12px;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        font-size: .83rem;
        color: var(--slate);
    }
    #bulk-toolbar.show { display: flex; }
    #bulk-toolbar .bulk-count { font-weight: 700; color: #b45309; }

    /* ── Table ──────────────────────────────────────────────────────────────── */
    .aip-table thead th {
        position: sticky; top: 0; z-index: 10;
        background: var(--surface);
        font-size: 0.7rem; font-weight: 700;
        color: var(--slate-light); text-transform: uppercase;
        letter-spacing: .4px;
        border-bottom: 2px solid var(--border);
        white-space: nowrap;
        padding: 12px 16px;
    }
    .aip-table tbody td {
        font-size: 0.83rem; color: var(--slate);
        vertical-align: middle; padding: 12px 16px;
    }
    .aip-table tbody tr:hover { background: var(--teal-light); }
    .aip-table tbody tr.selected-row { background: #fef9c3; }

    /* ── Status badges ──────────────────────────────────────────────────────── */
    .badge-active {
        background: rgba(16,185,129,.1);
        color: #059669;
        border: 1px solid rgba(16,185,129,.35);
        font-size: 0.68rem; font-weight: 700;
        letter-spacing: .3px;
        padding: 4px 12px; border-radius: 20px;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .badge-inactive {
        background: rgba(148,163,184,.1);
        color: var(--muted);
        border: 1px solid rgba(148,163,184,.35);
        font-size: 0.68rem; font-weight: 700;
        letter-spacing: .3px;
        padding: 4px 12px; border-radius: 20px;
        display: inline-flex; align-items: center; gap: 5px;
    }

    /* ── Icon action buttons ────────────────────────────────────────────────── */
    .icon-action-btn {
        width: 32px; height: 32px;
        border-radius: 8px;
        border: 1.5px solid var(--border);
        background: var(--surface);
        display: inline-flex; align-items: center; justify-content: center;
        transition: all .15s;
        cursor: pointer;
        text-decoration: none;
        color: var(--slate-light);
        font-size: 0.78rem;
    }
    .icon-action-btn:hover { border-color: var(--teal-mid); background: var(--teal-light); color: var(--teal); }
    .icon-action-btn.danger:hover { border-color: var(--danger); background: #fff5f5; color: var(--danger); }
    .icon-action-btn.toggle-on { color: var(--success); border-color: rgba(16,185,129,.35); }
    .icon-action-btn.toggle-on:hover { background: rgba(16,185,129,.08); border-color: var(--success); }
    .icon-action-btn.toggle-off { color: var(--muted); }
    .icon-action-btn.toggle-off:hover { background: rgba(148,163,184,.08); border-color: var(--muted); }

    /* ── Empty state ────────────────────────────────────────────────────────── */
    .aip-empty {
        padding: 52px 20px;
        text-align: center;
        color: var(--muted);
    }
    .aip-empty i { font-size: 2.4rem; margin-bottom: 12px; display: block; opacity: .4; }
    .aip-empty p { font-size: 0.85rem; margin: 0; }

    /* ── Pagination ─────────────────────────────────────────────────────────── */
    .aip-pagination { padding: 14px 22px; border-top: 1px solid var(--border); }
    .aip-pagination .pagination { margin: 0; gap: 4px; }
    .aip-pagination .page-item .page-link {
        border-radius: 8px !important;
        border: 1.5px solid var(--border);
        font-size: 0.8rem; font-weight: 600;
        color: var(--slate-light);
        padding: 6px 12px;
        transition: all .15s;
    }
    .aip-pagination .page-item.active .page-link {
        background: var(--teal); border-color: var(--teal); color: #fff;
    }
    .aip-pagination .page-item .page-link:hover {
        background: var(--teal-light); border-color: var(--teal-mid); color: var(--teal);
    }

    /* ── Flash alerts ───────────────────────────────────────────────────────── */
    .flash-alert {
        border-radius: var(--radius-card);
        border: none;
        font-size: 0.83rem;
        font-weight: 600;
        padding: 12px 18px;
        margin-bottom: 16px;
        display: flex; align-items: center; gap: 10px;
    }
</style>

<div class="aip-shell">

    {{-- ── Topbar ── --}}
    <div class="aip-topbar">
        <div>
            <p class="page-title"><i class="fa-solid fa-shield-halved me-2" style="color:var(--teal)"></i>Allowed IP Management</p>
            <p class="page-sub">Control which IP addresses employees are allowed to access the system from</p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <a href="{{ route('allowed-ips.dashboard') }}" class="btn-outline-aip">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="{{ route('allowed-ips.logs') }}" class="btn-outline-aip">
                <i class="fa-solid fa-history"></i> Logs
            </a>
            <a href="{{ route('allowed-ips.create') }}" class="btn-add-aip">
                <i class="fa-solid fa-plus"></i> Add IP
            </a>
        </div>
    </div>

    {{-- ── Flash messages ── --}}
    @if(session('success'))
        <div class="flash-alert alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="flash-alert alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-xmark"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('import_errors') && count(session('import_errors')))
        <div class="flash-alert alert alert-warning alert-dismissible fade show" role="alert" style="flex-direction:column; align-items:flex-start;">
            <div><i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Some rows were skipped:</strong></div>
            <ul class="mb-0 mt-1" style="font-size:.8rem; font-weight:400;">
                @foreach(session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- ── Bulk toolbar ── --}}
    <div id="bulk-toolbar">
        <span><i class="fa fa-check-square me-1"></i><span class="bulk-count" id="bulk-count">0</span> selected</span>
        <div class="d-flex gap-2 ms-2 flex-wrap">
            <button class="btn btn-sm btn-success" onclick="bulkAction('enable')">
                <i class="fa fa-toggle-on me-1"></i>Enable
            </button>
            <button class="btn btn-sm btn-secondary" onclick="bulkAction('disable')">
                <i class="fa fa-toggle-off me-1"></i>Disable
            </button>
            <button class="btn btn-sm btn-danger" onclick="bulkAction('delete')">
                <i class="fa fa-trash me-1"></i>Delete
            </button>
        </div>
        <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="clearSelection()">
            <i class="fa fa-times me-1"></i>Clear
        </button>
    </div>

    {{-- ── Main card ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-network-wired"></i></div>
                <h5 class="sc-title">IP Allowlist
                    <span style="font-weight:400;color:var(--muted);text-transform:none;letter-spacing:0;font-size:0.75rem;margin-left:6px;">
                        {{ $allowedIps->total() }} {{ Str::plural('entry', $allowedIps->total()) }}
                    </span>
                </h5>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                {{-- Search form --}}
                <form method="GET" action="{{ route('allowed-ips.index') }}" class="search-wrap" autocomplete="off">
                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="Search IP or description…"
                           value="{{ $search }}" />
                    <button type="submit" class="btn-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    @if($search)
                        <a href="{{ route('allowed-ips.index') }}" class="btn-outline-aip">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endif
                </form>
                {{-- Import --}}
                <button type="button" class="btn-outline-aip" data-bs-toggle="modal" data-bs-target="#importModal"
                        title="Import from CSV">
                    <i class="fa fa-upload"></i> Import
                </button>
                {{-- Export --}}
                <a href="{{ route('allowed-ips.export', $search ? ['search' => $search] : []) }}"
                   class="btn-outline-aip" title="Export to Excel">
                    <i class="fa fa-file-excel"></i> Export
                </a>
            </div>
        </div>

        <div class="sc-body">
            <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                <table class="table table-hover align-middle aip-table mb-0" id="aip-table">
                    <thead>
                        <tr>
                            <th class="ps-3" style="width:40px">
                                <input type="checkbox" id="select-all" class="form-check-input"
                                       title="Select all on this page">
                            </th>
                            <th>#</th>
                            <th>IP Address</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($allowedIps as $ip)
                        <tr data-id="{{ $ip->id }}">
                            <td class="ps-3">
                                <input type="checkbox" class="form-check-input row-check"
                                       value="{{ $ip->id }}">
                            </td>
                            <td style="color:var(--muted);font-size:0.75rem;">
                                {{ $allowedIps->firstItem() + $loop->index }}
                            </td>
                            <td>
                                <code style="background:var(--teal-light);color:var(--teal-dark);padding:3px 8px;border-radius:6px;font-size:0.82rem;font-weight:600;">
                                    {{ $ip->ip_address }}
                                </code>
                            </td>
                            <td style="color:var(--slate-light);">
                                {{ $ip->description ?: '—' }}
                            </td>
                            <td>
                                <span class="status-badge-{{ $ip->id }}">
                                    @if($ip->status)
                                        <span class="badge-active">
                                            <i class="fa-solid fa-circle" style="font-size:.45rem;"></i> Active
                                        </span>
                                    @else
                                        <span class="badge-inactive">
                                            <i class="fa-solid fa-circle" style="font-size:.45rem;"></i> Disabled
                                        </span>
                                    @endif
                                </span>
                            </td>
                            <td style="color:var(--muted);font-size:0.78rem;">
                                {{ $ip->created_at->format('M d, Y') }}
                            </td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end gap-2">

                                    {{-- Toggle status (AJAX) --}}
                                    <button type="button"
                                            class="icon-action-btn {{ $ip->status ? 'toggle-on' : 'toggle-off' }} toggle-btn"
                                            data-id="{{ $ip->id }}"
                                            data-url="{{ route('allowed-ips.toggle', $ip) }}"
                                            title="{{ $ip->status ? 'Disable' : 'Enable' }} this IP">
                                        <i class="fa-solid {{ $ip->status ? 'fa-toggle-on' : 'fa-toggle-off' }} toggle-icon-{{ $ip->id }}"></i>
                                    </button>

                                    {{-- Edit --}}
                                    <a href="{{ route('allowed-ips.edit', $ip) }}"
                                       class="icon-action-btn"
                                       title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>

                                    {{-- Delete --}}
                                    <button type="button"
                                            class="icon-action-btn danger delete-btn"
                                            data-id="{{ $ip->id }}"
                                            data-ip="{{ $ip->ip_address }}"
                                            data-url="{{ route('allowed-ips.destroy', $ip) }}"
                                            title="Delete">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>

                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">
                                <div class="aip-empty">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    <p>
                                        @if($search)
                                            No results for <strong>"{{ $search }}"</strong>. Try a different keyword.
                                        @else
                                            No IP addresses in the allowlist yet.
                                            <a href="{{ route('allowed-ips.create') }}" style="color:var(--teal);">Add one now.</a>
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($allowedIps->hasPages())
            <div class="aip-pagination d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span style="font-size:0.78rem;color:var(--muted);">
                    Showing {{ $allowedIps->firstItem() }}–{{ $allowedIps->lastItem() }}
                    of {{ $allowedIps->total() }} entries
                </span>
                {{ $allowedIps->links() }}
            </div>
            @endif
        </div>
    </div>

</div>

{{-- ── CSV Import Modal ── --}}
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 8px 40px rgba(0,0,0,.14);">
            <div class="modal-header" style="background:var(--slate);border-radius:14px 14px 0 0;">
                <h5 class="modal-title text-white" id="importModalLabel">
                    <i class="fa fa-upload me-2"></i>Import IPs from CSV
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="import-form" method="POST"
                  action="{{ route('allowed-ips.import') }}"
                  enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted mb-3" style="font-size:.85rem;">
                        Upload a CSV file with columns <code>ip_address</code> and <code>description</code> (optional).
                        Existing IPs will be updated; new ones will be added with status <em>Active</em>.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:.85rem;color:var(--slate)">CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                        <div class="form-text">Max 2 MB · Accepted: .csv</div>
                    </div>
                    <a href="{{ route('allowed-ips.import.template') }}"
                       style="font-size:.82rem; color:var(--teal);">
                        <i class="fa fa-download me-1"></i>Download sample template
                    </a>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--border);">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" style="background:var(--teal);color:#fff;min-width:110px"
                            id="import-submit-btn">
                        <i class="fa fa-upload me-1"></i>Upload & Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]')
        ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        : '{{ csrf_token() }}';

    // ── Select all ─────────────────────────────────────────────────────────────
    var selectAll = document.getElementById('select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.row-check').forEach(function (cb) {
                cb.checked = selectAll.checked;
                cb.closest('tr').classList.toggle('selected-row', cb.checked);
            });
            updateBulkToolbar();
        });
    }

    document.querySelectorAll('.row-check').forEach(function (cb) {
        cb.addEventListener('change', function () {
            cb.closest('tr').classList.toggle('selected-row', cb.checked);
            updateBulkToolbar();
        });
    });

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.row-check:checked')).map(function (cb) {
            return cb.value;
        });
    }

    function updateBulkToolbar() {
        var ids = getSelectedIds();
        var toolbar = document.getElementById('bulk-toolbar');
        document.getElementById('bulk-count').textContent = ids.length;
        toolbar.classList.toggle('show', ids.length > 0);
    }

    window.clearSelection = function () {
        document.querySelectorAll('.row-check').forEach(function (cb) {
            cb.checked = false;
            cb.closest('tr').classList.remove('selected-row');
        });
        if (selectAll) selectAll.checked = false;
        updateBulkToolbar();
    };

    // ── Bulk actions ───────────────────────────────────────────────────────────
    window.bulkAction = function (action) {
        var ids = getSelectedIds();
        if (!ids.length) return;

        var labels = { enable: 'enable', disable: 'disable', delete: 'permanently delete' };
        var confirmMsg = 'Are you sure you want to ' + labels[action] + ' ' + ids.length + ' IP(s)?';

        Swal.fire({
            title: 'Confirm',
            text: confirmMsg,
            icon: action === 'delete' ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonColor: action === 'delete' ? '#ef4444' : '#008080',
            confirmButtonText: 'Yes, ' + action,
        }).then(function (result) {
            if (!result.isConfirmed) return;

            var urls = {
                enable:  '{{ route("allowed-ips.bulk.enable") }}',
                disable: '{{ route("allowed-ips.bulk.disable") }}',
                delete:  '{{ route("allowed-ips.bulk.delete") }}',
            };

            axios.post(urls[action], { ids: ids }, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            })
            .then(function (res) {
                Swal.fire({
                    icon: 'success',
                    title: 'Done',
                    text: res.data.message,
                    confirmButtonColor: '#008080',
                    timer: 2000,
                    timerProgressBar: true,
                }).then(function () {
                    window.location.reload();
                });
            })
            .catch(function (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: err.response?.data?.message ?? 'Something went wrong.',
                    confirmButtonColor: '#008080',
                });
            });
        });
    };

    // ── AJAX toggle ────────────────────────────────────────────────────────────
    document.querySelectorAll('.toggle-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id  = btn.dataset.id;
            var url = btn.dataset.url;

            axios({ method: 'PUT', url: url, headers: { 'X-CSRF-TOKEN': csrfToken } })
                .then(function (res) {
                    var d = res.data;
                    // Swap icon
                    var icon = document.querySelector('.toggle-icon-' + id);
                    if (icon) {
                        icon.classList.toggle('fa-toggle-on',  d.status);
                        icon.classList.toggle('fa-toggle-off', !d.status);
                    }
                    // Swap button class
                    btn.classList.toggle('toggle-on',  d.status);
                    btn.classList.toggle('toggle-off', !d.status);
                    btn.title = (d.status ? 'Disable' : 'Enable') + ' this IP';

                    // Swap badge
                    var badgeWrap = document.querySelector('.status-badge-' + id);
                    if (badgeWrap) {
                        if (d.status) {
                            badgeWrap.innerHTML = '<span class="badge-active"><i class="fa-solid fa-circle" style="font-size:.45rem;"></i> Active</span>';
                        } else {
                            badgeWrap.innerHTML = '<span class="badge-inactive"><i class="fa-solid fa-circle" style="font-size:.45rem;"></i> Disabled</span>';
                        }
                    }

                    // Toast
                    Swal.fire({
                        toast: true, position: 'top-end',
                        icon: 'success', title: d.message,
                        showConfirmButton: false, timer: 2000, timerProgressBar: true,
                    });
                })
                .catch(function () {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Could not toggle status.', confirmButtonColor: '#008080' });
                });
        });
    });

    // ── AJAX delete ────────────────────────────────────────────────────────────
    document.querySelectorAll('.delete-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var ip  = btn.dataset.ip;
            var url = btn.dataset.url;
            var row = btn.closest('tr');

            Swal.fire({
                title: 'Remove IP?',
                text: 'Remove "' + ip + '" from the allowlist?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, delete',
            }).then(function (result) {
                if (!result.isConfirmed) return;

                axios({ method: 'DELETE', url: url, headers: { 'X-CSRF-TOKEN': csrfToken } })
                    .then(function () {
                        row.style.transition = 'opacity .3s';
                        row.style.opacity = 0;
                        setTimeout(function () { row.remove(); }, 300);
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'IP removed.', showConfirmButton: false, timer: 2000, timerProgressBar: true });
                    })
                    .catch(function (err) {
                        Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message ?? 'Could not delete.', confirmButtonColor: '#008080' });
                    });
            });
        });
    });

    // ── Import form spinner ────────────────────────────────────────────────────
    var importForm = document.getElementById('import-form');
    if (importForm) {
        importForm.addEventListener('submit', function () {
            var btn = document.getElementById('import-submit-btn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Importing…';
        });
    }
})();
</script>
@endpush
