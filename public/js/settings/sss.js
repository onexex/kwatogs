// JMC
$(document).ready(function() {

    var formaction = 0;
    var updateID = '';
    get_all();

    $(document).on("click", "#btnCreateSSS", function (e) {
        formaction = 1;
        $('#frmSSS')[0].reset();
    });

    $(document).on("click", "#btnUpdateSSS", function (e) {

        formaction = 2;
        $(".lblActionDesc").html("Update SSS Contribution");
        var id = $(this).val();
        updateID = id;

        axios.get('/updateSSS',{
            params: {
                id: id,
                updateID: updateID
              }
          })
        .then(function(response){
             var resultData = response.data.data;
             var status = response.data.status;

             if(status == 200){

                $(resultData).each(function(index, item){
                    $('#txtSSSC').val(item.sssc);
                    $('#txtSalaryFrom').val(item.from);
                    $('#txtSalaryTo').val(item.to);
                    $('#txtSSER').val(item.sser);
                    $('#txtSSEE').val(item.ssee);
                    $('#txtSSEC').val(item.ssec);
                });
                $('#btnSaveSSS').val(id);

             }

        })
        .catch(function(error){
            dialog.alert({
                message: error
            })
        })
        .then(function(response){})
    });

    $(document).on("click", "#btnSaveSSS", function (e) {

        var datas = $('#frmSSS');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formaction);
        formData.append('updateID', updateID);

        axios.post('/settings/SSS',formData)
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
                get_all();
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');
                $('#frmSSS')[0].reset();
                dialog.alert({
                    message: response.data.msg
                });
            }
            if(status == 202)
            {
                // get_empStatus();
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

    function get_all(){

        axios.get('/getSSS')
        .then(function(response){

            var resultData = response.data.data;
            var status = response.data.status;
            var content;
            var i = 1;

            if(status = 200)
            {
                $(resultData).each(function(index, item){

                    content += "<tr>" +
                        "<td>" + item.sssc + "</td>" +
                        "<td>" + item.from + "</td>" +
                        "<td>" + item.to + "</td>" +
                        "<td>" + item.sser + "</td>" +
                        "<td>" + item.ssee + "</td>" +
                        "<td>" + item.ssec + "</td>" +
                        "<td>" + "<button type='button' class='btn btn-details' value=" + item.id + " data-bs-toggle='modal' data-bs-target='#mdlSSS' id='btnUpdateSSS'> <i class='fa-solid fa-pencil'></i> </button> "
                    content += "</tr>";

                })
                $("#tblSSS").empty().append(content);
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
