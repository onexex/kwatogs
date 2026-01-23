$(document).ready(function() {
    var formAction=0;
    var wtID=0;
    wt_get();

    function wt_get(){
        axios.get('/wt/get')
        .then(function (response) {
            var htmlData='';
            var i=1;
            $(response.data.data).each(function(index, row) {
                htmlData += "<tr> <td>" + i++ + "</td>" +
                    "<td>" + row.wt_timefrom +  "</td>" +
                    "<td>" + row.wt_timeto +  "</td>" ;
                if(row.wt_timecross==0){
                    htmlData +="<td> No </td>" ;
                }else{
                    htmlData +="<td> Yes </td>" ;
                }
                htmlData +="<td>" +  '<button type="button" value='+ row.id +' class="btn btn-details btn-sm" data-toggle="tooltip" data-placement="bottom"  id="btnUpdateTime" title=" Schedule" data-bs-toggle="modal" data-bs-target="#mdlTime" > <i class="fa fa-pencil"></i> </button>'  + "</td>" ;
                htmlData += "</tr>";
            })
            $("#tblTime").empty().append(htmlData);

        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});
    }

    //create Function
    $(document).on('click', '#btnCreateTime', function(e) {
        formAction=1;
        $('.lblActionDesc').text('Creating Worktime');
        $('#frmCreateTime')[0].reset();
    });

    //edit Function
    $(document).on('click', '#btnUpdateTime', function(e) {
        formAction=2;
        wtID=$(this).val();
        $('.lblActionDesc').text('Updating Worktime');
        axios.get('/wt/wt_edit',{
            params: {
                wtID: wtID
              }
          })
        .then(function (response) {
            $(response.data.data).each(function(index, row) {
                $('#txtFromTime').val(row.wt_timefrom);
                $('#txtTimeTo').val(row.wt_timeto);
                $('#selTimeCross').val(row.wt_timecross);

            })
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});

    });

    $(document).on('click', '#btnAction', function(e) {
        var datas = $('#frmCreateTime');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formAction);
        formData.append('wtID', wtID);
        //create script
            axios.post('/wt/create_update',formData)
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
                    $('#frmCreateTime')[0].reset();
                    wt_get();
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
