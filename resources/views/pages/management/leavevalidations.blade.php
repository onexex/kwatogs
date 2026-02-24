@extends('layout.app', [
    'title' => 'Leave Validation'
])
@section('content')

<!--SHAIRA-->

<div class="container-fluid">
    <div class="mb-2">
        <h4 class="text-gray-800 mb-3">Leave Validations</h4>
        <button class=" mt-3 btn radius-1 btn-blue" name="btnCreateLeave" id="btnCreateLeave" data-bs-toggle="modal" data-bs-target="#mdlUpdateLeave"> <i class="fa fa-plus"></i> Leave Validation</button>
    </div>

     <!-- Content Row dar -->
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
                                        <th class="text-dark" scope="col">Company Name</th>
                                        <th class="text-dark" scope="col">Leave</th>
                                        <th class="text-dark" scope="col">Credits</th>
                                        <th class="text-dark" scope="col">Minimum Leave</th>
                                        <th class="text-dark" scope="col">No. of Day filling Before Leave</th>
                                        <th class="text-dark" scope="col">No. of Day filling After Leave</th>

                                        <th class="text-dark" scope="col">File Before</th>
                                        <th class="text-dark" scope="col">File After</th>
                                        <th class="text-dark" scope="col">File HalfDay</th>
                                        <th class="text-dark" scope="col">Pre Allocated</th>
                                        <th class="text-dark" scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tblLeave">
                                    <!-- <tr>
                                        <td>Wedo</td>
                                        <td>Vacation</td>
                                        <td>1</td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>YES</td>
                                        <td>YES</td>
                                        <td>30</td>
                                        <td>NO</td>
                                        <td><button class="btn btn-danger btn-sm radius-1" name="btnUpdateModal" id="btnUpdateModal" data-bs-toggle="modal" data-bs-target="#mdlUpdateLeave"><i class="fa-solid fa-pen"></i></button></td>
                                    </tr> -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Update Leave Validations -->
    <div class="modal fade" id="mdlUpdateLeave" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger  dragable_touch" >
                    <h5 class="modal-title fst-italic text-white lblActionDesc" id="staticBackdropLabel"><label for="" class="" id="lblTitleUpdate"> Update Leave </label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card mb-3 rounded">
                        <div class="card-body">

                            <form action="" id="frmCreateleaveValidation">

                                <div class="row">

                                    <div class="col-lg-6">
                                        <div class="row">

                                            <div class="col-lg-12 mb-1">
                                                <div class="form-floating">
                                                    <select  class="form-control" name="company" id="selCompanyNameU"  >
                                                        @if(count($company)>0)
                                                            @foreach($company as $companys)
                                                            <option value='{{$companys->comp_id }}'>{{$companys->comp_name }}</option>
                                                            @endforeach
                                                        @else

                                                        @endif
                                                    </select>
                                                    <label  class="form-check-label" for="selCompanyNameU" class="text-muted">Company Name<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text company_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-1">
                                                <div class="form-floating">
                                                    <select  class="form-control" name="leave" id="selLeaveTypeU"  >
                                                        @if(count($leaveTypeData)>0)
                                                            @foreach($leaveTypeData as $leaveTypeDatas)
                                                            <option value='{{$leaveTypeDatas->id }}'>{{$leaveTypeDatas->type_leave }}</option>
                                                            @endforeach
                                                        @else

                                                        @endif
                                                    </select>
                                                    <label  class="form-check-label" for="selLeaveTypeU" class="text-muted">Leave Type<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text leave_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-1">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtDaysBefore" name="daysbefore" type="number" placeholder="-"/>
                                                    <label class="form-check-label" for="txtDaysBefore">No of Day Before Leave<label for=""class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text daysbefore_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-1">
                                                <div class="form-floating">
                                                    <select  class="form-control" name="filebefore" id="selFileBefore"  >
                                                        <option value="1">Yes</option>
                                                        <option value="0">No</option>
                                                    </select>
                                                    <label  class="form-check-label" for="selFileBefore" class="text-muted">Can File Before<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text filebefore_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-1">
                                                <div class="form-floating">
                                                    <select  class="form-control" name="halfday" id="selHalfday"  >
                                                        <option value="1">Yes</option>
                                                        <option value="0">No</option>
                                                    </select>
                                                    <label  class="form-check-label" for="selFileAfter" class="text-muted">Half Day<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text halfday_error"></span>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="row">

                                            <div class="col-lg-12 mb-1">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtCreditsU" name="credits" type="number" placeholder="-"/>
                                                    <label class="form-check-label" for="txtCreditsU">Credits<label for=""class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text credits_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-1">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtMinLeaveU" name="minimum" type="number" placeholder="-"/>
                                                    <label class="form-check-label" for="txtMinLeaveU">Minimun Leave<label for=""class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text minimum_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-1">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtDaysAfter" name="daysafter" type="number" placeholder="-"/>
                                                    <label class="form-check-label" for="txtDaysAfter">No of Day After Leave<label for=""class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text daysafter_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-1">
                                                <div class="form-floating">
                                                    <select  class="form-control" name="fileafter" id="selFileAfter"  >
                                                        <option value="1">Yes</option>
                                                        <option value="0">No</option>
                                                    </select>
                                                    <label  class="form-check-label" for="selFileAfter" class="text-muted">Can File After<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text fileafter_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-1">
                                                <div class="form-floating">
                                                    <select  class="form-control" name="pre_allocated" id="selpreAllocated"  >
                                                        <option value="1">Yes</option>
                                                        <option selected value="0">No</option>
                                                    </select>
                                                    <label  class="form-check-label" for="selpreAllocated" class="text-muted">Is Pre-Allocated<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text preAllocated_error"></span>
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
                    <button  id="btnUpdateLeave" type="button" class="btn btn-secondary">Save Entries</button>
                </div>
            </div>
        </div>
    </div>

</div>


<script src="{{ asset('js/settings/leavevalidations.js') }}"  deffer></script>

@endsection

