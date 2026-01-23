$(document).ready(function() {
    var formAction=0;
    var archiveID = 0;


    //search
    $(document).on('keyup', '#txtSearchEmp', function(e) {
        var htmlData='';
        if($(this).val()===""){
             $("#tblEmployee").empty();
            return;
        }
        var datas = $('#frmSearch');
        var formData = new FormData($(datas)[0]);
            axios.post('/archive/search',formData)
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
                        htmlData += "<tr>"+
                        "<td>" + row.lname + " " + row.fname + "</td>" +
                        "<td>" + row.pos_desc + "</td>" +
                        "<td>" + row.empStatus + "</td>" ;
                        htmlData +="<td class=''>" +  '<button type="button" value='+ row.ids +' class="btn btn-details" data-toggle="tooltip" data-placement="bottom"  id="btnUpdateMdl" title="Employee Update" data-bs-toggle="modal" data-bs-target="#mdlRegEmployee" > <i class="fa fa-pencil"></i> </button>' + "</td>" ;
                        htmlData += "</tr>";
                    })
                    $("#tblEmployee").empty().append(htmlData);

                }

                if(response.data.status == 199){
                    $('span.error-text').text("");
                    $('input.border').removeClass('border border-danger');
                        htmlData += "<tr>" +
                                    "<td class='text-lowercase'> Result not found</td>" +
                                    "<td class='text-lowercase'> </td>" ;
                         htmlData += "</tr>";

                    $("#tblEmployee").empty().append(htmlData);

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

    // function getall(){
    //     axios.get('/archive/getall')
    //     .then(function (response) {
    //         var htmlData='';
    //         // var i=1;
    //         $(response.data.data).each(function(index, row) {
    //             htmlData += "<tr>"+
    //             "<td>" + row.lname + " " + row.fname + "</td>" +
    //             "<td>" + row.pos + "</td>" +
    //             "<td>" + row.empStatus + "</td>" ;
    //             htmlData +="<td class=''>" +  '<button type="button" value='+ row.id +' class="btn btn-secondary" data-toggle="tooltip" data-placement="bottom"  id="btnUpdateMdl" title="Employee Update" data-bs-toggle="modal" data-bs-target="#mdlRegEmployee" > <i class="fa fa-pencil"></i> </button>' + "</td>" ;
    //             htmlData += "</tr>";
    //         })
    //         $("#tblEmployee").empty().append(htmlData);
    //     })
    //     .catch(function (error) {
    //         dialog.alert({
    //             message: error
    //         });
    //     })
    //     .then(function () {});
    // }

     //CREATE MODAL//
     $(document).on('click', '#btnRegEmployee', function(e) {
        formAction = 1;
            $("#btnSaveEmployee").text("Save Entries")
            $('#frmRegEmp')[0].reset();
            $('span.error-text').text("");
            $('input.border').removeClass('border border-danger');
    })

    //edit Function
    $(document).on('click', '#btnUpdateMdl', function(e) {
        formAction = 2;
        archiveID=$(this).val();

        $("#btnSaveEmployee").text("Update")
        axios.get('/archive/edit',{
            params: {
                archiveID: archiveID
                }
            })
        .then(function (response) {
            $(response.data.data).each(function(index, row) {
                $('#txtFname').val(row.fname);
                $('#txtLname').val(row.lname);
                $('#selPosition').val(row.pos);
                $('#txtDateFrom').val(row.empDatesFrom);
                $('#txtDateTo').val(row.empDatesTo);
                $('#selStatus').val(row.empStatus);
                $('#selClearance').val(row.clearance);
                $('#txtReason').val(row.reasonForLeaving);
                $('#txtDerogatory').val(row.derogatoryRecords);
                $('#txtSalary').val(row.salary);
                $('#txtResignation').val(row.pendingResign);
                $('#txtRemarks').val(row.addRemarks);
                $('#txtVerify').val(row.verifiedBy);
                $("#btnUpdateMdl").val(row.archiveID);

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
    $(document).on('click', '#btnSaveEmployee', function() {
        var datas = $('#frmRegEmp');
        var formData = new FormData($(datas)[0]);
        var posdesc = $("#selPosition option:selected").text();

        formData.append('archiveID', archiveID);
        formData.append('formAction', formAction);
        formData.append('selectedPos', posdesc);

        axios.post('/archive/create_update', formData)
            .then(function(response) {

                if(response.data.status==201){
                    $.each(response.data.error, function(prefix, val) {
                        $('span.' + prefix + '_error').text("This field is required!");
                        $('#' + prefix ).addClass('border border-danger');
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

                    // $("#txtSearchEmp").trigger('keyup');
                    $("#frmRegEmp")[0].reset()
                    // getall();

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
});
