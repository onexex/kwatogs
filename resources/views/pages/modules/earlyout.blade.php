@extends('layout.app')
@section('content')

<!--SHAIRA-->

<div class="container-fluid">

    <div class="mb-2">
        <h4 class=" mb-0 text-gray-800">Early Out Filing System</h4>
        <button class=" mt-3 text-white btn" style="background-color: #008080" name="btnCreateEOModal" id="btnCreateEOModal" data-bs-toggle="modal" data-bs-target="#mdlEarlyOut"> <i class="fa fa-plus"></i> Early Out Form</button>
    </div>

    <div class="row">
        <div class="col-auto me-auto"></div>
        <div class="col-auto">
            <!-- <h5 class=" mb-0 text-danger">Filter:</h5>    -->
            <input type="date" id="txtDateFrom" class=" p-2 rounded border border-1">
            <input type="date" id="txtDateTo" class=" p-2 rounded border border-1">
        </div>
    </div>
       <!-- Content Row lilo -->
    <div class="row mt-2">
        <div class="col-xl-12 col-lg-12">
            <div class="card">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-secondary">Early Out History</h6>
                    <button class="btn radius-1" name="btnRefreshTbl" id="btnRefreshTbl"><i class="font-weight-bold fa fa-refresh fa-sm fa-fw" style="color: #008080"></i></button>
                     {{--<i class="font-weight-bold fa fa-refresh fa-sm fa-fw text-danger"></i> --}}
                </div>
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-area">
                        <div class="table-responsive border-0">
                            <table class="table table-hover table-border-none  ">
                                <thead>
                                    <tr>
                                        <th class="text-dark" scope="col">Purpose</th>
                                        <th class="text-dark" scope="col">DateField</th>
                                        <th class="text-dark" scope="col">Status</th>
                                        <th class="text-dark" scope="col">DateTimeInputed</th>
                                        <th class="text-dark" scope="col">Delete</th>
                                    </tr>
                                </thead>
                                <tbody id="tblEarlyOut">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

      <!-- Modal OVERTIME Form-->
      <div class="modal fade" id="mdlEarlyOut" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header dragable_touch" >
                    <h5 class="modal-title" id="staticBackdropLabel"><label for="" class="" id="lblTitleOBT"> Early Out Form</label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card rounded">
                        <div class="card-body ">

                            <form action="" id="frmEarlyOut">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="row">
                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtPersonnel" name="personnel" type="text" placeholder="-" readonly/>
                                                    <label class="form-check-label" for="txtPersonnel">Personnel Name <label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text personnel_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtCompany" name="company" type="text" placeholder="-" readonly/>
                                                    <label class="form-check-label" for="txtCompany">Company Name <label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text company_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtDepartment" name="department" type="text" placeholder="-" readonly/>
                                                    <label class="form-check-label" for="txtDepartment">Department<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text department_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtDesignation" name="designation" type="text" placeholder="-" readonly/>
                                                    <label class="form-check-label" for="txtDesignation">Designation<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text designation_error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="row">
                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtFilingDate" name="date" type="text" placeholder="-" readonly/>
                                                    <label class="form-check-label" for="txtFilingDate">Filing Date<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text date_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtEODate" name="date" type="date" placeholder="-"/>
                                                    <label class="form-check-label" for="txtEODate">EO Date<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text date_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-2">
                                                <div class="form-group">
                                                    <label class="form-check-label" for="txtPurposeRem"> Purpose <label for="" class="text-danger"></label></label>
                                                    <textarea class="form-control" id="txtPurposeRem" name="purpose" rows="4" placeholder="-" style="height: 100px"></textarea>
                                                    <span class="text-danger small error-text purpose_error"></span>
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
                    <button  id="btnSaveEO" type="button" class="btn text-white" style="background-color: #008080">Submit</button>
                </div>
            </div>
        </div>
    </div>


</div>

@endsection
