<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Under Maintenance</title>
    <link rel="icon" type="image/png" href="{{ asset('img/kwatogslogo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --teal: #008080; --teal-dark: #006666; --teal-mid: #4db6ac; --teal-light: #e0f2f1;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 24px; color: #e2e8f0;
            font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
            background: radial-gradient(1200px 600px at 50% -10%, #1e293b 0%, #0f172a 60%, #0b1120 100%);
        }
        .card {
            width: 100%; max-width: 560px; text-align: center;
            background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08);
            border-radius: 22px; padding: 44px 38px;
            box-shadow: 0 30px 80px -20px rgba(0,0,0,.6); backdrop-filter: blur(6px);
        }
        .logo { width: 74px; height: 74px; object-fit: contain; margin-bottom: 18px; filter: drop-shadow(0 6px 16px rgba(0,128,128,.5)); }
        .gear {
            width: 92px; height: 92px; margin: 0 auto 22px; border-radius: 50%;
            display: grid; place-items: center; font-size: 40px; color: #fff;
            background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dark) 100%);
            box-shadow: 0 12px 30px -6px rgba(0,128,128,.6);
        }
        .gear i { animation: spin 4.5s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        h1 { font-size: 26px; font-weight: 800; margin: 0 0 12px; color: #fff; letter-spacing: -.02em; }
        p.msg { font-size: 15.5px; line-height: 1.6; color: #cbd5e1; margin: 0 auto 8px; max-width: 440px; }
        .pill {
            display: inline-flex; align-items: center; gap: 8px; margin-top: 18px;
            background: rgba(77,182,172,.14); color: var(--teal-mid);
            border: 1px solid rgba(77,182,172,.35); border-radius: 999px;
            padding: 8px 16px; font-size: 13px; font-weight: 600;
        }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--teal-mid); animation: blink 1.5s infinite; }
        @keyframes blink { 0%,100% { opacity: 1; } 50% { opacity: .25; } }
        #countdown { font-variant-numeric: tabular-nums; font-weight: 700; color: #fff; }
        .actions { margin-top: 28px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn {
            text-decoration: none; font-weight: 700; font-size: 13.5px; border-radius: 11px; padding: 11px 22px;
            transition: all .18s; border: 1px solid transparent; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-retry { color: #0f172a; background: linear-gradient(135deg, var(--teal-mid) 0%, var(--teal) 100%); }
        .btn-retry:hover { filter: brightness(1.07); transform: translateY(-1px); }
        .btn-ghost { color: #cbd5e1; background: transparent; border-color: rgba(255,255,255,.18); }
        .btn-ghost:hover { background: rgba(255,255,255,.06); color: #fff; }
        .footnote { margin-top: 26px; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="card">
        <img class="logo" src="{{ asset('img/kwatogslogo.png') }}" alt="{{ config('app.name') }}">
        <div class="gear"><i class="fa-solid fa-gear"></i></div>

        <h1>We&rsquo;ll be right back</h1>
        <p class="msg">{{ $message ?? 'The system is temporarily unavailable while we perform scheduled maintenance.' }}</p>

        @if (!empty($endsAt))
            <div class="pill">
                <span class="dot"></span>
                <span>Expected back in <span id="countdown">{{ $endsAt->diffForHumans(null, true) }}</span></span>
            </div>
        @else
            <div class="pill"><span class="dot"></span> Maintenance in progress</div>
        @endif

        <div class="actions">
            <a href="{{ url()->current() }}" class="btn btn-retry"><i class="fa-solid fa-rotate-right"></i> Try again</a>
            <a href="{{ url('/logoutSystem') }}" class="btn btn-ghost"><i class="fa-solid fa-right-from-bracket"></i> Sign out</a>
        </div>

        <div class="footnote">{{ config('app.name') }} &middot; Need access during maintenance? Contact your system administrator.</div>
    </div>

    @if (!empty($endsAt))
    <script>
        // Live countdown; auto-reload when the maintenance window elapses so the
        // user is let back in without manually refreshing.
        (function () {
            var endsAt = new Date("{{ $endsAt->toIso8601String() }}").getTime();
            var el = document.getElementById('countdown');
            function tick() {
                var diff = endsAt - Date.now();
                if (diff <= 0) { location.reload(); return; }
                var s = Math.floor(diff / 1000);
                var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
                el.textContent = (h > 0 ? h + 'h ' : '') + (m > 0 || h > 0 ? m + 'm ' : '') + sec + 's';
                setTimeout(tick, 1000);
            }
            tick();
        })();
    </script>
    @endif
</body>
</html>
