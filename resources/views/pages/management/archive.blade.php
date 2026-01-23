@extends('layout.app')
@section('content')

<style>
    .fixTableHead {
      overflow-y: auto;
      height: 100%;
    }
    .fixTableHead thead th {
      position: sticky;
      top: 0;
      background-color: #f1f1f1;
    }
</style>

<div class="container-fluid">
    <div class="mb-4">
        <h4 class="text-gray-800 mb-3">Archive Management System</h4>
        <button class=" mt-3 btn btn-details radius-1" id="btnRegEmployee" data-bs-toggle="modal" data-bs-target="#mdlRegEmployee"> Register Employee <i class="fa fa-plus"></i> </button>
    </div>

    <div class="row mb-2">

        <div class="col-lg-4">
            <form  action='' id="frmSearch">
                <div class="form-floating mb-3">
                    <input class="form-control" id="txtSearchEmp" name="search" type="text" placeholder="Search Employee"/>
                    <label for="txtSearchEmp">Search Last Name</label>
                    <span class="text-danger small error-text search_error"></span>
                </div>
            </form>
        </div>

    </div>
     <!-- Content Row dar -->
     <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card   mb-4">
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-area overflow-auto;">
                        <div class="table-responsive fixTableHead">
                            <table class="table table-hover table-scroll sticky">
                                <thead style="background-color: #f1f1f1; ">
                                    <tr>
                                        <th scope="col">No</th>
                                        <th scope="col">Agency Name</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tblEmployee">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

  <!-- Modal -->
    <div class="modal fade" id="mdlRegEmployee" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header dragable_touch">
                    <h5 class="modal-title title" id="staticBackdropLabel"><label for=""> Employee Registration </label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card  mb-3 rounded">
                        <div class="card-body ">

                            <form action="" id="frmRegEmp">
                                <div class="row mb-3">

                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtFname" name="fname" type="text" placeholder="First Name"/>
                                            <label for="txtFname">First Name <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text fname_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtLname" name="lname" type="text" placeholder="Last Name"/>
                                            <label for="txtLname">Last Name <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text lname_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="form-floating mb-1">
                                            {{-- <select  class="form-control" name="position" id="selPosition"  >
                                                <option value="">Choose...</option>
                                                <option value="1">programmer</option>
                                                <option value="2">IT Supervisor</option>
                                                <option value="3">Agent</option>
                                                <option value="4">IT Support Specialist</option>
                                                <option value="5">Graphic Designer</option>
                                            </select> --}}
                                            <select class="form-select" aria-label="Default select example" id="selPosition" name="position">
                                                <option value="">Choose...</option>
                                                 @if(count($result)>0)
                                                     @foreach($result as $results)
                                                     <option value='{{ $results->id }}'>{{ $results->pos_desc }}</option>
                                                     @endforeach
                                                 @else

                                                 @endif
                                             </select>
                                            <label for="selPosition" class="text-muted"> Position<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text position_error"></span>
                                        </div>
                                    </div>

                                </div>

                                <div class="row">

                                    <p class="font-weight-bolder fs-6">Emloyment Date</p>
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtDateFrom" name="datefrom" type="date" placeholder="Date From"/>
                                            <label for="txtFrom">From <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text datefrom_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtDateTo" name="dateto" type="date" placeholder="Date To"/>
                                            <label for="txtTo">To <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text dateto_error"></span>
                                        </div>
                                    </div>

                                </div>

                                <div class="row mb-3">
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-1">
                                            <select  class="form-control" name="status" id="selStatus"  >
                                                <option value="">Choose...</option>
                                                <option value="1">Back End</option>
                                                <option value="2">IC</option>
                                            </select>
                                            {{-- <select class="form-select" aria-label="Default select example" id="selPosition" name="position">
                                                <option value="">Choose...</option>
                                                 @if(count($result)>0)
                                                     @foreach($result as $results)
                                                     <option value='{{ $results->id }}'>{{ $results->pos_desc }}</option>
                                                     @endforeach
                                                 @else

                                                 @endif
                                             </select> --}}

                                            <label for="selStatus" class="text-muted"> Status<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text status_error"></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-1">
                                            <select  class="form-control" name="clearance" id="selClearance"  >
                                                <option value="">Choose...</option>
                                                <option value="1">Yes</option>
                                                <option value="2">No</option>
                                            </select>
                                            <label for="selClearance" class="text-muted"> Clearance<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text clearance_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtReason" name="reason" type="text" placeholder="Reason for Leaving "/>
                                            <label for="txtReason">Reason for Leaving <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text reason_error"></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="txtDerogatory">Derogatory Records</label>
                                            <textarea rows="4" class="form-control" id="txtDerogatory" name="derogatory"></textarea>
                                            <span class="text-danger small error-text derogatory_error"></span>
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtSalary" name="salary" type="number" placeholder="Salary"/>
                                            <label for="txtSalary">Salary <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text salary_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtResignation" name="resignation" type="text" placeholder="Resignation"/>
                                            <label for="txtResignation">Pending Resignation <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text resignation_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="txtDerogatory">Additional Remarks</label>
                                            <textarea rows="4" class="form-control" id="txtRemarks" name="remarks"></textarea>
                                            <span class="text-danger small error-text remarks_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtVerify" name="verify" type="text" placeholder="Verify"/>
                                            <label for="txtVerify">Verified By <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text verify_error"></span>
                                        </div>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button  id="btnSaveEmployee" type="button" class="btn btn-details ">Save Entries</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/archive.js') }}" defer></script>
@endsection
