@extends('layout.app')
@section('content')

<!--SHAIRA-->

<div class="container-fluid">
    <div class="mb-2">
        <h4 class="text-gray-800 mb-3">Pag-ibig</h4>
        <button class=" mt-3 btn btn-details radius-1" name="btnCreatePagibig" id="btnCreatePagibig" data-bs-toggle="modal" data-bs-target="#mdlPagibig"> <i class="fa fa-plus"></i> Pag-ibig</button>
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
                                        <th class="text-dark" scope="col">EE</th>
                                        <th class="text-dark" scope="col">ER</th>
                                        <th class="text-dark" scope="col">Update</th>
                                    </tr>
                                </thead>
                                <tbody id="tblPagibig">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Department -->
    <div class="modal fade" id="mdlPagibig" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header dragable_touch" >
                    <h5 class="modal-title" id="staticBackdropLabel"><label for="" class="lblActionDesc" id="lblTitlePagibig"> Pag-ibig</label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card rounded">
                        <div class="card-body ">

                            <form action="" id="frmPagibig">
                                <div class="row">
                                    <div class="col-xl-6 col-lg-12">
                                        <div class="form-floating mb-2">
                                            <input class="form-control" id="txtEE" name="ee" type="number" placeholder="-"/>
                                            <label class="form-check-label" for="txtEE">EE<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text ee_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-12">
                                        <div class="form-floating mb-2">
                                            <input class="form-control" id="txtER" name="er" type="number" placeholder="-"/>
                                            <label class="form-check-label" for="txtER">ER<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text er_error"></span>
                                        </div>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <!-- <button type="button" class="btn btn-secondary closereset_update" data-bs-dismiss="modal">Close</button> -->
                    <button  id="btnPagibigModal" type="button" class="btn btn-details">Create</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="{{ asset('js/settings/pagibigcontribution.js') }}"  deffer></script>

@endsection

