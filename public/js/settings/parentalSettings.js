$(document).ready(function() {
    var formAction = "0";
    var pID= "0";
    getall();

    //get
    function getall(){
        axios.get('/parental/getall')
        .then(function (response) {
            var htmlData='';
            var i=1;
            $(response.data.data).each(function(index, row) {
                htmlData += "<tr> <td>" + i++ + "</td>" +
                "<td  >" + row.prtl_nameFam +  "</td>" +
                // "<td  >" + row.prtl_empID +  "</td>" +
                "<td>" + row.lname + " " + row.fname + "</td>"+

                "<td  >" + row.prtl_birthday +  "</td>" ;

                htmlData +="<td  >" +  '<button type="button" value='+ row.id +' class="btn btn-details" data-toggle="tooltip" data-placement="bottom"  id="btnUpdateAgency" title=" Schedule" data-bs-toggle="modal" data-bs-target="#mdlFamily" > <i class="fa fa-pencil"></i> </button>'   ;
                htmlData += '<button type="button" value='+ row.id +' class="btn btn-secondary ml-1" id="btnDeleteFamily" > <i class="fa fa-trash"></i> </button>'  + "</td>" ;
                htmlData += "</tr>";
            })
            $("#tblFamily").empty().append(htmlData);

        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});
    }
    //form1 save
    $(document).on('click', '#btnAddFamily', function(e) {
        formAction=1;
        $('.lblTitleModal').text('Creating Family Details');
        $('#frmFamily')[0].reset();
        $('span.error-text').text("");
        $('input.border').removeClass('border border-danger');
        $('select.border').removeClass('border border-danger');
    });

    //form2 update
    $(document).on('click', '#btnUpdateAgency', function(e) {
        formAction=2;
        pID=$(this).val();
        $('span.error-text').text("");
        $('input.border').removeClass('border border-danger');
        $('.lblTitleModal').text('Updating Family Details');
        $('select.border').removeClass('border border-danger');

        $('#frmFamily')[0].reset();
        axios.get('/parental/edit',{
            params: {
                pID: pID
              }
          })
        .then(function (response) {
            $(response.data.data).each(function(index, row) {
                $('#txtFamily').val(row.prtl_nameFam);
                $('#selEmployee').val(row.prtl_empID);
                $('#dateBirth').val(row.prtl_birthday);
            })
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});
    });

    //create_update//
    $(document).on('click', '#btnSaveFamily', function(e) {
        //this select data from the selective box
        var emplist=$('#selEmployee').val();
        var datas = $('#frmFamily');
        var formData = new FormData($(datas)[0]);
        formData.append('formAction', formAction);
        formData.append('pID', pID);
        formData.append('selectedEmpList', emplist);

        //create script
        axios.post('/parental/create_update',formData)
        .then(function (response) {
            //error response validtor
            if (response.data.status == 201) {
                $.each(response.data.error, function(prefix, val) {
                    $('input[name='+ prefix +']').addClass(" border border-danger") ;
                    $('select[name='+ prefix +']').addClass(" border border-danger") ;
                //  $('span.' + prefix + '_error').text("This field is required!");

                    $('span.' + prefix + '_error').text(val[0]);
                });
            }

            //success respose
            if(response.data.status == 200){
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');
                $('select.border').removeClass('border border-danger');

                $('#frmFamily')[0].reset();
                getall();
                dialog.alert({
                    message: response.data.msg
                });

                pID="0";
            }
                //error
                if(response.data.status == 202){
                $('span.error-text').text("");
                $('input.border').removeClass('border border-danger');
                $('select.border').removeClass('border border-danger');

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

    //delete
    $(document).on('click', '#btnDeleteFamily', function(e) {
        var id=$(this).val();

        axios.get('/parental/delete_record',{
            params: {
                id: id
              }
          })
        .then(function (response) {
            dialog.alert({
                message: response.data.msg
            });
            getall();

        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {

        });

     });

})

