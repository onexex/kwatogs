@extends('layout.app', ['title' => 'Audit Trail'])
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8; --bg:#f1f5f9;
        --surface:#fff; --border:#e2e8f0; --danger:#ef4444; --success:#10b981; --warning:#f59e0b; --info:#3b82f6;
        --radius:14px; --shadow:0 1px 3px rgba(0,0,0,.06),0 6px 18px rgba(0,0,0,.05);
    }
    .au-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .au-top { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); padding:16px 22px; margin-bottom:20px; }
    .au-top .t { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .au-top .s { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .sc { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); margin-bottom:18px; overflow:hidden; }
    .sc-h { display:flex; align-items:center; gap:10px; padding:13px 20px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .sc-i { width:28px; height:28px; border-radius:8px; background:var(--teal-light); color:var(--teal); display:flex; align-items:center; justify-content:center; font-size:.75rem; }
    .sc-t { font-size:.74rem; font-weight:800; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; margin:0; }
    .lbl { font-size:.66rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px; display:block; }
    .form-control,.form-select { border:1.5px solid var(--border); border-radius:8px; font-size:.83rem; color:var(--slate); background:#fafbfc; padding:.45rem .7rem; }
    .form-control:focus,.form-select:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
    .btn-teal { background:var(--teal); color:#fff; border:none; border-radius:8px; padding:.5rem 1.1rem; font-size:.8rem; font-weight:700; cursor:pointer; white-space:nowrap; }
    .au-table thead th { font-size:.66rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; border-bottom:2px solid var(--border); background:#f8fafc; white-space:nowrap; padding:11px 16px; }
    .au-table tbody td { font-size:.82rem; color:var(--slate); vertical-align:top; padding:11px 16px; border-bottom:1px solid #f1f5f9; }
    .act { font-size:.62rem; font-weight:800; padding:3px 9px; border-radius:999px; text-transform:uppercase; }
    .act-created { background:#dcfce7; color:#166534; } .act-updated { background:#dbeafe; color:#1e40af; } .act-deleted { background:#fee2e2; color:#991b1b; }
    .act-login { background:#e0f2fe; color:#075985; } .act-logout { background:#f1f5f9; color:#475569; } .act-failed { background:#fef3c7; color:#92400e; } .act-role { background:#ede9fe; color:#5b21b6; }
    .act-imported { background:#cffafe; color:#155e75; }
    .chg { font-size:.74rem; color:var(--slate-light); line-height:1.6; }
    .chg b { color:var(--slate); } .chg .from { color:#b91c1c; } .chg .to { color:#047857; }
    .chip { font-size:.7rem; background:#eef2f6; color:var(--slate); border-radius:6px; padding:2px 8px; font-weight:700; }
    .pager a, .pager span { font-size:.78rem; font-weight:700; padding:6px 12px; border-radius:8px; border:1px solid var(--border); color:var(--teal); text-decoration:none; }
    .pager span.disabled { color:var(--muted); }

    /* ── Clickable rows ── */
    .au-row { cursor:pointer; transition:background .12s; }
    .au-row:hover { background:var(--teal-light) !important; }
    .au-row .row-caret { color:var(--muted); font-size:.7rem; opacity:0; transition:opacity .12s; }
    .au-row:hover .row-caret { opacity:1; }

    /* ── Detail modal ── */
    #auditDetailModal .modal-content { border:none; border-radius:var(--radius); overflow:hidden; }
    #auditDetailModal .modal-header { background:var(--teal); color:#fff; border:none; padding:15px 22px; }
    #auditDetailModal .modal-header .modal-title { color:#fff; font-size:.95rem; font-weight:700; }
    #auditDetailModal .btn-close { filter:brightness(0) invert(1); }
    #auditDetailModal .modal-body { background:var(--bg); padding:20px 22px; }
    .ad-meta { display:grid; grid-template-columns:repeat(2,1fr); gap:10px 18px; background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:14px 16px; margin-bottom:16px; }
    .ad-meta .k { font-size:.62rem; font-weight:800; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; }
    .ad-meta .v { font-size:.84rem; color:var(--slate); font-weight:600; word-break:break-word; }
    .ad-diff { background:var(--surface); border:1px solid var(--border); border-radius:10px; overflow:hidden; }
    .ad-diff table { width:100%; margin:0; border-collapse:collapse; }
    .ad-diff th { font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.4px; color:var(--slate-light); background:#f8fafc; padding:9px 14px; border-bottom:2px solid var(--border); text-align:left; }
    .ad-diff td { font-size:.82rem; color:var(--slate); padding:9px 14px; border-bottom:1px solid #f1f5f9; vertical-align:top; word-break:break-word; }
    .ad-diff tr:last-child td { border-bottom:none; }
    .ad-diff .field { font-weight:700; color:var(--slate); width:26%; }
    .ad-diff .before { color:#b91c1c; background:#fef2f2; width:34%; }
    .ad-diff .after  { color:#047857; background:#f0fdf4; width:34%; }
    .ad-diff .arrow { width:6%; text-align:center; color:var(--muted); }
    .ad-empty { color:var(--slate-light); font-size:.82rem; padding:14px; text-align:center; }
</style>

@php
    $actBadge = [
        'created'=>'act-created','updated'=>'act-updated','deleted'=>'act-deleted',
        'login'=>'act-login','logout'=>'act-logout','login-failed'=>'act-failed',
        'role-assigned'=>'act-role','role-removed'=>'act-role','imported'=>'act-imported',
    ];

    // Full (untruncated) detail for each visible log, consumed by the click-to-open modal.
    $auditDetails = [];
    foreach ($logs as $l) {
        $auditDetails[$l->id] = [
            'action'   => $l->action,
            'model'    => $l->model,
            'model_id' => $l->model_id,
            'user'     => $l->user_name ?: 'system',
            'ip'       => $l->ip,
            'url'      => $l->url,
            'when'     => \Carbon\Carbon::parse($l->created_at)->format('M d, Y · h:i A'),
            'changes'  => $l->changes,
        ];
    }
@endphp

<div class="au-shell">
    <div class="au-top">
        <p class="t"><i class="fa fa-clipboard-list me-2 text-teal"></i>Audit Trail</p>
        <p class="s">Every create, update and delete across payroll, leave, OT, schedules, adjustments, loans and employees — who, what, when.</p>
    </div>

    <div class="sc">
        <div class="sc-h"><div class="sc-i"><i class="fa fa-filter"></i></div><h6 class="sc-t">Filters</h6></div>
        <div class="card-body" style="padding:16px 20px;">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-2"><label class="lbl">Record type</label>
                    <select name="model" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach($models as $m)<option value="{{ $m }}" @selected(request('model')===$m)>{{ $m }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2"><label class="lbl">Action</label>
                    <select name="action" class="form-select form-select-sm">
                        @foreach(['all'=>'All','created'=>'Created','updated'=>'Updated','deleted'=>'Deleted','imported'=>'Imported','login'=>'Login','logout'=>'Logout','login-failed'=>'Failed Login','role-assigned'=>'Role Assigned','role-removed'=>'Role Removed'] as $k=>$v)
                            <option value="{{ $k }}" @selected(request('action',' ')===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2"><label class="lbl">User</label><input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Name..."></div>
                <div class="col-6 col-md-2"><label class="lbl">From</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm"></div>
                <div class="col-6 col-md-2"><label class="lbl">To</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm"></div>
                <div class="col-6 col-md-2 d-flex gap-2"><button class="btn-teal flex-fill"><i class="fa fa-search me-1"></i>Filter</button><a href="{{ route('audit-trail.index') }}" class="btn-teal" style="background:#94a3b8;">Reset</a></div>
            </form>
        </div>
    </div>

    <div class="sc">
        <div class="sc-h"><div class="sc-i"><i class="fa fa-list"></i></div><h6 class="sc-t">Activity Log</h6><span class="ms-auto chip">{{ $logs->total() }} entries</span></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 au-table">
                <thead>
                    <tr>
                        <th class="ps-4" style="width:160px;">When</th>
                        <th style="width:160px;">User</th>
                        <th style="width:100px;">Action</th>
                        <th style="width:150px;">Record</th>
                        <th>Changes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr class="au-row" data-id="{{ $log->id }}" role="button" tabindex="0" title="Click to view detailed changes">
                        <td class="ps-4 small">{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y') }}<br><span class="text-muted">{{ \Carbon\Carbon::parse($log->created_at)->format('h:i A') }}</span></td>
                        <td><span class="fw-bold text-capitalize">{{ $log->user_name ?: 'system' }}</span>@if($log->ip)<br><span class="text-muted" style="font-size:.68rem;">{{ $log->ip }}</span>@endif</td>
                        <td><span class="act {{ $actBadge[$log->action] ?? '' }}">{{ $log->action }}</span></td>
                        <td><span class="chip">{{ $log->model }}</span><br><span class="text-muted small">#{{ $log->model_id }}</span></td>
                        <td>
                            @php $c = $log->changes; @endphp
                            @if($log->action === 'deleted')
                                <span class="text-muted">Record removed.</span>
                            @elseif(in_array($log->action, ['updated','role-assigned','role-removed','login-failed']) && is_array($c))
                                <div class="chg">
                                    @foreach($c as $field => $vals)
                                        <div><b>{{ \Illuminate\Support\Str::headline($field) }}:</b>
                                            <span class="from">{{ \Illuminate\Support\Str::limit((string)($vals['from'] ?? '—'), 40) ?: '—' }}</span>
                                            <i class="fa fa-arrow-right-long mx-1 text-muted" style="font-size:.6rem;"></i>
                                            <span class="to">{{ \Illuminate\Support\Str::limit((string)($vals['to'] ?? '—'), 40) ?: '—' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif(in_array($log->action, ['created','imported']) && is_array($c))
                                <div class="chg">
                                    @foreach(array_slice($c, 0, 6, true) as $field => $val)
                                        @if(!is_array($val))<span class="me-2"><b>{{ \Illuminate\Support\Str::headline($field) }}:</b> {{ \Illuminate\Support\Str::limit((string)$val, 30) }}</span>@endif
                                    @endforeach
                                </div>
                            @else<span class="text-muted">—</span>@endif
                            <i class="fa fa-chevron-right row-caret float-end mt-1" aria-hidden="true"></i>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No audit records match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="d-flex justify-content-between align-items-center p-3 pager">
            <span class="small text-muted">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</span>
            <div class="d-flex gap-2">
                @if($logs->onFirstPage())<span class="disabled">&lsaquo; Prev</span>@else<a href="{{ $logs->previousPageUrl() }}">&lsaquo; Prev</a>@endif
                @if($logs->hasMorePages())<a href="{{ $logs->nextPageUrl() }}">Next &rsaquo;</a>@else<span class="disabled">Next &rsaquo;</span>@endif
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ── Detail modal (populated on row click) ── --}}
<div class="modal fade" id="auditDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fa fa-clipboard-list me-2"></i>Audit Detail</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ad-meta" id="adMeta"></div>
                <div class="ad-diff" id="adDiff"></div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="auditDetailData">@json($auditDetails)</script>
<script>
(function () {
    var DATA = {};
    try { DATA = JSON.parse(document.getElementById('auditDetailData').textContent || '{}'); } catch (e) { DATA = {}; }

    var ACT_LABEL = {
        'created':'Created','updated':'Updated','deleted':'Deleted','login':'Login','logout':'Logout',
        'login-failed':'Failed Login','role-assigned':'Role Assigned','role-removed':'Role Removed','imported':'Imported'
    };
    var ACT_CLASS = {
        'created':'act-created','updated':'act-updated','deleted':'act-deleted','login':'act-login',
        'logout':'act-logout','login-failed':'act-failed','role-assigned':'act-role','role-removed':'act-role','imported':'act-imported'
    };

    function esc(v) {
        if (v === null || v === undefined || v === '') return '—';
        return String(v).replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }
    // Headline: snake/camel -> "Title Case"
    function headline(s) {
        return String(s).replace(/[_-]+/g, ' ').replace(/([a-z])([A-Z])/g, '$1 $2')
            .replace(/\s+/g, ' ').trim().replace(/\b\w/g, function (m) { return m.toUpperCase(); });
    }

    function renderMeta(d) {
        var badge = '<span class="act ' + (ACT_CLASS[d.action] || '') + '">' + esc(ACT_LABEL[d.action] || d.action) + '</span>';
        var rows = [
            ['Action', badge],
            ['Record', '<span class="chip">' + esc(d.model) + '</span> <span class="text-muted">#' + esc(d.model_id) + '</span>'],
            ['Performed by', esc(d.user)],
            ['When', esc(d.when)],
            ['IP Address', esc(d.ip)],
            ['Page', esc(d.url)]
        ];
        return rows.map(function (r) {
            return '<div><div class="k">' + r[0] + '</div><div class="v">' + r[1] + '</div></div>';
        }).join('');
    }

    function renderDiff(d) {
        var c = d.changes;
        if (d.action === 'deleted') {
            return '<div class="ad-empty">This record was removed.</div>';
        }
        if (!c || typeof c !== 'object' || Object.keys(c).length === 0) {
            return '<div class="ad-empty">No field-level changes were recorded for this entry.</div>';
        }
        // Updated / role / failed-login => {field:{from,to}};  Created => {field: value}
        var isDiff = Object.keys(c).some(function (k) {
            var v = c[k]; return v && typeof v === 'object' && ('from' in v || 'to' in v);
        });

        var body = Object.keys(c).map(function (field) {
            var v = c[field];
            var hasDiff = v && typeof v === 'object' && ('from' in v || 'to' in v);

            if (!isDiff) { // created snapshot: single "Value" column (no before/after)
                return '<tr><td class="field">' + esc(headline(field)) + '</td><td class="after" colspan="3">' + esc(v) + '</td></tr>';
            }
            var before = hasDiff ? v.from : null;
            var after  = hasDiff ? v.to   : v;
            return '<tr>' +
                '<td class="field">' + esc(headline(field)) + '</td>' +
                '<td class="before">' + esc(before) + '</td>' +
                '<td class="arrow"><i class="fa fa-arrow-right-long"></i></td>' +
                '<td class="after">' + esc(after) + '</td>' +
            '</tr>';
        }).join('');

        var head = isDiff
            ? '<thead><tr><th>Field</th><th>Before</th><th></th><th>After</th></tr></thead>'
            : '<thead><tr><th>Field</th><th colspan="3">Value</th></tr></thead>';
        return '<table>' + head + '<tbody>' + body + '</tbody></table>';
    }

    var modalEl = document.getElementById('auditDetailModal');
    var modal = modalEl ? new bootstrap.Modal(modalEl) : null;

    function openDetail(id) {
        var d = DATA[id];
        if (!d || !modal) return;
        document.getElementById('adMeta').innerHTML = renderMeta(d);
        document.getElementById('adDiff').innerHTML = renderDiff(d);
        modal.show();
    }

    document.querySelectorAll('tr.au-row').forEach(function (row) {
        row.addEventListener('click', function () { openDetail(this.getAttribute('data-id')); });
        row.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openDetail(this.getAttribute('data-id')); }
        });
    });
})();
</script>
@endsection
