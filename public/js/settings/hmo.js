// JMC
$(document).ready(function() {

    var formaction = 0;
    var updateID = '';
    get_hmo();

    $(document).on("click", "#btnAddHMO", function (e) {
        formaction = 1;
        $('#frmHMO')[0].reset();
    });

    $(document).on("click", "#btnUpdateHMO", function (e) {

        formaction = 2;
        $(".lblActionDesc").html("Update HMO");
        var id = $(this).val();
        updateID = id;

        axios.get('/getData',{
            params: {
                id: id
              }
          })
        .then(function(response){
             var resultData = response.data.data;
             var status = response.data.status;

             if(status == 1){

                $(resultData).each(function(index, item){
                    $('#txtID').val(item.idNo);
                    $('#txtName').val(item.hmoName);
                });

                $('#btnSaveHMO').val(id);

             }

        })
        .catch(function(error){
            dialog.alert({
                message: error
            })
        })
        .then(function(response){})
    });

    $(document).on("click", "#btnSaveHMO", function (e) {

        var datas = $('#frmHMO');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formaction);
        formData.append('updateID', updateID);

        axios.post('/classification/create_updateHMO',formData)
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
                get_hmo();
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');
                $('#frmHMO')[0].reset();
                dialog.alert({
                    message: response.data.msg
                });
            }
            if(status == 202)
            {
                get_hmo();
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

    function get_hmo(){

        axios.get('/getHMO')
        .then(function(response){

            var resultData = response.data.data;
            var status = response.data.status;
            var content;

            if(status = 1)
            {
                $(resultData).each(function(index, item){

                    content += "<tr>" +
                        "<td>" + item.idNo + "</td>" +
                        "<td>" + item.hmoName + "</td>" +
                        "<td>" + "<button type='button' class='btn btn-details' value=" + item.id + " data-bs-toggle='modal' data-bs-target='#mdlHMO' id='btnUpdateHMO'> <i class='fa-solid fa-pencil'></i> </button> "
                    content += "</tr>";

                })
                $("#tblHMO").empty().append(content);
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
