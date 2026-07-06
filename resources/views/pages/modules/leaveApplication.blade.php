@extends('layout.app', [
    'title' => 'Leave Application'
])
@push('scripts')
<script src="{{ asset('js/vendor/driver.iife.js') }}"></script>
@endpush
@section('content')
<link rel="stylesheet" href="{{ asset('css/driver.css') }}">
<link rel="stylesheet" href="{{ asset('css/driver-theme.css') }}">

<style>
    /* ── Design tokens (shared with Edit Employee / Attendance Viewer) ── */
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

    /* ── Page shell (matches Attendance Viewer) ──────────────────── */
    .home-shell {
        background: var(--bg);
        min-height: 100vh;
        margin: -1rem -1.5rem;
        padding: 24px 28px 60px;
    }

    /* ── Top header bar (matches Attendance Viewer) ──────────────── */
    .home-topbar {
        background: var(--surface);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        padding: 16px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 20px;
    }
    .home-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
        text-transform: uppercase;
    }
    .home-topbar .breadcrumb {
        font-size: 0.75rem;
        margin: 2px 0 0;
        padding: 0;
        background: none;
    }
    .home-topbar .breadcrumb-item.active {
        color: var(--teal);
        font-weight: 600;
    }

    /* ── Section card ───────────────────────────────────────────── */
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
        margin-left: auto;
    }
    .btn-refresh:hover { background: var(--teal-light); border-color: var(--teal-mid); }

    /* ── Standard button styles (matches Attendance Viewer) ─────── */
    .btn-teal {
        background: var(--teal);
        border-color: var(--teal);
        color: #fff;
    }
    .btn-teal:hover {
        background: var(--teal-dark);
        border-color: var(--teal-dark);
        color: #fff;
    }
    .btn-outline-teal {
        background: var(--surface);
        border: 1.5px solid var(--border);
        color: var(--slate);
    }
    .btn-outline-teal:hover {
        border-color: var(--teal);
        color: var(--teal);
        background: var(--teal-light);
    }

    /* ── Field helpers ──────────────────────────────────────────── */
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

    /* ── Sub-section divider ────────────────────────────────────── */
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

    /* ── Leave Credits highlight box ────────────────────────────── */
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

    /* ── Table styling (matches Attendance Viewer) ──────────────── */
    .table-sticky-header thead th {
        position: sticky !important;
        top: 0;
        background-color: #fafbfc;
        z-index: 10;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--slate-light);
        border-bottom: 2px solid var(--border);
    }
    .table tbody td {
        font-size: 0.8rem;
        vertical-align: middle;
    }
    .table-hover tbody tr:hover {
        background-color: var(--teal-light);
        transition: background-color 0.2s ease;
    }

    /* ── Soft badge (matches Attendance Viewer) ─────────────────── */
    .badge-soft-primary {
        background-color: rgba(0, 128, 128, 0.1);
        color: var(--teal);
        border: 1px solid rgba(0, 128, 128, 0.2);
    }
    .badge-soft-success {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    .badge-soft-danger {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    .badge-soft-warning {
        background-color: rgba(245, 158, 11, 0.1);
        color: #92400e;
        border: 1px solid rgba(245, 158, 11, 0.25);
    }
    .badge-soft-info {
        background-color: rgba(6, 182, 212, 0.1);
        color: #0e7490;
        border: 1px solid rgba(6, 182, 212, 0.2);
    }

    /* ── Modal styling ──────────────────────────────────────────── */
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

    /* ── Input group (matches Attendance Viewer) ────────────────── */
    .input-group-text {
        background: #fafbfc;
        border: 1.5px solid var(--border);
        color: var(--muted);
        font-size: 0.75rem;
    }
</style>

<div class="home-shell">

    {{-- ── Top header with breadcrumb (matches Attendance Viewer) ── --}}
    <div class="home-topbar">
        <div>
            <h4 class="page-title">Leave Application</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted">Modules</li>
                    <li class="breadcrumb-item active fw-semibold" aria-current="page">Leave Application</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" id="btnStartLeaveTour"
                class="btn btn-sm fw-semibold d-flex align-items-center gap-2"
                style="background:var(--teal-light);color:var(--teal-dark);border:1.5px solid var(--teal-mid);border-radius:20px;padding:6px 14px;">
                <i class="fa-solid fa-map"></i> Take a Tour
            </button>
            <button class="btn btn-teal rounded-pill px-4 fw-bold shadow-sm" name="btnCreateLeaveModal" id="btnCreateLeaveModal" data-bs-toggle="modal" data-bs-target="#mdlLeaveApp">
                <i class="fa fa-plus me-2"></i> Leave Application Form
            </button>
        </div>
    </div>

    {{-- ── Search / filter card (matches Attendance Viewer) ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
            <h5 class="sc-title">Search Filters</h5>
        </div>
        <div class="sc-body">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label class="field-label">From</label>
                    <input type="date" class="form-control" id="filterDateFrom">
                </div>
                <div class="col-lg-4 col-md-6">
                    <label class="field-label">To</label>
                    <input type="date" class="form-control" id="filterDateTo">
                </div>
                <div class="col-lg-4 col-md-12 text-end">
                    <div class="d-flex gap-2 justify-content-lg-end">
                        <button type="button" id="btnRefreshFilter" class="btn btn-outline-teal rounded-pill px-4 fw-bold flex-fill flex-lg-grow-0">
                            <i class="fa-solid fa-arrows-rotate me-2"></i>Refresh
                        </button>
                    </div>
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
        <div class="sc-body" style="padding:0;">
            <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0 table-sticky-header" id="leaveTable">
                    <thead>
                        <tr>
                            <th scope="col" class="ps-4">Leave Type</th>
                            <th scope="col">Filing Date</th>
                            <th scope="col">Date From</th>
                            <th scope="col">Date To</th>
                            <th scope="col">Duration</th>
                            <th scope="col">Purpose</th>
                            <th scope="col">Leave Kind</th>
                            <th scope="col">Status</th>
                            <th scope="col">Remarks</th>
                            <th scope="col" class="pe-4">Delete</th>
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
                    <button id="btnSaveLeave" type="button" class="btn btn-teal rounded-pill px-4 fw-bold shadow-sm">Submit</button>
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
                } else if (response.data.auto_disapproved) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Auto-Disapproved',
                        text: response.data.message,
                        confirmButtonText: 'OK',
                        customClass: { confirmButton: 'rounded-pill' }
                    });
                    $('#mdlLeaveApp').modal('hide');
                    datas[0].reset();
                    fetchLeaves();
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

        // Cache of all fetched leaves so the date filter can re-render without a round-trip.
        let allLeaves = [];

        // Refresh buttons (filter card + table header) re-fetch from the server.
        $(document).on('click', '#btnRefreshFilter, #btnRefreshTbl', function(e) {
            e.preventDefault();
            fetchLeaves();
        });

        // Changing either date bound re-applies the filter against the cached rows.
        $(document).on('change', '#filterDateFrom, #filterDateTo', function() {
            renderLeaves();
        });

        fetchLeaves()

        async function fetchLeaves() {
            try {
                const response = await axios.get('/pages/modules/leave/getall');
                allLeaves = response.data.leaves || [];
                renderLeaves();
            } catch (error) {
                console.error('Error fetching leave history:', error);
            }
        }

        function renderLeaves() {
            const tblLeaveApp = document.getElementById('tblLeaveApp');
            tblLeaveApp.innerHTML = '';

            const fromVal = document.getElementById('filterDateFrom').value;
            const toVal = document.getElementById('filterDateTo').value;
            const from = fromVal ? new Date(fromVal + 'T00:00:00') : null;
            const to = toVal ? new Date(toVal + 'T23:59:59') : null;

            const leaves = allLeaves.filter(leave => {
                const start = new Date(leave.start_date);
                if (from && start < from) return false;
                if (to && start > to) return false;
                return true;
            });

            if (leaves.length === 0) {
                tblLeaveApp.innerHTML = `<tr><td colspan="10" class="text-center text-muted py-4">No leave records found.</td></tr>`;
                return;
            }

            leaves.forEach(leave => {
                    let buttonAction = '';
                    let status = '';

                    if (leave.status === 'APPROVED') {
                        status = `<span class="badge badge-soft-success rounded-pill px-3 py-2 fw-semibold">APPROVED</span>`;
                    } else if (leave.status === 'DISAPPROVED') {
                        status = `<span class="badge badge-soft-danger rounded-pill px-3 py-2 fw-semibold">DISAPPROVED</span>`;
                    } else if (leave.status === 'FORAPPROVAL') {
                        status = `<span class="badge badge-soft-warning rounded-pill px-3 py-2 fw-semibold">FOR APPROVAL</span>`;
                    }  else if (leave.status === 'APPROVEDBYCFO') {
                        status = `<span class="badge badge-soft-info rounded-pill px-3 py-2 fw-semibold">APPROVED BY CFO</span>`;
                    }

                    if (leave.status === 'FORAPPROVAL') {
                        buttonAction = `<button class="btn btn-outline-danger btn-sm rounded-pill px-3 delete-leave" data-leave-id="${leave.id}">Delete</button>`;
                    }

                    const remarks = (leave.status === 'DISAPPROVED' && leave.disapproved_remarks)
                        ? `<span class="text-danger small">${leave.disapproved_remarks}</span>`
                        : `<span class="text-muted small">—</span>`;

                    const row = `
                        <tr>
                            <td class="ps-4">${leave.leave_type.type_leave}</td>
                            <td>${new Date(leave.created_at).toLocaleDateString()}</td>
                            <td>${new Date(leave.start_date).toLocaleDateString()}</td>
                            <td>${new Date(leave.end_date).toLocaleDateString()}</td>
                            <td>${leave.total_hrs / 8}</td>
                            <td>${leave.reason}</td>
                            <td>${leave.leave_kind == 0 ? 'Paid' : 'Unpaid'}</td>
                            <td>
                                ${status}
                            </td>
                            <td>${remarks}</td>
                            <td class="pe-4">
                                ${buttonAction}
                            </td>
                        </tr>
                    `;
                    tblLeaveApp.insertAdjacentHTML('beforeend', row);
                });
        }
    })
</script>

<script>
(function () {
    const TOUR_KEY = 'kwatogs_leave_tour_done_{{ auth()->id() }}';

    function buildSteps(driverRef) {
        return [
            {
                element: '.home-topbar',
                popover: {
                    title: '👋 Welcome to Leave Application!',
                    description: 'Here you can file leave requests and view your leave history. Let\'s walk through the page.',
                    side: 'bottom', align: 'start'
                }
            },
            {
                element: '.home-shell > .sc:first-of-type',
                popover: {
                    title: '🔍 Search Filters',
                    description: 'Filter your leave history by date range. Set From and To dates, then click <b>Refresh</b> to reload the table.',
                    side: 'bottom', align: 'start'
                }
            },
            {
                element: '.home-shell > .sc:nth-of-type(2)',
                popover: {
                    title: '📋 Leave History',
                    description: 'All your filed leave requests appear here — showing leave type, filing date, date range, duration, purpose, kind (Paid/Unpaid), and current status. Requests still <em>For Approval</em> have a Delete button if you need to cancel.',
                    side: 'top', align: 'start'
                }
            },
            {
                element: '#btnCreateLeaveModal',
                popover: {
                    title: '📝 File a Leave Request',
                    description: 'Click this to open the leave application form. Click <b>Next</b> to explore the form fields.',
                    side: 'bottom', align: 'end',
                    onNextClick: () => {
                        const modal = new bootstrap.Modal(document.getElementById('mdlLeaveApp'), { backdrop: false });
                        modal.show();
                        setTimeout(() => driverRef.moveNext(), 450);
                    }
                }
            },
            {
                element: '#selLeaveKind',
                popover: {
                    title: '💰 Leave Kind',
                    description: 'Choose <b>Paid</b> if this leave should consume your leave credits, or <b>Unpaid</b> if it should be deducted from your salary instead.',
                    side: 'bottom', align: 'start'
                }
            },
            {
                element: '#selLeaveType',
                popover: {
                    title: '🏷 Leave Type',
                    description: 'Select the type of leave you are filing (e.g. Vacation Leave, Sick Leave, Emergency Leave). The available types are configured by HR.',
                    side: 'bottom', align: 'start'
                }
            },
            {
                element: '#txtLeaveCredits',
                popover: {
                    title: '📊 Leave Credits',
                    description: 'Shows your remaining credits for the selected leave type. This updates automatically when you choose a leave type. Make sure you have enough credits for paid leave.',
                    side: 'bottom', align: 'start'
                }
            },
            {
                element: '#date_from',
                popover: {
                    title: '📆 Leave Date From',
                    description: 'Enter the first day of your leave.',
                    side: 'bottom', align: 'start'
                }
            },
            {
                element: '#date_to',
                popover: {
                    title: '📆 Leave Date To',
                    description: 'Enter the last day of your leave. For a single day, set From and To to the same date.',
                    side: 'bottom', align: 'start'
                }
            },
            {
                element: '#chkHalfDay',
                popover: {
                    title: '🕐 Half Day?',
                    description: 'Tick this if you are only taking half a day of leave. The duration will be adjusted to 0.5 days.',
                    side: 'top', align: 'start'
                }
            },
            {
                element: '#txtPurposeRem',
                popover: {
                    title: '📝 Explanation / Purpose',
                    description: 'Briefly explain the reason for your leave. Your approver will see this when reviewing the request.',
                    side: 'top', align: 'start'
                }
            },
            {
                element: '#btnSaveLeave',
                popover: {
                    title: '✅ Submit',
                    description: 'Click <b>Submit</b> to send your leave request for approval. It will appear in your Leave History with status <em>For Approval</em>. You can delete it before it is approved if you need to cancel.',
                    side: 'top', align: 'end'
                }
            },
            {
                popover: {
                    title: '🎉 You\'re all set!',
                    description: 'You now know how to file a leave application. Remember to file in advance whenever possible. Click <b>Take a Tour</b> anytime to replay this guide.'
                }
            },
        ];
    }

    let driverObj = null;

    function startTour() {
        const existing = bootstrap.Modal.getInstance(document.getElementById('mdlLeaveApp'));
        if (existing) existing.hide();

        const driver = window.driver.js.driver;
        driverObj = driver({
            popoverClass: 'kwatogs-tour',
            showProgress: true,
            progressText: 'Step __current__ of __total__',
            nextBtnText: 'Next →',
            prevBtnText: '← Back',
            doneBtnText: 'Done ✓',
            allowClose: true,
            stagePadding: 6,
            stageRadius: 10,
            overlayColor: '#0f172a',
            overlayOpacity: 0.6,
            smoothScroll: true,
            onDestroyStarted: () => {
                localStorage.setItem(TOUR_KEY, '1');
                const m = bootstrap.Modal.getInstance(document.getElementById('mdlLeaveApp'));
                if (m) m.hide();
                driverObj.destroy();
            },
            steps: []
        });
        driverObj.setSteps(buildSteps(driverObj));
        driverObj.drive();
    }

    if (!localStorage.getItem(TOUR_KEY)) {
        setTimeout(startTour, 800);
    }

    document.getElementById('btnStartLeaveTour').addEventListener('click', function () {
        localStorage.removeItem(TOUR_KEY);
        startTour();
    });
})();
</script>

@endsection