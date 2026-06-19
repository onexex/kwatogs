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

    <style>
        :root {
            --primary-teal: #008080;
            --dark-teal: #005a5a;
            --soft-teal: rgba(0, 128, 128, 0.1);
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-teal) 0%, var(--dark-teal) 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }

        .login-container {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            width: 95%;
            max-width: 1000px;
            overflow: hidden;
        }

        .login-card-form {
            padding: 50px;
        }

        .login-card-form h2 {
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .login-card-form p {
            color: #718096;
            margin-bottom: 35px;
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: var(--primary-teal);
        }

        .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-teal);
            box-shadow: 0 0 0 0.25rem var(--soft-teal);
        }

        .btn-color {
            background-color: var(--primary-teal);
            border: none;
            color: white;
            width: 100%;
            padding: 12px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-color:hover {
            background-color: var(--dark-teal);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 128, 128, 0.3);
        }

        .btn-color:disabled {
            opacity: .75;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .img-logo {
            background-color: #f8fafc;
            background-image: url('../img/kwatogslogo.jpg');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: contain;
            height: 100%;
            min-height: 500px;
            width: 100%;
            position: relative;
            image-rendering: -webkit-optimize-contrast;
        }

        .overlay-text {
            position: absolute;
            bottom: 40px;
            left: 40px;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .text-teal { color: var(--primary-teal); }
        .text-teal:hover { color: var(--dark-teal); text-decoration: underline !important; }

        .toggle-password {
            text-decoration: none;
            z-index: 5;
        }
        .toggle-password:hover { color: var(--primary-teal) !important; }
        .toggle-password:focus { box-shadow: none; }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        @media (max-width: 768px) {
            .img-logo { display: none; }
            .login-card-form { padding: 30px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="row g-0">

            {{-- Left: form column --}}
            <div class="col-lg-6 col-md-12">
                <div class="login-card-form">
                    <h2>Welcome Back</h2>
                    <p>Please enter your details to sign in.</p>

                    <form id="frmlogin" action="#" autocomplete="off" novalidate>
                        @csrf

                        {{-- Honeypot: bots fill this, humans don't --}}
                        <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

                        {{-- Email --}}
                        <div class="form-floating mb-3">
                            <input type="email"
                                   class="form-control"
                                   id="floatingInput"
                                   name="username"
                                   placeholder="name@example.com"
                                   autocomplete="username">
                            <label for="floatingInput">Email address</label>
                            <span class="error-text username_error text-danger" style="font-size:.8rem;"></span>
                        </div>

                        {{-- Password --}}
                        <div class="form-floating mb-3 position-relative">
                            <input type="password"
                                   class="form-control"
                                   id="floatingPassword"
                                   name="password"
                                   placeholder="Password"
                                   autocomplete="current-password">
                            <label for="floatingPassword">Password</label>
                            <a href="#"
                               class="toggle-password position-absolute end-0 top-50 translate-middle-y me-3 text-muted"
                               aria-label="Show password">
                                <i class="fa fa-eye"></i>
                            </a>
                            <span class="error-text password_error text-danger" style="font-size:.8rem;"></span>
                        </div>

                        {{-- Submit --}}
                        <button type="button" id="btnLogin" class="btn btn-color">
                            <span class="btn-label">
                                <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
                            </span>
                        </button>

                    </form>
                </div>
            </div>

            {{-- Right: logo / branding column --}}
            <div class="col-lg-6 d-none d-lg-block">
                <div class="img-logo">
                    <div class="overlay-text">
                        <h3 class="fw-bold mb-1">{{ config('app.name', 'HR Portal') }}</h3>
                        <p class="mb-0" style="opacity:.85;font-size:.9rem;">Your all-in-one workforce management solution</p>
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
