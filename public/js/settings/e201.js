$(document).ready(function() {
    var empID=0;
    var idFiles=0;
        $(document).on('click', '#btnSaveFile', function (e) {

            var empIDs = $("#txtLastname").val();
            var datas = $('#frmE201');
            var formData = new FormData($(datas)[0]);

            formData.append('selectedempID', empIDs);

            axios.post('/E201/create', formData)
                .then(function (response) {
                    //error response validtor
                    if (response.data.status == 201) {
                        $.each(response.data.error, function (prefix, val) {
                            $('input[name=' + prefix + ']').addClass(" border border-danger");
                            // $('select.border').removeClass('border border-danger');
                            $('span.' + prefix + '_error').text(val[0]);
                            // swal("Error", "Please check required fields!", "error");

                            Swal.fire({
                                position: 'center',
                                icon: 'warning',
                                title: response.data.msg,
                                showConfirmButton: false,
                                timer: 1500
                              })

                            // const Toast = Swal.mixin({
                            //     toast: true,
                            //     position: 'center',
                            //     showConfirmButton: false,
                            //     timer: 1500,
                            //     timerProgressBar: true,
                            //     didOpen: (toast) => {
                            //         toast.addEventListener('mouseenter', Swal.stopTimer)
                            //         toast.addEventListener('mouseleave', Swal.resumeTimer)
                            //     }
                            // })

                            // Toast.fire({
                            //     icon: 'warning',
                            //     title: response.data.msg
                            // })

                        });
                    }
                    //success respose
                    if (response.data.status == 200) {
                        $('span.error-text').text("");
                        $('input.border').removeClass('border border-danger');
                        // $('select.border').removeClass('border border-danger');
                        // $('#frmE201')[0].reset();

                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: response.data.msg,
                            showConfirmButton: false,
                            timer: 1500
                          })

                        // const Toast = Swal.mixin({
                        //     toast: true,
                        //     position: 'center',
                        //     showConfirmButton: false,
                        //     timer: 1500,
                        //     timerProgressBar: true,
                        //     didOpen: (toast) => {
                        //         toast.addEventListener('mouseenter', Swal.stopTimer)
                        //         toast.addEventListener('mouseleave', Swal.resumeTimer)
                        //     }
                        // })

                        // Toast.fire({
                        //     icon: 'warning',
                        //     title: response.data.msg
                        // })


                    }
                    //error
                    if (response.data.status == 202) {
                        $('span.error-text').text("");
                        $('input.border').removeClass('border border-danger');
                        // $('select.border').removeClass('border border-danger');

                        // swal("Error!", response.data.msg, "error");

                        Swal.fire({
                            position: 'center',
                            icon: 'warning',
                            title: response.data.msg,
                            showConfirmButton: false,
                            timer: 1500
                          })

                        // const Toast = Swal.mixin({
                        //     toast: true,
                        //     position: 'center',
                        //     showConfirmButton: false,
                        //     timer: 1500,
                        //     timerProgressBar: true,
                        //     didOpen: (toast) => {
                        //         toast.addEventListener('mouseenter', Swal.stopTimer)
                        //         toast.addEventListener('mouseleave', Swal.resumeTimer)
                        //     }
                        // })

                        // Toast.fire({
                        //     icon: 'warning',
                        //     title: response.data.msg
                        // })

                    }
                })
                .catch(function (error) {

                })
                .then(function () { });
        });

        $(document).on('change', '#txtLastname', function() {
            var id = $(this).val();
            empID=id;
            axios.get('/E201/get_empID',{
                params :{
                    id: id
                }
            })
            getall(id)

        })

        function getall(id) {

            axios.get('/E201/getall',{
                params:{
                    id: id
                }
            })
                .then(function(response) {
                    console.log(response.data.data)
                    var htmlData='';

                    $(response.data.data).each(function(index, row) {
                        htmlData += "<tr>"+
                    "<td>" + row.id + "</td>" +
                    "<td>" + row.path + "</td>" +

                    // htmlData +="<td class=''> +<button data-bs-toggle='modal' data-bs-target='#mdlFiles'  data-toggle='tooltip' data-placement='bottom' value= '" + row.id +
                    // "' id='btnviewFiles' title='View Resume'  type='button' class='btn btn-danger btn-sm ml-1'> <i class='fa-regular fa-file'></i>  </button>" + "</td>" ;

                    "<td class=''> <button class='btn btn-danger btn-sm'> <a class='text-decoration-none' target='_blank'  href="+ response.data.path + row.path +"> <i class='fa fa-eye text-white'></i> </a>  </button> </td>" ;

                    htmlData += "</tr>";
                    })
                    $("#tblE201").empty();
                    $("#tblE201").append(htmlData);

                })
                .catch(function(error) {
                    alert(error);
                })
                .then(function() {});
        }

        $(document).on("click", "#btnviewFiles", function () {
            var id = $(this).val();
            idFiles=id;
            axios
                .get("/E201/getall", {
                    params: {
                        id: idFiles,
                    },
                })
                .then(function (response) {
                    $(response.data.data).each(function (index, row) {
                        const modalPath = row.path || '';
                        const embedElement = document.getElementById('modalEmbed');
                        embedElement.src = modalPath;
                    });
                })
                .catch(function(error) {
                })
                .then(function() {});
        });






    });
