@extends('layout.app')
@section('content')

{{-- JOHN MARC CASQUIO --}}

<div class="container-fluid">
    <div class="mb-2">
        <h4 class="text-gray-800 mb-3">Leave Credits</h4>
        <h4 class="text-gray-800 mb-3">as of Today</h4>
    </div>

     <!-- Content Row dar -->
     <div class="row mt-5">
        <div class="col-xl-12 col-lg-12">
            <div class="card mb-4">
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-area">
                        <div class="table-responsive"> 

                            <table class="table table-hover ">
                                <thead>
                                    <tr>
                                        <th scope="col">Employee Name</th>
                                        <th scope="col">Used Credit</th>
                                        <th scope="col">Current Credit Earned</th>
                                        <th scope="col">Remaining Credit</th>
                                        <th scope="col">View Used Credit</th>
                                    </tr>
                                </thead>
                                <tbody id="tblLeave">
                                    <tr>
                                        <td>Arocha, Ronnie</td>
                                        <td>0</td>
                                        <td>5.1973</td>
                                        <td>1.8027</td>
                                        <td>
                                            <button class="btn bg-gradient-warning text-white" data-bs-toggle="modal" data-bs-target="#mdlLeave"><i class="fa fa-eye"></i></button>
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
</div>

    {{-- MODAL  --}}
    <div class="modal fade" id="mdlLeave" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger dragable_touch">
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">                                                  
                    <div class="card  mb-3 rounded">
                        <div class="card-body "> 
                            
                            <div class="chart-area">
                                <div class="table-responsive"> 
        
                                    <table class="table table-hover ">
                                        <thead>
                                            <tr>
                                                <th scope="col">Date</th>
                                                <th scope="col">Leave Type</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tblLeaveModal">
                                         
                                        </tbody>
                                    </table>                                        
                                </div>
                            </div>
                
                        </div>                                      
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="{{ asset('js/report/leave.js') }}" defer></script>
@endsection    