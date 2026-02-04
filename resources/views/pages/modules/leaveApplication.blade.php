@extends('layout.app', [
    'title' => 'Leave Application'
])
@section('content')

    <div class="container-fluid">

        <div class="mb-2">
            <h4 class=" mb-0 text-gray-800">Automated Leave Application</h4>
            <button class=" mt-3 btn text-white btn-blue" name="btnCreateLeaveModal" id="btnCreateLeaveModal" data-bs-toggle="modal" data-bs-target="#mdlLeaveApp"> <i class="fa fa-plus"></i> Leave Application Form</button>
        </div>

        <div class="row">
            <div class="col-auto me-auto"></div>
            <div class="col-auto">
                <input type="date" class=" p-2 rounded border border-1">
                <input type="date" class=" p-2 rounded border border-1">
            </div>
        </div>
        <!-- Content Row lilo -->
        <div class="row mt-2">
            <div class="col-xl-12 col-lg-12">
                <div class="card  mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-secondary">Leave History</h6>
                        <button class="btn radius-1" name="btnRefreshTbl" id="btnRefreshTbl"><i class="font-weight-bold fa fa-refresh fa-sm fa-fw" style="color: #008080"></i></button>
                    </div>
                    <!-- Card Body -->
                    <div class="card-body">
                        <div class="chart-area">
                            <div class="table-responsive border-0">
                                <table class="table table-hover table-border-none  ">
                                    <thead>
                                        <tr>
                                            <th class="text-dark" scope="col">LeaveType</th>
                                            <th class="text-dark" scope="col">FilingDate</th>
                                            <th class="text-dark" scope="col">DateFrom</th>
                                            <th class="text-dark" scope="col">DateTo</th>
                                            <th class="text-dark" scope="col">Duration</th>
                                            <th class="text-dark" scope="col">Purpose</th>
                                            <th class="text-dark" scope="col">Status</th>
                                            <th class="text-dark" scope="col">Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tblLeaveApp">

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal LEAVE APPLICATION Form-->
        <div class="modal fade" id="mdlLeaveApp" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header dragable_touch" >
                        <h5 class="modal-title" id="staticBackdropLabel"><label for="" class="" id="lblTitleLeaveApp"> Leave Application Form</label></h5>
                        <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="card mb-3 rounded">
                            <div class="card-body ">

                                <form action="" id="frmLeaveApp">
                                    <div class="row">
                                        <div class="col-xl-6 col-lg-12">
                                            <div class="row">
                                                <div class="col-lg-12 mb-2">
                                                    <div class="form-floating">
                                                        <input class="form-control text-capitalize" id="txtPersonnel" value="{{ $user->fname . ' ' . $user->mname . ' ' . $user->lname }}" name="personnel" type="text" placeholder="-" readonly/>
                                                        <label class="form-check-label" for="txtPersonnel">Personnel Name <label for="" class="text-danger">*</label></label>
                                                        <span class="text-danger small error-text personnel_error"></span>
                                                    </div>
                                                </div>

                                                <div class="col-lg-12 mb-2">
                                                    <div class="form-floating">
                                                    <input class="form-control" id="txtCompany" value="{{ $employeeDetails->company->comp_name }}" name="company" type="text" placeholder="-" readonly/>
                                                        <label class="form-check-label" for="txtCompany">Company Name <label for="" class="text-danger">*</label></label>
                                                        <span class="text-danger small error-text company_error"></span>
                                                    </div>
                                                </div>

                                                <div class="col-lg-12 mb-2">
                                                    <div class="form-floating">
                                                        <input class="form-control" id="txtDepartment" value="{{ $employeeDetails->department->dep_name }}" name="department" type="text" placeholder="-" readonly/>
                                                        <label class="form-check-label" for="txtDepartment">Department<label for="" class="text-danger">*</label></label>
                                                        <span class="text-danger small error-text department_error"></span>
                                                    </div>
                                                </div>

                                                <div class="col-lg-12 mb-2">
                                                    <div class="form-floating">
                                                        <input class="form-control" id="txtDesignation" value="{{ $employeeDetails->position->pos_desc }}" name="designation" type="text" placeholder="-" readonly/>
                                                        <label class="form-check-label" for="txtDesignation">Designation<label for="" class="text-danger">*</label></label>
                                                        <span class="text-danger small error-text designation_error"></span>
                                                    </div>
                                                </div>

                                                <div class="col-lg-12 mb-2">
                                                    <div class="form-floating">
                                                        <select  class="form-control" name="leavekind" id="selLeaveKind">
                                                            <option value="0">Paid</option>
                                                            <option value="1">Unpaid</option>
                                                        </select>
                                                        <label  class="form-check-label" for="missionobjective" class="text-muted">Leave Kind<label for="" class="text-danger">*</label></label>
                                                        <span class="text-danger small error-text leavekind_error"></span>
                                                    </div>
                                                </div>

                                                <div class="col-lg-12 mb-2">
                                                    <div class="form-floating">
                                                        <select  class="form-control" name="leavetype" id="selLeaveType">
                                                            @foreach ($leaveTypes as $leaveType)
                                                                <option value="{{ $leaveType->id }}">{{ $leaveType->type_leave }}</option>
                                                            @endforeach
                                                        </select>
                                                        <label  class="form-check-label" for="missionobjective" class="text-muted">Leave Type<label for="" class="text-danger">*</label></label>
                                                        <span class="text-danger small error-text leavetype_error"></span>
                                                    </div>
                                                </div>

                                                <div class="col-lg-12 mb-2">
                                                    <div class="form-group">
                                                        <label class="form-check-label" for="purpose"> Explanation / Purpose of Leave <label for="" class="text-danger"></label></label>
                                                        <textarea class="form-control" id="txtPurposeRem" name="purpose" rows="4" placeholder=""></textarea>
                                                        <span class="text-danger small error-text purpose_error"></span>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                        <div class="col-xl-6 col-lg-12">
                                            <div class="row">
                                                <div class="col-lg-12 mb-2">
                                                    <div class="form-group" style="border: 1px solid #008080;background-color: #008080;padding: 5px;border-radius: 10px;color: #fff;">
                                                    {{-- <div class="form-group" style="border: 1px solid red;background-color: #bc0c0c;padding: 5px;border-radius: 10px;color: #fff;"> --}}
                                                        <label>Leave Credits:</label>
                                                        <input name="leavecredits" type="text" id="txtLeaveCredits" style="color:#008080;" readonly="readonly" value="-" class="form-control">
                                                        <span class="text-danger small error-text leavecredits_error"></span>
                                                    </div>
                                                </div>

                                                <div class="col-lg-12 mb-2">
                                                    <div class="form-floating">
                                                        <input class="form-control" id="date_from" name="date_from" type="date" placeholder="-"/>
                                                        <label class="form-check-label" for="date_from">From <label for="" class="text-danger">*</label></label>
                                                        <span class="text-danger small error-text date_from_error"></span>
                                                    </div>
                                                </div>

                                                <div class="col-lg-12 mb-2">
                                                    <div class="form-floating">
                                                        <input class="form-control" id="date_to" name="date_to" type="date" placeholder="-"/>
                                                        <label class="form-check-label" for="txtLEndDate">To<label for="" class="text-danger">*</label></label>
                                                        <span class="text-danger small error-text date_to_error"></span>
                                                    </div>
                                                </div>

                                                <div class="col-lg-12">
                                                    <div class="ihd-dis" style="float: right;">
                                                        <input type="checkbox" class="form-check-input" style="position: relative;" id="chkHalfDay" name="halfday">
                                                        <label class="form-check-label" for="chkHalfDay">If Half Day?</label>
                                                    </div>
                                                </div>

                                                <div class="col-lg-12 mb-2">
                                                    <div class="form-floating">
                                                        <input class="form-control" id="txtDurationDays" name="days" type="number" placeholder="-" readonly/>
                                                        <label class="form-check-label" for="txtDurationDays">Duration Days<label for="" class="text-danger">*</label></label>
                                                        <span class="text-danger small error-text days_error"></span>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <!-- <button type="button" class="btn btn-secondary closereset_update" data-bs-dismiss="modal">Close</button> -->
                        <button  id="btnSaveLeave" type="button" class="btn text-white" style="background-color: #008080">Submit</button>
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

                axios.post('/pages/modules/leave',formData) 
                .then(function (response) {
                    if (response.data.status == 201) {
                        $.each(response.data.error, function(prefix, val) {
                            $('input[name='+ prefix +']').addClass(" border border-danger") ;
                            $('span.' + prefix + '_error').text(val[0]);
                        });

                        return
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


        })
    </script>
@endsection
