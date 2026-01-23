$(document).ready(function() {
    var formAction = 0;
    var obID = 0;
    getall();

    //get
    function getall(){
        axios.get('/ob/getall')
        .then(function (response) {
            var htmlData='';
            // var i=1;
            $(response.data.data).each(function(index, row) {
                htmlData += "<tr>";
                if(row.ob_fBefore==1){
                    htmlData +="<td> Yes </td>" ;
                }else{
                    htmlData +="<td> No </td>" ;
                }
                htmlData +="<td>" + row.ob_dBefore + "</td>" ;

                if(row.ob_fAfter==1){
                    htmlData +="<td> Yes </td>" ;
                }else{
                    htmlData +="<td> No </td>" ;
                }
                htmlData +="<td>" + row.ob_dAfter + "</td>" ;
                htmlData +="<td class=''>" +  '<button type="button" value='+ row.id +' class="btn btn-danger" data-toggle="tooltip" data-placement="bottom"  id="btnUpdateMdl" title=" Schedule" data-bs-toggle="modal" data-bs-target="#mdlOBVal" > <i class="fa fa-pencil"></i> </button>'  + "</td>" ;
                htmlData += "</tr>";
            })
            $("#tblOBVal").empty().append(htmlData);
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});
    }

     //CREATE MODAL//
     $(document).on('click', '#btnCreateMdl', function(e) {
        formAction = 1;
            $("#btnOBVal").text("Save Entries")
            $('#frmOBVal')[0].reset();
            $('span.error-text').text("");
            $('input.border').removeClass('border border-danger');
    })

    //edit Function
    $(document).on('click', '#btnUpdateMdl', function(e) {
        formAction = 2;
        obID=$(this).val();
        // $('span.error-text').text("");
        // $('input.border').removeClass('border border-danger');

        $("#btnOBVal").text("Update")
        axios.get('/ob/edit',{
            params: {
                obID: obID
                }
            })
        .then(function (response) {
            $(response.data.data).each(function(index, row) {
                $('#selFileBefore').val(row.ob_fBefore);
                $('#txtDaysBefore').val(row.ob_dBefore);
                $('#selFileAfter').val(row.ob_fAfter);
                $('#txtDaysAfter').val(row.ob_dAfter);
            })
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});
    });

     //create_update
     $(document).on('click', '#btnOBVal', function() {
        var datas = $('#frmOBVal');
        var formData = new FormData($(datas)[0]);
        formData.append('obID', obID);
        formData.append('formAction', formAction);

        axios.post('/ob/create_update', formData)
            .then(function(response) {

                // var status=response.data.status;
                // var errorData = response.data.error;

                if(response.data.status==201){
                    $.each(response.data.error, function(prefix, val) {
                        // $('span.' + prefix + '_error').text(val[0]);
                        //  $('span.' + prefix + '_error').text("This field is required!");
                        //  $('#' + prefix ).addClass('border border-danger');

                         $('input[name='+ prefix +']').addClass(" border border-danger") ;
                         $('span.' + prefix + '_error').text(val[0]);
                    });
                    dialog.alert({
                        // title: "Message",
                        message: "Some fields are required!",
                        animation: "slide"
                    });
                }else if(response.data.status==200){
                    $('span.error-text').text("");
                    $('select.border').removeClass('border border-danger');
                    $('input.border').removeClass('border border-danger');
                    $("#frmOBVal")[0].reset()
                    getall();
                    // $('#frmOBVal').modal('toggle');

                    dialog.alert({
                        message: response.data.msg,
                        animation: "slide"
                    })
                }else if(response.data.status==202){
                    $('span.error-text').text("");
                    $('select.border').removeClass('border border-danger');
                    $('input.border').removeClass('border border-danger');

                    dialog.alert({
                        message: response.data.msg,
                        animation: "slide"
                    });
                }
            })
            .catch(function(error) {
                dialog.alert({
                    message: error
                });
            })
            .then(function() {});
    })


})



