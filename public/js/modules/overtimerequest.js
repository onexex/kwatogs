    let otAllRows = [];
    let otCurrentPage = 1;
    const otPerPage = 10;

    loadOvertime()

    async function loadOvertime() {
        axios.get('/overtimerequests/getAll')
        .then(function (response) {
            otAllRows = (response.data && response.data.data) ? response.data.data : [];
            otCurrentPage = 1;
            renderOvertimePage();
        })
        .catch(function (error) {
            dialog.alert({ message: error });
        });
    }

    function renderOvertimePage() {
        const lastPage = Math.max(1, Math.ceil(otAllRows.length / otPerPage));
        if (otCurrentPage > lastPage) otCurrentPage = lastPage;
        const start = (otCurrentPage - 1) * otPerPage;
        const pageRows = otAllRows.slice(start, start + otPerPage);
        var htmlData='';
        $(pageRows).each(function(index, row) {

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
        if (!pageRows.length) {
            htmlData = '<tr><td colspan="8" class="text-center text-muted py-3">No records found.</td></tr>';
        }
        $("#tblOvertimeApp").empty().append(htmlData);
        renderOvertimePagination(lastPage);
    }

    function renderOvertimePagination(lastPage) {
        const c = document.getElementById('overtimePagination');
        if (!c) return;
        if (lastPage <= 1) { c.innerHTML = ''; return; }
        const cur = otCurrentPage, win = 1, pages = [];
        for (let i = 1; i <= lastPage; i++) { if (i===1||i===lastPage||(i>=cur-win&&i<=cur+win)) pages.push(i); }
        const item = (label, page, o) => (o && o.disabled)
            ? `<li class="page-item disabled"><span class="page-link">${label}</span></li>`
            : `<li class="page-item ${o&&o.active?'active':''}"><a href="#" class="page-link ot-page-link" data-page="${page}">${label}</a></li>`;
        const ell = () => `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
        let html = '<nav><ul class="pagination pagination-sm justify-content-end mb-0 gap-1">';
        html += item('&lsaquo;', cur-1, {disabled: cur<=1});
        let prev = 0;
        pages.forEach(i => { if (prev && i-prev>1) html += ell(); html += item(i, i, {active: i===cur}); prev = i; });
        html += item('&rsaquo;', cur+1, {disabled: cur>=lastPage});
        html += '</ul></nav>';
        c.innerHTML = html;
    }

    $(document).on('click', '.ot-page-link', function (e) {
        e.preventDefault();
        if ($(this).closest('.page-item').hasClass('disabled')) return;
        const page = parseInt($(this).data('page'), 10);
        if (!page) return;
        otCurrentPage = page;
        renderOvertimePage();
    });

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
                    confirmButtonColor: '#198754',
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
                    input: 'textarea',
                    inputLabel: 'Disapproval Remarks',
                    inputPlaceholder: 'Enter remarks for disapproval...',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Disapprove',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    customClass: { confirmButton: 'rounded-pill', cancelButton: 'rounded-pill' },
                    inputValidator: (value) => {
                        if (!value || !value.trim()) {
                            return 'Please enter remarks for disapproval!';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.post('/overtimerequests/updateStatus', {
                            leave_id: id,
                            status: 'DISAPPROVED',
                            remarks: result.value
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