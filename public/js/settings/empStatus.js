// JMC
$(document).ready(function() {

    var formaction = 0;
    var updateID = '';
    get_empStatus();

    $(document).on("click", "#btnEmployeeStatus", function (e) {
        formaction = 1;
        $('#frmEmployeeStatus')[0].reset();
    });

    $(document).on("click", "#btnUpdateEmployeeStatus", function (e) {

        formaction = 2;
        $(".lblActionDesc").html("Update Employee Status");
        var id = $(this).val();
        updateID = id;

        axios.get('/getEmployeeStatusData',{
            params: {
                id: id
              }
          })
        .then(function(response){
             var resultData = response.data.data;
             var status = response.data.status;

             if(status == 1){

                $(resultData).each(function(index, item){
                    $('#txtEmployeeStatus').val(item.empStatName);
                });
                $('#btnSaveEmployeeStatus').val(id);

             }

        })
        .catch(function(error){
            dialog.alert({
                message: error
            })
        })
        .then(function(response){})
    });

    $(document).on("click", "#btnSaveEmployeeStatus", function (e) {

        var datas = $('#frmEmployeeStatus');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formaction);
        formData.append('updateID', updateID);

        axios.post('/classification/create_updateEmpStat',formData)
        .then(function(response){
            var status = response.data.status;

            if(status == 201)
            {
                $.each(response.data.error, function(prefix, val) {
                    $('input[name='+ prefix +']').addClass(" border border-danger") ;
                    $('span.' + prefix + '_error').text(val[0]);
                });
            }
            if(status == 200)
            {
                get_empStatus();
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');
                $('#frmEmployeeStatus')[0].reset();
                dialog.alert({
                    message: response.data.msg
                });
            }
            if(status == 202)
            {
                get_empStatus();
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');
                dialog.alert({
                    message: response.data.msg
                });
            }
        })
        .catch(function(error){
            dialog.alert({
                message: error
            });
        })
        .then(function(response){})

    });

    function get_empStatus(){

        axios.get('/getEmployeeStatus')
        .then(function(response){

            var resultData = response.data.data;
            var status = response.data.status;
            var content;
            var i = 1;

            if(status = 1)
            {
                $(resultData).each(function(index, item){

                    content += "<tr>" +
                        "<td>" + i++ + "</td>" +
                        "<td>" + item.empStatName + "</td>" +
                        "<td>" + "<button type='button' class='btn btn-details btn-sm' value=" + item.id + " data-bs-toggle='modal' data-bs-target='#mdlEmployeeStatus' id='btnUpdateEmployeeStatus'> <i class='fa-solid fa-pencil'></i> </button> "
                    content += "</tr>";

                })
                $("#tblEmployeeStatus").empty().append(content);
            }
            else
            {
                dialog.alert({
                    message: "Something went wrong!"
                });
            }
        })
        .catch(function(error){
            dialog.alert({
                message: error
            });
        })
        .then(function(response){})

    }

})
