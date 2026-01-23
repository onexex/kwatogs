@extends('layout.app')
@section('content')

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
        <h4 class="text-gray-800 mb-3" id="jobTitle">EO Validation</h4>
        <button class=" mt-3 btn btn-danger radius-1" id="btnAddEO" data-bs-toggle="modal" data-bs-target="#mdlEO"> Early Out Validation <i class="fa fa-plus"></i> </button>
    </div>

     <!-- Content Row dar -->

    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card  mb-4">
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-area overflow-auto;">
                        <div class="table-responsive fixTableHead">
                            <table class="table table-hover table-scroll sticky">
                                <thead style="background-color: #f1f1f1; ">
                                    <tr>
                                        <th scope="col">Can File Before</th>
                                        <th scope="col">Can File After</th>
                                        <th scope="col">Can File with Tardy</th>
                                        <th scope="col">Update</th>
                                    </tr>
                                </thead>
                                <tbody id="tblEO">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

  <!-- Modal -->
    <div class="modal fade" id="mdlEO" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog   modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger dragable_touch">
                    <h5 class="modal-title fst-italic text-white lblActionDesc" id="staticBackdropLabel"><label for=""> Early Out Validation </label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card  mb-3 rounded">
                        <div class="card-body ">

                            <form action="" id="frmEO">

                                <div class="row">

                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <select  class="form-control" name="before" id="selBefore">
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                            </select>
                                            <label for="selBefore">Can File Before? <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text before_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <select  class="form-control" name="after" id="selAfter">
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                            </select>
                                            <label for="selAfter">Can File After? <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text after_error"></span>
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <select  class="form-control" name="tardy" id="selTardy">
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                            </select>
                                            <label for="selTardy">Can File Tardy? <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text tardy_error"></span>
                                        </div>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button  id="btnSaveEO" type="button" class="btn btn-secondary ">Save Entries</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/eo.js') }}" defer></script>
@endsection
