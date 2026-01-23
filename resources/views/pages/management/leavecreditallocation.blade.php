@extends('layout.app')
@section('content')
    <div class="container-fluid">
        <div class="mb-2">
            <h4 class="text-gray-800 mb-3 title">Leave Credit Allocation Maintenance</h4>
            <div class="mb-2">
                <button class="mt-3 btn text-white btn-blue radius-1"
                    data-bs-toggle="modal"
                    data-bs-target="#mdlOTFile"
                    onclick="resetForm()">
                    <i class="fa fa-plus"></i> Add Leave Credit Allocation
                </button>
            </div>
        </div>

        <!-- Content Row dar -->
        <div class="tblTitle col-lg-12">
            <p for="" id="lblOpt" class="text-danger mt-4 fs-3"></p>
        </div>

        <div class="row">
            <div class="col-xl-12 col-lg-12">
                <div class="card mb-4">
                    <!-- Card Body -->
                    <div class="card-body">
                        <div class="chart-area overflow-auto">
                            <div class="table-responsive fixTableHead">
                                <table class="table table-hover table-scroll sticky">
                                    <thead style="background-color: #f1f1f1; ">
                                        <tr>
                                            <th scope="col">Employee</th>
                                            <th scope="col">Company</th>
                                            <th scope="col">Leave Type</th>
                                            <th scope="col">Leave Credit</th>
                                            <th scope="col">Year</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tblOTFile">
                                        @if(count($leaveCreditAllocations) > 0)
                                            @foreach($leaveCreditAllocations as $allocation)
                                                <tr>
                                                    <td class="text-capitalize">{{ $allocation->user->lname }} {{ $allocation->user->fname }}</td>
                                                    <td>{{ $allocation->user->empDetail->company->comp_name ?? 'N/A' }}</td>
                                                    <td>{{ $allocation->leaveType->type_leave ?? 'N/A' }}</td>
                                                    <td>{{ $allocation->credits_allocated }}</td>
                                                    <td>{{ $allocation->year }}</td>
                                                    <td>
                                                        <button
                                                            class="btn btn-sm btn-primary btn-edit"
                                                            data-id="{{ $allocation->id }}"
                                                            data-employee="{{ $allocation->employee_id }}"
                                                            data-leavetype="{{ $allocation->leavetype_id }}"
                                                            data-credit="{{ $allocation->credits_allocated }}"
                                                            data-year="{{ $allocation->year }}"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#mdlOTFile"
                                                        >
                                                            Edit
                                                        </button>

                                                        <button
                                                            class="btn btn-sm btn-danger btn-delete"
                                                            data-id="{{ $allocation->id }}"
                                                        >
                                                            Delete
                                                        </button>
                                                    </td>

                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="6" class="text-center">No leave credit allocations found.</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>


    </div>

    {{-- modal  --}}

    <div class="modal fade" id="mdlOTFile" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog   modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger dragable_touch">
                    <h5 class="modal-title fst-italic lblActionDesc text-white title" id="staticBackdropLabel"><label for=""> Leave Credit Allocation </label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ">
                    <div class="card  mb-3 rounded">
                        <div class="card-body ">

                            <form action="" id="frmLeaveCredit">

                                <!-- <div class="col-lg-6"> -->
                                    <div class="row">
                                        <input type="hidden" name="id" id="leave_credit_id">
                                        <div class="col-lg-6 mb-1">
                                            <div class="form-floating ">
                                                <select  class="form-control text-capitalize" name="employee_id" id="txtEmployee"  >
                                                    @if(count($employees)>0)
                                                        @foreach($employees as $employee)
                                                        <option class="text-capitalize" value='{{$employee->empID }}'>{{$employee->lname }} {{$employee->fname }} </option>
                                                        @endforeach
                                                    @else

                                                    @endif
                                                </select>
                                                <label  class="form-check-label" for="selLeaveType" class="text-muted">Employee<label for="" class="text-danger">*</label></label>
                                                <span class="text-danger small error-text employee_error"></span>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 mb-1">
                                            <div class="form-floating ">
                                                <select  class="form-control text-capitalize" name="leave_type" id="txtleave"  >
                                                    @if(count($leavetypes)>0)
                                                        @foreach($leavetypes as $leavetype)
                                                        <option class="text-capitalize" value='{{$leavetype->id }}'>{{$leavetype->type_leave }}</option>
                                                        @endforeach
                                                    @else

                                                    @endif
                                                </select>
                                                <label  class="form-check-label" for="selLeaveType" class="text-muted">Leave Type<label for="" class="text-danger">*</label></label>
                                                <span class="text-danger small error-text leave_error"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-6 mb-1">
                                            <div class="form-floating ">
                                                <input class="form-control" id="txtleave_credit" required name="leave_credit" type="number" placeholder="Days Before"/>
                                                <label for="txtleave_credit">Leave Credit <label for="" class="text-danger">*</label></label>
                                                <span class="text-danger small error-text leave_credit_error"></span>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 mb-1">
                                            <div class="form-floating">
                                                <input readonly class="form-control" id="txtDaysAfter"  value="{{ date('Y') }}" name="year" type="number" placeholder="Days After"/>
                                                <label for="txtyear">Year <label for="" class="text-danger">*</label></label>
                                                <span class="text-danger small error-text daysAfter_error"></span>
                                            </div>
                                        </div>
                                    </div>

                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button  id="btnSaveLeaveCredit" type="button" class="btn btn-secondary ">Save Entries</button>
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
