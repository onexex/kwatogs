$(document).ready(function() {
var empIDS='0';
var darID="0";
var liloID= "0";

getdar()
getLog()
getall_attendance()

    $(document).on('click', '#btnAddDAR', function() {
        var datas = $('#frmAddDAR');
        var formData = new FormData($(datas)[0]);
        formData.append('empIDS', empIDS);
        // formData.append('IDinserted', IDinserted);

        axios.post('/home/create_dar', formData)
            .then(function(response) {
                console.log(formData)

                var statusU=response.data.stat;
                var errorDataU = response.data.error;

                if(statusU=='201'){
                    $.each(errorDataU, function(prefix, val) {
                        // $('span.' + prefix + '_error').text(val[0]);
                         $('span.' + prefix + '_error').text("This field is required!");
                         $('#' + prefix ).addClass('border border-danger');
                    });

                    // dialog.alert({
                    //     // title: "Message",
                    //     message: response.data.msg,
                    //     animation: "slide"
                    // });

                    // Swal.fire({
                    //     icon: 'warning',
                    //     title: response.data.msg,
                    //     // text: response.data.msg,
                    //     width:'25em',
                    //     heightAuto:true,
                    //     confirmButtonColor:'#ff0000',
                    // })

                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'center',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    })

                    Toast.fire({
                        icon: 'warning',
                        title: response.data.msg
                    })

                }else if(statusU==200){
                    getdar()
                    // $("#frmOBVal")[0].reset()
                    $('span.error-text').text("");
                    $('select.border').removeClass('border border-danger');
                    $('input.border').removeClass('border border-danger');

                    $("#txtSearchEmp").trigger('keyup');
                    $('#mdlAddDAR').modal('toggle');
                    $('#txtDAR').val("");
                    // $('#frmOBVal').modal('toggle');
                    // dialog.alert({
                    //     message:response.data.msg
                    // })

                    // Swal.fire({
                    //     icon: 'success',
                    //     title: response.data.msg,
                    //     // text: response.data.msg,
                    //     width:'25em',
                    //     heightAuto:true,
                    //     confirmButtonColor:'#ff0000',
                    // })

                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'center',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    })

                    Toast.fire({
                        icon: 'success',
                        title: response.data.msg
                    })

                }
            })
            .catch(function(error) {
                dialog.alert({
                    message: error
                });
            })
            .then(function() {});
    })

    //clear dar modal
    $(document).on('click', '#btnCloseDar', function() {
        $('span.error-text').text("");
        $('select.border').removeClass('border border-danger');
        $('input.border').removeClass('border border-danger');
        $('#txtDAR').val("");
    })

//getdar
function getdar(){
    axios.get('/home/getall_dar')
    .then(function (response) {
        var htmlData='';
        // var i=1;
        $(response.data.data).each(function(index, row) {
            htmlData += "<tr>"+
            // "<td>" + i++ + "</td>" +
            "<td>" + row.a + "</td>" +
            "<td>" + row.b + "</td>" +
            "<td>" + row.c + "</td>" ,
            htmlData += "</tr>";
        })
        $("#tblDar").empty().append(htmlData);
    })
    .catch(function (error) {

        dialog.alert({
            message: error
        });
    })
    .then(function () {});
}

//refdar
$(document).on('click', '#btnDARRef', function() {
    var dFrom = $('#txtDateFrom').val();
    var dTo = $('#txtDateTo').val();
    axios.get('/home/filter_dar', {
        params: {
            dFrom: dFrom,
            dTo: dTo,
        }
    })
        .then(function(response) {
            var htmlData='';
            var status=response.data.status;
            // var errorData = response.data.error;
        if(status==200){
            var htmlData = '';
            // invloadtable();
            $(response.data.data).each(function(index, row) {
                htmlData += "<tr>"+
                "<td>" + row.a + "</td>" +
                "<td>" + row.b + "</td>" +
                "<td>" + row.c + "</td>" ,
                htmlData += "</tr>";
            })
            $("#tblDar").empty();
            $("#tblDar").append(htmlData);
        }
        else if(status==201){
            $("#tblDar").empty();

            // dialog.alert({
            //     // title: "Message",
            //     message: "No result Found!",
            //     animation: "slide"
            // });

            const Toast = Swal.mixin({
                toast: true,
                position: 'center',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            })

            Toast.fire({
                icon: 'warning',
                title: 'No result Found!'
            })
        }
    })
        .catch(function(error) {
            dialog.alert({
                message: error
            });
        })
        .then(function() {});
    })


    //ATTENDANCE LOG
    function getLog(){
        axios.get('/home/mdl_attendance')
        .then(function (response) {
        if(response.data.status==200){
            // alert(response.data.data);
            $("#btnLogMdl").text(response.data.data);
            $("#btnLogMdl").val(response.data.btnval);
        }else{

        }
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});
    }

    //validate
    $(document).on('click', '#btnLog', function(e) {

        axios.post('home/create_attendance')
            .then(function(response) {
                var status=response.data.stat;

            if(status==200){
                // dialog.alert({
                //     message: response.data.msg
                // });

                // Swal.fire({
                //     icon: 'success',
                //     title: response.data.msg,
                //     // text: response.data.msg,
                //     width:'25em',
                //     heightAuto:true,
                //     confirmButtonColor:'#ff0000',
                //   })

                const Toast = Swal.mixin({
                    toast: true,
                    position: 'center',
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                })

                Toast.fire({
                    icon: 'success',
                    title: response.data.msg
                })

                $('#mdlLogin').modal('toggle');
                getdar()
                getLog()
                getall_attendance()
            }
            if(status==201){

                // dialog.alert({
                //     message: response.data.msg
                // });

                // Swal.fire({
                //     // icon: 'warning',
                //     title: response.data.msg,
                //     // text: response.data.msg,
                //     width:'25em',
                //     heightAuto:true,
                //     confirmButtonColor:'#ff0000',
                //   })

                const Toast = Swal.mixin({
                toast: true,
                position: 'center',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
                })

                Toast.fire({
                    icon: 'warning',
                    title: response.data.msg,
                    confirmButtonColor:'#ff0000',

                })


                $('#mdlLogin').modal('toggle');
                getdar()
                getLog()
                getall_attendance()
            }
        })
            .catch(function(error) {
                dialog.alert({
                    message: error
                });
            })
            .then(function() {});
        })

    // login
    $(document).on('click', '#btnLogMdl', function(e) {

        if($(this).val() == 1){
            $('#txtLogMdl').text("You are about to Logout, Continue?");
            $('#txtTitle').text("LOGOUT");
            // $('#btnLogMdl').css('background-color', '#008080');

        }else{
            $('#txtLogMdl').text("You are about to Login, Continue?");
            $('#txtTitle').text("LOGIN");

            // $('#btnLogMdl').css('background-color', '#858796');
        }
    });

    function getall_attendance(){
        var DFrom = $('#txtDateFrom').val();
        var DTo = $('#txtDateTo').val();

        axios.get('/home/getall_attendance', {
            params: {
                DFrom: DFrom,
                DTo: DTo,
            }
        })
        .then(function (response) {
            $("#tblAttendance").empty().append(response.data.data);
        })
        .catch(function (error) {
        })
        .then(function () {});
    }

    //refAttendance
    $(document).on('click', '#btnLogRef', function() {
        var DFrom = $('#txtDateFrom').val();
        var DTo = $('#txtDateTo').val();

        axios.get('/home/filter_attendance', {
            params: {
                DFrom: DFrom,
                DTo: DTo,
            }
        })
        .then(function (response) {
            $("#tblAttendance").empty().append(response.data.data);
        })
        .catch(function (error) {
        })
        .then(function () {});
    })

});

