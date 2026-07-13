    let otCurrentPage = 1;

    loadOvertime();

    async function loadOvertime(page = otCurrentPage) {
        axios.get('/overtimerequests/getAll', { params: { page } })
        .then(function (response) {
            const res = response.data || {};
            const rows = res.data || [];
            otCurrentPage = res.current_page || 1;
            const lastPage = res.last_page || 1;
            var htmlData='';
            $(rows).each(function(index, row) {

                var actionButtons = '';
                
                let status = '';

                if (row.status === 'APPROVED') {
                    status = `<span class="st-pill is-cfo"><span class="dot"></span><i class="fa-solid fa-user-check"></i> Verified by Supervisor</span>`;
                } else if (row.status === 'APPROVEDBYCFO') {
                    status = `<span class="st-pill is-approved"><span class="dot"></span><i class="fa-solid fa-circle-check"></i> Approved by CFO</span>`;
                } else if (row.status === 'DISAPPROVED') {
                    status = `<span class="st-pill is-rejected"><span class="dot"></span><i class="fa-solid fa-circle-xmark"></i> Disapproved</span>`;
                } else if (row.status === 'FORAPPROVAL') {
                    status = `<span class="st-pill is-hr"><span class="dot"></span><i class="fa-regular fa-clock"></i> Pending HR Approval</span>`;
                }

                if (window.userPermissions.includes("approveovertime") && row.status === 'FORAPPROVAL') {
                    actionButtons += `
                            <button class="act-btn act-approve btnApproveLeave" data-id="${row.id}" title="Approve request">
                                <i class="fa-solid fa-check"></i> Approve
                            </button>`;
                    actionButtons += `
                            <button class="act-btn act-reject btnDisapproveLeave" data-id="${row.id}" title="Disapprove request">
                                <i class="fa-solid fa-xmark"></i> Disapprove
                            </button>`;
                }

                if (window.userPermissions.includes("approvecfoovertime") && row.status === 'APPROVED') {
                    actionButtons += `
                            <button class="act-btn act-confirm btnConfirmLeave" data-id="${row.id}" title="Confirm as CFO">
                                <i class="fa-solid fa-circle-check"></i> Confirm
                            </button>`;
                    actionButtons += `
                            <button class="act-btn act-reject btnCfoDisapprove" data-id="${row.id}" title="Disapprove request">
                                <i class="fa-solid fa-xmark"></i> Disapprove
                            </button>`;
                }
                htmlData += "<tr>"+
                // "<td>" + date("F j, Y", row.obFD )+ "</td>" +
                "<td class='text-capitalize'>" + row.employee_name + "</td>" +
                "<td>" + row.department + "</td>" +
                "<td>" + row.fillingDate + "</td>" +
                "<td>" + row.date_from + "</td>" +
                "<td>" + row.date_to + "</td>" +
                "<td>" + row.days + "</td>" +
                "<td>" + row.reason + "</td>" +
                "<td>" + status + "</td>" +
                "<td class='text-end'>" + (actionButtons ? "<div class='otreq-actions'>" + actionButtons + "</div>" : "<span class='text-muted'>—</span>") + "</td>";
                
                htmlData += "</tr>";
            })
            if (!rows.length) {
                htmlData = '<tr><td colspan="9" class="text-center text-muted py-3">No records found.</td></tr>';
            }
            $("#tblOvertimeApp").empty().append(htmlData);
            renderOvertimePagination(lastPage, otCurrentPage);
        })
        .catch(function (error) { dialog.alert({ message: error }); });
    }

    function renderOvertimePagination(lastPage, cur) {
        const c = document.getElementById('overtimePagination');
        if (!c) return;
        if (lastPage <= 1) { c.innerHTML = ''; return; }
        const win = 1, pages = [];
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
        loadOvertime(page);
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

            const btnCfoDisapprove = e.target.closest('.btnCfoDisapprove');

            if (btnCfoDisapprove) {
                const id = btnCfoDisapprove.dataset.id;
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
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: error?.response?.data?.message || 'Something went wrong.'
                            });
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