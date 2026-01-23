$(document).ready(function() {
var obID=0;
getall();
    $(document).on('click', '#btnCreateOBTModal', function(e) {
        // obID=$(this).val();

        axios.get('/obtTracker/get_details')

        .then(function (response) {
            $(response.data.data).each(function(index, row) {

                $('#txtPersonnel').val(row.lname + " " + row.fname);
                $('#txtCompany').val(row.comp_name);
                $('#txtDept').val(row.dep_name);
                $('#txtDesignation').val(row.pos_desc);
                // $('#txtDesignation').val(row.lname + " " + row.fname);
            })
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});
    });

    //create
    $(document).on('click', '#btnSaveOBT', function (e) {
        var datas = $('#frmOBT');
        var formData = new FormData($(datas)[0]);
        // var id = $(this).val();
        // obID=id;
        axios.post('/obtTracker/create_obt', formData)
            .then(function (response) {
                //error response validtor
                if (response.data.status == 201) {
                    $.each(response.data.error, function (prefix, val) {
                        $('input[name=' + prefix + ']').addClass(" border border-danger");
                        $('span.' + prefix + '_error').text(val[0]);
                        // swal("Error", "Please check required fields!", "error");
                    });

                    Swal.fire({
                        position: 'center',
                        icon: 'warning',
                        title: response.data.msg,
                        showConfirmButton: false,
                        timer: 1500
                      })
                }
                //success respose
                if (response.data.status == 200) {
                    $('span.error-text').text("");
                    $('input.border').removeClass('border border-danger');
                    // $('#frmApplicant')[0].reset();
                    // swal("Good job!", response.data.msg, "success");

                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: response.data.msg,
                        showConfirmButton: false,
                        timer: 1500
                      })

                      getall();
                }
                //error
                if (response.data.status == 202) {
                    $('span.error-text').text("");
                    $('input.border').removeClass('border border-danger');
                    // swal("Error!", response.data.msg, "error");

                    Swal.fire({
                        position: 'center',
                        icon: 'warning',
                        title: response.data.msg,
                        showConfirmButton: false,
                        timer: 1500
                      })
                }

            })
            .catch(function (error) {

            })
            .then(function () { });
    });

     //get
     function getall(){
        axios.get('/obtTracker/getall')
        .then(function (response) {
            var htmlData='';
            // var i=1;
            $(response.data.data).each(function(index, row) {
                htmlData += "<tr>"+
                // "<td>" + date("F j, Y", row.obFD )+ "</td>" +
                "<td>" + row.obFD + "</td>" +

                "<td>" + row.obDateFrom + "</td>" +
                "<td>" + row.obDateTo + "</td>" +
                "<td>" + row.obTFrom + "</td>" +
                "<td>" + row.obTTo + "</td>" +
                "<td>" + row.obIFrom + "</td>" +
                "<td>" + row.obITo + "</td>" +
                "<td>" + row.obPurpose + "</td>" +
                "<td>" + row.obCAAmt + "</td>" +
                "<td>" + row.obCAPurpose + "</td>" +
                "<td>" + row.obStatus + "</td>" ;
                htmlData += "</tr>";
            })
            $("#tblOBTTracker").empty().append(htmlData);
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});
    }




});
