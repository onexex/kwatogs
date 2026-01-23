$(document).ready(function() {

    var formAction=0;
    var jobID=0;
    joblevel_get();
    function joblevel_get(){
        axios.get('/joblevel/get_joblevel')
        .then(function (response) {
            var htmlData='';
            $(response.data.data).each(function(index, row) {
                htmlData += "<tr><td class='text-lowercase'>" + row.id  +  "</td>" +
                            "<td class='text-lowercase'>" + row.job_desc +  "</td>" ;

                htmlData +="<td >" +  '<button type="button" value='+ row.id +' class="btn btn-details" data-toggle="tooltip" data-placement="bottom"  id="btnUpdateJobLevel" data-bs-toggle="modal" data-bs-target="#mdlJobLevel" > <i class="fa fa-pencil"></i> </button></td>' ;
                htmlData += "</tr>";
            })
            $("#tblJobLevels").empty().append(htmlData);

        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});
    }

    //create Function
    $(document).on('click', '#btnAddJobLevel', function(e) {
        formAction=1;
        $('.lblActionDesc').text('Creating Job level');
        $('span.error-text').text("");
        $('input.border').removeClass('border border-danger');
        $('#frmJobLevels')[0].reset();
    });

    //edit Function
    $(document).on('click', '#btnUpdateJobLevel', function(e) {


        formAction=2;
        jobID=$(this).val();
        $('.lblActionDesc').text('Updating Job level');
        axios.get('/joblevel/edit',{
            params: {
                jobID: jobID
              }
          })
        .then(function (response) {
            $(response.data.data).each(function(index, row) {
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');
                $('#txtJobLevel').val(row.job_desc);

            })
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});

    });

    $(document).on('click', '#btnSaveJobLevel', function(e) {
        var datas = $('#frmJobLevels');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formAction);
        formData.append('jobID', jobID);
        //create script
            axios.post('/joblevel/create_update',formData)
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
                    $('#frmJobLevels')[0].reset();
                    joblevel_get();
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

});
