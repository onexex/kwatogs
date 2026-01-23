$(document).ready(function() {
    var formAction=0;
    var OTFilID=0;
    otMaintenance_get();
    function otMaintenance_get(){
        axios.get('/otfilling/getall')  
        .then(function (response) {
            var htmlData='';
            $(response.data.data).each(function(index, row) {
                htmlData += "<tr>" +
                            "<td class='text-lowercase'>" + row.comp_name +  "</td>" ;
                            if(row.filebefore==1){
                                htmlData += "<td class='text-lowercase'>Yes</td>" ;
                            }else{
                                htmlData += "<td class='text-lowercase'>No</td>" ;
                            }
                            
                            if(row.fileafter==1){
                                htmlData += "<td class='text-lowercase'>Yes</td>" ;
                            }else{
                                htmlData += "<td class='text-lowercase'>No</td>" ;
                            }
                            htmlData += "<td class='text-lowercase'>" + row.no_days_before +  "</td>" +
                            "<td class='text-lowercase'>" + row.no_days_after +  "</td>" ;
                            if(row.holiday==1){
                                htmlData += "<td class='text-lowercase'>Yes</td>" ;
                            }else{
                                htmlData += "<td class='text-lowercase'>No</td>" ;
                            }

                            if(row.tardy==1){
                                htmlData += "<td class='text-lowercase'>Yes</td>" ;
                            }else{
                                htmlData += "<td class='text-lowercase'>No</td>" ;
                            }
              
                htmlData +="<td >" +  '<button type="button" value='+ row.ids +' class="btn btn-danger" data-toggle="tooltip" data-placement="bottom"  id="btnOTMaintenance" data-bs-toggle="modal" data-bs-target="#mdlOTFile" > <i class="fa fa-pencil"></i> </button></td>' ;                                                         
                htmlData += "</tr>";  
            })
            $("#tblOTFile").empty().append(htmlData);

        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });  
        })
        .then(function () {});  
    }

    //create Function
    $(document).on('click', '#btnCreateOTMaintinance', function(e) {
        formAction=1;
        $('.lblActionDesc').text('Creating Leavetype');
        $('span.error-text').text("");
        $('input.border').removeClass('border border-danger');
        $('#frmOTFile')[0].reset();
    });

    //edit Function
    $(document).on('click', '#btnOTMaintenance', function(e) {
      
        formAction=2;
        OTFilID=$(this).val();
        $('.lblActionDesc').text('Updating Leavetype');
        axios.get('/otfilling/edit',{
            params: {
                OTFilID: OTFilID
              }
          })  
        .then(function (response) {
            $(response.data.data).each(function(index, row) {
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');
                $('#txtCompany').val(row.comp_id);
                $('#selBefore').val(row.filebefore);
                $('#selAfter').val(row.fileafter);
                $('#selHoliday').val(row.holiday);
                $('#selTardy').val(row.tardy);
                $('#txtDaysBefore').val(row.no_days_before);
                $('#txtDaysAfter').val(row.no_days_after);
            })
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });  
        })
        .then(function () {});

    });

    $(document).on('click', '#btnOTFile', function(e) {
        var datas = $('#frmOTFile');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formAction);
        formData.append('OTFilID', OTFilID);
        //create script
            axios.post('/otfilling/create_update',formData)  
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
                    $('#frmOTFile')[0].reset();
                    otMaintenance_get();
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
