@extends('layout.app', [
    'title' => 'Pending Leave Requests'
])
@section('content')

    <div class="container-fluid">

        <div class="mb-2">
            <h4 class=" mb-0 text-gray-800">Pending Leave Requests</h4>
        </div>
        <div class="row mt-2">
            <div class="col-xl-12 col-lg-12">
                <div class="card  mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-secondary">Leave History</h6>
                        <button class="btn radius-1" name="btnRefreshTbl" id="btnRefreshTbl"><i class="font-weight-bold fa fa-refresh fa-sm fa-fw" style="color: #008080"></i></button>
                    </div>
                    <!-- Card Body -->
                    <div class="card-body">
                        <div class="chart-area">
                            <div class="table-responsive border-0">
                                <table class="table table-hover table-border-none  ">
                                    <thead>
                                        <tr>
                                            <th class="text-dark" scope="col">Employee</th>
                                            <th class="text-dark" scope="col">LeaveType</th>
                                            <th class="text-dark" scope="col">FilingDate</th>
                                            <th class="text-dark" scope="col">DateFrom</th>
                                            <th class="text-dark" scope="col">DateTo</th>
                                            <th class="text-dark" scope="col">Duration</th>
                                            <th class="text-dark" scope="col">Purpose</th>
                                            <th class="text-dark" scope="col">Leave Kind</th>
                                            <th class="text-dark" scope="col">Status</th>
                                            <th class="text-dark" scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tblLeaveApp">

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('js/modules/leaverequest.js') }}" defer></script>
@endsection