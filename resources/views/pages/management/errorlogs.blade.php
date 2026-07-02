@extends('layout.app', ['title' => 'Error Logs'])
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
    .btn-teal { background:var(--teal); color:#fff; border:none; border-radius:8px; padding:.5rem 1.1rem; font-size:.8rem; font-weight:700; cursor:pointer; white-space:nowrap; text-decoration:none; display:inline-block; }
    .au-table thead th { font-size:.66rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; border-bottom:2px solid var(--border); background:#f8fafc; white-space:nowrap; padding:11px 16px; }
    .au-table tbody td { font-size:.82rem; color:var(--slate); vertical-align:top; padding:11px 16px; border-bottom:1px solid #f1f5f9; }
    .etype { font-size:.62rem; font-weight:800; padding:3px 9px; border-radius:999px; text-transform:none; background:#fee2e2; color:#991b1b; display:inline-block; }
    .stat { font-size:.62rem; font-weight:800; padding:3px 9px; border-radius:999px; text-transform:uppercase; }
    .stat-open { background:#fef3c7; color:#92400e; } .stat-resolved { background:#dcfce7; color:#166534; }
    .msg { font-size:.8rem; color:var(--slate); line-height:1.5; }
    .chip { font-size:.7rem; background:#eef2f6; color:var(--slate); border-radius:6px; padding:2px 8px; font-weight:700; }
    .pager a, .pager span { font-size:.78rem; font-weight:700; padding:6px 12px; border-radius:8px; border:1px solid var(--border); color:var(--teal); text-decoration:none; }
    .pager span.disabled { color:var(--muted); }

    /* ── Clickable rows ── */
    .au-row { cursor:pointer; transition:background .12s; }
    .au-row:hover { background:var(--teal-light) !important; }
    .au-row .row-caret { color:var(--muted); font-size:.7rem; opacity:0; transition:opacity .12s; }
    .au-row:hover .row-caret { opacity:1; }
    .au-row.is-resolved { opacity:.6; }

    /* ── Detail modal ── */
    #errorDetailModal .modal-content { border:none; border-radius:var(--radius); overflow:hidden; }
    #errorDetailModal .modal-header { background:var(--teal); color:#fff; border:none; padding:15px 22px; }
    #errorDetailModal .modal-header .modal-title { color:#fff; font-size:.95rem; font-weight:700; }
    #errorDetailModal .btn-close { filter:brightness(0) invert(1); }
    #errorDetailModal .modal-body { background:var(--bg); padding:20px 22px; }
    .ad-meta { display:grid; grid-template-columns:repeat(2,1fr); gap:10px 18px; background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:14px 16px; margin-bottom:16px; }
    .ad-meta .k { font-size:.62rem; font-weight:800; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; }
    .ad-meta .v { font-size:.84rem; color:var(--slate); font-weight:600; word-break:break-word; }
    .ad-sec-t { font-size:.62rem; font-weight:800; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin:0 0 6px; }
    .err-copyblock { background:#0f172a; color:#e2e8f0; border-radius:10px; padding:14px 16px; font-size:.76rem; line-height:1.5; white-space:pre-wrap; word-break:break-word; max-height:340px; overflow:auto; margin:0; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; }
    .ad-empty { color:var(--slate-light); font-size:.82rem; padding:14px; text-align:center; }
</style>

@php
    // Full (untruncated) detail for each visible log, consumed by the click-to-open modal.
    $errorDetails = [];
    foreach ($logs as $l) {
        $errorDetails[$l->id] = [
            'id'         => $l->id,
            'type'       => $l->type,
            'class'      => $l->exception_class,
            'message'    => $l->message,
            'file'       => $l->file,
            'line'       => $l->line,
            'code'       => $l->code,
            'method'     => $l->method,
            'url'        => $l->url,
            'user'       => $l->user_name ?: 'system',
            'ip'         => $l->ip,
            'agent'      => $l->user_agent,
            'when'       => \Carbon\Carbon::parse($l->created_at)->format('M d, Y · h:i A'),
            'trace'      => $l->trace,
            'input'      => $l->input,
            'resolved'   => (bool) $l->resolved,
        ];
    }
@endphp

<div class="au-shell" data-resolve-url="{{ url('/pages/management/error-logs') }}" data-csrf="{{ csrf_token() }}">
    <div class="au-top">
        <p class="t"><i class="fa fa-bug me-2 text-teal"></i>Error Logs</p>
        <p class="s">Every real server-side error, captured with its message, file, line, stack trace and request context — click a row to view and copy the full details.</p>
    </div>

    <div class="sc">
        <div class="sc-h"><div class="sc-i"><i class="fa fa-filter"></i></div><h6 class="sc-t">Filters</h6></div>
        <div class="card-body" style="padding:16px 20px;">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-3"><label class="lbl">Error type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach($types as $t)<option value="{{ $t }}" @selected(request('type')===$t)>{{ $t }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2"><label class="lbl">Status</label>
                    <select name="resolved" class="form-select form-select-sm">
                        @foreach(['all'=>'All','open'=>'Open','resolved'=>'Resolved'] as $k=>$v)
                            <option value="{{ $k }}" @selected(request('resolved')===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2"><label class="lbl">Search</label><input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Message or user..."></div>
                <div class="col-6 col-md-2"><label class="lbl">From</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm"></div>
                <div class="col-6 col-md-2"><label class="lbl">To</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm"></div>
                <div class="col-6 col-md-1 d-flex gap-2"><button class="btn-teal flex-fill"><i class="fa fa-search"></i></button></div>
                <div class="col-12"><a href="{{ route('error-logs.index') }}" class="btn-teal" style="background:#94a3b8;"><i class="fa fa-rotate-left me-1"></i>Reset filters</a></div>
            </form>
        </div>
    </div>

    <div class="sc">
        <div class="sc-h"><div class="sc-i"><i class="fa fa-list"></i></div><h6 class="sc-t">Captured Errors</h6><span class="ms-auto chip">{{ $logs->total() }} entries</span></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 au-table">
                <thead>
                    <tr>
                        <th class="ps-4" style="width:150px;">When</th>
                        <th style="width:170px;">Type</th>
                        <th>Message</th>
                        <th style="width:150px;">User</th>
                        <th style="width:100px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr class="au-row {{ $log->resolved ? 'is-resolved' : '' }}" data-id="{{ $log->id }}" role="button" tabindex="0" title="Click to view full error detail">
                        <td class="ps-4 small">{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y') }}<br><span class="text-muted">{{ \Carbon\Carbon::parse($log->created_at)->format('h:i A') }}</span></td>
                        <td><span class="etype">{{ $log->type }}</span>@if($log->file)<br><span class="text-muted" style="font-size:.66rem;">{{ \Illuminate\Support\Str::limit(basename($log->file), 26) }}:{{ $log->line }}</span>@endif</td>
                        <td><div class="msg">{{ \Illuminate\Support\Str::limit($log->message, 120) }}</div>@if($log->url)<span class="text-muted" style="font-size:.68rem;">{{ $log->method }} {{ \Illuminate\Support\Str::limit($log->url, 60) }}</span>@endif<i class="fa fa-chevron-right row-caret float-end mt-1" aria-hidden="true"></i></td>
                        <td><span class="fw-bold text-capitalize">{{ $log->user_name ?: 'system' }}</span>@if($log->ip)<br><span class="text-muted" style="font-size:.68rem;">{{ $log->ip }}</span>@endif</td>
                        <td><span class="stat {{ $log->resolved ? 'stat-resolved' : 'stat-open' }}">{{ $log->resolved ? 'Resolved' : 'Open' }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No errors have been captured{{ request()->hasAny(['type','resolved','search','date_from','date_to']) ? ' for these filters' : '' }}. 🎉</td></tr>
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
<div class="modal fade" id="errorDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fa fa-bug me-2"></i>Error Detail</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ad-meta" id="adMeta"></div>
                <p class="ad-sec-t">Full details (select all &amp; copy)</p>
                <pre class="err-copyblock" id="adCopy"></pre>
                <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn-teal" id="adResolveBtn"></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="errorDetailData">@json($errorDetails)</script>
<script>
(function () {
    var DATA = {};
    try { DATA = JSON.parse(document.getElementById('errorDetailData').textContent || '{}'); } catch (e) { DATA = {}; }

    var shell = document.querySelector('.au-shell');
    var BASE_URL = shell ? shell.getAttribute('data-resolve-url') : '';
    var CSRF = shell ? shell.getAttribute('data-csrf') : '';

    function esc(v) {
        if (v === null || v === undefined || v === '') return '—';
        return String(v).replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }

    function renderMeta(d) {
        var rows = [
            ['Type', '<span class="etype">' + esc(d.type) + '</span>'],
            ['Exception class', esc(d.class)],
            ['File', esc(d.file) + (d.line ? ':' + esc(d.line) : '')],
            ['Code', esc(d.code)],
            ['Request', esc(d.method) + ' ' + esc(d.url)],
            ['Performed by', esc(d.user)],
            ['IP Address', esc(d.ip)],
            ['When', esc(d.when)]
        ];
        return rows.map(function (r) {
            return '<div><div class="k">' + r[0] + '</div><div class="v">' + r[1] + '</div></div>';
        }).join('');
    }

    // Consolidated plain-text block for manual select-copy into Claude.
    function buildCopyText(d) {
        var inputStr = '';
        try {
            if (d.input && Object.keys(d.input).length) inputStr = JSON.stringify(d.input, null, 2);
        } catch (e) { inputStr = ''; }
        var lines = [
            '[' + (d.type || 'Error') + '] ' + (d.message || ''),
            '',
            'Exception : ' + (d.class || '') ,
            'Location  : ' + (d.file || '') + (d.line ? ':' + d.line : ''),
            'Code      : ' + (d.code || ''),
            'Request   : ' + (d.method || '') + ' ' + (d.url || ''),
            'User      : ' + (d.user || '') + (d.ip ? ' (' + d.ip + ')' : ''),
            'When      : ' + (d.when || '')
        ];
        if (inputStr) { lines.push('', 'Input:', inputStr); }
        lines.push('', 'Stack trace:', d.trace || '(none)');
        return lines.join('\n');
    }

    var modalEl = document.getElementById('errorDetailModal');
    var modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    var resolveBtn = document.getElementById('adResolveBtn');
    var current = null;

    function paintResolveBtn(resolved) {
        if (!resolveBtn) return;
        if (resolved) {
            resolveBtn.innerHTML = '<i class="fa fa-rotate-left me-1"></i>Mark as Open';
            resolveBtn.style.background = '#94a3b8';
        } else {
            resolveBtn.innerHTML = '<i class="fa fa-check me-1"></i>Mark Resolved';
            resolveBtn.style.background = '';
        }
    }

    function openDetail(id) {
        var d = DATA[id];
        if (!d || !modal) return;
        current = d;
        document.getElementById('adMeta').innerHTML = renderMeta(d);
        document.getElementById('adCopy').textContent = buildCopyText(d);
        paintResolveBtn(d.resolved);
        modal.show();
    }

    if (resolveBtn) {
        resolveBtn.addEventListener('click', function () {
            if (!current) return;
            resolveBtn.disabled = true;
            fetch(BASE_URL + '/' + current.id + '/resolve', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
            }).then(function (r) { return r.json(); }).then(function (res) {
                resolveBtn.disabled = false;
                if (res && res.success) {
                    current.resolved = res.resolved;
                    paintResolveBtn(res.resolved);
                    var row = document.querySelector('tr.au-row[data-id="' + current.id + '"]');
                    if (row) {
                        row.classList.toggle('is-resolved', res.resolved);
                        var badge = row.querySelector('.stat');
                        if (badge) {
                            badge.className = 'stat ' + (res.resolved ? 'stat-resolved' : 'stat-open');
                            badge.textContent = res.resolved ? 'Resolved' : 'Open';
                        }
                    }
                }
            }).catch(function () { resolveBtn.disabled = false; });
        });
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
