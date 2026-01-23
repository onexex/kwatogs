@extends('layout.app')
@section('content')

<!--SHAIRA-->

<div class="container-fluid">
    <div class="mb-2">
        <h4 class="text-gray-800 mb-3">SSS (Social Security System)</h4>
        <button class=" mt-3 btn btn-details radius-1" name="btnCreateSSS" id="btnCreateSSS" data-bs-toggle="modal" data-bs-target="#mdlSSS"> <i class="fa fa-plus"></i> SSS</button>
    </div>

     <!-- Content Row dar -->
     <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card mb-4">
                <!-- Card Body -->
                <div class="card-body table-responsive">
                    <div class="chart-area overflow-auto">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th class="text-dark" scope="col">SSSC</th>
                                    <th class="text-dark" scope="col">Salary From</th>
                                    <th class="text-dark" scope="col">Salary To</th>
                                    <th class="text-dark" scope="col">SSER</th>
                                    <th class="text-dark" scope="col">SSEE</th>
                                    <th class="text-dark" scope="col">SSEC</th>
                                    <th class="text-dark" scope="col">Update</th>
                                </tr>
                            </thead>
                            <tbody id="tblSSS">

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal SSS -->
    <div class="modal fade" id="mdlSSS" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header dragable_touch" >
                    <h5 class="modal-title" id="staticBackdropLabel"><label for="" class="lblActionDesc" id="lblTitleSSS"> Social Security System</label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card rounded">
                        <div class="card-body ">

                            <form action="" id="frmSSS">
                                <div class="row">
                                    <div class="col-xl-6 col-lg-12">
                                        <div class="form-floating mb-2">
                                            <input class="form-control" id="txtSSSC" name="sssc" type="number" placeholder="-"/>
                                            <label class="form-check-label" for="txtSSSC">SSSC<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text sssc_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-xl-6 col-lg-12">
                                        <div class="form-floating mb-2">
                                            <input class="form-control" id="txtSalaryFrom" name="salaryfrom" type="number" placeholder="-"/>
                                            <label class="form-check-label" for="txtSalaryFrom">Salary From<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text salaryfrom_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-12">
                                        <div class="form-floating mb-2">
                                            <input class="form-control" id="txtSalaryTo" name="salaryto" type="number" placeholder="-"/>
                                            <label class="form-check-label" for="txtSalaryTo">Salary To<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text salaryto_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-xl-6 col-lg-12">
                                        <div class="form-floating mb-2">
                                            <input class="form-control" id="txtSSER" name="sser" type="number" placeholder="-"/>
                                            <label class="form-check-label" for="txtSSER">SSER<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text sser_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-12">
                                        <div class="form-floating mb-2">
                                            <input class="form-control" id="txtSSEE" name="ssee" type="number" placeholder="-"/>
                                            <label class="form-check-label" for="txtSSEE">SSEE<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text ssee_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-xl-6 col-lg-12">
                                        <div class="form-floating mb-2">
                                            <input class="form-control" id="txtSSEC" name="ssec" type="number" placeholder="-"/>
                                            <label class="form-check-label" for="txtSSEC">SSEC<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text ssec_error"></span>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button  id="btnSaveSSS" type="button" class="btn btn-details">Create</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="{{ asset('js/settings/sss.js') }}"  defer></script>

@endsection

