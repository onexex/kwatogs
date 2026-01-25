$(document).ready(function() {
    var formAction = 0;
    var posID = 0;

    getPos();

    // 1. Fetch and Display Positions
    function getPos() {
        // Show loading spinner in table
        $("#tblPositions").html('<tr><td colspan="3" class="text-center py-5"><div class="spinner-border text-primary opacity-50" role="status"></div></td></tr>');

        axios.get('/position/get_position')
            .then(function (response) {
                var htmlData = '';
                var resultData = response.data.data;

                if (resultData.length > 0) {
                    $(resultData).each(function(index, row) {
                        htmlData += "<tr> " +
                            "<td class='ps-4 fw-bold text-dark text-uppercase'>" + row.pos_desc + "</td>" +
                            // "<td><span class='badge bg-light text-primary border rounded-pill px-3'>" + row.pos_jl_desc + "</span></td>" +
                            "<td class='pe-4 text-end'>" +
                                "<div class='d-flex justify-content-end gap-2'>" +
                                    "<button type='button' value='"+ row.id +"' class='btn btn-light btn-sm rounded-circle shadow-sm p-2' id='btnUpdatePosition' data-bs-toggle='modal' data-bs-target='#mdlPosition' title='Edit'>" +
                                        "<i class='fa-solid fa-pencil text-primary'></i>" +
                                    "</button>" +
                                    "<button type='button' value='"+ row.id +"' class='btn btn-light btn-sm rounded-circle shadow-sm p-2' id='btnDeletePosition' title='Delete'>" +
                                        "<i class='fa-solid fa-trash text-danger'></i>" +
                                    "</button>" +
                                "</div>" +
                            "</td>" +
                        "</tr>";
                    });
                } else {
                    htmlData = "<tr><td colspan='3' class='text-center py-5 text-muted small'>No positions found.</td></tr>";
                }
                $("#tblPositions").empty().append(htmlData);
            })
            .catch(function (error) {
                Swal.fire({ icon: 'error', title: 'Fetch Error', text: error });
            });
    }

    // 2. Open Create Modal
    $(document).on('click', '#btnAddPosition', function(e) {
        formAction = 1;
        $('.lblActionDesc').text('Creating Position'); // Fixed label
        $('#frmPosition')[0].reset();
        $('span.error-text').text("");
        $('input, select').removeClass('border border-danger');
    });

    // 3. Open Update Modal & Fetch Data
    $(document).on('click', '#btnUpdatePosition', function(e) {
        formAction = 2;
        posID = $(this).val();
        $('.lblActionDesc').text('Updating Position'); // Fixed label
        $('span.error-text').text("");
        $('input, select').removeClass('border border-danger');

        Swal.fire({
            title: 'Loading...',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => { Swal.showLoading(); }
        });

        axios.get('/position/edit', { params: { posID: posID } })
            .then(function (response) {
                Swal.close();
                $(response.data.data).each(function(index, row) {
                    $('#txtPosition').val(row.pos_desc);
                    // $('#selJobLevel').val(row.pos_jl);
                });
            })
            .catch(function (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error });
            });
    });

    // 4. Save/Update Function
    $(document).on('click', '#btnSavePosition', function(e) {
        var datas = $('#frmPosition');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formAction);
        // formData.append('jobdesc', $('#selJobLevel option:selected').text());
        // formData.append('jobdesc');
        formData.append('posID', posID);

        Swal.fire({
            title: 'Processing...',
            text: 'Please wait while we save the entry.',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => { Swal.showLoading(); }
        });

        axios.post('/position/create_update', formData)
            .then(function (response) {
                $('span.error-text').text("");
                $('input, select').removeClass('border border-danger');

                if (response.data.status == 201) { // Validation Error
                    Swal.close();
                    $.each(response.data.error, function(prefix, val) {
                        $('[name='+ prefix +']').addClass("border border-danger");
                        $('span.' + prefix + '_error').text(val[0]);
                    });
                } 
                else if (response.data.status == 200 || response.data.status == 202) {
                    Swal.fire({ icon: 'success', title: 'Success!', text: response.data.msg, timer: 2000, showConfirmButton: false });
                    $('#mdlPosition').modal('hide');
                    if(formAction == 1) $('#frmPosition')[0].reset();
                    getPos();
                }
            })
            .catch(function (error) {
                Swal.fire({ icon: 'error', title: 'Oops...', text: error });
            });
    });

    // 5. Delete Function
    $(document).on('click', '#btnDeletePosition', function(e) {
        var id = $(this).val();
        Swal.fire({
            title: 'Are you sure?',
            text: "This position will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                axios.get('/position/delete', { params: { posID: id } })
                    .then(function (response) {
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: response.data.msg, timer: 2000, showConfirmButton: false });
                        getPos();
                    })
                    .catch(function (error) {
                        Swal.fire({ icon: 'error', title: 'Error', text: error });
                    });
            }
        });
    });
});