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
        <h4 class="text-gray-800 mb-3">Lilo Validation</h4>
        <button class=" mt-3 btn btn-details radius-1" name="btnCreateMdl" id="btnCreateMdl" data-bs-toggle="modal" data-bs-target="#mdlLiloVal"> <i class="fa fa-plus"></i> Lilo Validation</button>
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
                                <thead style=" background-color: #f1f1f1;">
                                    <tr>
                                        <th class="text-dark" scope="col">Grace Period</th>
                                        <th class="text-dark" scope="col">Managers Override</th>
                                        <th class="text-dark" scope="col">Managers Time</th>
                                        <th class="text-dark" scope="col">No Logout Has Deduction</th>
                                        <th class="text-dark" scope="col">Minute Deduction</th>
                                        {{-- <th class="text-dark" scope="col">Updated Time</th> --}}
                                        <th class="text-dark" scope="col">Update</th>
                                    </tr>
                                </thead>
                                <tbody >
                                    @foreach ($lilovalidations as $lilovalidation)
                                        <tr>
                                            <td>{{ $lilovalidation->lilo_gracePrd }}</td>
                                            <td>{{ $lilovalidation->managersOverride }}</td>
                                            <td>{{ $lilovalidation->managersTime }}</td>
                                            <td>{{ $lilovalidation->no_logout_has_deduction == 0 ? 'No' : 'Yes' }}</td>
                                            <td>{{ $lilovalidation->minute_deduction }}</td>
                                            <td class=''>
                                                <button type="button" value='{{ $lilovalidation->id  }}' class="btn btn-details" data-toggle="tooltip" data-placement="bottom"  id="btnUpdateMdl" title=" Schedule" data-bs-toggle="modal" data-bs-target="#mdlLiloVal" > <i class="fa fa-pencil"></i> </button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

      <!-- Modal Lilo Validation -->
    <div class="modal fade" id="mdlLiloVal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header dragable_touch" >
                    <h5 class="modal-title" id="staticBackdropLabel"><label for="" class="" id="lblTitleGraceP"> Lilo Validation</label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="card mb-3 rounded">
                        <div class="card-body ">

                            <form action="" id="frmLiloVal">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-floating mb-2">
                                            <input class="form-control" id="txtGracePeriod" name="graceperiod" type="number" placeholder="-"/>
                                            <label class="form-check-label" for="txtGracePeriod">Grace Period<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text graceperiod_error"></span>
                                        </div>
                                        <div class="form-floating mb-2">
                                            <input class="form-control" id="txtMngrOverride" name="mngrsOverride" type="number" placeholder="-"/>
                                            <label class="form-check-label" for="txtMngrOverride">Managers Override<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text mngrsOverride_error"></span>
                                        </div>
                                        <div class="form-floating mb-2">
                                            <input class="form-control" id="txtMngrTime" name="mngrsTime" type="number" placeholder="-"/>
                                            <label class="form-check-label" for="txtMngrTime">Managers Time<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text mngrsTime_error"></span>
                                        </div>
                                        <div class="form-floating mb-2">
                                            <div class="form-floating mb-1">
                                                <select  class="form-control" name="no_logout_has_deduction" id="no_logout_deduction"  >
                                                    <option value="1">Yes</option>
                                                    <option value="0">No</option>
                                                </select>
                                                <label for="missionobjective" class="text-muted"> No Logout Has Deduction<label for="" class="text-danger">*</label></label>
                                                <span class="text-danger small error-text status_error"></span>
                                            </div>
                                        </div>
                                        <div class="form-floating mb-2">
                                            <input class="form-control" id="minute_deduction" name="minute_deduction" type="number" placeholder="-"/>
                                            <label class="form-check-label" for="txtMngrTime">Minute Deduction<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text mngrsTime_error"></span>
                                        </div>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button  id="btnLiloVal" type="button" class="btn btn-details">Submit</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="{{ asset('js/settings/liloValidations.js') }}"  deffer></script>

@endsection

