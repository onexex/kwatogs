@extends('layout.app')
@section('content')
<div class="container-fluid">
    <div class="mb-2">
        <h4 class="text-gray-800 mb-3" id="jobTitle">SIL Loan</h4>
        <button class=" mt-3 btn btn-details radius-1" id="btnAddSil" data-bs-toggle="modal" data-bs-target="#mdlSil"> SIL Loan <i class="fa fa-plus"></i> </button>
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
                                        <th scope="col">Employee Name</th>
                                        <th scope="col">Loan Amount</th>
                                        <th scope="col">Loan Type</th>
                                        <th scope="col">Loan Status</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tblSil">
                                    {{-- <tr>
                                        <td>Ronnie Arocha</td>
                                        <td>1051.99</td>
                                        <td>PI</td>
                                        <td>ACTIVE</td>
                                        <td>
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#mdlSil" id="btnUpdateSil">
                                                <i class="fa-solid fa-pencil"></i>
                                            </button>
                                        </td>
                                    </tr> --}}

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

  <!-- Modal -->
    <div class="modal fade" id="mdlSil" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header dragable_touch">
                    <h5 class="modal-title lblActionDesc" id="staticBackdropLabel"><label for=""> SIL Loan </label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card  mb-3 rounded">
                        <div class="card-body ">

                            <form action="" id="frmSil">

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <select  class="form-control" name="employee" id="selEmployee" placeholder="employee">
                                                {{-- <option value=""></option>
                                                <option value="admin">admin, admin</option>                                                                                          --}}
                                            </select>
                                            <label for="selEmployee">Employee <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text employee_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="numLoan" name="loan" type="number" placeholder="Loan Amount"/>
                                            <label for="numLoan">Loan Amount <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text loan_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <select  class="form-control" name="type" id="selLoanType">
                                                <option value=""></option>
                                                <option value="SSS">SSS</option>
                                                <option value="SL">Salary Loan</option>
                                                <option value="PagIbig">PAG-IBIG</option>
                                            </select>
                                            <label for="selLoanType">Loan Type <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text type_error"></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <select  class="form-control" name="status" id="selStatus">
                                                <option value=""></option>
                                                <option value="0">INACTIVE</option>
                                                <option value="1">ACTIVE</option>
                                            </select>
                                            <label for="selStatus">Loan Status <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text status_error"></span>
                                        </div>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button  id="btnSaveSil" type="button" class="btn btn-secondary ">Save Entries</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/sil.js') }}" defer></script>
@endsection
