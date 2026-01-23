$(document).ready(function() {
    var formAction=0;
    var userID=0;

    //edit Function
    $(document).on('click', '#btnUpdateUserRole', function(e) {

        formAction=2;
        userID=$(this).val();
        $('.lblActionDesc').text('Updating Roles');
        axios.get('/roles/edit',{
            params: {
                userID: userID
              }
          })
        .then(function (response) {
            $(response.data.data).each(function(index, row) {
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');
                $('#txtUserRole').val(row.role);
            })
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});

    });

    $(document).on('click', '#btnSaveUserRole', function(e) {
        var datas = $('#frmUserRole');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formAction);
        formData.append('userID', userID);
        //create script
            axios.post('/roles/create_update',formData)
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
                    $('#frmUserRole')[0].reset();
                    $("#txtSearchStr").trigger('keyup');

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

    //search
    $(document).on('keyup', '#txtSearchStr', function(e) {
        var htmlData='';
        if($(this).val()===""){
             $("#tblUserRole").empty();
            return;
        }
        var datas = $('#frmSearch');
        var formData = new FormData($(datas)[0]);
            axios.post('/roles/search',formData)
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

                    $(response.data.data).each(function(index, row) {
                        htmlData += "<tr>" +
                            "<td class='text-lowercase'>" + row.fname + " " + row.lname +  "</td>" ;
                            if(row.role==1){
                                htmlData +="<td  >Superuser</td>" ;
                            }else if(row.role==2){
                                htmlData +="<td  >Admin</td>" ;

                            }else{
                                htmlData +="<td  >User</td>" ;

                            }

                        htmlData +="<td >" +  '<button type="button" value='+ row.id +' class="btn btn-details" data-toggle="tooltip" data-placement="bottom"  id="btnUpdateUserRole" data-bs-toggle="modal" data-bs-target="#mdlUserRole" > <i class="fa fa-pencil"></i> </button></td>' ;
                        htmlData += "</tr>";
                    })
                    $("#tblUserRole").empty().append(htmlData);

                }

                if(response.data.status == 199){
                    $('span.error-text').text("");
                    $('input.border').removeClass('border border-danger');
                        htmlData += "<tr>" +
                                    "<td class='text-lowercase'> Result not found</td>" +
                                    "<td class='text-lowercase'> </td>" ;
                         htmlData += "</tr>";

                    $("#tblUserRole").empty().append(htmlData);

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
