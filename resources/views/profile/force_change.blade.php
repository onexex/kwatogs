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

    <title>Change Your Password — {{ config('app.name', 'HR Portal') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

    <style>
        :root { --primary-teal:#008080; --dark-teal:#005a5a; }
        body {
            display:flex; justify-content:center; align-items:center; min-height:100vh;
            background:linear-gradient(135deg,var(--primary-teal) 0%,var(--dark-teal) 100%);
            font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; margin:0; padding:1.5rem 0;
        }
        .card-pw { background:#fff; border-radius:1.25rem; box-shadow:0 20px 40px rgba(0,0,0,.2);
            width:100%; max-width:460px; padding:2.25rem; }
        .card-pw h2 { color:#2d3748; font-weight:700; font-size:1.5rem; }
        .btn-color { background:var(--primary-teal); color:#fff; width:100%; padding:.65rem; font-weight:600; }
        .btn-color:hover { background:var(--dark-teal); color:#fff; }
        .strength-wrap { height:6px; background:#e2e8f0; border-radius:4px; overflow:hidden; }
        #strengthBar { height:100%; width:0; transition:width .2s; }
    </style>
</head>
<body>
    <div class="card-pw">
        <div class="text-center mb-3">
            <i class="fa-solid fa-shield-halved fa-2x mb-2" style="color:var(--primary-teal);"></i>
            <h2>Update Your Password</h2>
            <p class="text-muted mb-0" style="font-size:.9rem;">
                For your security, you must set a new password before continuing.
            </p>
        </div>

        <form id="forceChangeForm" autocomplete="off" novalidate>
            @csrf

            {{-- Current password --}}
            <div class="mb-3 position-relative">
                <label class="form-label">Current Password</label>
                <input type="password" class="form-control" name="current_password" autocomplete="current-password">
                <span class="error-text current_password_error text-danger" style="font-size:.8rem;"></span>
            </div>

            {{-- New password --}}
            <div class="mb-2 position-relative">
                <label class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password">
                <span class="error-text new_password_error text-danger" style="font-size:.8rem;"></span>
            </div>

            {{-- Strength meter --}}
            <div class="strength-wrap mb-1"><div id="strengthBar"></div></div>
            <small id="strengthText" class="d-block mb-3" style="font-size:.78rem;"></small>

            {{-- Confirm --}}
            <div class="mb-2">
                <label class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" name="new_password_confirmation" autocomplete="new-password">
                <small class="conf_msg d-block" style="font-size:.78rem;"></small>
            </div>

            <p class="text-muted" style="font-size:.75rem;">
                Must be at least 6 characters and include upper &amp; lower case letters, a number, and a symbol.
            </p>

            <button type="button" id="btnForceUpdate" class="btn btn-color mt-2">
                <i class="fa-solid fa-save me-2"></i>Change Password
            </button>
            <a href="/logoutSystem" class="btn btn-link w-100 mt-2 text-muted" style="font-size:.85rem;">Sign out</a>
        </form>
    </div>

    <script>
        $(document).ready(function () {

            // Strength meter
            $('#new_password').on('keyup', function () {
                let val = $(this).val(), strength = 0;
                if (val.length > 7) strength += 25;
                if (val.match(/[a-z]/) && val.match(/[A-Z]/)) strength += 25;
                if (val.match(/\d/)) strength += 25;
                if (val.match(/[^a-zA-Z\d]/)) strength += 25;

                let bar = $('#strengthBar'), text = $('#strengthText');
                bar.css('width', strength + '%');
                if (strength <= 25) {
                    bar.css('background', '#dc3545'); text.text('Weak password ⚠️').css('color', '#dc3545');
                } else if (strength <= 75) {
                    bar.css('background', '#ffc107'); text.text('Good password 👍').css('color', '#b8860b');
                } else {
                    bar.css('background', '#198754'); text.text('Strong password 💪').css('color', '#198754');
                }
                checkMatch();
            });

            function checkMatch() {
                let a = $('#new_password').val();
                let b = $('input[name="new_password_confirmation"]').val();
                if (b === '') { $('.conf_msg').text(''); return; }
                if (a === b) {
                    $('input[name="new_password_confirmation"]').addClass('is-valid').removeClass('is-invalid');
                    $('.conf_msg').text('Passwords match!').css('color', '#198754');
                } else {
                    $('input[name="new_password_confirmation"]').addClass('is-invalid').removeClass('is-valid');
                    $('.conf_msg').text('Passwords do not match.').css('color', '#dc3545');
                }
            }
            $('input[name="new_password_confirmation"]').on('keyup', checkMatch);

            $('#btnForceUpdate').on('click', function () {
                const btn = $(this);
                const form = document.getElementById('forceChangeForm');
                const formData = new FormData(form);

                btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Updating...');
                $('.error-text').text('');
                $('.form-control').removeClass('is-invalid');

                axios.post('/force-password-change/update', formData)
                    .then(function (response) {
                        if (response.data.status == 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Password Updated',
                                text: response.data.message,
                                timer: 1400,
                                showConfirmButton: false,
                                allowOutsideClick: false
                            }).then(function () {
                                window.location.href = response.data.redirect || '/';
                            });
                        }
                    })
                    .catch(function (error) {
                        if (error.response && error.response.status === 422) {
                            const errors = error.response.data.errors;
                            Object.keys(errors).forEach(function (key) {
                                $('[name="' + key + '"]').addClass('is-invalid');
                                $('.' + key + '_error').text(errors[key][0]);
                            });
                        } else {
                            Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
                        }
                    })
                    .finally(function () {
                        btn.prop('disabled', false).html('<i class="fa-solid fa-save me-2"></i>Change Password');
                    });
            });
        });
    </script>
</body>
</html>
