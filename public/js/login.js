$(document).ready(function() {

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

        let axiosConfig = {
            headers: {
                'Content-Type': 'application/json;charset=UTF-8',
                "Access-Control-Allow-Origin": "*",
            }
        };

        var frmdata = $('#frmlogin');
        var formData = new FormData($(frmdata)[0]);

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

        axios.post('/loginSystem', formData, axiosConfig)
        .then(function (res) {
            // 201 validation errors
            if (res.data.status == 201) {
                $('input').removeClass('border border-danger is-invalid');
                $('span.error-text').text("");

                $.each(res.data.error, function(prefix, val) {
                    // Highlight input
                    $('input[name=' + prefix + ']').addClass("border border-danger is-invalid");
                    $('.' + prefix + '_error').text(val[0]);

                    // Show SweetAlert toast
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

            // 403 IP address not in the allowlist
            // The server already destroyed the session before returning this,
            // so no cleanup is needed here — just surface the reason.
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
        })
        .catch(function (error) {
            if (error.response && error.response.status === 429) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Too many attempts',
                    text: 'Please wait a moment before trying again.',
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Something went wrong. Please try again.',
                });
            }
        })
        .finally(function() {
            btn.prop('disabled', false);
            btn.find('.btn-label').html(originalLabel);
        });
    });
});
