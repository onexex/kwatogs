@extends('layout.app', [
    'title' => 'Pending Overtime Requests'
])
@section('content')

    <div class="container-fluid">

        <div class="mb-2">
            <h4 class=" mb-0 text-gray-800">Overtime Requests</h4>
        </div>
        <div class="row mt-2">
            <div class="col-xl-12 col-lg-12">
                <div class="card  mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-secondary">Overtime</h6>
                        <button class="btn radius-1" name="btnRefreshTbl" id="btnRefreshTbl"><i class="font-weight-bold fa fa-refresh fa-sm fa-fw" style="color: #008080"></i></button>
                    </div>
                    <!-- Card Body -->
                    <div class="card-body">
                        <div class="alert alert-info d-flex align-items-start gap-2 py-2 px-3 small mb-3" role="alert">
                            <i class="fa fa-circle-info mt-1" style="color:#008080;"></i>
                            <div>
                                <strong>OT pay rule (Regular &amp; Rest day):</strong>
                                first 8 hrs &times; <strong>1.30</strong>, hours beyond 8 &times; <strong>1.25</strong>.
                                A <strong>1-hour meal break</strong> is deducted when the filed span is <strong>&ge; 9 hours</strong>.
                                Holiday day-types use the standard multipliers.
                            </div>
                        </div>

                        <div class="chart-area">
                            <div class="table-responsive border-0">
                                <table class="table table-hover table-border-none  ">
                                    <thead>
                                        <tr>
                                            <th class="text-dark" scope="col">Employee</th>
                                            <th class="text-dark" scope="col">FilingDate</th>
                                            <th class="text-dark" scope="col">DateFrom</th>
                                            <th class="text-dark" scope="col">DateTo</th>
                                            <th class="text-dark" scope="col">Duration</th>
                                            <th class="text-dark" scope="col">Purpose</th>
                                            <th class="text-dark" scope="col">Status</th>
                                            <th class="text-dark" scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tblOvertimeApp">

                                    </tbody>
                                </table>
                <div id="overtimePagination" class="mt-2 px-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('js/modules/overtimerequest.js') }}" defer></script>
@endsection