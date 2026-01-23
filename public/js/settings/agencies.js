$(document).ready(function() {
    var formAction = "0";
    var agID= "0";

    //Modal //
    $(document).on('click', '#btnSaveAgency', function(e) {
        var datas = $('#frmAgencies');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formAction);
        formData.append('agID', agID);
        //create script

            axios.post('/agency/create_update',formData)
            .then(function (response) {
                //error response validtor
                if (response.data.status == 201) {
                    $.each(response.data.error, function(prefix, val) {
                        $('input[name='+ prefix +']').addClass(" border border-danger") ;
                        $('span.' + prefix + '_error').text("This field is required!");

                        // $('span.' + prefix + '_error').text(val[0]);
                    });
                }

                //success respose
                if(response.data.status == 200){
                    $('span.error-text').text("");
                    $('input.border').removeClass('border border-danger');
                    $('#frmAgencies')[0].reset();
                    getall();
                    dialog.alert({
                        message: response.data.msg
                    });

                    agID="0";
                }
                 //error
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

    //Create//
     $(document).on('click', '#btnAddAgency', function(e) {
        formAction = 1;
            $("#lblTitleMdl").text("Agency")
            $("#btnSaveAgency").text("Save Entries")
            $('#frmAgencies')[0].reset();
            $('span.error-text').text("");
            $('input.border').removeClass('border border-danger');
    })


    //get
    getall();
    function getall(){
        axios.get('/agency/getall')
        .then(function (response) {
            var htmlData='';
            var i=1;
            $(response.data.data).each(function(index, row) {
                htmlData += "<tr>"+

                "<td>" + i++ + "</td>" +
                "<td>" + row.ag_name + "</td>" ;
                // "<td>" + row.ag_status + "</td>" ;
                if(row.ag_status==1){
                    htmlData +="<td> Activate </td>" ;
                }else{
                    htmlData +="<td> Not Activate </td>" ;
                }
                htmlData +="<td class=''>" +  '<button type="button" value='+ row.id +' class="btn btn-details btn-sm" data-toggle="tooltip" data-placement="bottom"  id="btnUpdateAgency" title=" Schedule" data-bs-toggle="modal" data-bs-target="#mdlAgency" > <i class="fa fa-pencil"></i> </button>'  + "</td>" ;
                htmlData += "</tr>";
            })
            $("#tblAgencies").empty().append(htmlData);

        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});
    }

    //edit Function
    $(document).on('click', '#btnUpdateAgency', function(e) {
        formAction=2;
        agID=$(this).val();

        $('span.error-text').text("");
        $('input.border').removeClass('border border-danger');

        $('#lblTitleMdl').text('Updating Agency');
        $("#btnSaveAgency").text("Update")

        axios.get('/agency/edit',{
            params: {
                agID: agID
                }
            })
        .then(function (response) {
            $(response.data.data).each(function(index, row) {
                $('#txtAgencyName').val(row.ag_name);
                $('#selStatusAgency').val(row.ag_status);
            })
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});

    });

})

