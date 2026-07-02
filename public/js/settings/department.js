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
        depID=0;
        $('#lblTitleDept').text('Add Department');
        $('.lblActionDesc').text('Creating Department');
        $('span.error-text').text("");
        $('input.border').removeClass('border border-danger');
        $('#frmDepartment')[0].reset();
        $('#imgDeptLogoPreview').hide().attr('src', '');
        // Documents need an existing department — only available when editing.
        $('#deptDocsSection').hide();
        $('#frmDeptDoc')[0].reset();
        $('#tblDeptDocs').empty();
    });

    //edit Function
    $(document).on('click', '#btnUpdateDepartment', function(e) {

        formAction=2;
        depID=$(this).val();
        $('#lblTitleDept').text('Edit Department');
        $('.lblActionDesc').text('Updating Department');
        $('#frmDepartment')[0].reset();
        $('#frmDeptDoc')[0].reset();
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
                $('#txtDeptPhone').val(row.dep_contact_phone);
                $('#txtDeptEmail').val(row.dep_email);
                $('#txtDeptAddress').val(row.dep_address);
                $('#txtDeptDescription').val(row.dep_description);
                $('#txtDeptTin').val(row.dep_tin);
                $('#txtDeptSss').val(row.dep_sss_employer_no);
                $('#txtDeptPhilhealth').val(row.dep_philhealth_employer_no);
                $('#txtDeptPagibig').val(row.dep_pagibig_employer_no);

                if (row.dep_logo_path) {
                    $('#imgDeptLogoPreview').attr('src', '/img/departments/' + row.dep_logo_path).show();
                } else {
                    $('#imgDeptLogoPreview').hide().attr('src', '');
                }
            })
        })
        .catch(function (error) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Could not load department: ' + error });
        })
        .then(function () {});

        // Show + load the related-documents panel for this department.
        $('#deptDocsSection').show();
        deptDocs_get(depID);

    });

    // ── Related Documents ───────────────────────────────────────
    function deptDocs_get(id) {
        axios.get('/department/documents', { params: { depID: id } })
            .then(function (response) {
                var rows = response.data.data || [];
                var html = '';
                if (rows.length > 0) {
                    $(rows).each(function (i, doc) {
                        html += "<tr>" +
                            "<td class='ps-3'>" + (doc.label || '') + "</td>" +
                            "<td class='small text-muted'>" + (doc.original_name || '') + "</td>" +
                            "<td class='text-end pe-3'>" +
                                "<div class='d-flex justify-content-end gap-2'>" +
                                    "<a href='/department/document/download?id=" + doc.id + "' class='icon-action-btn' title='Download'>" +
                                        "<i class='fa-solid fa-download text-primary'></i></a>" +
                                    "<button type='button' class='icon-action-btn danger btnDeleteDeptDoc' data-id='" + doc.id + "' title='Delete'>" +
                                        "<i class='fa-solid fa-trash text-danger'></i></button>" +
                                "</div>" +
                            "</td>" +
                        "</tr>";
                    });
                } else {
                    html = "<tr><td colspan='3' class='text-center py-4 text-muted small'>No documents uploaded.</td></tr>";
                }
                $('#tblDeptDocs').empty().append(html);
            })
            .catch(function (error) {
                $('#tblDeptDocs').empty().append("<tr><td colspan='3' class='text-center py-4 text-danger small'>Could not load documents.</td></tr>");
            });
    }

    // Upload a document
    $(document).on('click', '#btnUploadDeptDoc', function(e) {
        e.preventDefault();
        $('span.document_error').text("");
        $('#txtDeptDocFile').removeClass('border border-danger');

        var formData = new FormData($('#frmDeptDoc')[0]);
        formData.append('depID', depID);

        Swal.fire({
            title: 'Uploading...',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => { Swal.showLoading(); }
        });

        axios.post('/department/document/upload', formData)
            .then(function (response) {
                var status = response.data.status;
                if (status == 201) { // Validation errors
                    Swal.close();
                    $.each(response.data.error, function(prefix, val) {
                        $('input[name=' + prefix + ']').addClass("border border-danger");
                        $('span.' + prefix + '_error').text(val[0]);
                    });
                } else if (status == 200) {
                    $('#frmDeptDoc')[0].reset();
                    deptDocs_get(depID);
                    Swal.fire({ icon: 'success', title: 'Uploaded', text: response.data.msg, timer: 1500, showConfirmButton: false });
                }
            })
            .catch(function (error) {
                Swal.fire({ icon: 'error', title: 'Upload Failed', text: 'Something went wrong: ' + error.message });
            });
    });

    // Delete a document
    $(document).on('click', '.btnDeleteDeptDoc', function(e) {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Delete Document?',
            text: "This will permanently remove the file.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                axios.get('/department/document/delete', { params: { id: id } })
                    .then(function (response) {
                        deptDocs_get(depID);
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: response.data.msg, timer: 1500, showConfirmButton: false });
                    })
                    .catch(function (error) {
                        Swal.fire({ icon: 'error', title: 'Error!', text: 'An error occurred: ' + error.message });
                    });
            }
        });
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
