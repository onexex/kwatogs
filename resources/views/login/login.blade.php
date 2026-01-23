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
            overflow: hidden; /* Clips the inner content to radius */
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

        /* Floating Label Customization */
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

        .img-logo {
            background: #f8fafc url('../img/kwatogslogo.jpg') no-repeat center;
            background-size: cover;
            height: 100%;
            min-height: 500px;
            position: relative;
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

        @media (max-width: 768px) {
            .img-logo { display: none; }
            .login-card-form { padding: 30px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="row g-0">
            <div class="col-lg-6 col-md-12">
                <div class="login-card-form">
                    <h2>Welcome Back</h2>
                    <p>Please enter your details to sign in.</p>
                    
                    <form id="frmlogin" action="#">
                        @csrf
                        <div class="form-floating mb-3">
                            <input type="email" name="username" class="form-control" id="floatingInput" placeholder="name@example.com" value="{{ old('username') }}">
                            <label for="floatingInput"><i class="fa-regular fa-envelope me-2"></i>Email Address</label>
                            <span class="text-danger small error-text username_error"></span>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Password">
                            <label for="floatingPassword"><i class="fa-solid fa-lock me-2"></i>Password</label>
                            <span class="text-danger small error-text password_error"></span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="rememberMe">
                                <label class="form-check-label small text-muted" for="rememberMe">Remember me</label>
                            </div>
                            <a href="#" class="small text-decoration-none text-teal fw-bold">Forgot Password?</a>
                        </div>

                        <button type="submit" class="btn btn-color shadow-sm" id="btnLogin">
                            Sign In <i class="fa-solid fa-arrow-right ms-2"></i>
                        </button>

                        <div class="text-center mt-4 pt-2">
                            <span class="text-muted small">Don't have an account?</span> 
                            <a href="#" class="small text-decoration-none text-teal fw-bold">Create Account</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-6 d-none d-lg-block">
                <div class="img-logo">
                    <div class="overlay-text">
                        <h3 class="fw-bold">KWATOGS. Portal</h3>
                        <p class="mb-0">Secure Management System v2.0</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/login.js') }}" defer></script>
</body>
</html>