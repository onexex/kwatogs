@extends('layout.app', [
    'title' => 'Overtime Filing'
])
@section('content')

<!--SHAIRA-->
<style>
    /* ── Design tokens (shared with Edit Employee / Leave Application) ── */
    :root {
        --teal:         #008080;
        --teal-dark:    #006666;
        --teal-mid:     #4db6ac;
        --teal-light:   #e0f2f1;
        --slate:        #334155;
        --slate-light:  #64748b;
        --muted:        #94a3b8;
        --bg:           #f1f5f9;
        --surface:      #ffffff;
        --border:       #e2e8f0;
        --danger:       #ef4444;
        --success:      #10b981;
        --warning:      #f59e0b;
        --radius-card:  14px;
        --radius-input: 8px;
        --shadow-card:  0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }

    /* ── Page shell ──────────────────────────────────────────── */
    .ot-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    /* ── Top header bar ──────────────────────────────────────── */
    .ot-topbar {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        padding: 16px 22px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .ot-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .ot-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }

    .btn-add-ot {
        background: var(--teal);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: .3px;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(0,128,128,.25);
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-add-ot:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

    /* ── Section card ────────────────────────────────────────── */
    .sc {
        background: var(--surface);
        border-radius: var(--radius-card);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-card);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .sc-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 22px;
        border-bottom: 1px solid var(--border);
        background: linear-gradient(to right, #fafcff, #f8fbfa);
    }
    .sc-head-left { display: flex; align-items: center; gap: 10px; }
    .sc-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: var(--teal-light);
        color: var(--teal);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        flex-shrink: 0;
    }
    .sc-title {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--slate);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin: 0;
    }
    .sc-body { padding: 22px; }

    .btn-refresh {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1.5px solid var(--border);
        background: var(--surface);
        color: var(--teal);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .15s;
    }
    .btn-refresh:hover { background: var(--teal-light); border-color: var(--teal-mid); }

    /* ── Filter bar ──────────────────────────────────────────── */
    .filter-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .filter-bar .field-label { margin-bottom: 0; white-space: nowrap; }
    .filter-bar .form-control { width: auto; }

    /* ── Field helpers ───────────────────────────────────────── */
    .field-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--slate-light);
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: 5px;
        display: block;
    }
    .field-label .req { color: var(--danger); margin-left: 2px; }

    .form-control, .form-select {
        border: 1.5px solid var(--border);
        border-radius: var(--radius-input);
        font-size: 0.875rem;
        color: var(--slate);
        background: #fafbfc;
        transition: border-color .15s, box-shadow .15s;
        padding: 0.55rem 0.85rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--teal);
        box-shadow: 0 0 0 3px rgba(0,128,128,.1);
        background-color: #fff;
        outline: none;
    }
    .form-control[readonly] {
        background: var(--teal-light);
        color: var(--teal);
        font-weight: 600;
        cursor: default;
    }
    .error-text {
        font-size: 0.68rem;
        font-weight: 500;
        color: var(--danger);
        display: block;
        min-height: 14px;
        margin-top: 3px;
    }

    /* ── Sub-section divider ─────────────────────────────────── */
    .sub-divider {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 6px 0 18px;
    }
    .sub-divider span {
        font-size: 0.73rem;
        font-weight: 700;
        color: var(--teal);
        text-transform: uppercase;
        letter-spacing: .4px;
        white-space: nowrap;
    }
    .sub-divider::after {
        content: '';
        flex-grow: 1;
        height: 1px;
        background: var(--border);
    }

    /* ── Table styling ───────────────────────────────────────── */
    .ot-table thead th {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--slate-light);
        text-transform: uppercase;
        letter-spacing: .4px;
        border-bottom: 2px solid var(--border);
        white-space: nowrap;
    }
    .ot-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
    }
    .ot-table tbody tr:hover { background: var(--teal-light); }

    /* ── Modal styling ───────────────────────────────────────── */
    #mdlOvertime .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #mdlOvertime .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #mdlOvertime .modal-header .modal-title { color: #fff; }
    #mdlOvertime .modal-body { background: var(--bg); padding: 22px; }

    .btn-submit-ot {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 10px 26px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(245,158,11,.3);
        transition: all .2s;
    }
    .btn-submit-ot:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,.4); color: #fff; }

    #mdlStatusUpdate .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
</style>

<div class="ot-shell">

    {{-- ── Top header ── --}}
    <div class="ot-topbar">
        <div>
            <p class="page-title">Overtime Filing System</p>
            <p class="page-sub">File and track your overtime requests</p>
        </div>
        @can('createovertime')
            <button class="btn-add-ot" name="btnCreateOTModal" id="btnCreateOTModal" data-bs-toggle="modal" data-bs-target="#mdlOvertime">
                <i class="fa fa-plus"></i> Overtime Filing Form
            </button>
        @endcan
    </div>

    {{-- ── Filters ── --}}
    <div class="sc">
        <div class="sc-body" style="padding: 16px 22px;">
            <div class="filter-bar">
                <div>
                    <label class="field-label" for="txtDateFromTop">From</label>
                    <input type="date" id="txtDateFromTop" class="form-control">
                </div>
                <div>
                    <label class="field-label" for="txtDateToTop">To</label>
                    <input type="date" id="txtDateToTop" class="form-control">
                </div>
            </div>
        </div>
    </div>

    {{-- ── Overtime History ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa fa-history"></i></div>
                <h5 class="sc-title">Overtime History</h5>
            </div>
            <button class="btn-refresh" name="btnRefreshTbl" id="btnRefreshTbl" title="Refresh">
                <i class="fa fa-refresh fa-sm"></i>
            </button>
        </div>
        <div class="sc-body">
            <div class="table-responsive">
                <table class="table table-hover ot-table">
                    <thead>
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Filing Date Time</th>
                            <th scope="col">Time In</th>
                            <th scope="col">Time Out</th>
                            <th scope="col">Purpose</th>
                            <th scope="col">Duration</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
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

    <!-- Modal OVERTIME Form-->
    <div class="modal fade" id="mdlOvertime" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header dragable_touch">
                    <h5 class="modal-title" id="staticBackdropLabel"><label for="" id="lblTitleOBT">Overtime Filing Form</label></h5>
                    <button type="button" class="btn-close btn-close-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('overtime.store') }}" method="POST" id="frmOvertimeForm">
                        @csrf

                        <div class="sub-divider"><span>Personnel Details</span></div>
                        <div class="row g-3 mb-3">
                            <div class="col-lg-6">
                                <label class="field-label" for="txtPersonnel">Personnel Name <span class="req">*</span></label>
                                <input class="form-control text-uppercase" id="txtPersonnel" name="personnel" value="{{ auth()->user()->fname . ' ' . auth()->user()->lname }}" type="text" placeholder="-" readonly/>
                                <span class="error-text personnel_error"></span>
                            </div>

                            <div class="col-lg-6">
                                <label class="field-label" for="txtCompany">Company Name <span class="req">*</span></label>
                                <input class="form-control" id="txtCompany" value="{{ auth()->user()->empDetail->company->comp_name ?? 'N/A' }}" name="company" type="text" placeholder="-" readonly/>
                                <span class="error-text company_error"></span>
                            </div>

                            <div class="col-lg-6">
                                <label class="field-label" for="txtDepartment">Department <span class="req">*</span></label>
                                <input class="form-control" id="txtDepartment" value="{{ auth()->user()->empDetail?->department?->dep_name ?? 'N/A' }}" name="department" type="text" placeholder="-" readonly/>
                                <span class="error-text department_error"></span>
                            </div>

                            <div class="col-lg-6">
                                <label class="field-label" for="txtDesignation">Designation <span class="req">*</span></label>
                                <input class="form-control" id="txtDesignation" value="{{ auth()->user()->empDetail?->position?->pos_desc ?? 'N/A' }}" name="designation" type="text" placeholder="-" readonly/>
                                <span class="error-text designation_error"></span>
                            </div>

                            <div class="col-lg-12">
                                <label class="field-label" for="txtPurposeRem">Purpose</label>
                                <textarea class="form-control" id="txtPurposeRem" name="purpose" rows="4" placeholder="-" style="height: 100px">{{ old('purpose')}}</textarea>
                                @if ($errors->has('purpose'))
                                    @foreach ($errors->get('purpose') as $error)
                                        <span class="error-text d-block">{{ $error }}</span>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        <div class="sub-divider"><span>Overtime Schedule</span></div>
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label class="field-label" for="txtFilingDate">Filing Date <span class="req">*</span></label>
                                <input class="form-control" id="txtFilingDate" name="dateFil" value="{{ now()->format('Y-m-d') }}" type="date" placeholder="-" readonly/>
                                <span class="error-text dateFil_error"></span>
                            </div>

                            <div class="col-lg-6">
                                <label class="field-label" for="txtFilingTime">Filing Time <span class="req">*</span></label>
                                <input class="form-control" id="txtFilingTime" name="timeFil" value="{{ now()->format('H:i') }}" type="time" placeholder="-" readonly/>
                                <span class="error-text timeFil_error"></span>
                            </div>

                            <div class="col-lg-6">
                                <label class="field-label" for="txtOTDateFrom">OT Date From <span class="req">*</span></label>
                                <input class="form-control" id="txtOTDateFrom" value="{{ old('dateFrom') }}" name="dateFrom" required type="date" placeholder="-"/>
                                @if ($errors->has('dateFrom'))
                                    @foreach ($errors->get('dateFrom') as $error)
                                        <span class="error-text d-block">{{ $error }}</span>
                                    @endforeach
                                @endif
                            </div>

                            <div class="col-lg-6">
                                <label class="field-label" for="txtOTTimeFrom">OT Time From <span class="req">*</span></label>
                                <input class="form-control" id="txtOTTimeFrom" value="{{ old('timeFrom') }}"  name="timeFrom" required type="time" placeholder="-"/>
                                @if ($errors->has('timeFrom'))
                                    @foreach ($errors->get('timeFrom') as $error)
                                        <span class="error-text d-block">{{ $error }}</span>
                                    @endforeach
                                @endif
                            </div>

                            <div class="col-lg-6">
                                <label class="field-label" for="txtOTDateTo">OT Date To <span class="req">*</span></label>
                                <input class="form-control" id="txtOTDateTo" value="{{ old('dateTo') }}" name="dateTo" required type="date" placeholder="-"/>
                                @if ($errors->has('dateTo'))
                                    @foreach ($errors->get('dateTo') as $error)
                                        <span class="error-text d-block">{{ $error }}</span>
                                    @endforeach
                                @endif
                            </div>

                            <div class="col-lg-6">
                                <label class="field-label" for="txtOTTimeTo">OT Time To <span class="req">*</span></label>
                                <input class="form-control" id="txtOTTimeTo" name="timeTo" value="{{ old('timeTo') }}" required type="time" placeholder="-"/>
                                @if ($errors->has('timeTo'))
                                    @foreach ($errors->get('timeTo') as $error)
                                        <span class="error-text d-block">{{ $error }}</span>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button id="btnSaveOT" type="submit" class="btn-submit-ot">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="mdlStatusUpdate" tabindex="-1" aria-labelledby="mdlStatusUpdateLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--teal); color: #fff;">
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
