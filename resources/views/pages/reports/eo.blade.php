@extends('layout.app')
@section('content')

{{-- JOHN MARC CASQUIO --}}

<div class="container-fluid">
    <div class="mb-2">
        <h4 class="text-gray-800 mb-3">Early Out Viewer</h4>
    </div>

     <!-- Content Row dar -->
     <div class="row mt-5">
        <div class="col-xl-12 col-lg-12">
            <div class="card mb-4">
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-area">
                        <div class="table-responsive"> 
                            
                            <div class="d-flex">
                                <div class="col-lg-3 mr-auto p-2">
                                    <div class="form-floating mb-3">
                                        <select  class="form-control" name="cross" id="selEmployee"  >      
                                            <option value="">All</option>                                                                                         
                                            <option value="">admin</option>                                                                                         
                                            <option value="">Gemana, Ramon Jr</option>                                                                                         
                                            <option value="">Ledesma, Keyo</option>                                                                                         
                                            <option value="">Panaligan, John Vincent</option>                                                                                         
                                            <option value="">Mendoza, Francis Dylle</option>                                                                                         
                                            <option value="">Casquio, John Marc</option>                                                                                         
                                            <option value="">Lobarbio, Shaira</option>                                                                                         
                                            <option value="">Franco, Bernard</option>                                                                                         
                                        </select>
                                        <label for="selEmployee">Choose Employee <label for="" class="text-danger">*</label></label>
                                        <span class="text-danger small error-text cross_error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtFrom" name="from" type="date" placeholder="date"/>
                                            <label for="txtFrom">Date From <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text cross_error"></span>
                                        </div>
                                </div>

                                <div class="col-lg-2">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtTo" name="to" type="date" placeholder="date"/>
                                            <label for="txtTo">Date To <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text cross_error"></span>
                                        </div>
                                </div>

                                <span class="text-danger fs-3 p-2" id="btnRefresh"><i class="fa-solid fa-arrows-rotate"></i></span>
                              </div>


                            <table class="table table-hover ">
                                <thead>
                                    <tr>
                                        <th scope="col">No</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Filing Date</th>
                                        <th scope="col">Purpose</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="tblEO">
                                    <tr>
                                        <td>1</td>
                                        <td>Aquino, Bianca</td>
                                        <td>September 22, 2022 04:07:47 PM</td>
                                        <td>PEST CONTROL</td>
                                        <td>Approved by HR</td>
                                    </tr>
                                </tbody>
                                <tfoot class="p-3">
                                    <tr>
                                        <td colspan="6">
                                            <button class="btn bg-gradient-info text-white" id="btnPrint"><i class="fa fa-print"></i></button>
                                            <button class="btn bg-gradient-success text-white" id="btnExcel"><i class="fa-regular fa-file-excel" aria-hidden="true"></i> Export Data to Excel File</button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>                                        
                        </div>
                    </div>
                </div>

              
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/report/eo.js') }}" defer></script>
@endsection    