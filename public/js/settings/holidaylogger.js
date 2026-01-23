// JMC
$(document).ready(function() {

    var formaction = 0;
    var updateID = '';
    get_hl();

    $(document).on("click", "#btnCreateHoliday", function (e) {
        formaction = 1;
        $('#frmHoliday')[0].reset();
    });

    // $(document).on("click", "#btnSaveHoliday", function (e) {

    //     var datas = $('#frmHoliday');
    //     var type  = $("#selTypeHoliday option:selected").val();
    //     var formData = new FormData($(datas)[0]);
    //     formData.append('formAction', formaction);
    //     formData.append('type', type);
    //     formData.append('updateID', updateID);

    //     axios.post('/classification/createHolidayLogger',formData)
    //     .then(function(response){
    //         var status = response.data.status;

    //         if(status == 201)
    //         {
    //             get_hl();
    //             $.each(response.data.error, function(prefix, val) {
    //                 $('input[name='+ prefix +']').addClass(" border border-danger") ;
    //                 $('span.' + prefix + '_error').text(val[0]);
    //             });
    //         }
    //         if(status == 200)
    //         {
    //             get_hl();
    //             $('span.error-text').text("");
    //             $('input.border').removeClass('border border-danger');
    //             $('#frmHoliday')[0].reset();
    //             dialog.alert({
    //                 message: response.data.msg
    //             });
    //         }
    //         if(status == 202)
    //         {
    //             get_hl();
    //             $('span.error-text').text("");
    //             $('input.border').removeClass('border border-danger');
    //             dialog.alert({
    //                 message: response.data.msg
    //             });
    //         }
    //     })
    //     .catch(function(error){
    //         dialog.alert({
    //             message: error
    //         });
    //     })
    //     .then(function(response){})

    // });


    $(document).on("click", "#btnSaveHoliday", function (e) {
    e.preventDefault();

    // 1. Prepare Data
    var datas = $('#frmHoliday');
    var type = $("#selTypeHoliday").val();
    var formData = new FormData($(datas)[0]);
    formData.append('formAction', formaction);
    formData.append('type', type);
    formData.append('updateID', updateID);

    // 2. Show "Processing" Alert
    Swal.fire({
        title: 'Processing...',
        text: 'Please wait while we save the entries.',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // 3. Send Request
    axios.post('/classification/createHolidayLogger', formData)
        .then(function (response) {
            var status = response.data.status;
            
            // Clear previous errors first
            $('span.error-text').text("");
            $('input, select').removeClass('border border-danger');

            if (status == 201) { // Validation Failed
                Swal.close(); // Close loading
                get_hl();
                $.each(response.data.error, function (prefix, val) {
                    $('#frmHoliday [name=' + prefix + ']').addClass("border border-danger");
                    $('span.' + prefix + '_error').text(val[0]);
                });
            } 
            else if (status == 200 || status == 202) { // Success (Create or Update)
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.data.msg,
                    timer: 2000,
                    showConfirmButton: false
                });

                get_hl();
                if (status == 200) $('#frmHoliday')[0].reset(); // Reset only on new entry
                
                // Hide modal if everything is successful
                $('#mdlHoliday').modal('hide');
            }
        })
        .catch(function (error) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Something went wrong: ' + error
            });
        });
});
  function get_hl() {
    axios.get('/getHL')
        .then(function(response) {
            var resultData = response.data.data;
            var status = response.data.status;
            var content = "";    var holidayBadge = "";

            if (status == 200) { 
                $(resultData).each(function(index, item) {
                    
                    if (item.type === 0) {
                        holidayBadge = '<span class="badge badge-regular rounded-pill px-3 py-2">Regular Holiday</span>';
                    } else {
                        holidayBadge = '<span class="badge badge-special rounded-pill px-3 py-2">Special Holiday</span>';
                    }

                    content += "<tr>" +
                        "<td class='ps-4 fw-medium'>" + item.date + "</td>" + // ps-4 matches header padding
                        "<td>" + holidayBadge + "</td>" +
                        "<td class='text-muted'>" + item.description + "</td>" +
                        "<td class='pe-4 text-end'>" + 
                            "<button type='button' class='btn btn-light btn-sm rounded-circle shadow-sm p-2' value='" + item.id + "' data-bs-toggle='modal' data-bs-target='#mdlHoliday' id='btnUpdateHL' title='Edit'>" + 
                                "<i class='fa-solid fa-pencil text-primary'></i>" + 
                            "</button> " +
                        "</td>" +
                    "</tr>";
                });
                
                // If no data, show a clean empty state
                if (resultData.length === 0) {
                    content = "<tr><td colspan='4' class='text-center py-5 text-muted small'>No holidays logged yet.</td></tr>";
                }

                $("#tblHolidaysLog").empty().append(content);
            } else {
                dialog.alert({
                    message: "Something went wrong!"
                });
            }
        })
        .catch(function(error) {
            dialog.alert({
                message: "Error fetching data: " + error
            });
        });
    }

    $(document).on("click", "#btnUpdateHL", function (e) {

        formaction = 2;
        $(".lblActionDesc").html("Update Holiday Logger");
        var id = $(this).val();
        updateID = id;

        axios.get('/getHLData',{
            params: {
                id: id,
                updateID: updateID
              }
          })
        .then(function(response){
             var resultData = response.data.data;
             var status = response.data.status;

             if(status == 200){
                get_hl();
                $(resultData).each(function(index, item){
                    $('#txtDate').val(item.date);
                    $('#txtDescription').val(item.description);
                    $('#selTypeHoliday').val(item.type);
                });
                $('#btnSaveHoliday').val(id);

             }

        })
        .catch(function(error){
            dialog.alert({
                message: error
            })
        })
        .then(function(response){})
    });

})
