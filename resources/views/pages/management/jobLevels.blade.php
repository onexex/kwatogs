@extends('layout.app')
@section('content')
<div class="container-fluid">
    <div class="mb-2">
        <h4 class="text-gray-800 mb-3" id="jobTitle">Job Levels</h4>
        <button class=" mt-3 btn btn-details radius-1" id="btnAddJobLevel" data-bs-toggle="modal" data-bs-target="#mdlJobLevel"> Job Levels <i class="fa fa-plus"></i> </button>
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
                                        <th scope="col">Job Level ID</th>
                                        <th scope="col">Job Level</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tblJobLevels">
                                    <!-- <tr>
                                        <td>1</td>
                                        <td>Superuser</td>
                                        <td>
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#mdlJobLevel" id="btnUpdateJobLevel">
                                                <i class="fa-solid fa-pencil"></i>
                                            </button>
                                        </td>
                                    </tr> -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

  <!-- Modal -->
    <div class="modal fade" id="mdlJobLevel" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog   modal-lg">
            <div class="modal-content">
                <div class="modal-header dragable_touch">
                    <h5 class="modal-title title lblActionDesc" id="staticBackdropLabel"><label for=""> Job Level </label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card  mb-3 rounded">
                        <div class="card-body ">

                            <form action="" id="frmJobLevels">
                                <div class="row">
                                    <!-- <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <select  class="form-control" name="company" id="selCompany" placeholder="Company">

                                            </select>
                                            <label for="selCompany">Company <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text company_error"></span>
                                        </div>
                                    </div> -->

                                    <div class="col-lg-12">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtJobLevel" name="job" type="text" placeholder="Job Level"/>
                                            <label for="txtJobLevel">Job Level <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text job_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtJobLevelID" name="jobID" type="text" placeholder="Job Level ID"/>
                                            <label for="txtJobLevelID">Job Level ID<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text jobID_error"></span>
                                        </div>
                                    </div>
                                </div> -->

                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button  id="btnSaveJobLevel" type="button" class="btn btn-details">Save Entries</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/joblevels.js') }}" defer></script>
@endsection
