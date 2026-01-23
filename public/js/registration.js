$(document).ready(function() {

    $(document).on('click', '#btnAction', function(e) {
        var datas = $('#frmCreateTime');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formAction);
        formData.append('wtID', wtID);
        //create script
            axios.post('/wt/create_update',formData)
            .then(function (response) {
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
                    // $('#frmCreateTime')[0].reset();
                    wt_get();
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
            })
            .catch(function (error) {
                dialog.alert({
                    message: error
                });
            })
            .then(function () {});
    });

    //employee number fetch
    empNumberGenerate();
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

});
