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
            --deep-teal: #003d3d;
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
            background: radial-gradient(120% 120% at 0% 0%, #0b3d3d 0%, #04292b 45%, #011b1d 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 2rem 1rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient aurora glows behind the card */
        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
            z-index: 0;
        }
        body::before {
            width: 46vw; height: 46vw;
            top: -14vw; left: -10vw;
            background: rgba(0, 128, 128, .45);
            animation: drift 18s ease-in-out infinite alternate;
        }
        body::after {
            width: 38vw; height: 38vw;
            bottom: -12vw; right: -8vw;
            background: rgba(77, 182, 172, .28);
            animation: drift 22s ease-in-out infinite alternate-reverse;
        }
        @keyframes drift {
            from { transform: translate3d(0, 0, 0) scale(1); }
            to   { transform: translate3d(4vw, 3vw, 0) scale(1.15); }
        }

        .login-container {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, .96);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, .5);
            border-radius: var(--radius-card);
            box-shadow: 0 30px 70px -20px rgba(0, 0, 0, .55), 0 0 0 1px rgba(0, 0, 0, .04);
            width: 100%;
            max-width: 1020px;
            overflow: hidden;
            animation: riseIn .6s cubic-bezier(.16, 1, .3, 1) both;
        }
        @keyframes riseIn {
            from { opacity: 0; transform: translateY(18px) scale(.985); }
            to   { opacity: 1; transform: none; }
        }

        .login-card-form {
            padding: 56px 52px;
        }

        .brand-chip {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .35rem .75rem .35rem .4rem;
            border: 1px solid var(--border);
            border-radius: 50px;
            background: #fff;
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--slate-light);
            margin-bottom: 28px;
        }
        .brand-chip img {
            height: 24px; width: 24px;
            border-radius: 50%;
            object-fit: cover;
        }

        .login-card-form h2 {
            font-weight: 800;
            font-size: 2rem;
            letter-spacing: -.02em;
            color: var(--slate);
            margin-bottom: 8px;
        }

        .login-card-form .subtitle {
            color: var(--slate-light);
            margin-bottom: 32px;
            font-size: .95rem;
        }

        .field-label {
            display: block;
            font-size: .8rem;
            font-weight: 600;
            color: var(--slate);
            margin-bottom: .45rem;
            letter-spacing: .01em;
        }

        .field-icon {
            position: absolute;
            left: 1.1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            z-index: 4;
            pointer-events: none;
            transition: color .2s ease;
            font-size: .95rem;
        }
        .input-wrap:focus-within .field-icon { color: var(--primary-teal); }

        .form-control {
            height: 54px;
            border: 1.5px solid var(--border);
            border-radius: 14px;
            padding: .75rem 3rem;
            background: #f8fafc;
            font-size: .95rem;
            color: var(--slate);
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        }
        .form-control::placeholder {
            color: var(--muted);
            opacity: 1;
        }
        .form-control:focus {
            border-color: var(--primary-teal);
            background: #fff;
            box-shadow: 0 0 0 4px var(--soft-teal);
        }
        .form-control.is-invalid {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .btn-color {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary-teal) 0%, var(--dark-teal) 100%);
            border: none;
            color: #fff;
            width: 100%;
            padding: 15px;
            border-radius: 14px;
            font-weight: 700;
            font-size: .98rem;
            letter-spacing: .01em;
            transition: transform .25s ease, box-shadow .25s ease, filter .25s ease;
            margin-top: 6px;
            box-shadow: 0 10px 24px -8px rgba(0, 128, 128, .6);
        }
        .btn-color::after {
            content: '';
            position: absolute;
            inset: 0 auto 0 -60%;
            width: 40%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.28), transparent);
            transform: skewX(-20deg);
            transition: left .6s ease;
        }
        .btn-color:hover {
            color: #fff;
            transform: translateY(-2px);
            filter: brightness(1.06);
            box-shadow: 0 16px 32px -10px rgba(0, 128, 128, .7);
        }
        .btn-color:hover::after { left: 120%; }
        .btn-color:active { transform: translateY(0); }
        .btn-color:disabled {
            opacity: .7;
            cursor: not-allowed;
            transform: none !important;
            filter: none !important;
            box-shadow: none !important;
        }
        .btn-color:disabled::after { display: none; }

        .toggle-password {
            text-decoration: none;
            z-index: 5;
            color: var(--muted);
            transition: color .2s ease;
        }
        .toggle-password:hover { color: var(--primary-teal) !important; }
        .toggle-password:focus { box-shadow: none; }

        .error-text {
            display: block;
            margin-top: .35rem;
            font-size: .78rem;
            font-weight: 500;
        }

        .form-footnote {
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid var(--border);
            font-size: .8rem;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: .45rem;
        }
        .form-footnote i { color: var(--teal-mid); }

        /* ---------- Branding panel ---------- */
        .brand-panel {
            position: relative;
            height: 100%;
            min-height: 560px;
            background: linear-gradient(150deg, var(--primary-teal) 0%, var(--dark-teal) 55%, var(--deep-teal) 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 44px;
            overflow: hidden;
        }
        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 80% 15%, rgba(255,255,255,.16) 0%, transparent 45%),
                radial-gradient(circle at 10% 90%, rgba(77,182,172,.35) 0%, transparent 50%);
        }
        /* Fine grid texture */
        .brand-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
            background-size: 34px 34px;
            mask-image: radial-gradient(circle at 50% 50%, #000 0%, transparent 78%);
            -webkit-mask-image: radial-gradient(circle at 50% 50%, #000 0%, transparent 78%);
        }
        /* Fills the panel so the block sits vertically centered above the footer */
        .brand-inner {
            position: relative;
            z-index: 2;
            color: #fff;
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .brand-logo-wrap {
            width: 190px;
            height: 190px;
            border-radius: 40px;
            background: rgba(255,255,255,.94);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            box-shadow: 0 18px 40px -12px rgba(0,0,0,.45);
            margin: 0 auto 30px;
        }
        .brand-logo-wrap img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            image-rendering: -webkit-optimize-contrast;
        }

        .brand-title {
            font-weight: 800;
            font-size: 1.75rem;
            letter-spacing: -.02em;
            margin-bottom: .5rem;
        }
        .brand-sub {
            font-size: .95rem;
            opacity: .8;
            line-height: 1.6;
            margin: 0 auto 34px;
            max-width: 34ch;
        }

        /* Feature rows stay left-aligned to each other, but the group is centered */
        .brand-features {
            display: inline-block;
            text-align: left;
        }

        .brand-feature {
            display: flex;
            align-items: center;
            gap: .8rem;
            font-size: .875rem;
            color: rgba(255,255,255,.9);
            margin-bottom: .9rem;
        }
        .brand-feature .dot {
            flex: 0 0 auto;
            width: 30px; height: 30px;
            border-radius: 10px;
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .75rem;
        }

        .brand-foot {
            position: relative;
            z-index: 2;
            font-size: .75rem;
            color: rgba(255,255,255,.55);
            letter-spacing: .02em;
            text-align: center;
        }

        /* Mobile brand header */
        .mobile-logo img {
            height: 130px;
            width: 130px;
            object-fit: contain;
            padding: 12px;
            border-radius: 28px;
            background: #fff;
            border: 1px solid var(--border);
            box-shadow: 0 8px 20px -8px rgba(0,0,0,.25);
        }

        @media (max-width: 991.98px) {
            .login-card-form { padding: 40px 34px; }
            .brand-chip { display: none; }
        }

        @media (max-width: 480px) {
            body { padding: 1rem .75rem; }
            .login-container { border-radius: 18px; }
            .login-card-form { padding: 30px 22px; }
            .login-card-form h2 { font-size: 1.5rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            body::before, body::after, .login-container { animation: none; }
            .btn-color, .btn-color::after { transition: none; }
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
                    {{-- Mobile brand header: shown only when the right branding panel is hidden --}}
                    <div class="mobile-logo d-flex d-lg-none flex-column align-items-center text-center mb-4">
                        <img src="{{ asset('img/kwatogslogo.jpg') }}" alt="{{ config('app.name') }}">
                        <h4 class="fw-bold mt-3 mb-1" style="color:var(--slate);">{{ config('app.name', 'HR Portal') }}</h4>
                        <p class="mb-0" style="color:var(--slate-light);font-size:.85rem;">Your all-in-one workforce management solution</p>
                    </div>

                    <span class="brand-chip">
                        <img src="{{ asset('img/kwatogslogo.jpg') }}" alt="">
                        {{ config('app.name', 'HR Portal') }}
                    </span>

                    <h2>Welcome back</h2>
                    <p class="subtitle">Sign in to your account to continue.</p>

                    <form id="frmlogin" action="#" autocomplete="off" novalidate>
                        @csrf

                        {{-- Honeypot: bots fill this, humans don't --}}
                        <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

                        {{-- Email or Username --}}
                        <div class="mb-3">
                            <label class="field-label" for="floatingInput">Email or Username</label>
                            <div class="input-wrap position-relative">
                                <i class="fa-regular fa-user field-icon"></i>
                                <input type="text"
                                       class="form-control"
                                       id="floatingInput"
                                       name="username"
                                       placeholder="Enter your email or username"
                                       autocomplete="username">
                            </div>
                            <span class="error-text username_error text-danger"></span>
                        </div>

                        {{-- Password --}}
                        <div class="mb-3">
                            <label class="field-label" for="floatingPassword">Password</label>
                            <div class="input-wrap position-relative">
                                <i class="fa-solid fa-lock field-icon"></i>
                                <input type="password"
                                       class="form-control"
                                       id="floatingPassword"
                                       name="password"
                                       placeholder="Enter your password"
                                       autocomplete="current-password">
                                <a href="#"
                                   class="toggle-password position-absolute end-0 top-50 translate-middle-y me-3"
                                   aria-label="Show password">
                                    <i class="fa fa-eye"></i>
                                </a>
                            </div>
                            <span class="error-text password_error text-danger"></span>
                        </div>

                        {{-- Submit --}}
                        <button type="button" id="btnLogin" class="btn btn-color mt-3">
                            <span class="btn-label">
                                <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
                            </span>
                        </button>

                        <div class="form-footnote">
                            <i class="fa-solid fa-shield-halved"></i>
                            Secure sign-in. Forgot your password? Contact HR to have it reset.
                        </div>
                    </form>
{{-- 
                    <div class="secure-note">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Secured with encrypted access</span>
                    </div> --}}
                </div>
            </div>

            {{-- Right: branding column --}}
            <div class="col-lg-6 d-none d-lg-block">
                <div class="brand-panel">
                    <div class="brand-inner">
                        <div class="brand-logo-wrap">
                            <img src="{{ asset('img/kwatogslogo.jpg') }}" alt="{{ config('app.name') }}">
                        </div>
                        <h3 class="brand-title">{{ config('app.name', 'HR Portal') }}</h3>
                        <p class="brand-sub">Your all-in-one workforce management solution — payroll, attendance, and people, in one place.</p>

                        <div class="brand-features">
                            <div class="brand-feature">
                                <span class="dot"><i class="fa-solid fa-money-check-dollar"></i></span>
                                Payroll &amp; government compliance
                            </div>
                            <div class="brand-feature">
                                <span class="dot"><i class="fa-regular fa-clock"></i></span>
                                Attendance, schedules &amp; overtime
                            </div>
                            <div class="brand-feature">
                                <span class="dot"><i class="fa-regular fa-folder-open"></i></span>
                                201 files, leave &amp; certificates
                            </div>
                        </div>
                    </div>

                    <div class="brand-foot">
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
