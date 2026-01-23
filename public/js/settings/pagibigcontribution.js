// JMC
$(document).ready(function() {

    var formaction = 0;
    var updateID = '';
    get_all();

    $(document).on("click", "#btnCreatePagibig", function (e) {
        formaction = 1;
        $('#frmPagibig')[0].reset();
    });

    $(document).on("click", "#btnUpdatePagibig", function (e) {

        formaction = 2;
        $(".lblActionDesc").html("Update PAG-IBIG Contribution");
        var id = $(this).val();
        updateID = id;

        axios.get('/updatePagibig',{
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
                    $('#txtEE').val(item.EE);
                    $('#txtER').val(item.ER);
                });
                $('#btnPagibigModal').val(id);

             }

        })
        .catch(function(error){
            dialog.alert({
                message: error
            })
        })
        .then(function(response){})
    });

    $(document).on("click", "#btnPagibigModal", function (e) {

        var datas = $('#frmPagibig');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formaction);
        formData.append('updateID', updateID);

        axios.post('/settings/Pagibig',formData)
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
                $('#frmPagibig')[0].reset();
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

        axios.get('/getPagibig')
        .then(function(response){

            var resultData = response.data.data;
            var status = response.data.status;
            var content;
            var i = 1;

            if(status = 200)
            {
                $(resultData).each(function(index, item){

                    content += "<tr>" +
                        "<td>" + item.EE + "</td>" +
                        "<td>" + item.ER + "</td>" +
                        "<td>" + "<button type='button' class='btn btn-details' value=" + item.id + " data-bs-toggle='modal' data-bs-target='#mdlPagibig' id='btnUpdatePagibig'> <i class='fa-solid fa-pencil'></i> </button> "
                    content += "</tr>";

                })
                $("#tblPagibig").empty().append(content);
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
