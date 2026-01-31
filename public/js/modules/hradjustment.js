 // Open Modal and Set Data
    $(document).on('click', '.btn-deduct', function() {
        const id = $(this).data('id');
        const name = $(this).data('employee');
        
        $('#summary_id').val(id);
        $('#deductionEmployeeName').text('Deduct from: ' + name);
        $('#frmDeduction')[0].reset();
        $('.error-text').text('');
    });

    // Save Deduction via AJAX
    $('#btnSaveDeduction').on('click', function(e) {
        e.preventDefault();
        const btn = $(this);
        const formData = $('#frmDeduction').serialize();

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

        $.ajax({
            url: "{{ route('attendance.deductions.store') }}", // Create this route in web.php
            method: "POST",
            data: formData,
            success: function(response) {
                if(response.status == 200) {
                    location.reload();
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).text('Confirm Deduction');
                if(xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, val) {
                        $('.' + key + '_error').text(val[0]);
                    });
                }
            }
        });
    });