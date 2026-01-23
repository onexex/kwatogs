$(document).ready(function() {
    var formAction=0;
    var CompanyID=0;
    get_company();

    function get_company() {
    axios.get('/company/get_all')
        .then(function (response) {
            var htmlData = '';
            var i = 1;
            var resultData = response.data.data;

            if (resultData.length > 0) {
                $(resultData).each(function (index, row) {
                    // Create a visual color preview circle instead of just showing the hex code
                    var colorCircle = `
                        <div class="d-flex align-items-center justify-content-center">
                            <span class="color-preview" style="background-color: ${row.comp_color};"></span>
                            <small class="ms-2 text-muted fw-bold">${row.comp_color.toUpperCase()}</small>
                        </div>`;

                    htmlData += "<tr>" +
                        // Index/ID column
                        "<td class='ps-4 text-center fw-medium text-secondary'>" + i++ + "</td>" +
                        
                        // Company Code (Bold)
                        "<td class='fw-bold text-dark text-uppercase'>" + row.comp_code + "</td>" +
                        
                        // Company Name
                        "<td class='text-muted'>" + row.comp_name + "</td>" +
                        
                        // Brand Color Preview
                        "<td>" + colorCircle + "</td>" +
                        
                        // Modern Action Buttons (Circle style)
                        "<td class='pe-4 text-end'>" +
                            "<div class='d-flex justify-content-end gap-2'>" +
                                // Edit Button
                                "<button type='button' value='" + row.id + "' class='btn btn-light btn-sm rounded-circle shadow-sm p-2' id='btnUpdateCompany' data-bs-toggle='modal' data-bs-target='#mdlCompany' title='Edit Company'>" +
                                    "<i class='fa-solid fa-pencil text-primary'></i>" +
                                "</button>" +
                                
                                // Delete Button
                                "<button type='button' value='" + row.id + "' class='btn btn-light btn-sm rounded-circle shadow-sm p-2' id='btnUpdatedDelete' title='Delete Company'>" +
                                    "<i class='fa-solid fa-trash text-danger'></i>" +
                                "</button>" +
                            "</div>" +
                        "</td>" +
                    "</tr>";
                });
            } else {
                htmlData = "<tr><td colspan='5' class='text-center py-5 text-muted small'>No companies found in the records.</td></tr>";
            }

            $("#tblCompanies").empty().append(htmlData);

            // Re-initialize tooltips if enabled
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el));
        })
        .catch(function (error) {
            Swal.fire({
                icon: 'error',
                title: 'Data Load Error',
                text: 'Something went wrong: ' + error
            });
        });
}

    //create Function
    $(document).on('click', '#createCompany', function(e) {
        formAction=1;
        $('span.error-text').text("");
        $('input.border').removeClass('border border-danger');
        $('.lblActionDesc').text('Creating Company');
        $('#frmCreateCompany')[0].reset();
    });

    //edit Function
    $(document).on('click', '#btnUpdateCompany', function(e) {
        formAction=2;
        $('span.error-text').text("");
        $('input.border').removeClass('border border-danger');
        CompanyID=$(this).val();
        $('.lblActionDesc').text('Updating Company');
        axios.get('/company/get_edit',{
            params: {
                CompanyID: CompanyID
              }
          })
        .then(function (response) {
            $(response.data.data).each(function(index, row) {
                $('#txtCompanyID').val(row.comp_id);
                $('#txtCompanyName').val(row.comp_name);
                $('#txtCompanyCode').val(row.comp_code);
                $('#txtCompanyColor').val(row.comp_color);
                // $('#txtCompanyLogo').val(row.wt_timecross);
            })
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});

    });

    $(document).on('click', '#btnSaveCompany', function (e) {
        e.preventDefault();

        var datas = $('#frmCreateCompany');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formAction);
        formData.append('CompanyID', CompanyID);

        // 1. Show "Processing" Loader
        Swal.fire({
            title: 'Saving Company...',
            text: 'Please wait while we upload the details and logo.',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // 2. Execute Request
        axios.post('/company/create_update', formData)
            .then(function (response) {
                // Clear previous error styles
                $('span.error-text').text("");
                $('input').removeClass('border border-danger');

                var status = response.data.status;

                if (status == 201) { // Validation Errors
                    Swal.close(); // Hide loader so user can fix inputs
                    $.each(response.data.error, function (prefix, val) {
                        $('input[name=' + prefix + ']').addClass("border border-danger");
                        $('span.' + prefix + '_error').text(val[0]);
                    });
                } 
                else if (status == 200 || status == 202) { // Success
                    // Refresh the list
                    get_company();

                    // Success Message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.msg,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Close Modal and Reset Form (if new entry)
                        $('#mdlCompany').modal('hide');
                        if(status == 200) $('#frmCreateCompany')[0].reset();
                    });
                }
            })
            .catch(function (error) {
                // Handle System/Network Errors
                Swal.fire({
                    icon: 'error',
                    title: 'Submission Failed',
                    text: error.message || 'An unexpected error occurred.'
                });
            });
    });

    $(document).on('click', '#btnUpdatedDelete', function(e) {
        const id = $(this).val();

        // 1. Confirm Delete with a Warning Dialog
        Swal.fire({
            title: 'Are you sure?',
            text: "Deleting this company will remove all associated brand settings. This cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', // Danger Red
            cancelButtonColor: '#6c757d', // Neutral Grey
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {

                // 2. Show Processing Spinner
                Swal.fire({
                    title: 'Deleting Company...',
                    text: 'Removing records and logo files.',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // 3. Execute Axios Request
                axios.get('/company/delete', {
                    params: { id: id }
                })
                .then(function (response) {
                    // 4. Success Toast/Alert
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.data.msg || 'The company has been removed.',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Refresh the modern table
                    get_company();
                })
                .catch(function (error) {
                    // 5. Handle System Error
                    Swal.fire({
                        icon: 'error',
                        title: 'System Error',
                        text: 'Unable to delete company: ' + error.message
                    });
                });
            }
        });
    });

});
