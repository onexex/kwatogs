$(document).ready(function() {
    var formAction=0;
    var relID=0;
    joblevel_get();
    function joblevel_get(){
        axios.get('/relationship/getall')
        .then(function (response) {
            var htmlData='';
            $(response.data.data).each(function(index, row) {
                htmlData += "<tr> "+
                            "<td class='text-lowercase'>" + row.relation +  "</td>" ;

                htmlData +="<td >" +  '<button type="button" value='+ row.id +' class="btn btn-details" data-toggle="tooltip" data-placement="bottom"  id="btnUpdateRelation" data-bs-toggle="modal" data-bs-target="#mdlRelation" > <i class="fa fa-pencil"></i> </button></td>' ;
                htmlData += "</tr>";
            })
            $("#tblRelationship").empty().append(htmlData);

        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});
    }

    //create Function
    $(document).on('click', '#btnCreateRelation', function(e) {
        formAction=1;
        $('.lblActionDesc').text('Creating Relation');
        $('span.error-text').text("");
        $('input.border').removeClass('border border-danger');
        $('#frmRelation')[0].reset();
    });

    //edit Function
    $(document).on('click', '#btnUpdateRelation', function(e) {

        formAction=2;
        relID=$(this).val();
        $('.lblActionDesc').text('Updating Relation');
        axios.get('/relationship/edit',{
            params: {
                relID: relID
              }
          })
        .then(function (response) {
            $(response.data.data).each(function(index, row) {
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');
                $('#txtRelationship').val(row.relation);

            })
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});

    });

    $(document).on('click', '#btnRelationSave', function(e) {
        var datas = $('#frmRelation');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formAction);
        formData.append('relID', relID);
        //create script
            axios.post('/relationship/create_update',formData)
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
                    $('#frmRelation')[0].reset();
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
