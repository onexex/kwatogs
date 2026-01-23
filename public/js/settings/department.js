$(document).ready(function() {
    var formAction=0;
    var depID=0;
    department_get();
function department_get() {
    axios.get('/department/getall')
        .then(function (response) {
            var htmlData = '';
            var resultData = response.data.data;

            if (resultData.length > 0) {
                $(resultData).each(function (index, row) {
                    htmlData += "<tr>" +
                        // Department Name with padding and styling
                        "<td class='ps-4 fw-bold text-dark text-uppercase'>" + row.dep_name + "</td>" +
                        
                        // Modern Action Buttons (Circle style)
                        "<td class='pe-4 text-end'>" +
                            "<div class='d-flex justify-content-end gap-2'>" +
                                // Edit Button
                                "<button type='button' value='" + row.id + "' class='btn btn-light btn-sm rounded-circle shadow-sm p-2' id='btnUpdateDepartment' data-bs-toggle='modal' data-bs-target='#mdlDepartment' title='Edit Department'>" +
                                    "<i class='fa-solid fa-pencil text-primary'></i>" +
                                "</button>" +
                                
                                // Delete Button (Added for uniformity)
                                "<button type='button' value='" + row.id + "' class='btn btn-light btn-sm rounded-circle shadow-sm p-2' id='btnDeleteDepartment' title='Delete Department'>" +
                                    "<i class='fa-solid fa-trash text-danger'></i>" +
                                "</button>" +
                            "</div>" +
                        "</td>" +
                    "</tr>";
                });
            } else {
                // Modern Empty State
                htmlData = "<tr><td colspan='2' class='text-center py-5 text-muted small'>No departments found.</td></tr>";
            }

            $("#tblDepartments").empty().append(htmlData);

            // Re-initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        })
        .catch(function (error) {
            // Updated to use SweetAlert for error reporting
            Swal.fire({
                icon: 'error',
                title: 'Connection Error',
                text: 'Could not load departments: ' + error
            });
        });
}

    //create Function
    $(document).on('click', '#btnCreateDept', function(e) {
        formAction=1;
        $('.lblActionDesc').text('Creating Department');
        $('span.error-text').text("");
        $('input.border').removeClass('border border-danger');
        $('#frmDepartment')[0].reset();
    });

    //edit Function
    $(document).on('click', '#btnUpdateDepartment', function(e) {

        formAction=2;
        depID=$(this).val();
        $('.lblActionDesc').text('Updating Department');
        axios.get('/department/edit',{
            params: {
                depID: depID
              }
          })
        .then(function (response) {
            $(response.data.data).each(function(index, row) {
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');
                $('#txtDeptName').val(row.dep_name);

            })
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});

    });

   $(document).on('click', '#btnDepSave', function(e) {
    e.preventDefault();

    var datas = $('#frmDepartment');
    var formData = new FormData($(datas)[0]);
    formData.append('formAction', formAction);
    formData.append('depID', depID);

    // 1. Show Processing Spinner
    Swal.fire({
        title: 'Processing...',
        text: 'Please wait while we save the department details.',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // 2. Execute Request
    axios.post('/department/create_update', formData)
        .then(function (response) {
            // Clear previous error styles immediately
            $('span.error-text').text("");
            $('input').removeClass('border border-danger');

            var status = response.data.status;

            if (status == 201) { // Validation Errors
                Swal.close(); // Close loader so user can see red input borders
                $.each(response.data.error, function(prefix, val) {
                    $('input[name=' + prefix + ']').addClass("border border-danger");
                    $('span.' + prefix + '_error').text(val[0]);
                });
            } 
            else if (status == 200 || status == 202) { // Success (Created or Updated)
                // Refresh the table (which now includes the DESC order)
                department_get();

                // Show Success Alert
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.data.msg,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Close Modal and Reset Form (if status 200/New)
                    $('#mdlDepartment').modal('hide');
                    if(status == 200) $('#frmDepartment')[0].reset();
                });
            }
        })
        .catch(function (error) {
            // Handle Network or System Errors
            Swal.fire({
                icon: 'error',
                title: 'Request Failed',
                text: 'Something went wrong: ' + error.message
            });
        });
    });
    $(document).on('click', '#btnDeleteDepartment', function(e) {
    // Get ID from the button value
    const id = $(this).val();

    // 1. Trigger the Confirmation Dialog
    Swal.fire({
        title: 'Delete Department?',
        text: "Are you sure you want to remove this department? This action cannot be reversed.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33', // Modern Danger Red
        cancelButtonColor: '#6c757d', // Muted Grey
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true // Standard modern button placement
    }).then((result) => {
        if (result.isConfirmed) {

            // 2. Show Processing Spinner while communicating with server
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while the records are updated.',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // 3. Axios Request based on your edit reference
            axios.get('/department/delete', {
                params: {
                    depID: id // Matching your naming convention
                }
            })
            .then(function (response) {
                // 4. Show Success Notification
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: response.data.msg || 'The department has been removed successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });

                // Refresh the table (Descending order)
                department_get();
            })
            .catch(function (error) {
                // 5. Handle Errors
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while deleting: ' + error.message
                });
            });
        }
    });
});


});
