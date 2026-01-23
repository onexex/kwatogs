@extends('layout.app')
@section('content')

<!--SHAIRA-->

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between ">
        <h4 class=" mb-4 text-gray-800">Check Writer</h4>
        <!-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50"></i> Generate Report </a> -->
    </div>

    <div class="row mb-4">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="card rounded">
                <div class="card-body">

                    <form action="" id="frmCheckReg">
                        <div class="row">
                            <div class="col-lg-5 col-md-12 col-sm-12">
                                <div class="row py-2 pl-2">

                                    <div class="col-lg-12 col-md-12 col-sm-12">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtBankInfo" name="bankinfo" type="text" placeholder="-"/>
                                            <label class="form-check-label" for="txtBankInfo">Bank Info<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text bankinfo_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12 col-md-12 col-sm-12">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtCheckNum" name="number" type="text" placeholder="-"/>
                                            <label class="form-check-label" for="txtCheckNum">Check Number<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text number_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12 col-md-12 col-sm-12">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtPayee" name="payee" type="text" placeholder="-"/>
                                            <label class="form-check-label" for="txtPayee">Payee<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text payee_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12 col-md-12 col-sm-12">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtDateCheck" name="date" type="date" placeholder="-"/>
                                            <label class="form-check-label" for="txtDateCheck">Date of Check<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text date_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12 col-md-12 col-sm-12">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtCheckAmount" name="amount" type="text" placeholder="-"/>
                                            <label class="form-check-label" for="txtCheckAmount">Check Amount <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text amount_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12 col-md-12 col-sm-12">
                                        <div class="form-group mb-3">
                                            <label class="form-check-label" for="txtRemakrs"> Remarks <label for="" class="text-danger"></label></label>
                                            <textarea class="form-control" id="txtRemakrs" name="remarks" rows="4" placeholder=""></textarea>
                                            <span class="text-danger small error-text remarks_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 col-md-6 col-sm-12">
                                        <button class="btn btn-danger radius-1" name="btnSaveConfirm" id="btnSaveConfirm" type="button"> <i class="fa-solid fa-floppy-disk"></i> Save and Confirm</button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-7 col-md-7 col-sm-12">
                                <div class="row p-4">
                                    <div class="col-lg-12 col-md-12 col-sm-12">
                                        <label for="">For Print</label>
                                        <p class="bg-light p-2" style="height: 100px">
                                            SAMPLE . . .
                                        </p>
                                    </div>

                                    <div class="col-lg-12 col-md-12 col-sm-12">
                                        <button class="btn btn-danger radius-1" name="btnPrintCheck" id="btnPrintCheck" type="button"> <i class="fa-solid fa-floppy-disk"></i> Print</button>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>

    <hr>


    <div class="d-sm-flex align-items-center justify-content-between ">
        <h4 class=" mb-4 text-gray-800">Check Register History</h4>
    </div>

    <div class="row px-4">
        <div class="col-lg-12 col-md-12 col-sm-12">

            <div class="row mb-3">
                {{-- <div class="col-auto me-auto"></div> --}}
                <div class="col-auto">
                    <!-- <h5 class=" mb-0 text-danger">Filter:</h5>    -->
                    <input type="date" id="txtDateFrom" class=" p-2 rounded border border-1">
                    <input type="date" id="txtDateTo" class=" p-2 rounded border border-1">
                    <button class="btn radius-1 btn-danger btn-sm mb-1 ml-1" name="btnRefresh" id="btnRefresh"><i class="font-weight-bold fa fa-refresh fa-sm fa-fw text-white"></i></button>
                </div>
            </div>

            <div class="row">

                <div class="col-lg-4 col-md-4 col-sm-12">
                    <div class="form-floating mb-2">
                        <select  class="form-control" name="payeeFil" id="selPayeeFilter">
                            <option value="-">-</option>
                        </select>
                        <label  class="form-check-label" for="selPayeeFilter" class="text-muted">Payee Filter<label for="" class="text-danger">*</label></label>
                        <span class="text-danger small error-text payeeFil_error"></span>
                    </div>
                </div>

                <div class="col-lg-4 col-md-4 col-sm-12">
                    <div class="form-floating mb-2">
                        <select  class="form-control" name="bank" id="selBankFilter">
                            <option value="-">-</option>
                        </select>
                        <label  class="form-check-label" for="selBankFilter" class="text-muted">Bank Filter<label for="" class="text-danger">*</label></label>
                        <span class="text-danger small error-text bank_error"></span>
                    </div>
                </div>

                <div class="col-lg-2 col-md-4 col-sm-12">
                    <div class="form-floating mb-2">
                        <select  class="form-control" name="orderBy" id="selOrderBy">
                            <option value="-">-</option>
                        </select>
                        <label  class="form-check-label" for="selOrderBy" class="text-muted">Order By<label for="" class="text-danger">*</label></label>
                        <span class="text-danger small error-text orderBy_error"></span>
                    </div>
                </div>

                <div class="col-lg-2 col-md-2 col-sm-12">
                    <div class="form-floating mb-2">
                        <select  class="form-control" name="sortBy" id="selSortBy">
                            <option value="-">-</option>
                        </select>
                        <label  class="form-check-label" for="selSortBy" class="text-muted">Sort By<label for="" class="text-danger">*</label></label>
                        <span class="text-danger small error-text sortBy_error"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="row mt-2">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="card mb-3">
                <!-- Card Body -->
                <div class="card-body">
                    {{-- <div class="chart-area"> --}}
                        <div class="table-responsive border-0">
                            <table class="table table-hover table-border-none  ">
                                <thead>
                                    <tr>
                                        <th class="text-dark" scope="col">#</th>
                                        <th class="text-dark" scope="col">Payee</th>
                                        <th class="text-dark" scope="col">Bank</th>
                                        <th class="text-dark" scope="col">CheckNumber</th>
                                        <th class="text-dark" scope="col">CheckAmount</th>
                                        <th class="text-dark" scope="col">CheckDate</th>
                                        <th class="text-dark" scope="col">Status</th>
                                        <th class="text-dark" scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tblCheckRegister">

                                </tbody>
                            </table>
                        </div>
                    {{-- </div> --}}
                </div>
            </div>
        </div>

        <div class="col-lg-12 col-md-12 col-sm-12">
            <button class="btn btn-danger radius-1" name="btnPrintTbl" id="btnPrintTbl" type="button"> <i class="fa-solid fa-print"></i> Print</button>
        </div>

    </div>

</div>

@endsection
