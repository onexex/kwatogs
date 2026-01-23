@extends('layout.app')
@section('content')
<div class="container-fluid">
    <div class="mb-2">
        <h4 class="text-gray-800 mb-3" id="jobTitle">PhilHealth</h4>
        <button class=" mt-3 btn btn-details radius-1" id="btnAddPhilhealth" data-bs-toggle="modal" data-bs-target="#mdlPhilhealth"> PhilHealth <i class="fa fa-plus"></i> </button>
    </div>

     <!-- Content Row dar -->
     <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card   mb-4">
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-area overflow-auto">
                        <div class="table-responsive fixTableHead">
                            <table class="table table-hover table-scroll sticky">
                                <thead style="background-color: #f1f1f1; ">
                                    <tr>
                                        <th scope="col">PHSB</th>
                                        <th scope="col">Salary From</th>
                                        <th scope="col">Salary To</th>
                                        <th scope="col">PHEE</th>
                                        <th scope="col">PHER</th>
                                        <th scope="col">Update</th>
                                    </tr>
                                </thead>
                                <tbody id="tblPhilhealth">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

  <!-- Modal -->
    <div class="modal fade" id="mdlPhilhealth" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header dragable_touch">
                    <h5 class="modal-title lblActionDesc" id="staticBackdropLabel"><label for=""> Philhealth </label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card  mb-3 rounded">
                        <div class="card-body ">

                            <form action="" id="frmPhilhealth">

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="numPHSB" name="PHSB" type="number" placeholder="PHSB"/>
                                            <label for="numPHSB">PHSB <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text PHSB_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="numFrom" name="from" type="number" placeholder="From"/>
                                            <label for="numFrom">Salary From <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text from_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="numTo" name="to" type="number" placeholder="To"/>
                                            <label for="numTo">Salary To <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text to_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="numPHEE" name="PHEE" type="number" placeholder="PHEE"/>
                                            <label for="numPHEE">PHEE <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text PHEE_error"></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="numPHER" name="PHER" type="number" placeholder="PHER"/>
                                            <label for="numPHER">PHER <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text PHER_error"></span>
                                        </div>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button  id="btnSavePhilhealth" type="button" class="btn btn-details">Save Entries</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/philhealth.js') }}" defer></script>
@endsection
