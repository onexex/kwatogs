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
        <h4 class="text-secondary-800 m-0">Settings / <label class="text-black">Schedule Time </label></h4>
    </div>

    <div class="row pb-2">
        <div class="col-lg-4 col-md-12">
            {{-- <button class=" mb-2 btn btn-details" name="btnCreateModal" id="btnCreateModal" data-bs-toggle="modal" data-bs-target="#mdlEmpScheduler">Add Schedule</button> --}}
            <button type="button" class="btn btn-details mb-2" data-toggle="tooltip" data-placement="bottom"  id="btnCreateTime" title="Create Schedule" data-bs-toggle="modal" data-bs-target="#mdlTime" > Schedule Time </button>
        </div>
    </div>

    {{-- <div class="mb-2">
        <h4 class="text-gray-800 mb-3">Schedule Time</h4>
        <button type="button" class="btn btn-details mt-1" data-toggle="tooltip" data-placement="bottom"  id="btnCreateTime" title="Create Schedule" data-bs-toggle="modal" data-bs-target="#mdlTime" > <i class="fa fa-plus"></i> Schedule Time </button>
    </div> --}}

     <!-- Content Row dar -->
     <div class="row px-2">
        <div class="card shadow">
            <div class="card-body">
                <div class="row">
                    <div class="table-responsive overflow-auto-settings">
                        <table class="table table-hover">
                            <thead class="text-center">
                                 <th scope="col">#</th>
                                    <th scope="col" class="text-dark">Time From</th>
                                    <th scope="col" class="text-dark">Time To</th>
                                    <th scope="col" class="text-dark">Time  Cross</th>
                                    <th scope="col" class="text-dark">Action</th>
                            </thead>
                            <tbody id="tblTime" class="text-center">

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
     </div>

    {{-- <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card  mb-4">
                <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover ">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Time From</th>
                                        <th scope="col">Time To</th>
                                        <th scope="col">Time  Cross</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tblTime">

                                </tbody>
                            </table>
                        </div>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- modal Work Time -->
    <div class="modal fade" id="mdlTime" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog   modal-md ">
            <div class="modal-content">
                <div class="modal-header dragable_touch" >
                    <h5 class="modal-title" id="staticBackdropLabel"><label for="" class="lblActionDesc"></label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card  mb-3 rounded">
                        <div class="card-body ">
                            <form action="" id="frmCreateTime">
                                <div class="form-floating mb-1">
                                    <input class="form-control" id="txtFromTime" name="from" type="time"   />
                                    <label for="missionDesc">Time From <label for="" class="text-danger">*</label></label>
                                    <span class="text-danger small error-text from_error"></span>
                                </div>

                                <div class="form-floating mb-1">
                                    <input class="form-control" id="txtTimeTo" name="to" type="time"    />
                                    <label for="missionDesc">Time To<label for="" class="text-danger">*</label></label>
                                    <span class="text-danger small error-text to_error"></span>
                                </div>

                                <div class="form-floating mb-1 fs-6">
                                    <select  class="form-control" name="cross" id="selTimeCross"  >
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                    <label for="missionobjective" class="text-muted">Time Crossing<label for="" class="text-danger">*</label></label>
                                    <span class="text-danger small error-text cross_error"></span>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <!-- <button type="button" class="btn btn-secondary closereset_update" data-bs-dismiss="modal">Close</button> -->
                    <button  id="btnAction" type="button" class="btn btn-details">Save Entries</button>
                </div>
            </div>
        </div>
    </div>

</div>
<script src="{{ asset('js/settings/worktime.js') }}" defer></script>
@endsection
