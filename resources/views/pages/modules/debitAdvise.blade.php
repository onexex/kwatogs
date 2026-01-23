@extends('layout.app')
@section('content')

<!--SHAIRA-->

<div class="container-fluid">

        <div class="form-group m-1">
            <div class="row">
                <div class="col-xl-12 col-lg-12">
                    <h4 class="text-gray-800">Debit Advise Settings</h4>
                    <label for="" class="label text-xs rounded m-0 mr-1"><u> Update Bank Contact Information </u></label>
                    <label for="" class="label text-xs rounded m-0 mr-1"><u> Register Bank Information </u></label>
                    <label for="" class="label text-xs rounded m-0 mr-1"><u> Register Bank Contact Information </u></label>

                    <hr>
                    <div class="">
                        <button class="btn btn-danger radius-1" name="btnRegisterModal" id="btnRegisterModal" data-bs-toggle="modal" data-bs-target="#mdlRegister"> <i class="fa fa-plus"></i> Register</button>
                        <button class="btn btn-danger radius-1" name="btnRefreshTbl" id="btnRefreshTbl"><i class="font-weight-bold fa fa-refresh fa-fw"></i></button>
                    </div>

                </div>
            </div>
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
                <div class="card-header d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-secondary">List</h6>
                     {{--<i class="font-weight-bold fa fa-refresh fa-sm fa-fw text-danger"></i> --}}
                </div>
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-area">
                        <div class="table-responsive border-0">
                            <table class="table table-hover table-border-none  ">
                                <thead>
                                    <tr>
                                        <th class="text-dark" scope="col">#</th>
                                        <th class="text-dark" scope="col">Name</th>
                                        <th class="text-dark" scope="col">Position</th>
                                        <th class="text-dark" scope="col">Branch</th>
                                        <th class="text-dark" scope="col">CA/SA Account</th>
                                        <th class="text-dark" scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tblDebitAdvise">
                                    <tr>
                                        <td>1</td>
                                        <td>Shaira Lobarbio</td>
                                        <td>IT</td>
                                        <td>SAMPLE 01</td>
                                        <td>
                                            <button class="btn btn-secondary btn-sm radius-1" name="btnAddCASA" id="btnAddCASA" data-bs-toggle="modal" data-bs-target="#mdlCASA"><i class="fa fa-plus"></i> CA/SA</button>
                                        </td>
                                        <td><button class="btn btn-secondary btn-sm radius-1" name="btnUpdateModal" id="btnUpdateModal" data-bs-toggle="modal" data-bs-target="#mdlRegister"><i class="fa-solid fa-pen"></i></button>
                                             <button class="btn btn-danger btn-sm radius-1" name="btnDelete" id="btnDelete"><i class="fa fa-ban"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

     <!-- Modal DEBIT ADVISE Form-->
     <div class="modal fade" id="mdlRegister" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger dragable_touch" >
                    <h5 class="modal-title fst-italic text-white" id="staticBackdropLabel"><label for="" class="" id="lblTitleDA"> Register Bank Contact Information</label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card mb-3 rounded">
                        <div class="card-body ">

                            <form action="" id="frmDebitAdvise">
                                <div class="row">
                                    <div class="col-lg-12">

                                        <div class="row">
                                            <div class="col-lg-2 mb-2">
                                                <div class="form-floating">
                                                    <select  class="form-select" name="salutation" id="selSalutation" placeholder="-">
                                                        <option value="0">Ms.</option>
                                                        <option value="0">Mrs.</option>
                                                        <option value="0">Mr.</option>
                                                    </select>
                                                    <label  class="form-check-label" for="selSalutation" class="text-muted">Leave Type<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text salutation_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-4 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtFname" name="firstname" type="text" placeholder="-"/>
                                                    <label class="form-check-label" for="txtfname">First Name <label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text firstname_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-2 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtInitial" name="initial" type="text" placeholder="-"/>
                                                    <label class="form-check-label" for="txtinitial">Initial<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text initial_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-4 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtLname" name="lastname" type="text" placeholder="-"/>
                                                    <label class="form-check-label" for="txtlname">Last Name<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text lastname_error"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-6 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtPosition" name="position" type="text" placeholder="-"/>
                                                    <label class="form-check-label" for="txtPosition">Position<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text position_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-6 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtBranch" name="branch" type="text" placeholder="-"/>
                                                    <label class="form-check-label" for="txtBranch">Branch<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text branch_error"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-6 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtCityAddress" name="address" type="text" placeholder="-"/>
                                                    <label class="form-check-label" for="txtCityAddress">City Address<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text address_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-6 mb-2">
                                                <div class="form-floating">
                                                    <input class="form-control" id="txtOtherBranch" name="branch" type="text" placeholder="-"/>
                                                    <label class="form-check-label" for="txtOtherBranch">Other Branch<label for="" class="text-danger">*</label></label>
                                                    <span class="text-danger small error-text branch_error"></span>
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
                    <button  id="btnRegBankInfo" type="button" class="btn btn-secondary ">Register</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal CA/SA -->
    <div class="modal fade" id="mdlCASA" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger dragable_touch" >
                    <h5 class="modal-title fst-italic text-white" id="staticBackdropLabel"><label for="" class="" id="lblTitleCASA"> CA/SA link to:</label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card mb-3 rounded">
                        <div class="card-body ">
                            {{-- align-items-center --}}
                            {{-- justify-content-center --}}
                            <form action="" id="formspecsdetails" name="formspecsdetails">
                                <div class="row align-items-center">
                                    <div class="col-lg-7 mb-2">
                                        <div class="form-floating">
                                            <input class="form-control" id="txtAccountNo" name="accountno" type="text" placeholder="-"/>
                                            <label class="form-check-label" for="txtAccountNo">Account Number<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text accountno_error"></span>

                                        </div>
                                    </div>

                                    <div class="col-lg-2 mb-2">
                                        <button id="btnAddCASA" type="button" class="btn btn-secondary"><i class="fa fa-plus"></i>
                                        ADD
                                        </button>
                                    </div>
                                </div> <br>

                                <div class="row">
                                    <div class="col-xl-12 col-lg-12">
                                        {{-- <div class="card mb-2">
                                            <!-- Card Body -->
                                            <div class="card-body"> --}}
                                                {{-- <div class="chart-area"> --}}
                                                    <div class="table-responsive">
                                                        <table class="table table-hover">
                                                            <thead>
                                                                <tr>
                                                                    <th class="text-dark" scope="col">#</th>
                                                                    <th class="text-dark" scope="col">CA/SA Account</th>
                                                                    <th class="text-dark" scope="col">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="tblCASA">
                                                                <tr>
                                                                    <td>1</td>
                                                                    <td>1234567</td>
                                                                    <td><button class="btn btn-danger btn-sm radius-1" name="btnDeleteCASA" id="btnDeleteCASA"><i class="fa fa-ban"></i></button></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                {{-- </div> --}}
                                            {{-- </div>
                                        </div> --}}
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer mb-4">


                </div>
            </div>
        </div>
    </div>


</div>
<script src="{{ asset('js/debitAdvise.js') }}"  deffer></script>

@endsection
