<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="https://unpkg.com/axios@0.27.0/dist/axios.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Portal Login') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-teal: #008080;
            --dark-teal: #006666;
            --deep-teal: #004d4d;
            --teal-mid: #4db6ac;
            --teal-light: #e0f2f1;
            --soft-teal: rgba(0, 128, 128, 0.12);
            --slate: #334155;
            --slate-light: #64748b;
            --muted: #94a3b8;
            --border: #e2e8f0;
            --radius-card: 24px;
        }

        * { box-sizing: border-box; }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            background: radial-gradient(circle at 20% 20%, #0a5c5c 0%, transparent 55%),
                        radial-gradient(circle at 80% 80%, #013a3a 0%, transparent 55%),
                        linear-gradient(135deg, var(--primary-teal) 0%, var(--deep-teal) 100%);
        }

        /* Ambient animated blobs behind the card */
        .bg-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(70px);
            opacity: .45;
            z-index: 0;
            pointer-events: none;
        }
        .bg-blob.b1 { width: 460px; height: 460px; top: -140px; left: -120px; background: var(--teal-mid); animation: float1 16s ease-in-out infinite; }
        .bg-blob.b2 { width: 520px; height: 520px; bottom: -180px; right: -140px; background: #00b3a4; animation: float2 20s ease-in-out infinite; }
        .bg-blob.b3 { width: 300px; height: 300px; top: 40%; left: 55%; background: #0fd4c0; opacity: .25; animation: float1 24s ease-in-out infinite; }

        @keyframes float1 { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(40px,-30px) scale(1.08); } }
        @keyframes float2 { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(-50px,30px) scale(1.12); } }

        .login-container {
            position: relative;
            z-index: 1;
            background-color: rgba(255, 255, 255, 0.98);
            border-radius: var(--radius-card);
            box-shadow: 0 30px 70px -20px rgba(0, 40, 40, 0.55),
                        0 0 0 1px rgba(255, 255, 255, 0.4) inset;
            width: 100%;
            max-width: 1040px;
            overflow: hidden;
            animation: cardIn .7s cubic-bezier(.16,.84,.44,1) both;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(24px) scale(.985); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ---------------- Form side ---------------- */
        .login-card-form {
            padding: 56px 52px;
        }

        .brand-mark {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 34px;
        }
        .brand-mark img {
            height: 46px;
            width: 46px;
            object-fit: contain;
            border-radius: 12px;
            background: var(--teal-light);
            padding: 5px;
            box-shadow: 0 4px 12px rgba(0,128,128,.18);
        }
        .brand-mark .brand-name {
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--slate);
            letter-spacing: -.01em;
            line-height: 1.1;
        }
        .brand-mark .brand-sub {
            font-size: .72rem;
            color: var(--muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .login-card-form h2 {
            font-weight: 800;
            color: var(--slate);
            margin-bottom: 8px;
            font-size: 1.9rem;
            letter-spacing: -.02em;
        }

        .login-card-form .subtitle {
            color: var(--slate-light);
            margin-bottom: 34px;
            font-size: .95rem;
        }

        .form-floating > label {
            color: var(--slate-light);
            padding-left: 1rem;
        }
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: var(--primary-teal);
            font-weight: 600;
        }

        .form-control {
            border: 1.5px solid var(--border);
            border-radius: 14px;
            padding: 1rem;
            background: #fbfdfd;
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        }
        .form-control:hover { border-color: #cbd5e1; }
        .form-control:focus {
            border-color: var(--primary-teal);
            box-shadow: 0 0 0 4px var(--soft-teal);
            background: #ffffff;
        }

        .btn-color {
            background: linear-gradient(135deg, var(--primary-teal) 0%, var(--dark-teal) 100%);
            border: none;
            color: white;
            width: 100%;
            padding: 14px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: .01em;
            transition: all 0.25s ease;
            margin-top: 8px;
            box-shadow: 0 10px 22px -8px rgba(0, 128, 128, 0.6);
        }
        .btn-color:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px -10px rgba(0, 128, 128, 0.7);
            filter: brightness(1.05);
        }
        .btn-color:active { transform: translateY(0); }
        .btn-color:disabled {
            opacity: .8;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
            filter: none;
        }

        .toggle-password {
            text-decoration: none;
            z-index: 5;
            color: var(--muted);
        }
        .toggle-password:hover { color: var(--primary-teal) !important; }
        .toggle-password:focus { box-shadow: none; }

        .form-control.is-invalid { border-color: #dc3545; }
        .error-text { display: block; margin-top: .3rem; }

        .form-divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 28px 0 6px;
            color: var(--muted);
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .1em;
        }
        .form-divider::before, .form-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .secure-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--muted);
            font-size: .82rem;
            margin-top: 22px;
        }
        .secure-note i { color: var(--teal-mid); }

        /* ---------------- Branding side ---------------- */
        .brand-panel {
            position: relative;
            height: 100%;
            min-height: 560px;
            background: linear-gradient(150deg, var(--primary-teal) 0%, var(--deep-teal) 100%);
            color: #fff;
            padding: 56px 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }
        /* decorative rings */
        .brand-panel::before,
        .brand-panel::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            border: 1.5px solid rgba(255,255,255,.14);
        }
        .brand-panel::before { width: 340px; height: 340px; top: -110px; right: -90px; }
        .brand-panel::after  { width: 220px; height: 220px; bottom: -70px; left: -60px; background: rgba(255,255,255,.05); border: none; }

        .brand-panel .panel-top { position: relative; z-index: 2; }
        .brand-panel .panel-logo {
            height: 96px;
            width: 96px;
            object-fit: contain;
            border-radius: 20px;
            background: rgba(255,255,255,.95);
            padding: 12px;
            box-shadow: 0 12px 30px rgba(0,0,0,.22);
            margin-bottom: 26px;
        }
        .brand-panel h3 {
            font-weight: 800;
            font-size: 1.75rem;
            margin-bottom: 10px;
            letter-spacing: -.02em;
        }
        .brand-panel .panel-lead {
            opacity: .85;
            font-size: .98rem;
            line-height: 1.6;
            max-width: 340px;
        }

        .feature-list {
            position: relative;
            z-index: 2;
            list-style: none;
            padding: 0;
            margin: 34px 0 0;
        }
        .feature-list li {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
            font-size: .95rem;
            font-weight: 500;
        }
        .feature-list .fi-icon {
            flex: 0 0 auto;
            height: 38px;
            width: 38px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: rgba(255,255,255,.14);
            backdrop-filter: blur(4px);
            font-size: .95rem;
        }

        .brand-panel .panel-foot {
            position: relative;
            z-index: 2;
            font-size: .8rem;
            opacity: .7;
        }

        /* ---------------- Responsive ---------------- */
        @media (max-width: 991.98px) {
            .brand-panel { display: none; }
            .login-card-form { padding: 44px 40px; }
        }
        @media (max-width: 575.98px) {
            body { padding: 0; }
            .login-container { border-radius: 0; min-height: 100vh; display: flex; align-items: center; }
            .login-card-form { padding: 34px 22px; width: 100%; }
            .login-card-form h2 { font-size: 1.6rem; }
        }
    </style>
</head>
<body>
    {{-- Ambient background --}}
    <span class="bg-blob b1"></span>
    <span class="bg-blob b2"></span>
    <span class="bg-blob b3"></span>

    <div class="login-container">
        <div class="row g-0">

            {{-- Left: form column --}}
            <div class="col-lg-6 col-md-12">
                <div class="login-card-form">

                    {{-- Brand mark (visible on all sizes; doubles as mobile logo) --}}
                    <div class="brand-mark">
                        <img src="{{ asset('img/kwatogslogo.jpg') }}?v=demo" alt="{{ config('app.name') }}">
                        <div>
                            <div class="brand-name">{{ config('app.name', 'HR Portal') }}</div>
                            <div class="brand-sub">Workforce Suite</div>
                        </div>
                    </div>

                    <h2>Welcome back</h2>
                    <p class="subtitle">Sign in to access your workspace.</p>

                    <form id="frmlogin" action="#" autocomplete="off" novalidate>
                        @csrf

                        {{-- Honeypot: bots fill this, humans don't --}}
                        <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

                        {{-- Email or Username --}}
                        <div class="form-floating mb-3">
                            <input type="text"
                                   class="form-control"
                                   id="floatingInput"
                                   name="username"
                                   placeholder="Email or Username"
                                   autocomplete="username">
                            <label for="floatingInput"><i class="fa-regular fa-user me-2"></i>Email or Username</label>
                            <span class="error-text username_error text-danger" style="font-size:.8rem;"></span>
                        </div>

                        {{-- Password --}}
                        <div class="form-floating mb-2 position-relative">
                            <input type="password"
                                   class="form-control"
                                   id="floatingPassword"
                                   name="password"
                                   placeholder="Password"
                                   autocomplete="current-password">
                            <label for="floatingPassword"><i class="fa-solid fa-lock me-2"></i>Password</label>
                            <a href="#"
                               class="toggle-password position-absolute end-0 top-50 translate-middle-y me-3"
                               aria-label="Show password">
                                <i class="fa fa-eye"></i>
                            </a>
                            <span class="error-text password_error text-danger" style="font-size:.8rem;"></span>
                        </div>

                        {{-- Submit --}}
                        <button type="button" id="btnLogin" class="btn btn-color mt-3">
                            <span class="btn-label">
                                <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
                            </span>
                        </button>

                    </form>

                    <div class="secure-note">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Secured with encrypted access</span>
                    </div>
                </div>
            </div>

            {{-- Right: branding column --}}
            <div class="col-lg-6 d-none d-lg-block">
                <div class="brand-panel">
                    <div class="panel-top">
                        <img class="panel-logo" src="{{ asset('img/kwatogslogo.jpg') }}?v=demo" alt="{{ config('app.name') }}">
                        <h3>{{ config('app.name', 'HR Portal') }}</h3>
                        <p class="panel-lead">Your all-in-one workforce management solution — payroll, attendance, leave, and compliance in one place.</p>

                        <ul class="feature-list">
                            <li><span class="fi-icon"><i class="fa-solid fa-money-check-dollar"></i></span> Automated payroll &amp; payslips</li>
                            <li><span class="fi-icon"><i class="fa-solid fa-clock"></i></span> Real-time attendance tracking</li>
                            <li><span class="fi-icon"><i class="fa-solid fa-file-shield"></i></span> Government-compliant reporting</li>
                        </ul>
                    </div>

                    <div class="panel-foot">
                        &copy; {{ date('Y') }} {{ config('app.name', 'HR Portal') }}. All rights reserved.
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="{{ asset('js/login.js') }}"></script>

    {{-- IP-denied flash: fires when CheckEmployeeIp middleware redirects here --}}
    @if(session('ip_denied'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'error',
                title: 'Access Denied',
                text: '{{ session("ip_denied") }}',
                confirmButtonColor: '#008080',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false,
            });
        });
    </script>
    @endif

</body>
</html>
