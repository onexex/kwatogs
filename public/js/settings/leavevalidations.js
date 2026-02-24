$(document).ready(function() {
    var formAction=0;
    var leaveValID=0;
     leavetval_get();
    function leavetval_get(){
        axios.get('/leaveval/getall')  
        .then(function (response) {
            var htmlData='';
            $(response.data.data).each(function(index, row) {
                htmlData += "<tr>" +
                            "<td class='text-lowercase'>" + row.comp_name +  "</td>" +
                            "<td class='text-lowercase'>" + row.type_leave +  "</td>" +
                            "<td class='text-lowercase'>" + row.credits +  "</td>" +
                            "<td class='text-lowercase'>" + row.min_leave +  "</td>" +
                            "<td class='text-lowercase'>" + row.no_before_file +  "</td>" +
                            "<td class='text-lowercase'>" + row.no_after_file +  "</td>" ;
                            if(row.file_before ==1){
                                htmlData += "<td class='text-lowercase'>Yes</td>";
                            }else{
                                htmlData += "<td class='text-lowercase'>No</td>";
                            }
                            if(row.file_after ==1){
                                htmlData += "<td class='text-lowercase'>Yes</td>";
                            }else{
                                htmlData += "<td class='text-lowercase'>No</td>";
                            }
                            if(row.file_halfday ==1){
                                htmlData += "<td class='text-lowercase'>Yes</td>";
                            }else{
                                htmlData += "<td class='text-lowercase'>No</td>";
                            }
                            htmlData += "<td class='text-lowercase'>" + (row.pre_allocated == 1 ? 'Yes' : 'No') +  "</td>" ;
                            
                htmlData +="<td >" +  '<button type="button" value='+ row.ids +' class="btn btn-danger" data-toggle="tooltip" data-placement="bottom"  id="btnUpdateModal" data-bs-toggle="modal" data-bs-target="#mdlUpdateLeave" > <i class="fa fa-pencil"></i> </button></td>' ;                                                         
                htmlData += "</tr>";  
            })
            $("#tblLeave").empty().append(htmlData);

        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });  
        })
        .then(function () {});  
    }

    //create Function
    $(document).on('click', '#btnCreateLeave', function(e) {
        formAction=1;
        $('.lblActionDesc').text('Creating Leave Validation');
        $('span.error-text').text("");
        $('input.border').removeClass('border border-danger');
        $('#frmCreateleaveValidation')[0].reset();
    });

    //edit Function
    $(document).on('click', '#btnUpdateModal', function(e) {
      
        formAction=2;
        leaveValID=$(this).val();
        $('.lblActionDesc').text('Updating Leave Validation');
        axios.get('/leaveval/edit',{
            params: {
                leaveValID: leaveValID
              }
          })  
        .then(function (response) {
            $(response.data.data).each(function(index, row) {
                console.log(row.pre_allocated)
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');
                $('#selCompanyNameU').val(row.compID);
                $('#selLeaveTypeU').val(row.leave_type);
                $('#txtDaysBefore').val(row.no_before_file);
                $('#selFileBefore').val(row.file_before);
                $('#selHalfday').val(row.file_halfday);
                $('#txtMinLeaveU').val(row.min_leave);
                $('#txtDaysAfter').val(row.no_after_file);
                $('#selFileAfter').val(row.file_after);
                $('#txtCreditsU').val(row.credits);
                $('#selpreAllocated').val(row.pre_allocated);
              
            })
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });  
        })
        .then(function () {});

    });

    $(document).on('click', '#btnUpdateLeave', function(e) {
        var datas = $('#frmCreateleaveValidation');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formAction);
        formData.append('leaveValID', leaveValID);
        //create script
            axios.post('/leaveval/create_update',formData)  
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
                    $('#frmCreateleaveValidation')[0].reset();
                    leavetval_get();
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
