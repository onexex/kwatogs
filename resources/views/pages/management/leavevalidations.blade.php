@extends('layout.app', [
    'title' => 'Leave Validation'
])
@section('content')

<!--SHAIRA-->

<style>
    /* ── Design tokens (shared with Edit Employee / Leave / Overtime / Loans) ── */
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

    .leavevalidations-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    .leavevalidations-topbar {
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
    .leavevalidations-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .leavevalidations-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }

    .btn-add-leavevalidation {
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
    .btn-add-leavevalidation:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

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
    .sc-body { padding: 0; }

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

    .leavevalidations-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: var(--surface);
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--slate-light);
        text-transform: uppercase;
        letter-spacing: .4px;
        border-bottom: 2px solid var(--border);
        white-space: nowrap;
        padding: 12px 16px;
    }
    .leavevalidations-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .leavevalidations-table tbody tr:hover { background: var(--teal-light); }

    .icon-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1.5px solid var(--border);
        background: var(--surface);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all .15s;
    }
    .icon-action-btn:hover { border-color: var(--teal-mid); background: var(--teal-light); }
    .icon-action-btn.danger:hover { border-color: var(--danger); background: #fff5f5; }

    #mdlUpdateLeave .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #mdlUpdateLeave .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #mdlUpdateLeave .modal-header .modal-title { color: #fff; }
    #mdlUpdateLeave .modal-header .modal-title i { color: #fff; }
    #mdlUpdateLeave .btn-close { filter: brightness(0) invert(1); }
    #mdlUpdateLeave .modal-body { background: var(--bg); padding: 22px; }
    #mdlUpdateLeave .modal-footer {
        background: var(--surface);
        border-top: 1px solid var(--border);
    }
    #mdlUpdateLeave .card { border: none; box-shadow: none; background: transparent; }

    .btn-submit-leavevalidation {
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
    .btn-submit-leavevalidation:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,.4); color: #fff; }
</style>

<div class="leavevalidations-shell">

    {{-- ── Top header ── --}}
    <div class="leavevalidations-topbar">
        <div>
            <p class="page-title">Leave Validations</p>
            <p class="page-sub">Configure leave entitlement, credit and filing rules</p>
        </div>
        <button class="btn-add-leavevalidation" name="btnCreateLeave" id="btnCreateLeave" data-bs-toggle="modal" data-bs-target="#mdlUpdateLeave">
            <i class="fa fa-plus"></i> Leave Validation
        </button>
    </div>

    {{-- ── Leave Validation Records ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-calendar-check"></i></div>
                <h5 class="sc-title">Leave Validation Records</h5>
            </div>
        </div>
        <div class="sc-body">
            <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover align-middle leavevalidations-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Company Name</th>
                            <th>Leave</th>
                            <th>Credits</th>
                            <th>Minimum Leave</th>
                            <th>No. of Day filling Before Leave</th>
                            <th>No. of Day filling After Leave</th>
                            <th>File Before</th>
                            <th>File After</th>
                            <th>File HalfDay</th>
                            <th>Pre Allocated</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tblLeave">
                        <!-- <tr>
                            <td>Wedo</td>
                            <td>Vacation</td>
                            <td>1</td>
                            <td>0</td>
                            <td>0</td>
                            <td>YES</td>
                            <td>YES</td>
                            <td>30</td>
                            <td>NO</td>
                            <td><button class="btn btn-danger btn-sm radius-1" name="btnUpdateModal" id="btnUpdateModal" data-bs-toggle="modal" data-bs-target="#mdlUpdateLeave"><i class="fa-solid fa-pen"></i></button></td>
                        </tr> -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Update Leave Validations -->
<div class="modal fade" id="mdlUpdateLeave" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header dragable_touch">
                <h5 class="modal-title fst-italic lblActionDesc" id="staticBackdropLabel">
                    <i class="fa-solid fa-calendar-check me-2"></i>
                    <label for="" class="" id="lblTitleUpdate">Update Leave</label>
                </h5>
                <button type="button" class="btn-close closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card mb-3 rounded">
                    <div class="card-body">

                        <form action="" id="frmCreateleaveValidation">

                            <div class="row g-3">

                                <div class="col-lg-6">
                                    <div class="row g-3">

                                        <div class="col-lg-12">
                                            <label class="field-label" for="selCompanyNameU">Company Name <span class="req">*</span></label>
                                            <select  class="form-select" name="company" id="selCompanyNameU"  >
                                                @if(count($company)>0)
                                                    @foreach($company as $companys)
                                                    <option value='{{$companys->comp_id }}'>{{$companys->comp_name }}</option>
                                                    @endforeach
                                                @else

                                                @endif
                                            </select>
                                            <span class="text-danger small error-text company_error"></span>
                                        </div>

                                        <div class="col-lg-12">
                                            <label class="field-label" for="selLeaveTypeU">Leave Type <span class="req">*</span></label>
                                            <select  class="form-select" name="leave" id="selLeaveTypeU"  >
                                                @if(count($leaveTypeData)>0)
                                                    @foreach($leaveTypeData as $leaveTypeDatas)
                                                    <option value='{{$leaveTypeDatas->id }}'>{{$leaveTypeDatas->type_leave }}</option>
                                                    @endforeach
                                                @else

                                                @endif
                                            </select>
                                            <span class="text-danger small error-text leave_error"></span>
                                        </div>

                                        <div class="col-lg-12">
                                            <label class="field-label" for="txtDaysBefore">No of Day Before Leave <span class="req">*</span></label>
                                            <input class="form-control" id="txtDaysBefore" name="daysbefore" type="number" placeholder="-"/>
                                            <span class="text-danger small error-text daysbefore_error"></span>
                                        </div>

                                        <div class="col-lg-12">
                                            <label class="field-label" for="selFileBefore">Can File Before <span class="req">*</span></label>
                                            <select  class="form-select" name="filebefore" id="selFileBefore"  >
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                            <span class="text-danger small error-text filebefore_error"></span>
                                        </div>

                                        <div class="col-lg-12">
                                            <label class="field-label" for="selHalfday">Half Day <span class="req">*</span></label>
                                            <select  class="form-select" name="halfday" id="selHalfday"  >
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                            <span class="text-danger small error-text halfday_error"></span>
                                        </div>

                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="row g-3">

                                        <div class="col-lg-12">
                                            <label class="field-label" for="txtCreditsU">Credits <span class="req">*</span></label>
                                            <input class="form-control" id="txtCreditsU" name="credits" type="number" placeholder="-"/>
                                            <span class="text-danger small error-text credits_error"></span>
                                        </div>

                                        <div class="col-lg-12">
                                            <label class="field-label" for="txtMinLeaveU">Minimun Leave <span class="req">*</span></label>
                                            <input class="form-control" id="txtMinLeaveU" name="minimum" type="number" placeholder="-"/>
                                            <span class="text-danger small error-text minimum_error"></span>
                                        </div>

                                        <div class="col-lg-12">
                                            <label class="field-label" for="txtDaysAfter">No of Day After Leave <span class="req">*</span></label>
                                            <input class="form-control" id="txtDaysAfter" name="daysafter" type="number" placeholder="-"/>
                                            <span class="text-danger small error-text daysafter_error"></span>
                                        </div>

                                        <div class="col-lg-12">
                                            <label class="field-label" for="selFileAfter">Can File After <span class="req">*</span></label>
                                            <select  class="form-select" name="fileafter" id="selFileAfter"  >
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                            <span class="text-danger small error-text fileafter_error"></span>
                                        </div>

                                        <div class="col-lg-12">
                                            <label class="field-label" for="selpreAllocated">Is Pre-Allocated <span class="req">*</span></label>
                                            <select  class="form-select" name="pre_allocated" id="selpreAllocated"  >
                                                <option value="1">Yes</option>
                                                <option selected value="0">No</option>
                                            </select>
                                            <span class="text-danger small error-text preAllocated_error"></span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </form>

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button  id="btnUpdateLeave" type="button" class="btn-submit-leavevalidation">Save Entries</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/leavevalidations.js') }}"  deffer></script>

@endsection
