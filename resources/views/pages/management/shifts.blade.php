@extends('layout.app')
@section('content')
<div class="container-fluid">
    <div class="mb-2">
        <h4 class="text-gray-800 mb-3">Work Time</h4>
        <button class=" mt-3 btn btn-danger radius-1" id="btnAddShift" data-bs-toggle="modal" data-bs-target="#mdlShift"> Work Time <i class="fa fa-plus"></i> </button>
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
                                        <th scope="col">No</th>
                                        <th scope="col">Time From</th>
                                        <th scope="col">Time To</th>
                                        <th scope="col">Time Cross</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tblShift">
                                    <tr>
                                        <td>0</td>
                                        <td>Rest</td>
                                        <td>Day</td>
                                        <td>No</td>
                                        <td>
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#mdlShift" id="btnUpdateShift">
                                                <i class="fa-solid fa-pencil"></i>
                                            </button>
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

  <!-- Modal -->
    <div class="modal fade" id="mdlShift" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog   modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-danger dragable_touch">
                    <h5 class="modal-title fst-italic text-white title" id="staticBackdropLabel"><label for=""> Work Time </label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card  mb-3 rounded">
                        <div class="card-body ">

                            <form id="frmShift">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="timeFrom" name="from" type="time" placeholder="time"/>
                                            <label for="timeFrom">Time From <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text from_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="timeTo" name="to" type="time" placeholder="time"/>
                                            <label for="timeTo">Time To <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text to_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="form-floating mb-3">
                                            <select  class="form-control" name="cross" id="selTimeCross"  >
                                                <option value=""></option>
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                            </select>
                                            <label for="selTimeCross">Time Cross <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text cross_error"></span>
                                        </div>
                                    </div>

                                </div>

                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button  id="btnSaveShift" type="button" class="btn btn-secondary ">Save Entries</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/shift.js') }}" defer></script>
@endsection
