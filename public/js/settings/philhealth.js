// JMC
$(document).ready(function() {

    var formaction = 0;
    var updateID = '';
    get_all();

    $(document).on("click", "#btnAddPhilhealth", function (e) {
        formaction = 1;
        $('#frmPhilhealth')[0].reset();
    });

    $(document).on("click", "#btnUpdatePhilhealth", function (e) {

        formaction = 2;
        $(".lblActionDesc").html("Update Philhealth Contribution");
        var id = $(this).val();
        updateID = id;

        axios.get('/updatePhilhealth',{
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
                    $('#numPHSB').val(item.phsb);
                    $('#numFrom').val(item.salaryFrom);
                    $('#numTo').val(item.salaryTo);
                    $('#numPHEE').val(item.phee);
                    $('#numPHER').val(item.pher);
                });
                $('#btnSavePhilhealth').val(id);

             }

        })
        .catch(function(error){
            dialog.alert({
                message: error
            })
        })
        .then(function(response){})
    });

    $(document).on("click", "#btnSavePhilhealth", function (e) {

        var datas = $('#frmPhilhealth');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formaction);
        formData.append('updateID', updateID);

        axios.post('/settings/Philhealth',formData)
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
                $('#frmPhilhealth')[0].reset();
                dialog.alert({
                    message: response.data.msg
                });
            }
            if(status == 202)
            {
                get_all();
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

        axios.get('/getPhilhealth')
        .then(function(response){

            var resultData = response.data.data;
            var status = response.data.status;
            var content;
            var i = 1;

            if(status = 200)
            {
                $(resultData).each(function(index, item){

                    content += "<tr>" +
                        "<td>" + item.phsb + "</td>" +
                        "<td>" + item.salaryFrom + "</td>" +
                        "<td>" + item.salaryTo + "</td>" +
                        "<td>" + item.phee + "</td>" +
                        "<td>" + item.pher + "</td>" +
                        "<td>" + "<button type='button' class='btn btn-details' value=" + item.id + " data-bs-toggle='modal' data-bs-target='#mdlPhilhealth' id='btnUpdatePhilhealth'> <i class='fa-solid fa-pencil'></i> </button> "
                    content += "</tr>";

                })
                $("#tblPhilhealth").empty().append(content);
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
