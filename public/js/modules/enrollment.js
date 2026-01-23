$(document).ready(function() {
     //employee number fetch
     empNumberGenerate();
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
 
   $(document).on('click', '#btnSaveAll', function(e) {
        var datas = $('#frmEnrolment');
        var city=$("#txtCity option:selected" ).text();
        var brgy=$("#txtBrgy option:selected" ).text();
        var prov=$("#txtProvince option:selected" ).text();
        var formData = new FormData($(datas)[0]);
        formData.append('citydesc', city);
        formData.append('brgydesc', brgy);
        formData.append('provdesc', prov);

        //create script
            axios.post('/enroll/save',formData)  
            .then(function (response) {

                // console.log(response);
                // return false;
                //error response
                if (response.data.status == 201) {
                    $.each(response.data.error, function(prefix, val) {
                        $('input[name='+ prefix +']').addClass(" border border-danger") ;
                        $('span.' + prefix + '_error').text(val[0]);
                    });
                }
                //success respose
                if(response.data.status == 200){
                    $('span.error-text').text("");
                    $('input.border').removeClass('border border-danger');
                    $('#frmEnrolment')[0].reset();
                    empNumberGenerate();
                    dialog.alert({
                        message: response.data.msg
                    });
                }
                 //success respose
                 if(response.data.status == 202){
                    $('span.error-text').text("");
                    $('input.border').removeClass('border border-danger');
                    dialog.alert({
                        message: response.data.msg
                    });
                }

                if(response.data.status == 203){
                   
                    dialog.alert({
                        message: response.data.msg
                    });
                }
            })
            .catch(function (error) {
                dialog.alert({
                    message: error
                });  
            })
            .then(function () {});   
       
    });

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
});

