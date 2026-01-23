$(document).ready(function() {

    $(document).on('click', '#btnLogin', function(e) {
        e.preventDefault(); // prevent default form submission

        let axiosConfig = {
            headers: {
                'Content-Type': 'application/json;charset=UTF-8',
                "Access-Control-Allow-Origin": "*",
            }
        };

        var frmdata = $('#frmlogin');
        var formData = new FormData($(frmdata)[0]);

        axios.post('/loginSystem', formData, axiosConfig)
        .then(function (res) {
            // 201 validation errors
            if (res.data.status == 201) {
                $('input').removeClass('border border-danger');
                $('span.error-text').text("");

                $.each(res.data.error, function(prefix, val) {
                    // Highlight input
                    $('input[name=' + prefix + ']').addClass("border border-danger");
                    
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
                $('input.border').removeClass('border border-danger');

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: res.data.msg,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.href = "/";
                });
            }

            // 202 general error
            if (res.data.status == 202) {
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');

                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: res.data.msg,
                });
            }
        })
        .catch(function (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error,
            });
        });
    });
});
