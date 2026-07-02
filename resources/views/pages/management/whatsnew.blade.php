@extends('layout.app', ['title' => "What's New"])
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8;
        --bg:#f1f5f9; --surface:#ffffff; --border:#e2e8f0;
        --radius-card:14px; --radius-input:8px;
        --shadow-card:0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }
    .wn-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }

    .wn-topbar {
        background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); padding:18px 22px; margin-bottom:20px;
        display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px;
    }
    .wn-topbar .page-title { font-size:1.15rem; font-weight:800; color:var(--slate); margin:0; letter-spacing:-.2px; }
    .wn-topbar .page-title i { color:var(--teal); margin-right:8px; }
    .wn-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:3px 0 0; }

    /* toggle */
    .wn-toggle { display:flex; align-items:center; gap:9px; font-size:.8rem; font-weight:600; color:var(--slate-light); cursor:pointer; user-select:none; margin:0; }
    .wn-toggle input { width:34px; height:19px; cursor:pointer; accent-color:var(--teal); }

    .wn-card {
        background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); overflow:hidden;
    }

    .wn-month {
        font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.6px; color:var(--slate-light);
        padding:13px 22px 9px; background:#f8fafc; border-bottom:1px solid var(--border);
    }
    .wn-month:not(:first-child) { border-top:1px solid var(--border); }

    .wn-item {
        display:flex; align-items:flex-start; gap:12px; padding:13px 22px; border-bottom:1px solid var(--border);
    }
    .wn-item:last-child { border-bottom:none; }
    .wn-item.is-technical { display:none; }
    body.wn-show-tech .wn-item.is-technical { display:flex; }

    .wn-badge {
        flex-shrink:0; font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.4px;
        padding:3px 9px; border-radius:999px; min-width:62px; text-align:center; margin-top:1px;
    }
    .badge-new   { background:var(--teal-light); color:var(--teal-dark); }
    .badge-fixed { background:#fef3c7; color:#92400e; }
    .badge-tech  { background:#eef2f6; color:var(--slate-light); }
    .badge-break { background:#fee2e2; color:#991b1b; }

    .wn-body { flex:1; min-width:0; }
    .wn-text { font-size:.86rem; color:var(--slate); line-height:1.45; }
    .wn-text .wn-scope { font-weight:700; color:var(--teal-dark); }
    .wn-meta { font-size:.7rem; color:var(--muted); margin-top:2px; }
    .wn-meta code { color:var(--muted); background:transparent; font-size:.68rem; }

    .wn-empty { padding:54px 22px; text-align:center; }
    .wn-empty i { font-size:2rem; color:var(--teal-mid); }
    .wn-empty p { font-size:.9rem; color:var(--slate-light); margin:12px 0 0; font-weight:600; }
    .wn-empty span { font-size:.78rem; color:var(--muted); }
</style>

@php
    $entries     = $changelog['entries'] ?? [];
    $generatedAt = $changelog['generated_at'] ?? null;
    $current     = $changelog['current'] ?? null;

    // feat/fix are client-facing; everything else is "technical" (hidden behind the toggle).
    $typeMeta = [
        'feat' => ['label' => 'New',      'class' => 'badge-new',   'tech' => false],
        'fix'  => ['label' => 'Fixed',    'class' => 'badge-fixed', 'tech' => false],
        'perf' => ['label' => 'Improved', 'class' => 'badge-tech',  'tech' => true],
    ];

    // Group entries by "Month Year" (already newest-first from git log).
    $groups = [];
    foreach ($entries as $e) {
        $key = 'Earlier';
        if (!empty($e['date'])) {
            try { $key = \Carbon\Carbon::parse($e['date'])->format('F Y'); } catch (\Throwable $ex) {}
        }
        $groups[$key][] = $e;
    }
@endphp

<div class="wn-shell">

    <div class="wn-topbar">
        <div>
            <h1 class="page-title"><i class="fas fa-bullhorn"></i>What's New</h1>
            <p class="page-sub">
                Latest updates and improvements to the system.
                @if ($generatedAt)
                    &middot; Updated {{ \Carbon\Carbon::parse($generatedAt)->format('M j, Y') }}
                @endif
                @if ($current)
                    &middot; Version <code>{{ $current }}</code>
                @endif
            </p>
        </div>
        @if (count($entries))
            <label class="wn-toggle">
                <input type="checkbox" id="wnTechToggle"> Show technical changes
            </label>
        @endif
    </div>

    <div class="wn-card">
        @forelse ($groups as $month => $items)
            <div class="wn-month">{{ $month }}</div>
            @foreach ($items as $e)
                @php
                    $meta = $typeMeta[$e['type']] ?? ['label' => ucfirst($e['type'] ?: 'Change'), 'class' => 'badge-tech', 'tech' => true];
                    $isTech = $meta['tech'];
                @endphp
                <div class="wn-item {{ $isTech ? 'is-technical' : '' }}">
                    @if (!empty($e['breaking']))
                        <span class="wn-badge badge-break">Breaking</span>
                    @else
                        <span class="wn-badge {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                    @endif
                    <div class="wn-body">
                        <div class="wn-text">
                            @if (!empty($e['scope']))
                                <span class="wn-scope">{{ $e['scope'] }}:</span>
                            @endif
                            {{ $e['subject'] }}
                        </div>
                        <div class="wn-meta">
                            @if (!empty($e['date'])){{ \Carbon\Carbon::parse($e['date'])->format('M j, Y') }}@endif
                            @if (!empty($e['short'])) &middot; <code>{{ $e['short'] }}</code>@endif
                        </div>
                    </div>
                </div>
            @endforeach
        @empty
            <div class="wn-empty">
                <i class="fas fa-inbox"></i>
                <p>No changelog available yet.</p>
                <span>Updates will appear here after the next release.</span>
            </div>
        @endforelse
    </div>

</div>

<script>
    (function () {
        var toggle = document.getElementById('wnTechToggle');
        if (!toggle) return;

        function refreshMonths() {
            // Hide a month header when it has no visible items under the current filter.
            document.querySelectorAll('.wn-month').forEach(function (header) {
                var hasVisible = false;
                var node = header.nextElementSibling;
                while (node && node.classList.contains('wn-item')) {
                    if (node.offsetParent !== null) { hasVisible = true; break; }
                    node = node.nextElementSibling;
                }
                header.style.display = hasVisible ? '' : 'none';
            });
        }

        toggle.addEventListener('change', function () {
            document.body.classList.toggle('wn-show-tech', toggle.checked);
            refreshMonths();
        });

        refreshMonths();
    })();
</script>

@endsection
