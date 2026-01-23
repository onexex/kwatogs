@extends('layout.app')
@section('content')
<div class="container-fluid">
    <div class="mb-2">
        <h4 class="text-gray-800 mb-3 title">OT Filing Maintenance</h4>
        <div class="mb-2">
            <button class=" mt-3 btn text-white btn-blue radius-1" name="btnCreateOTMaintinance" id="btnCreateOTMaintinance" data-bs-toggle="modal" data-bs-target="#mdlOTFile"> <i class="fa fa-plus"></i> Filing Maintenance</button>
        </div>
    </div>

     <!-- Content Row dar -->
     <div class="tblTitle col-lg-12">
        <p for="" id="lblOpt" class="text-danger mt-4 fs-3"></p>
     </div>

     <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card mb-4">
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-area overflow-auto">
                        <div class="table-responsive fixTableHead">
                            <table class="table table-hover table-scroll sticky">
                                <thead style="background-color: #f1f1f1; ">
                                    <tr>
                                        <th scope="col">Company</th>
                                        <th scope="col">File Before</th>
                                        <th scope="col">File After</th>
                                        <th scope="col">No of Days Before</th>
                                        <th scope="col">No of Days After</th>
                                        <th scope="col">Holiday</th>
                                        <th scope="col">Tardy</th>
                                        <th scope="col">Update</th>
                                    </tr>
                                </thead>
                                <tbody id="tblOTFile">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>


</div>

{{-- modal  --}}

<div class="modal fade" id="mdlOTFile" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
    <div class="modal-dialog   modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger dragable_touch">
                <h5 class="modal-title fst-italic lblActionDesc text-white title" id="staticBackdropLabel"><label for=""> OT Maintenance </label></h5>
                <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body ">
                <div class="card  mb-3 rounded">
                    <div class="card-body ">

                        <form action="" id="frmOTFile">

                            <!-- <div class="col-lg-6"> -->
                                <div class="row">
                                    <div class="col-lg-6 mb-1">
                                        <!-- <div class="form-floating ">
                                            <input class="form-control" id="txtCompany" name="company" type="text" placeholder="Days Before"/>
                                            <label for="txtDaysBefore">Company <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text daysBefore_error"></span>
                                        </div> -->
                                        <div class="form-floating ">
                                            <select  class="form-control" name="company" id="txtCompany"  >
                                                @if(count($companyData)>0)
                                                    @foreach($companyData as $companyDatas)
                                                    <option value='{{$companyDatas->comp_id }}'>{{$companyDatas->comp_name }}</option>
                                                    @endforeach
                                                @else

                                                @endif
                                            </select>
                                            <label  class="form-check-label" for="selLeaveType" class="text-muted">Company<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text company_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6 mb-1">
                                        <div class="form-floating">
                                            <select  class="form-control" name="before" id="selBefore">
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                            <label for="selBefore">File Before <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text before_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-1">
                                        <div class="form-floating">
                                            <select  class="form-control" name="after" id="selAfter">
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                            <label for="selAfter">File After <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text after_error"></span>
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-lg-6 mb-1">
                                        <div class="form-floating">
                                            <select  class="form-control" name="holiday" id="selHoliday">
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                            <label for="selHoliday">Is Holiday <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text holiday_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-1">
                                        <div class="form-floating">
                                            <select  class="form-control" name="tardy" id="selTardy">
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                            <label for="selTardy">Is Tardy <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text tardy_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6 mb-1">
                                        <div class="form-floating ">
                                            <input class="form-control" id="txtDaysBefore" name="daysBefore" type="number" placeholder="Days Before"/>
                                            <label for="txtDaysBefore">No of Days Before <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text daysBefore_error"></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-1">
                                        <div class="form-floating">
                                            <input class="form-control" id="txtDaysAfter" name="daysAfter" type="number" placeholder="Days After"/>
                                            <label for="txtDaysBefore">No of Days After <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text daysAfter_error"></span>
                                        </div>
                                    </div>
                                </div>

                        </form>

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button  id="btnOTFile" type="button" class="btn btn-secondary ">Save Entries</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/otfiling.js') }}" defer></script>
@endsection
