@extends('layout.app')
@section('content')

<!--SHAIRA-->
<div class="container-fluid">

    <div class="mb-2">
        <h4 class=" mb-0 text-gray-800">Send to Official Business Trip</h4>
        <button class=" mt-3 btn btn-danger radius-1" name="btnCreateSendOBTModal" id="btnCreateSendOBTModal" data-bs-toggle="modal" data-bs-target="#mdlSendOBT"> <i class="fa fa-plus"></i> Send to OBT Form</button>
    </div>

    <!-- Page Heading -->
    {{-- <div class="d-sm-">
        <h4 class=" mb-0 text-gray-800">Official Business Trip Tracker</h4>
        <button class=" mt-3 btn btn-danger radius-1 btn-sm" name="department" id="btnCreateDept" data-bs-toggle="modal" data-bs-target="#mdlDepartment"> <i class="fa fa-plus"></i> Official Business Trip Form</button>

        <!-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50"></i> Generate Report </a> -->
    </div> --}}

    <div class="row">
        <div class="col-auto me-auto"></div>
        <div class="col-auto">
            <!-- <h5 class=" mb-0 text-danger">Filter:</h5>    -->
            <input type="date" id="txtDateFromTop" class=" p-2 rounded border border-1">
            <input type="date" id="txtDateToTop" class=" p-2 rounded border border-1">
        </div>
    </div>
       <!-- Content Row lilo -->
    <div class="row mt-2">
        <div class="col-xl-12 col-lg-12">
            <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-secondary">OB History  </h6>
                    <button class="btn radius-1" name="btnRefreshTbl" id="btnRefreshTbl"><i class="font-weight-bold fa fa-refresh fa-sm fa-fw text-danger"></i></button>
                     {{--<i class="font-weight-bold fa fa-refresh fa-sm fa-fw text-danger"></i> --}}
                </div>
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-area">
                        <div class="table-responsive border-0">
                            <table class="table table-hover table-border-none  ">
                                <thead>
                                    <tr>
                                        <th class="text-dark" scope="col">FilingDate</th>
                                        <th class="text-dark" scope="col">Name</th>
                                        <th class="text-dark" scope="col">DateFrom</th>
                                        <th class="text-dark" scope="col">DateTo</th>
                                        <th class="text-dark" scope="col">ItineraryTo</th>
                                        <th class="text-dark" scope="col">Purpose</th>
                                        <th class="text-dark" scope="col">CashAdvance</th>
                                        <th class="text-dark" scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="tblSendOBT">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

     <!-- Modal OOBT Form-->
     <div class="modal fade" id="mdlSendOBT" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-danger dragable_touch" >
                    <h5 class="modal-title fst-italic text-white" id="staticBackdropLabel"><label for="" class="" id="lblTitleOBT"> Send to Trip Form</label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card mb-3 rounded">
                        <div class="card-body ">

                            <form action="" id="frmSendOBT">
                                <div class="row">
                                    <div class="col-lg-6">

                                        <div class="row">
                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtPersonnel" name="personnel" type="text" placeholder="-"/>
                                                    <label class="form-check-label" for="txtPersonnel">Personnel Name <label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text personnel_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtCompany" name="company" type="text" placeholder="-" readonly/>
                                                    <label class="form-check-label" for="txtCompany">Company Name<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text company_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtDept" name="department" type="text" placeholder="-" readonly/>
                                                    <label class="form-check-label" for="txtDept">Department <label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text department_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtDesignation" name="designation" type="text" placeholder="-" readonly/>
                                                    <label class="form-check-label" for="txtDesignation">Designation <label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text designation_error"></span>
                                                </div>
                                            </div>


                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="row">
                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtFilingDate" name="dateFil" type="text" placeholder="-" readonly/>
                                                    <label class="form-check-label" for="txtDateFiling">Filing Date <label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text dateFil_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtDateFromOB" name="dateFrom" type="date" placeholder="-"/>
                                                    <label class="form-check-label" for="txtDateFromOB">OB Date To <label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text dateFrom_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtDateToOB" name="dateTo" type="date" placeholder="-"/>
                                                    <label class="form-check-label" for="txtDateToOB">OB Date To <label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text dateTo_error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div> <br>


                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="row">
                                            <h5 class="my-1 text-gray-800">ITINERARY</h5>
                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtItineraryFrom" name="itineraryF" type="text" placeholder="-" readonly/>
                                                    <label class="form-check-label" for="txtItineraryFrom">From <label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text itineraryF_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtItineraryTo" name="itineraryT" type="text" placeholder="-"/>
                                                    <label class="form-check-label" for="txtItineraryTo">To<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text itineraryT_error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div> <br>

                                    <div class="col-lg-4">

                                        <div class="row">
                                            <h5 class="my-1 text-gray-800">PURPOSE</h5>
                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <label for="txtPurposeRem" class="form-label"></label>
                                                    <textarea class="form-control" id="txtPurposeRem" name="purpose" rows="3"></textarea>
                                                    <span class="text-danger small error-text purpose_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtCAAmount" name="amount" type="number" placeholder="-"/>
                                                    <label class="form-check-label" for="txtCAAmount">Cash Advance Amount <label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text amount_error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">

                                        <div class="row">
                                            <h5 class="my-1 text-gray-800">INCLUSIVE TIME</h5>
                                            <div class="col-lg-6 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtTimeDeparture" name="departure" type="time" placeholder="-"/>
                                                    <label class="form-check-label" for="txtTimeDeparture">Departure<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text departure_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-6 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtTimeReturn" name="return" type="time" placeholder="-"/>
                                                    <label class="form-check-label" for="txtTimeReturn">Return <label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text return_error"></span>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="row">
                                            <div class="col-lg-12 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtPurposeITime" name="purposeI" type="text" placeholder="-"/>
                                                    <label class="form-check-label" for="txtPurposeITime">Purpose <label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text purposeI_error"></span>
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
                    <button  id="btnSaveSendOBT" type="button" class="btn btn-secondary ">Submit</button>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
