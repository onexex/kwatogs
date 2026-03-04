
    loadLeave()

    async function loadLeave() {
        axios.get('/leaverequests/getAll')
        .then(function (response) {
            var htmlData='';

            $(response.data.data).each(function(index, row) {

                var actionButtons = '';
                
                let status = '';

                if (row.status === 'APPROVED') {
                    status = `<span class="badge bg-success">APPROVED</span>`;
                } else if (row.status === 'DISAPPROVED') {
                    status = `<span class="badge bg-danger">DISAPPROVED</span>`;
                } else if (row.status === 'FORAPPROVAL') {
                    status = `<span class="badge bg-warning text-dark p-2">FOR APPROVAL</span>`;
                }

                if (window.userPermissions.includes("approveleave") && row.status === 'FORAPPROVAL') {
                    actionButtons += `
                            <button class="btn btn-sm btn-primary ms-1" data-idprice="${row.id}" data-id="${row.id}" id="branchUpdateprice">
                                APPROVE
                            </button>`;
                    actionButtons += `
                            <button class="btn btn-sm btn-danger bg-danger text-white ms-1" data-idprice="${row.id}" data-id="${row.id}" id="branchUpdateprice">
                                DISAPPROVE
                            </button>`;
                }

                if (window.userPermissions.includes("approvecfoleave") && row.status === 'APPROVED') {
                    actionButtons += `
                            <button class="btn btn-sm btn-primary ms-1" data-idprice="${row.id}" data-id="${row.id}" id="branchUpdateprice">
                                CONFIRM
                            </button>`;
                }
                htmlData += "<tr>"+
                // "<td>" + date("F j, Y", row.obFD )+ "</td>" +
                "<td class='text-capitalize'>" + row.employee_name + "</td>" +
                "<td class='text-capitalize'>" + row.leave_type + "</td>" +
                "<td>" + row.fillingDate + "</td>" +
                "<td>" + row.date_from + "</td>" +
                "<td>" + row.date_to + "</td>" +
                "<td>" + row.days + "</td>" +
                "<td>" + row.reason + "</td>" +
                "<td>" + row.leaveKind + "</td>" +
                "<td>" + status + "</td>" +
                "<td>" + actionButtons + "</td>";
                
                htmlData += "</tr>";
            })
            $("#tblLeaveApp").empty().append(htmlData);
        })
        .catch(function (error) {
            dialog.alert({
                message: error
            });
        })
        .then(function () {});
    }