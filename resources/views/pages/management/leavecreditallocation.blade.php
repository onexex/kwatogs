@extends('layout.app')
@section('content')

<style>
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

    .lca-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    .lca-topbar {
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
    .lca-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .lca-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }

    .btn-add-lca {
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
    .btn-add-lca:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

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
    .sc-body { padding: 0; }

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

    .lca-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: var(--surface);
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--slate-light);
        text-transform: uppercase;
        letter-spacing: .4px;
        border-bottom: 2px solid var(--border);
        white-space: nowrap;
        padding: 12px 16px;
    }
    .lca-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .lca-table tbody tr:hover { background: var(--teal-light); }

    .badge-status {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: .3px;
        padding: 5px 12px;
        border-radius: 20px;
        border: 1px solid transparent;
    }

    .icon-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1.5px solid var(--border);
        background: var(--surface);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all .15s;
        cursor: pointer;
    }
    .icon-action-btn:hover { border-color: var(--teal-mid); background: var(--teal-light); }
    .icon-action-btn.danger:hover { border-color: var(--danger); background: #fff5f5; }

    /* ── Modal styling ───────────────────────────────────────── */
    #mdlOTFile .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #mdlOTFile .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #mdlOTFile .modal-header .modal-title,
    #mdlOTFile .modal-header .modal-title label { color: #fff; }
    #mdlOTFile .btn-close { filter: brightness(0) invert(1); }
    #mdlOTFile .modal-body { background: var(--bg); padding: 22px; }
    #mdlOTFile .modal-body .card {
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        box-shadow: none;
    }
    #mdlOTFile .modal-footer {
        background: var(--surface);
        border-top: 1px solid var(--border);
    }

    .btn-submit-lca {
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
    .btn-submit-lca:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,.4); color: #fff; }
</style>

<div class="lca-shell">

    {{-- ── Top header ── --}}
    <div class="lca-topbar">
        <div>
            <p class="page-title title">Leave Credit Allocation Maintenance</p>
            <p class="page-sub">Manage employee leave credit allocations per leave type and year</p>
        </div>
        <button class="btn-add-lca"
            data-bs-toggle="modal"
            data-bs-target="#mdlOTFile"
            onclick="resetForm()">
            <i class="fa-solid fa-plus"></i> Add Leave Credit Allocation
        </button>
    </div>

    <p for="" id="lblOpt" class="text-danger mt-2 fs-6"></p>

    {{-- ── Allocation Records ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-list-check"></i></div>
                <h5 class="sc-title">Leave Credit Allocations</h5>
            </div>
        </div>
        <div class="sc-body">
            <div class="table-responsive fixTableHead" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover table-scroll sticky lca-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" scope="col">Employee</th>
                            <th scope="col">Company</th>
                            <th scope="col">Leave Type</th>
                            <th scope="col">Allocated</th>
                            <th scope="col">Remaining</th>
                            <th scope="col">Year</th>
                            <th class="pe-4 text-end" scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tblOTFile">
                        @if(count($leaveCreditAllocations) > 0)
                            @foreach($leaveCreditAllocations as $allocation)
                                <tr>
                                    <td class="ps-4 text-capitalize fw-semibold text-dark">{{ $allocation->user->lname }} {{ $allocation->user->fname }}</td>
                                    <td>{{ $allocation->user->empDetail->company->comp_name ?? 'N/A' }}</td>
                                    <td>{{ $allocation->leaveType->type_leave ?? 'N/A' }}</td>
                                    <td>{{ $allocation->credits_allocated }}</td>
                                    <td class="fw-semibold {{ (float) $allocation->balance <= 0 ? 'text-danger' : 'text-success' }}">{{ rtrim(rtrim(number_format((float) $allocation->balance, 2), '0'), '.') }}</td>
                                    <td>{{ $allocation->year }}</td>
                                    <td class="pe-4 text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button
                                                class="icon-action-btn btn-edit"
                                                data-id="{{ $allocation->id }}"
                                                data-employee="{{ $allocation->employee_id }}"
                                                data-leavetype="{{ $allocation->leavetype_id }}"
                                                data-credit="{{ $allocation->credits_allocated }}"
                                                data-year="{{ $allocation->year }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#mdlOTFile"
                                                title="Edit"
                                            >
                                                <i class="fa-solid fa-pencil" style="color: var(--teal);"></i>
                                            </button>

                                            <button
                                                class="icon-action-btn danger btn-delete"
                                                data-id="{{ $allocation->id }}"
                                                title="Delete"
                                            >
                                                <i class="fa-solid fa-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </td>

                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="text-center">No leave credit allocations found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- modal  --}}

<div class="modal fade" id="mdlOTFile" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header dragable_touch">
                <h5 class="modal-title fst-italic lblActionDesc title" id="staticBackdropLabel">
                    <i class="fa-solid fa-list-check me-2"></i>
                    <label for="">Leave Credit Allocation</label>
                </h5>
                <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="card mb-3 rounded">
                    <div class="card-body">

                        <form action="" id="frmLeaveCredit">

                            <input type="hidden" name="id" id="leave_credit_id">

                            <div class="sub-divider"><span>Allocation Details</span></div>
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <label class="field-label" for="txtEmployee">Employee <span class="req">*</span></label>
                                    <select class="form-select text-capitalize" name="employee_id" id="txtEmployee">
                                        @if(count($employees)>0)
                                            @foreach($employees as $employee)
                                            <option class="text-capitalize" value='{{$employee->empID }}'>{{ strtoupper($employee->lname) }}, {{ strtoupper($employee->fname) }}</option>
                                            @endforeach
                                        @else

                                        @endif
                                    </select>
                                    <span class="text-danger small error-text employee_error"></span>
                                </div>
                                <div class="col-lg-6">
                                    <label class="field-label" for="txtleave">Leave Type <span class="req">*</span></label>
                                    <select class="form-select text-capitalize" name="leave_type" id="txtleave">
                                        @if(count($leavetypes)>0)
                                            @foreach($leavetypes as $leavetype)
                                            <option class="text-capitalize" value='{{$leavetype->id }}'>{{$leavetype->type_leave }}</option>
                                            @endforeach
                                        @else

                                        @endif
                                    </select>
                                    <span class="text-danger small error-text leave_error"></span>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <label class="field-label" for="txtleave_credit">Leave Credit <span class="req">*</span></label>
                                    <input class="form-control" id="txtleave_credit" required name="leave_credit" type="number" placeholder="Enter number of days"/>
                                    <span class="text-danger small error-text leave_credit_error"></span>
                                </div>
                                <div class="col-lg-6">
                                    <label class="field-label" for="txtDaysAfter">Year <span class="req">*</span></label>
                                    <input readonly class="form-control" id="txtDaysAfter" value="{{ date('Y') }}" name="year" type="number" placeholder="Days After"/>
                                    <span class="text-danger small error-text daysAfter_error"></span>
                                </div>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btnSaveLeaveCredit" type="button" class="btn-submit-lca">Save Entries</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $(document).on('click', '#btnSaveLeaveCredit', function(e) {
            const employeeId = $('#txtEmployee').val();
            const leaveTypeId = $('#txtleave').val();
            const leaveCredit = $('#txtleave_credit').val();
            $(".error-text").text("");

            if (!employeeId) {
                $(".employee_error").text("Please select an employee.");
                return;
            }
            if (!leaveTypeId) {
                $(".leave_error").text("Please select a leave type.");
                return;
            }

            if (!leaveCredit || leaveCredit <= 0) {
                $(".leave_credit_error").text("Please enter a valid leave credit.");
                return;
            }

            var datas = $('#frmLeaveCredit');
            var formData = new FormData($(datas)[0]);

            let url = $('#leave_credit_id').val()
                ? '/pages/leavecreditallocations/update'
                : '/pages/leavecreditallocations/store';

            axios.post(url, formData)
            .then(function (response) {
                if (response.data.status === 'error') {
                    swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.data.message,
                    });
                    return;
                } else {
                    swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            })
            .catch(function (error) {
                swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.response.data.message || 'An error occurred while saving the leave credit allocation.',
                });
            })
        });

        $(document).on('click', '.btn-edit', function () {
            $('#leave_credit_id').val($(this).data('id'));
            $('#txtEmployee').val($(this).data('employee'));
            $('#txtleave').val($(this).data('leavetype'));
            $('#txtleave_credit').val($(this).data('credit'));
            $('#txtDaysAfter').val($(this).data('year'));

            $('.lblActionDesc').text('Edit Leave Credit Allocation');
        });

        function resetForm() {
            $('#frmLeaveCredit')[0].reset();
            $('#leave_credit_id').val('');
            $('.lblActionDesc').text('Leave Credit Allocation');
        }

        $(document).on('click', '.btn-delete', function () {
            let id = $(this).data('id');

            swal.fire({
                title: 'Are you sure?',
                text: 'This record will be permanently deleted.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/pages/leavecreditallocations/delete/${id}`)
                        .then(response => {
                            swal.fire('Deleted!', response.data.message, 'success')
                                .then(() => location.reload());
                        })
                        .catch(error => {
                            swal.fire('Error', 'Failed to delete record.', 'error');
                        });
                }
            });
        });
    });

</script>
@endsection
