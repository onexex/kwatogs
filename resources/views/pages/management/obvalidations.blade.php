@extends('layout.app')
@section('content')

<!--SHAIRA-->
<style>
    .fixTableHead {
      overflow-y: auto;
      height: 100%;
    }
    .fixTableHead thead th {
      position: sticky;
      top: 0;
      background-color: #f1f1f1;
    }
</style>

<div class="container-fluid">
    <div class="mb-2">
        <h4 class="text-gray-800 mb-3">OB Validation</h4>
        <button class=" mt-3 btn btn-danger radius-1" name="btnCreateMdl" id="btnCreateMdl" data-bs-toggle="modal" data-bs-target="#mdlOBVal"> <i class="fa fa-plus"></i> OB Validation</button>
    </div>

     <!-- Content Row dar -->
     <div class="row ">
        <div class="col-xl-12 col-lg-12">
            <div class="card mb-2 mt-1">
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-area overflow-auto">
                        <div class="table-responsive fixTableHead">
                            <table class="table table-scroll sticky">
                                <thead style=" background-color: #f1f1f1;"  >
                                    <tr>
                                        <th class="text-dark" scope="col">Can File Before</th>
                                        <th class="text-dark" scope="col">Days Before</th>
                                        <th class="text-dark" scope="col">Can File After</th>
                                        <th class="text-dark" scope="col">Days After</th>
                                        <th class="text-dark" scope="col">Update</th>

                                    </tr>
                                </thead>
                                <tbody id="tblOBVal">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal OB Validation -->
    <div class="modal fade" id="mdlOBVal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger dragable_touch" >
                    <h5 class="modal-title fst-italic text-white" id="staticBackdropLabel"><label for="" class="" id="lblTitleOB"> OB Validation</label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card mb-3 rounded">
                        <div class="card-body ">

                            <form action="" id="frmOBVal">

                                <div class="row">
                                    <div class="col-lg-6 mb-2">
                                        <div class="form-floating">
                                            <select  class="form-control" name="filebefore" id="selFileBefore"  >
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                            <label  class="form-check-label" for="selFileBefore" class="text-muted">Can File Before?<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text filebefore_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <div class="form-floating">
                                            <input class="form-control" id="txtDaysBefore" name="daysbefore" type="number" placeholder="-"/>
                                            <label class="form-check-label" for="txtDaysBefore">Days Before?<label for=""class="text-danger">*</label></label>
                                            <span class="text-danger small error-text daysbefore_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6 mb-2">
                                        <div class="form-floating">
                                            <select  class="form-control" name="fileafter" id="selFileAfter"  >
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                            <label  class="form-check-label" for="selFileAfter" class="text-muted">Can File After?<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text fileafter_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <div class="form-floating">
                                            <input class="form-control" id="txtDaysAfter" name="daysafter" type="number" placeholder="-"/>
                                            <label class="form-check-label" for="txtDaysAfter">Days After?<label for=""class="text-danger">*</label></label>
                                            <span class="text-danger small error-text daysafter_error"></span>
                                        </div>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <!-- <button type="button" class="btn btn-secondary closereset_update" data-bs-dismiss="modal">Close</button> -->
                    <button  id="btnOBVal" type="button" class="btn btn-secondary ">Update</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="{{ asset('js/settings/obValidations.js') }}"  deffer></script>

@endsection

