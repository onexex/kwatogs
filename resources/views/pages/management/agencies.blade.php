@extends('layout.app')
@section('content')

<style>
    .table thead th {
        position:sticky !important;
        top: 0;
        background-color: #f8f9fa;
        z-index: 10;
    }
</style>

<div class="container-fluid">

    <div class="pb-2">
        <h4 class="text-secondary-800 m-0">Settings / <label class="text-black">Agency </label></h4>
    </div>

    <div class="row pb-2">
        <div class="col-lg-4 col-md-12">
            <button type="button" class="btn btn-details mb-2" id="btnAddAgency" name="btnAddAgency" data-bs-toggle="modal" data-bs-target="#mdlAgency"> Agency </button>
        </div>
    </div>

     <!-- Content Row dar -->
    <div class="row px-2">
        <div class="card shadow">
            <div class="card-body">
                <div class="row">
                    <div class="table-responsive overflow-auto-settings">
                        <table class="table table-hover">
                            <thead class="text-center">
                                <th scope="col">No</th>
                                <th scope="col">Agency Name</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            <tbody id="tblAgencies" class="text-center">

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
     </div>

  <!-- Modal -->
    <div class="modal fade" id="mdlAgency" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog   modal-lg">
            <div class="modal-content">
                <div class="modal-header dragable_touch" >
                    <h5 class="modal-title title" id="staticBackdropLabel"><label class="lblTitleMdl" for=""> Agency </label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card  mb-3 rounded">
                        <div class="card-body ">

                            <form action="" id="frmAgencies">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-1">
                                            <input class="form-control" id="txtAgencyName" name="agency" type="text" placeholder="Agency Name"/>
                                            <label for="txtAgencyName"> Agency Name<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text agency_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-floating mb-1">
                                            <select  class="form-control" name="status" id="selStatusAgency"  >
                                                <option value="1">Active</option>
                                                <option value="0">Not Active</option>
                                            </select>
                                            <label for="missionobjective" class="text-muted"> Status<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text status_error"></span>
                                        </div>
                                    </div>

                                </div>

                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button  id="btnSaveAgency" type="button" class="btn btn-details">Save Entries</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/agencies.js') }}" defer></script>
@endsection
