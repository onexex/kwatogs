$(document).ready(function() {
    var formAction=0;
    var leaveTypeID=0;
    leavetype_get();
    function leavetype_get(){
        axios.get('/leavetype/getall')
        .then(function (response) {
            var htmlData='';
            $(response.data.data).each(function(index, row) {
                htmlData += "<tr>" +
                            "<td class='text-lowercase'>" + row.type_leave +  "</td>" ;

                htmlData +="<td >" +  '<button type="button" value='+ row.id +' class="btn btn-details" data-toggle="tooltip" data-placement="bottom"  id="btnUpdateLeaveType" data-bs-toggle="modal" data-bs-target="#mdlLeaveTypes" > <i class="fa fa-pencil"></i> </button></td>' ;
                htmlData += "</tr>";
            })
            $("#tblLeaveTypes").empty().append(htmlData);

        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});
    }

    //create Function
    $(document).on('click', '#btnLeaveTypes', function(e) {
        formAction=1;
        $('.lblActionDesc').text('Creating Leavetype');
        $('span.error-text').text("");
        $('input.border').removeClass('border border-danger');
        $('#frmLeaveTypes')[0].reset();
    });

    //edit Function
    $(document).on('click', '#btnUpdateLeaveType', function(e) {

        formAction=2;
        leaveTypeID=$(this).val();
        $('.lblActionDesc').text('Updating Leavetype');

        $("#btnSaveLeaveTypes").text("Update")
        axios.get('/leavetype/edit',{
            params: {
                leaveTypeID: leaveTypeID
              }
          })
        .then(function (response) {
            $(response.data.data).each(function(index, row) {
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');
                $('#txtLeaveTypes').val(row.type_leave);

            })
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});

    });

    $(document).on('click', '#btnSaveLeaveTypes', function(e) {
        var datas = $('#frmLeaveTypes');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formAction);
        formData.append('leaveTypeID', leaveTypeID);
        //create script
            axios.post('/leavetype/create_update',formData)
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
                    $('#frmLeaveTypes')[0].reset();
                    leavetype_get();
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
