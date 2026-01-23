$(document).ready(function() {
    var formAction =0;
    var liloID= 0;
    getall();

    //get
    function getall(){
        axios.get('/lilo/getall')
        .then(function (response) {
            var htmlData='';
            // var i=1;
            $(response.data.data).each(function(index, row) {
                htmlData += "<tr>"+
                "<td>" + row.lilo_gracePrd + "</td>" +
                "<td>" + row.managersOverride + "</td>" +
                "<td>" + row.managersTime + "</td>" ;
                htmlData +="<td class=''>" +  '<button type="button" value='+ row.id +' class="btn btn-details" data-toggle="tooltip" data-placement="bottom"  id="btnUpdateMdl" title=" Schedule" data-bs-toggle="modal" data-bs-target="#mdlLiloVal" > <i class="fa fa-pencil"></i> </button>' + "</td>" ;
                htmlData += "</tr>";
            })
            $("#tblLiloVal").empty().append(htmlData);
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});
    }

    //Create MODAL//
    $(document).on('click', '#btnCreateMdl', function(e) {
        formAction = 1;
            $("#btnLiloVal").text("Save Entries")
            $('#frmLiloVal')[0].reset();
            $('span.error-text').text("");
            $('input.border').removeClass('border border-danger');

    })

     //edit Function
     $(document).on('click', '#btnUpdateMdl', function(e) {
        formAction=2;
        liloID=$(this).val();
        // $('span.error-text').text("");
        // $('input.border').removeClass('border border-danger');

        $("#btnLiloVal").text("Update")
        axios.get('/lilo/edit',{
            params: {
                liloID: liloID
                }
            })
        .then(function (response) {
            $(response.data.data).each(function(index, row) {
                $('#txtGracePeriod').val(row.lilo_gracePrd);
                $('#txtMngrOverride').val(row.managersOverride);
                $('#txtMngrTime').val(row.managersTime);
                $('#no_logout_deduction').val(row.no_logout_has_deduction);
                $('#minute_deduction').val(row.minute_deduction);
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
     $(document).on('click', '#btnLiloVal', function(e) {
        var datas = $('#frmLiloVal');
        var formData = new FormData($(datas)[0]);
        formData.append('liloID', liloID);
        formData.append('formAction', formAction);

        axios.post('/lilo/create_update', formData)
            .then(function(response) {

                if(response.data.status == 201){
                    $.each(response.data.error, function(prefix, val) {
                        // $('span.' + prefix + '_error').text("This field is required!");
                        // $('#' + prefix ).addClass('border border-danger');

                        $('input[name='+ prefix +']').addClass(" border border-danger") ;
                        $('span.' + prefix + '_error').text(val[0]);

                    });
                    dialog.alert({
                        message: "Some fields are required!",
                        animation: "slide"
                    });

                }else if(response.data.status== 200){
                    $('span.error-text').text("");
                    $('input.border').removeClass('border border-danger');
                    $('#frmLiloVal')[0].reset();
                    // $('#frmLiloVal').modal('toggle');
                    getall();

                    dialog.alert({
                        message: response.data.msg,
                    })
                    window.location.reload()
                }else if(response.data.status == 202){
                    $('span.error-text').text("");
                    $('input.border').removeClass('border border-danger');
                    dialog.alert({
                        message: response.data.msg,
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

    // //UPDATE MODAL//
    // $(document).on('click', '#btnUpdateMdl', function(e) {
    //         // $('#frmLiloVal')[0].reset();
    //         $('span.error-text').text("");
    //         $('input.border').removeClass('border border-danger');
    // })

})



