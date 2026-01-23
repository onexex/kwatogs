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
        <h4 class="text-secondary-800 m-0">Settings / <label class="text-black">Employee Status </label></h4>
    </div>

    <div class="row pb-2">
        <div class="col-lg-4 col-md-12">
            <button type="button" class="btn btn-details mb-2" id="btnEmployeeStatus" data-bs-toggle="modal" data-bs-target="#mdlEmployeeStatus"> Employee Status </button>
        </div>
    </div>

    <!-- Content Row table -->
    <div class="row px-2">
        <div class="card shadow">
            <div class="card-body">
                <div class="row">
                    <div class="table-responsive overflow-auto-settings">
                        <table class="table table-hover">
                            <thead class="text-center">
                               <th scope="col">Employee Status No.</th>
                                <th scope="col">Employee Status</th>
                                <th scope="col">Action</th>
                            <tbody id="tblEmployeeStatus" class="text-center">

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

  <!-- Modal -->
    <div class="modal fade" id="mdlEmployeeStatus" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header dragable_touch" >
                    <h5 class="modal-title lblActionDesc" id="staticBackdropLabel"><label for=""> Employee Status </label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card  mb-3 rounded">
                        <div class="card-body ">

                            <form action="" id="frmEmployeeStatus">

                                <div class="form-floating mb-1">
                                    <input class="form-control" id="txtEmployeeStatus" name="employeestatus" type="text" placeholder="Status"/>
                                    <label for="txtEmployeeStatus"> Employee Status <label for="" class="text-danger">*</label></label>
                                    <span class="text-danger small error-text employeestatus_error"></span>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button  id="btnSaveEmployeeStatus" type="button" class="btn btn-details">Save Entries</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/empStatus.js') }}" defer></script>
@endsection
