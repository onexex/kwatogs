    loadOvertime()

    async function loadOvertime() {
         axios.get('/overtimerequests/getAll')
        .then(function (response) {
            var htmlData='';
            $(response.data.data).each(function(index, row) {

                var actionButtons = '';
                
                let status = '';

                if (row.status === 'APPROVED') {
                    status = `<span class="badge bg-success  p-2">APPROVED</span>`;
                } else if (row.status === 'DISAPPROVED') {
                    status = `<span class="badge bg-danger  p-2">DISAPPROVED</span>`;
                } else if (row.status === 'FORAPPROVAL') {
                    status = `<span class="badge bg-warning text-dark p-2">FOR APPROVAL</span>`;
                }

                if (window.userPermissions.includes("approveleave") && row.status === 'FORAPPROVAL') {
                    actionButtons += `
                            <button class="btn btn-sm btn-success ms-1 btnApproveLeave" data-id="${row.id}" id="btnApproveLeave">
                                APPROVE
                            </button>`;
                    actionButtons += `
                            <button class="btn btn-sm btn-danger bg-danger text-white ms-1 btnDisapproveLeave" data-id="${row.id}" id="btnDisapproveLeave">
                                DISAPPROVE
                            </button>`;
                }

                if (window.userPermissions.includes("approvecfoleave") && row.status === 'APPROVED') {
                    actionButtons += `
                            <button class="btn btn-sm btn-primary ms-1 btnConfirmLeave" data-id="${row.id}" id="btnConfirmLeave">
                                CONFIRM
                            </button>`;
                }
                htmlData += "<tr>"+
                // "<td>" + date("F j, Y", row.obFD )+ "</td>" +
                "<td class='text-capitalize'>" + row.employee_name + "</td>" +
                "<td>" + row.fillingDate + "</td>" +
                "<td>" + row.date_from + "</td>" +
                "<td>" + row.date_to + "</td>" +
                "<td>" + row.days + "</td>" +
                "<td>" + row.reason + "</td>" +
                "<td>" + status + "</td>" +
                "<td>" + actionButtons + "</td>";
                
                htmlData += "</tr>";
            })
            $("#tblOvertimeApp").empty().append(htmlData);
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});
    }

      document.addEventListener("DOMContentLoaded", function () {

        document.addEventListener('click', function (e) {
            const btnApprove = e.target.closest('.btnApproveLeave');

            if (btnApprove) {
                const id = btnApprove.dataset.id;
                Swal.fire({
                    title: 'Approve Overtime Request',
                    text: 'Are you sure you want to approve this overtime request?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Apporve',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    customClass: { confirmButton: 'rounded-pill', cancelButton: 'rounded-pill' }
                }).then((result) => {

                    if (result.isConfirmed) {
                        axios.post('/overtimerequests/updateStatus', {
                            leave_id: id,
                            status: 'APPROVED'
                        })
                        .then(function (response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadOvertime()
                        })
                        .catch(function (error) {
                            console.log(error);
                        });

                    }
                })
            }

            const btnDisapprove = e.target.closest('.btnDisapproveLeave');

            if (btnDisapprove) {
                const id = btnDisapprove.dataset.id;
                Swal.fire({
                    title: 'DisApprove Overtime Request',
                    text: 'Are you sure you want to disapprove this overtime request?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Apporve',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    customClass: { confirmButton: 'rounded-pill', cancelButton: 'rounded-pill' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.post('/overtimerequests/updateStatus', {
                            leave_id: id,
                            status: 'DISAPPROVED'
                        })
                        .then(function (response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadOvertime()
                        })
                        .catch(function (error) {
                            console.log(error);
                        });
                    }
                })
            }

            const btnConfirmLeave = e.target.closest('.btnConfirmLeave');

            if (btnConfirmLeave) {
                const id = btnConfirmLeave.dataset.id;
                Swal.fire({
                    title: 'Confirm Overtime Request',
                    text: 'Are you sure you want to confirm this overtime request?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Confirm',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    customClass: { confirmButton: 'rounded-pill', cancelButton: 'rounded-pill' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.post('/overtimerequests/updateStatus', {
                            leave_id: id,
                            status: 'APPROVEDBYCFO'
                        })
                        .then(function (response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadOvertime()
                        })
                        .catch(function (error) {
                            console.log(error);
                        });
                                
                    }
                })
            }
            
        });

    })