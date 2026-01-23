@extends('layout.app')
@section('content')
<div class="container-fluid">
    <div class="mb-2">
        <h4 class="text-gray-800 mb-3" id="jobTitle">Parental Family Details</h4>
        <button class=" mt-3 btn btn-details radius-1" id="btnAddFamily" data-bs-toggle="modal" data-bs-target="#mdlFamily"> Parental Family Details <i class="fa fa-plus"></i> </button>
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
                                        <th scope="col">#</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Employee Name</th>
                                        <th scope="col">Birthday</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tblFamily">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

  <!-- Modal -->
    <div class="modal fade" id="mdlFamily" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header dragable_touch">
                    <h5 class="modal-title title" id="staticBackdropLabel"><label class="lblTitleModal" for=""> Creating Family Details </label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card  mb-3 rounded">
                        <div class="card-body ">

                            <form action="" id="frmFamily">

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtFamily" name="family" type="text" placeholder="Name of Family"/>
                                            <label for="txtFamily">Name of Family <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text family_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <select  class="form-control" name="employee" id="selEmployee" placeholder="employee">
                                                <option selected></option>
                                                @if(count($resultEmp)>0)
                                                    @foreach($resultEmp as $resultEmpS)
                                                                                                        <!--CONCAT-->
                                                    <option value='{{ $resultEmpS->empID }}'>{{ $resultEmpS->lname . " " . $resultEmpS->fname}}</option>
                                                    @endforeach
                                                @else

                                                @endif
                                            </select>
                                            <label for="selEmployee">Employee Name<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text employee_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="dateBirth" name="birthday" type="date" placeholder="Date"/>
                                            <label for="dateBirth">Date of Birth <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text birthday_error"></span>
                                        </div>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button  id="btnSaveFamily" type="button" class="btn btn-details">Save Entries</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/parentalSettings.js') }}" defer></script>
@endsection
