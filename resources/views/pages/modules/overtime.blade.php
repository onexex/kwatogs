@extends('layout.app', [
    'title' => 'Overtime Filing'
])
@section('content')

<!--SHAIRA-->
<div class="container-fluid">

    <div class="mb-2">
        <h4 class=" mb-0 text-gray-800">Overtime Filing System</h4>
        @can('createovertime')
            <button class=" mt-3 btn text-white" style="background-color: #008080" name="btnCreateOTModal" id="btnCreateOTModal" data-bs-toggle="modal" data-bs-target="#mdlOvertime"> <i class="fa fa-plus"></i> Overtime Filing Form</button>
        @endcan
    </div>

    <!-- Page Heading -->
    {{-- <div class="d-sm-">
        <h4 class=" mb-0 text-gray-800">Official Business Trip Tracker</h4>
        <button class=" mt-3 btn btn-danger radius-1 btn-sm" name="department" id="btnCreateDept" data-bs-toggle="modal" data-bs-target="#mdlDepartment"> <i class="fa fa-plus"></i> Official Business Trip Form</button>

        <!-- <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50"></i> Generate Report </a> -->
    </div> --}}

    <div class="row">
        <div class="col-auto me-auto"></div>
        <div class="col-auto">
            <!-- <h5 class=" mb-0 text-danger">Filter:</h5>    -->
            <input type="date" id="txtDateFromTop" class=" p-2 rounded border border-1">
            <input type="date" id="txtDateToTop" class=" p-2 rounded border border-1">
        </div>
    </div>
       <!-- Content Row lilo -->
    <div class="row mt-2">
        <div class="col-xl-12 col-lg-12">
            <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-secondary">Overtime History</h6>
                    <button class="btn radius-1" name="btnRefreshTbl" id="btnRefreshTbl"><i class="font-weight-bold fa fa-refresh fa-sm fa-fw" style="color: #008080"></i></button>
                     {{--<i class="font-weight-bold fa fa-refresh fa-sm fa-fw text-danger"></i> --}}
                </div>
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-area">
                        <div class="table-responsive border-0">
                            <table class="table table-hover table-border-none  ">
                                <thead>
                                    <tr>
                                        <th class="text-dark" scope="col">No</th>
                                        <th class="text-dark" scope="col">Filing Date Time</th>
                                        <th class="text-dark" scope="col">Time In</th>
                                        <th class="text-dark" scope="col">Time Out</th>
                                        <th class="text-dark" scope="col">Purpose</th>
                                        <th class="text-dark" scope="col">Duration</th>
                                        <th class="text-dark" scope="col">Status</th>
                                        <th class="text-dark" scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tblOvertime">
                                    @forelse ($overtimes as $index => $overtime)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                {{ $overtime['filing_datetime'] }}
                                            </td>
                                            <td>
                                                {{ $overtime['time_in'] }}
                                            </td>
                                            <td>
                                                {{ $overtime['time_out'] }}
                                            </td>
                                            <td>{{ $overtime['purpose'] ?? '-' }}</td>
                                            <td>
                                                {{ $overtime['duration'] }}
                                            </td>

                                            <td>
                                                @php
                                                    $badgeClass = match($overtime['status']) {
                                                        'APPROVED' => 'bg-info',
                                                        'APPROVEDBYCFO' => 'bg-success',
                                                        'DISAPPROVED' => 'bg-danger',
                                                        'CANCELED' => 'bg-danger',
                                                        'FORAPPROVAL' => 'bg-warning text-dark',
                                                        default => 'bg-secondary'
                                                    };
                                                @endphp
                                                <span class="badge p-2 {{ $badgeClass }}">{{ strtoupper($overtime['status_value']) }}</span>
                                            </td>

                                            <td>
                                                <div class="btn-group gap-2">
                                                    @if ($overtime['status'] == 'FORAPPROVAL') 
                                                        @can('cancelovertime')
                                                            <a href="javascript:void(0)"
                                                                class="btn btn-sm btn-danger text-uppercase btnChangeStatus"
                                                                data-id="{{ $overtime['id'] }}"
                                                                data-url="{{ route('overtime.status.update', ['overtime' => $overtime['id']]) }}"
                                                                data-status="CANCELED"
                                                                data-title="Cancel Overtime"
                                                                data-message="Are you sure you want to cancel this overtime request? This action cannot be undone."
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#mdlStatusUpdate">
                                                                Cancel
                                                            </a>
                                                        @endcan
                                                        @can('disapproveovertime')
                                                            <a href="javascript:void(0)"
                                                                class="btn btn-sm btn-danger bg-danger text-white text-uppercase btnChangeStatus"
                                                                data-id="{{ $overtime['id'] }}"
                                                                data-url="{{ route('overtime.status.update', ['overtime' => $overtime['id']]) }}"
                                                                data-status="DISAPPROVED"
                                                                data-title="Disapprove Overtime"
                                                                data-message="Are you sure you want to disapprove this overtime request? This action cannot be undone."
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#mdlStatusUpdate">
                                                                Disapprove
                                                            </a>
                                                        @endcan
                                                        @can('approveovertime')
                                                      
                                                            <a href="javascript:void(0)"
                                                                class="btn btn-sm btn-info text-uppercase btnChangeStatus"
                                                                data-id="{{ $overtime['id'] }}"
                                                                data-url="{{ route('overtime.status.update', ['overtime' => $overtime['id']]) }}"
                                                                data-status="APPROVED"
                                                                data-title="Approve Overtime"
                                                                data-message="Are you sure you want to approve this overtime request? This action cannot be undone."
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#mdlStatusUpdate">
                                                                Approve by COO
                                                            </a>
                                                        @endcan
                                                    @endif
                                                    @if ($overtime['status'] == 'APPROVED') 
                                                        @can('disapproveovertime')
                                                            <a href="javascript:void(0)"
                                                                class="btn btn-sm btn-danger bg-danger text-white text-uppercase btnChangeStatus"
                                                                data-id="{{ $overtime['id'] }}"
                                                                data-url="{{ route('overtime.status.update', ['overtime' => $overtime['id']]) }}"
                                                                data-status="DISAPPROVED"
                                                                data-title="Disapprove Overtime"
                                                                data-message="Are you sure you want to disapprove this overtime request? This action cannot be undone."
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#mdlStatusUpdate">
                                                                Disapprove
                                                            </a>
                                                        @endcan
                                                        @can('approvecfoovertime')
                                                            <a href="javascript:void(0)"
                                                                class="btn btn-sm btn-sucess bg-success text-white text-uppercase btnChangeStatus"
                                                                data-id="{{ $overtime['id'] }}"
                                                                data-url="{{ route('overtime.status.update', ['overtime' => $overtime['id']]) }}"
                                                                data-status="APPROVEDBYCFO"
                                                                data-title="Approve and Confirm Overtime"
                                                                data-message="Are you sure you want to CFO Approve this overtime request? This action cannot be undone."
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#mdlStatusUpdate">
                                                                Approve by CFO
                                                            </a>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No overtime records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

      <!-- Modal OVERTIME Form-->
    <div class="modal fade" id="mdlOvertime" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true" style="background-color: rgb(249 200 200 / 17%);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header dragable_touch" >
                    <h5 class="modal-title" id="staticBackdropLabel"><label for="" class="" id="lblTitleOBT"> Overtime Filing Form</label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card mb-3 rounded">
                        <div class="card-body ">

                            <form action="{{ route('overtime.store') }}" method="POST" id="frmOvertimeForm">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="row">
                                            <div class="col-lg-12 ">
                                                <div class="form-group mb-1">
                                                    <label class="form-check-label mb-0" for="txtPersonnel">Personnel Name <label for="" class="text-danger mb-0">*</label></label>
                                                    <input class="form-control text-uppercase" id="txtPersonnel" name="personnel" value="{{ auth()->user()->fname . ' ' . auth()->user()->lname }}" type="text" placeholder="-" readonly/>

                                                    <span class="text-danger small error-text personnel_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12">
                                                <div class="form-group mb-1">
                                                    <label class="form-check-label mb-0" for="txtCompany">Company Name <label for="" class="text-danger mb-0">*</label></label>
                                                    <input class="form-control" id="txtCompany" value="{{ auth()->user()->empDetail->company->comp_name }}" name="company" type="text" placeholder="-" readonly/>

                                                    <span class="text-danger small error-text company_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12">
                                                <div class="form-group mb-1">
                                                    <label class="form-check-label mb-0" for="txtDepartment">Department<label for="" class="text-danger mb-0">*</label></label>
                                                    <input class="form-control" id="txtDepartment" value="{{ auth()->user()->empDetail?->department?->dep_name }}" name="department" type="text" placeholder="-" readonly/>

                                                    <span class="text-danger small error-text department_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12">
                                                <div class="form-group mb-1">
                                                    <label class="form-check-label mb-0" for="txtDesignation">Designation<label for="" class="text-danger mb-0">*</label></label>
                                                    <input class="form-control" id="txtDesignation" value="{{ auth()->user()->empDetail?->position?->pos_desc }}" name="designation" type="text" placeholder="-" readonly/>

                                                    <span class="text-danger small error-text designation_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-12">
                                                <div class="form-group mb-1">
                                                    <label class="form-check-label mb-0" for="txtPurposeRem"> Purpose <label for="" class="text-danger mb-0"></label></label>
                                                    <textarea class="form-control" id="txtPurposeRem" name="purpose" rows="4" placeholder="-" style="height: 100px">{{ old('purpose')}}</textarea>
                                                    @if ($errors->has('purpose'))
                                                        @foreach ($errors->get('purpose') as $error)
                                                            <span class="text-danger small d-block error-text">{{ $error }}</span>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group mb-1">
                                                    <label class="form-check-label mb-0" for="txtFilingDate">Filing Date<label for="" class="text-danger mb-0">*</label></label>
                                                    <input class="form-control" id="txtFilingDate" name="dateFil" value="{{ now()->format('Y-m-d') }}" type="date" placeholder="-" readonly/>

                                                    <span class="text-danger small error-text dateFil_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div class="form-group mb-1">
                                                    <label class="form-check-label mb-0" for="txtFilingTime">filing Time<label for="" class="text-danger mb-0">*</label></label>
                                                    <input class="form-control" id="txtFilingTime" name="timeFil" value="{{ now()->format('H:i') }}" type="time" placeholder="-" readonly/>

                                                    <span class="text-danger small error-text timeFil_error"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group mb-1">
                                                    <label class="form-check-label mb-0" for="txtOTDateFrom">OT Date From<label for="" class="text-danger mb-0">*</label></label>
                                                    <input class="form-control" id="txtOTDateFrom" value="{{ old('dateFrom') }}" name="dateFrom" required type="date" placeholder="-"/>
                                                    @if ($errors->has('dateFrom'))
                                                        @foreach ($errors->get('dateFrom') as $error)
                                                            <span class="text-danger small d-block error-text">{{ $error }}</span>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div class="form-group mb-1">
                                                    <label class="form-check-label mb-0" for="txtOTTimeFrom">OT Time From<label for="" class="text-danger mb-0">*</label></label>
                                                    <input class="form-control" id="txtOTTimeFrom" value="{{ old('timeFrom') }}"  name="timeFrom" required type="time" placeholder="-"/>
                                                    @if ($errors->has('timeFrom'))
                                                        @foreach ($errors->get('timeFrom') as $error)
                                                            <span class="text-danger small d-block error-text">{{ $error }}</span>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group mb-1">
                                                    <label class="form-check-label mb-0" for="txtOTDateTo">OT Date To<label for="" class="text-danger mb-0">*</label></label>
                                                    <input class="form-control" id="txtOTDateTo" value="{{ old('dateTo') }}" name="dateTo" required type="date" placeholder="-"/>
                                                    @if ($errors->has('dateTo'))
                                                        @foreach ($errors->get('dateTo') as $error)
                                                            <span class="text-danger small d-block error-text">{{ $error }}</span>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div class="form-group mb-1">
                                                    <label class="form-check-label mb-0" for="txtOTTimeTo">OT Time To<label for="" class="text-danger mb-0">*</label></label>
                                                    <input class="form-control" id="txtOTTimeTo" name="timeTo" value="{{ old('timeTo') }}" required type="time" placeholder="-"/>
                                                    @if ($errors->has('timeTo'))
                                                        @foreach ($errors->get('timeTo') as $error)
                                                            <span class="text-danger small d-block error-text">{{ $error }}</span>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <!-- <button type="button" class="btn btn-secondary closereset_update" data-bs-dismiss="modal">Close</button> -->
                                    <button  id="btnSaveOT" type="submit" class="btn text-white" style="background-color: #008080">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

   <!-- Status Update Modal -->
    <div class="modal fade" id="mdlStatusUpdate" tabindex="-1" aria-labelledby="mdlStatusUpdateLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="mdlStatusUpdateLabel">Update Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p id="statusUpdateMessage">Are you sure?</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                    <form id="statusUpdateForm" method="POST" style="display:inline;">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" id="statusInput">
                        <button type="submit" class="btn btn-primary" id="statusUpdateButton">Yes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var myModal = new bootstrap.Modal(document.getElementById('mdlOvertime'));
            myModal.show();
            
        });

    </script>
@endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('mdlStatusUpdate');
            const form = document.getElementById('statusUpdateForm');
            const statusInput = document.getElementById('statusInput');
            const modalTitle = document.getElementById('mdlStatusUpdateLabel');
            const modalMessage = document.getElementById('statusUpdateMessage');
            const modalButton = document.getElementById('statusUpdateButton');

            // Listen for button clicks that open the modal
            document.querySelectorAll('.btnChangeStatus').forEach(btn => {
                btn.addEventListener('click', () => {
                    const url = btn.getAttribute('data-url');
                    const status = btn.getAttribute('data-status');
                    const title = btn.getAttribute('data-title');
                    const message = btn.getAttribute('data-message');

                    // Update form action and modal content dynamically
                    form.setAttribute('action', url);
                    statusInput.value = status;
                    modalTitle.textContent = title || 'Update Status';
                    modalMessage.textContent = message || 'Are you sure you want to proceed?';

                    // Change button color depending on status
                    modalButton.className = 'btn';
                    if (status === 'CANCELED') modalButton.classList.add('btn-danger');
                    else if (status === 'APPROVED') modalButton.classList.add('btn-success');
                    else modalButton.classList.add('btn-primary');

                    modalButton.textContent = `Yes, ${status.charAt(0) + status.slice(1).toLowerCase()}`;
                });
            });
        });
    </script>

@endsection
