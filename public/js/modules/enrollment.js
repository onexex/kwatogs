$(document).ready(function() {
     //employee number fetch
    //  empNumberGenerate();
     loadprovince();
     function empNumberGenerate(){
         axios.post('/function/generateEmpid',)  
         .then(function (response) {
             //error response
             if (response.data.status == 200) {
                 $('#txtEmployeeNo').val(response.data.data);
             }
         })
         .catch(function (error) {
             dialog.alert({
                 message: error
             });  
         })
         .then(function () {});   
 
     }
 
    //$(document).on('click', '#btnSaveAll', function(e) {
//         var datas = $('#frmEnrolment');
//         var city=$("#txtCity option:selected" ).text();
//         var brgy=$("#txtBrgy option:selected" ).text();
//         var prov=$("#txtProvince option:selected" ).text();
//         var formData = new FormData($(datas)[0]);
//         formData.append('citydesc', city);
//         formData.append('brgydesc', brgy);
//         formData.append('provdesc', prov);

//         //create script
//             axios.post('/enroll/save',formData)  
//             .then(function (response) {

//                 // console.log(response);
//                 // return false;
//                 //error response
//                 if (response.data.status == 201) {
//                     $.each(response.data.error, function(prefix, val) {
//                         $('input[name='+ prefix +']').addClass(" border border-danger") ;
//                         $('span.' + prefix + '_error').text(val[0]);
//                     });
//                 }
//                 //success respose
//                 if(response.data.status == 200){
//                     $('span.error-text').text("");
//                     $('input.border').removeClass('border border-danger');
//                     $('#frmEnrolment')[0].reset();
//                     empNumberGenerate();
//                     dialog.alert({
//                         message: response.data.msg
//                     });
//                 }
//                  //success respose
//                  if(response.data.status == 202){
//                     $('span.error-text').text("");
//                     $('input.border').removeClass('border border-danger');
//                     dialog.alert({
//                         message: response.data.msg
//                     });
//                 }

//                 if(response.data.status == 203){
                   
//                     dialog.alert({
//                         message: response.data.msg
//                     });
//                 }
//             })
//             .catch(function (error) {
//                 dialog.alert({
//                     message: error
//                 });  
//             })
//             .then(function () {});   
       
//     });

    $(document).on('change', '#txtProvince ', function(e) {
        var provCode = $(this).val();
        axios.get('/get_city',{
            params: {
                id: provCode
              }
          })    .then(function (response) {
            if (response.data.status == 200) {
               var bodyData = '';
            //    bodyData += ("<option value=0>-</option>");
               $.each(response.data.data, function(index, row) {
                   bodyData += ("<option value=" + row.citymunCode + ">" + row.citymunDesc + "</option>");
               })
               $("#txtCity").empty();
               $("#txtCity").append(bodyData);
            }
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });  
        })
        .then(function () {}); 
    });

    $(document).on('change', '#txtCity ', function(e) {
        var citycode = $(this).val();
        axios.get('/get_brgy',{
            params: {
                id: citycode
              }
          })    .then(function (response) {
            if (response.data.status == 200) {
               var bodyData = '';
            //    bodyData += ("<option value=0>-</option>");
               $.each(response.data.data, function(index, row) {
                   bodyData += ("<option value=" + row.brgyCode + ">" + row.brgyDesc + "</option>");
               })
               $("#txtBrgy").empty();
               $("#txtBrgy").append(bodyData);
            }
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });  
        })
        .then(function () {}); 

    });

    function loadprovince(e) {

        axios.post('/get_province',)  
         .then(function (response) {
             //error response
             if (response.data.status == 200) {
                var bodyData = '';
                bodyData += ("<option value=0>-</option>");
                $.each(response.data.data, function(index, row) {
                    bodyData += ("<option value=" + row.provCode + ">" + row.provDesc + "</option>");
                })
                $("#txtProvince").empty();
                $("#txtProvince").append(bodyData);
             }
         })
         .catch(function (error) {
             dialog.alert({
                 message: error
             });  
         })
         .then(function () {});  
    }

    function on_save(){
        $('.spin').attr("disabled", "disabled");
        $('.spin').attr('data-btn-text', $('.spin').text());
        $('.spin').html('<span class="spinner"><i class="fa fa-spinner fa-spin"></i></span> Please Wait. Do not Refresh!');
        $('.spin').addClass('active');
    }
    
    function on_done(){
        $('.spin').html($('.spin').attr('data-btn-text'));
        $('.spin').html('<span ><i class="fa fa-plus"></i></span> Save Entries');
        $('.spin').removeClass('active');
        $('.spin').removeAttr("disabled");
    }

    $(document).on('click', '#btnSaveAll', function(e) {
        var btn = $(this);
        var datas = $('#frmEnrolment');
        
        // 1. Disable button & show loading state
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        var city = $("#txtCity option:selected").text();
        var brgy = $("#txtBrgy option:selected").text();
        var prov = $("#txtProvince option:selected").text();

        var formData = new FormData($(datas)[0]);
        formData.append('citydesc', city);
        formData.append('brgydesc', brgy);
        formData.append('provdesc', prov);

        axios.post('/enroll/save', formData)
            .then(function (response) {
                // Reset validation visuals
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');

                // Validation Error (201)
                if (response.data.status == 201) {
                    $.each(response.data.error, function(prefix, val) {
                        $('input[name=' + prefix + ']').addClass(" border border-danger");
                        $('span.' + prefix + '_error').text(val[0]);
                    });
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please check the required fields.'
                    });
                }

                // Success (200)
                if (response.data.status == 200) {
                    $('#frmEnrolment')[0].reset();
                    empNumberGenerate();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.msg,
                        timer: 2000
                    });
                }

                // Warning or Other (202, 203)
                if (response.data.status == 202 || response.data.status == 203) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Notice',
                        text: response.data.msg
                    });
                }
            })
            .catch(function (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'Could not connect to the server.'
                });
            })
            .finally(function () {
                // 2. Re-enable button
                btn.prop('disabled', false).text('Save All');
            });
    });

    // Email availability check Feb 18 2026 Mon
    let emailCheckTimer;  
    let currentRequest = null;  

    $('#txtEmailAddress').on('keyup', function() {
        const email = $(this).val().trim();
        const errorSpan = $('.email_error');
        const inputField = $(this);

        // Reset state agad
        errorSpan.text("").removeClass('text-danger text-success');
        inputField.removeClass('is-invalid is-valid');

        clearTimeout(emailCheckTimer);

        if (email === "") return;

        // 1. FORMAT CHECK (Regex)
        if (!validateEmail(email)) {
            errorSpan.text("Please enter a valid email format (e.g. name@domain.com)").addClass('text-danger');
            inputField.addClass('is-invalid');
            return; // STOP! Huwag nang mag-AJAX kung mali ang format.
        }

        // 2. ATOMIC AJAX CHECK (Dito lang pupunta kung valid ang format)
        emailCheckTimer = setTimeout(function() {
            if (currentRequest != null) currentRequest.abort();

            currentRequest = $.ajax({
                url: '/registerCtrl/checkEmailAvailability',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { email: email },
                dataType: 'json',
                beforeSend: function() {
                    errorSpan.text("Checking availability...").addClass('text-muted');
                },
                success: function(response) {
                    if (response.exists) {
                        errorSpan.text("This email is already taken.").removeClass('text-muted').addClass('text-danger');
                        inputField.addClass('is-invalid');
                    } else {
                        errorSpan.text("Email is available!").removeClass('text-muted').addClass('text-success');
                        inputField.addClass('is-valid');
                    }
                }
            });
        }, 500);
    });

    // Helper function para sa email pattern
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
 

});

