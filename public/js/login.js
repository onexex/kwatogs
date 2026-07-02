$(document).ready(function() {

    // Send the CSRF token as a header (alongside the form's _token field) and mark requests
    // as AJAX so Laravel returns JSON — not the HTML "Page Expired" page — on a 419.
    axios.defaults.headers.common['X-CSRF-TOKEN'] = $('meta[name="csrf-token"]').attr('content');
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    // Pull a fresh CSRF token from the server and apply it to the header + hidden field, so an
    // expired/stale token (idle page, or a cached page shown after logout) self-heals.
    function refreshCsrf() {
        return axios.get('/csrf-token').then(function (r) {
            var t = r.data.token;
            $('meta[name="csrf-token"]').attr('content', t);
            $('#frmlogin input[name="_token"]').val(t);
            axios.defaults.headers.common['X-CSRF-TOKEN'] = t;
            return t;
        });
    }

    // 👁️ Toggle password visibility
    $(document).on('click', '.toggle-password', function() {
        const input = $(this).closest('.form-floating').find('input');
        const icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
            $(this).attr('aria-label', 'Hide password');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
            $(this).attr('aria-label', 'Show password');
        }
    });

    $(document).on('submit', '#frmlogin', function(e) {
        e.preventDefault();
    });

    $(document).on('click', '#btnLogin', function(e) {
        e.preventDefault(); // prevent default form submission

        const btn = $(this);
        const originalLabel = btn.find('.btn-label').html();

        // Reset previous error states
        $('input').removeClass('border border-danger is-invalid');
        $('span.error-text').text('');

        // Basic client-side guard
        const username = $('#floatingInput').val().trim();
        const password = $('#floatingPassword').val();

        if (!username || !password) {
            if (!username) {
                $('#floatingInput').addClass('border border-danger is-invalid');
                $('.username_error').text('Email or username is required.');
            }
            if (!password) {
                $('#floatingPassword').addClass('border border-danger is-invalid');
                $('.password_error').text('Password is required.');
            }
            return;
        }

        btn.prop('disabled', true);
        btn.find('.btn-label').html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Signing in...');

        function reenable() {
            btn.prop('disabled', false);
            btn.find('.btn-label').html(originalLabel);
        }

        function handleResponse(res) {
            // 201 validation errors
            if (res.data.status == 201) {
                $('input').removeClass('border border-danger is-invalid');
                $('span.error-text').text("");

                $.each(res.data.error, function(prefix, val) {
                    $('input[name=' + prefix + ']').addClass("border border-danger is-invalid");
                    $('.' + prefix + '_error').text(val[0]);

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: val[0],
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                });
            }

            // 200 success
            if (res.data.status == 200) {
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger is-invalid');

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Welcome back! Redirecting...',
                    showConfirmButton: false,
                    timer: 1200,
                    allowOutsideClick: false
                }).then(() => {
                    window.location.href = "/";
                });
                return; // keep button disabled while redirecting
            }

            // 202 invalid credentials / inactive account
            if (res.data.status == 202) {
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger is-invalid');
                $('#floatingInput, #floatingPassword').addClass('border border-danger is-invalid');

                Swal.fire({
                    icon: 'error',
                    title: 'Sign in failed',
                    text: res.data.msg,
                });
            }

            // 403 IP address not in the allowlist (session already destroyed server-side)
            if (res.data.status == 403) {
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger is-invalid');

                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: res.data.msg,
                    confirmButtonColor: '#008080',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                });
            }

            // 429 too many attempts (rate limited)
            if (res.data.status == 429) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Too many attempts',
                    text: res.data.msg,
                });
            }

            reenable();
        }

        // Post the login; on an expired CSRF token (419) refresh it and retry ONCE.
        function postLogin(isRetry) {
            axios.post('/loginSystem', new FormData($('#frmlogin')[0]))
                .then(handleResponse)
                .catch(function (error) {
                    const status = error.response && error.response.status;

                    if (status === 419 && !isRetry) {
                        // Token expired/stale — get a fresh one and try again transparently.
                        refreshCsrf()
                            .then(function () { postLogin(true); })
                            .catch(function () {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Session expired',
                                    text: 'Please refresh the page and sign in again.',
                                });
                                reenable();
                            });
                        return;
                    }

                    if (status === 429) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Too many attempts',
                            text: 'Please wait a moment before trying again.',
                        });
                    } else if (status === 419) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Session expired',
                            text: 'Your session expired. Please try signing in again.',
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong. Please try again.',
                        });
                    }
                    reenable();
                });
        }

        postLogin(false);
    });
});
