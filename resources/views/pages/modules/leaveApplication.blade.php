@extends('layout.app', [
    'title' => 'Leave Application'
])
@section('content')

<style>
    /* ── Design tokens (shared with Edit Employee) ──────────────── */
    :root {
        --teal:         #008080;
        --teal-dark:    #006666;
        --teal-mid:     #4db6ac;
        --teal-light:   #e0f2f1;
        --slate:        #334155;
        --slate-light:  #64748b;
        --muted:        #94a3b8;
        --bg:           #f1f5f9;
        --surface:      #ffffff;
        --border:       #e2e8f0;
        --danger:       #ef4444;
        --success:      #10b981;
        --warning:      #f59e0b;
        --radius-card:  14px;
        --radius-input: 8px;
        --shadow-card:  0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }

    /* ── Page shell ──────────────────────────────────────────── */
    .leave-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    /* ── Top header bar ──────────────────────────────────────── */
    .leave-topbar {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        padding: 16px 22px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .leave-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .leave-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }

    .btn-add-leave {
        background: var(--teal);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: .3px;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(0,128,128,.25);
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-add-leave:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); }

    /* ── Section card ────────────────────────────────────────── */
    .sc {
        background: var(--surface);
        border-radius: var(--radius-card);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-card);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .sc-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 22px;
        border-bottom: 1px solid var(--border);
        background: linear-gradient(to right, #fafcff, #f8fbfa);
    }
    .sc-head-left { display: flex; align-items: center; gap: 10px; }
    .sc-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: var(--teal-light);
        color: var(--teal);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        flex-shrink: 0;
    }
    .sc-title {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--slate);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin: 0;
    }
    .sc-body { padding: 22px; }

    .btn-refresh {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1.5px solid var(--border);
        background: var(--surface);
        color: var(--teal);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .15s;
    }
    .btn-refresh:hover { background: var(--teal-light); border-color: var(--teal-mid); }

    /* ── Filter bar ──────────────────────────────────────────── */
    .filter-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .filter-bar .field-label { margin-bottom: 0; white-space: nowrap; }
    .filter-bar .form-control { width: auto; }

    /* ── Field helpers ───────────────────────────────────────── */
    .field-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--slate-light);
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: 5px;
        display: block;
    }
    .field-label .req { color: var(--danger); margin-left: 2px; }

    .form-control, .form-select {
        border: 1.5px solid var(--border);
        border-radius: var(--radius-input);
        font-size: 0.875rem;
        color: var(--slate);
        background: #fafbfc;
        transition: border-color .15s, box-shadow .15s;
        padding: 0.55rem 0.85rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--teal);
        box-shadow: 0 0 0 3px rgba(0,128,128,.1);
        background-color: #fff;
        outline: none;
    }
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: var(--danger);
        background-color: #fff8f8;
        box-shadow: none;
    }
    .form-control[readonly] {
        background: var(--teal-light);
        color: var(--teal);
        font-weight: 600;
        cursor: default;
    }
    .error-text {
        font-size: 0.68rem;
        font-weight: 500;
        color: var(--danger);
        display: block;
        min-height: 14px;
        margin-top: 3px;
    }

    /* ── Sub-section divider ─────────────────────────────────── */
    .sub-divider {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 6px 0 18px;
    }
    .sub-divider span {
        font-size: 0.73rem;
        font-weight: 700;
        color: var(--teal);
        text-transform: uppercase;
        letter-spacing: .4px;
        white-space: nowrap;
    }
    .sub-divider::after {
        content: '';
        flex-grow: 1;
        height: 1px;
        background: var(--border);
    }

    /* ── Leave Credits highlight box ─────────────────────────── */
    .credit-box {
        border: 1px solid var(--teal);
        background: var(--teal-light);
        padding: 6px 10px 10px;
        border-radius: var(--radius-input);
    }
    .credit-box .field-label { color: var(--teal-dark); }
    .credit-box .form-control {
        background: #fff;
        color: var(--teal-dark);
        font-weight: 700;
        border-color: var(--teal-mid);
    }

    /* ── Table styling ───────────────────────────────────────── */
    .leave-table thead th {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--slate-light);
        text-transform: uppercase;
        letter-spacing: .4px;
        border-bottom: 2px solid var(--border);
        white-space: nowrap;
    }
    .leave-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
    }
    .leave-table tbody tr:hover { background: var(--teal-light); }

    /* ── Modal styling ───────────────────────────────────────── */
    #mdlLeaveApp .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #mdlLeaveApp .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #mdlLeaveApp .modal-header .modal-title { color: #fff; }
    #mdlLeaveApp .modal-body { background: var(--bg); padding: 22px; }
    #mdlLeaveApp .modal-footer {
        background: var(--surface);
        border-top: 1px solid var(--border);
    }

    .btn-submit-leave {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 10px 26px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(245,158,11,.3);
        transition: all .2s;
    }
    .btn-submit-leave:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,.4); }
</style>

<div class="leave-shell">

    {{-- ── Top header ── --}}
    <div class="leave-topbar">
        <div>
            <p class="page-title">Automated Leave Application</p>
            <p class="page-sub">File and track your leave requests</p>
        </div>
        <button class="btn-add-leave" name="btnCreateLeaveModal" id="btnCreateLeaveModal" data-bs-toggle="modal" data-bs-target="#mdlLeaveApp">
            <i class="fa fa-plus"></i> Leave Application Form
        </button>
    </div>

    {{-- ── Filters ── --}}
    <div class="sc">
        <div class="sc-body" style="padding: 16px 22px;">
            <div class="filter-bar">
                <div>
                    <label class="field-label">From</label>
                    <input type="date" class="form-control">
                </div>
                <div>
                    <label class="field-label">To</label>
                    <input type="date" class="form-control">
                </div>
            </div>
        </div>
    </div>

    {{-- ── Leave History ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa fa-history"></i></div>
                <h5 class="sc-title">Leave History</h5>
            </div>
            <button class="btn-refresh" name="btnRefreshTbl" id="btnRefreshTbl" title="Refresh">
                <i class="fa fa-refresh fa-sm"></i>
            </button>
        </div>
        <div class="sc-body">
            <div class="table-responsive">
                <table class="table table-hover leave-table">
                    <thead>
                        <tr>
                            <th scope="col">Leave Type</th>
                            <th scope="col">Filing Date</th>
                            <th scope="col">Date From</th>
                            <th scope="col">Date To</th>
                            <th scope="col">Duration</th>
                            <th scope="col">Purpose</th>
                            <th scope="col">Leave Kind</th>
                            <th scope="col">Status</th>
                            <th scope="col">Delete</th>
                        </tr>
                    </thead>
                    <tbody id="tblLeaveApp">

                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal LEAVE APPLICATION Form --}}
    <div class="modal fade" id="mdlLeaveApp" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header dragable_touch">
                    <h5 class="modal-title" id="staticBackdropLabel"><label for="" id="lblTitleLeaveApp">Leave Application Form</label></h5>
                    <button type="button" class="btn-close btn-close-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" id="frmLeaveApp">

                        <div class="sub-divider"><span>Personnel Details</span></div>
                        <div class="row g-3 mb-3">
                            <div class="col-lg-6">
                                <label class="field-label" for="txtPersonnel">Personnel Name <span class="req">*</span></label>
                                <input class="form-control text-capitalize" id="txtPersonnel" value="{{ $user->fname . ' ' . $user->mname . ' ' . $user->lname }}" name="personnel" type="text" placeholder="-" readonly/>
                                <span class="error-text personnel_error"></span>
                            </div>
                            <div class="col-lg-6">
                                <label class="field-label" for="txtCompany">Company Name <span class="req">*</span></label>
                                <input class="form-control" id="txtCompany" value="{{ $employeeDetails->company->comp_name ?? '' }}" name="company" type="text" placeholder="-" readonly/>
                                <span class="error-text company_error"></span>
                            </div>
                            <div class="col-lg-6">
                                <label class="field-label" for="txtDepartment">Department <span class="req">*</span></label>
                                <input class="form-control" id="txtDepartment" value="{{ $employeeDetails->department->dep_name ?? '' }}" name="department" type="text" placeholder="-" readonly/>
                                <span class="error-text department_error"></span>
                            </div>
                            <div class="col-lg-6">
                                <label class="field-label" for="txtDesignation">Designation <span class="req">*</span></label>
                                <input class="form-control" id="txtDesignation" value="{{ $employeeDetails->position->pos_desc ?? '' }}" name="designation" type="text" placeholder="-" readonly/>
                                <span class="error-text designation_error"></span>
                            </div>
                        </div>

                        <div class="sub-divider"><span>Leave Details</span></div>
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label class="field-label" for="selLeaveKind">Leave Kind <span class="req">*</span></label>
                                <select class="form-select" name="leavekind" id="selLeaveKind">
                                    <option value="0">Paid</option>
                                    <option value="1">Unpaid</option>
                                </select>
                                <span class="error-text leavekind_error"></span>
                            </div>
                            <div class="col-lg-6">
                                <label class="field-label" for="selLeaveType">Leave Type <span class="req">*</span></label>
                                <select class="form-select" name="leavetype" id="selLeaveType">
                                    @foreach ($leaveTypes as $leaveType)
                                        <option value="{{ $leaveType->id }}">{{ $leaveType->type_leave }}</option>
                                    @endforeach
                                </select>
                                <span class="error-text leavetype_error"></span>
                            </div>

                            <div class="col-lg-4">
                                <div class="credit-box">
                                    <label class="field-label">Leave Credits</label>
                                    <input name="leavecredits" type="text" id="txtLeaveCredits" readonly="readonly" value="-" class="form-control">
                                    <span class="error-text leavecredits_error"></span>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <label class="field-label" for="date_from">From <span class="req">*</span></label>
                                <input class="form-control" id="date_from" name="date_from" type="date" placeholder="-"/>
                                <span class="error-text date_from_error"></span>
                            </div>
                            <div class="col-lg-4">
                                <label class="field-label" for="date_to">To <span class="req">*</span></label>
                                <input class="form-control" id="date_to" name="date_to" type="date" placeholder="-"/>
                                <span class="error-text date_to_error"></span>
                            </div>

                            <div class="col-lg-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="chkHalfDay" name="halfday">
                                    <label class="form-check-label field-label mb-0" for="chkHalfDay" style="text-transform:none;">If Half Day?</label>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <label class="field-label" for="txtDurationDays">Duration Days <span class="req">*</span></label>
                                <input class="form-control" id="txtDurationDays" name="days" type="number" placeholder="-" readonly/>
                                <span class="error-text days_error"></span>
                            </div>

                            <div class="col-lg-12">
                                <label class="field-label" for="txtPurposeRem">Explanation / Purpose of Leave</label>
                                <textarea class="form-control" id="txtPurposeRem" name="purpose" rows="4" placeholder=""></textarea>
                                <span class="error-text purpose_error"></span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button id="btnSaveLeave" type="button" class="btn-submit-leave">Submit</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    $(document).ready(function() {

        $(document).on('click', '#btnSaveLeave', function(e) {
            var datas = $('#frmLeaveApp');
            var formData = new FormData($(datas)[0]);

            $('.error-text').text('');
            $('.form-control').removeClass('is-invalid') ;

            axios.post('/pages/modules/leave',formData)
            .then(function (response) {
                if (response.data.status == 201) {
                    $.each(response.data.error, function(prefix, val) {
                        $('input[name='+ prefix +']').addClass("is-invalid") ;
                        $('span.' + prefix + '_error').text(val[0]);
                    });

                    return
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.message || 'Your leave application has been submitted successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#mdlLeaveApp').modal('hide');
                    datas[0].reset();
                    fetchLeaves();
                }
            })

        })

        $(document).on('change', '#selLeaveType', function(e) {
            var leaveCredit = document.getElementById("txtLeaveCredits");
            const leaveKind = document.getElementById("selLeaveKind").value;
            if (leaveKind == 0) {
                axios.get('/pages/modules/leave-check-credit', {
                    params: {
                        leave_id: $(this).val()
                    }
                }).then((response) => {
                    if (response.data.status == 404) {
                        if (leaveCredit) {
                            leaveCredit.value = response.data.message
                        }
                    } else if (response.data.leave_credit) {
                        leaveCredit.value = response.data.leave_credit
                    }
                })
            } else {
                leaveCredit.value = 0
            }
        })

        $(document).on('change', '#date_from, #date_to', function(e) {

            var startDate = document.getElementById("date_from").value;
            var endDate = document.getElementById("date_to").value;

            if (startDate !== "" && endDate !== "") {

                var start = new Date(startDate);
                var end = new Date(endDate);

                start.setHours(0,0,0,0);
                end.setHours(0,0,0,0);

                var diffTime = end - start;

                var diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1;

                if (diffDays < 1) diffDays = 1;

                document.getElementById("txtDurationDays").value = diffDays;

            } else {
                document.getElementById("txtDurationDays").value = 0;
            }
        });

        $(document).on('click', '.delete-leave', function(e) {
            const leaveId = $(this).data('leave-id');

            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to delete this leave application?',
                icon: 'warning',
                buttons: true,
                dangerMode: true,
                showCancelButton: true,
                confirmButtonText: 'Yes, delete!'
            }).then((willDelete) => {

                if (willDelete.isConfirmed) {
                    axios.delete(`/pages/modules/leave/delete/${leaveId}`)
                        .then((response) => {
                            if (response.data.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.data.message || 'The leave has been removed successfully.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });

                                fetchLeaves();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.data.message || 'An error occurred while deleting the leave.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                    })
                    .catch((error) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: error.response?.data?.message || 'An error occurred while deleting the leave.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        console.error('Error deleting leave application:', error);
                    });
                }
            })
        });

        fetchLeaves()

        async function fetchLeaves() {
            try {
                const response = await axios.get('/pages/modules/leave/getall');
                const leaves = response.data.leaves;
                const tblLeaveApp = document.getElementById('tblLeaveApp');
                tblLeaveApp.innerHTML = '';

                leaves.forEach(leave => {
                    let buttonAction = '';
                    let status = '';

                    if (leave.status === 'APPROVED') {
                        status = `<span class="badge bg-success">APPROVED</span>`;
                    } else if (leave.status === 'DISAPPROVED') {
                        status = `<span class="badge bg-danger">DISAPPROVED</span>`;
                    } else if (leave.status === 'FORAPPROVAL') {
                        status = `<span class="badge bg-warning text-dark p-2">FOR APPROVAL</span>`;
                    }  else if (leave.status === 'APPROVEDBYCFO') {
                        status = `<span class="badge bg-info p-2">APPROVED BY CFO</span>`;
                    }

                    if (leave.status === 'FORAPPROVAL') {
                        buttonAction = `<button class="btn btn-danger btn-sm bg-danger text-white delete-leave" data-leave-id="${leave.id}">Delete</button>`;
                    }

                    const row = `
                        <tr>
                            <td>${leave.leave_type.type_leave}</td>
                            <td>${new Date(leave.created_at).toLocaleDateString()}</td>
                            <td>${new Date(leave.start_date).toLocaleDateString()}</td>
                            <td>${new Date(leave.end_date).toLocaleDateString()}</td>
                            <td>${leave.total_hrs / 8}</td>
                            <td>${leave.reason}</td>
                            <td>${leave.leave_kind == 0 ? 'Paid' : 'Unpaid'}</td>
                            <td>
                                ${status}
                            </td>
                            <td>
                                ${buttonAction}
                            </td>
                        </tr>
                    `;
                    tblLeaveApp.insertAdjacentHTML('beforeend', row);
                });
            } catch (error) {
                console.error('Error fetching leave history:', error);
            }
        }
    })
</script>
@endsection
