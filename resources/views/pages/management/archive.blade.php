@extends('layout.app')
@section('content')

<style>
    /* ── Design tokens (shared with Edit Employee / Loan) ── */
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

    .archive-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    .archive-topbar {
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
    .archive-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .archive-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }

    .btn-add-archive {
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
    .btn-add-archive:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

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
    .sc-body { padding: 0; }

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
    .form-floating > .form-control,
    .form-floating > .form-select {
        height: calc(3.2rem + 2px);
        padding: 1rem 0.85rem;
    }
    .form-floating > label { color: var(--slate-light); font-size: 0.85rem; }

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
    .archive-table thead th {
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
    .archive-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .archive-table tbody tr:hover { background: var(--teal-light); }

    .badge-status {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: .3px;
        padding: 5px 12px;
        border-radius: 20px;
        border: 1px solid transparent;
    }
    .badge-status.active {
        background: rgba(16,185,129,.1);
        color: var(--success);
        border-color: var(--success);
    }
    .badge-status.inactive {
        background: var(--bg);
        color: var(--slate-light);
        border-color: var(--border);
    }

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

    /* ── Modal styling ───────────────────────────────────────── */
    #mdlRegEmployee .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #mdlRegEmployee .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #mdlRegEmployee .modal-header .modal-title,
    #mdlRegEmployee .modal-header .modal-title label { color: #fff; }
    #mdlRegEmployee .btn-close { filter: brightness(0) invert(1); }
    #mdlRegEmployee .modal-body { background: var(--bg); padding: 22px; }
    #mdlRegEmployee .modal-footer {
        background: var(--surface);
        border-top: 1px solid var(--border);
    }
    #mdlRegEmployee .card { border: 1px solid var(--border); border-radius: var(--radius-card); box-shadow: none; }

    .btn-submit-archive {
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
    .btn-submit-archive:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,.4); color: #fff; }

    /* ── Sticky table head (preserve legacy class used by archive.js) ── */
    .fixTableHead {
      overflow-y: auto;
      height: 100%;
    }
    .fixTableHead thead th {
      position: sticky;
      top: 0;
      background-color: var(--surface);
    }
</style>

<div class="archive-shell">

    {{-- ── Top header ── --}}
    <div class="archive-topbar">
        <div>
            <p class="page-title">Archive Management System</p>
            <p class="page-sub">Manage archived and resigned employee records</p>
        </div>
        <button class="btn-add-archive" id="btnRegEmployee" data-bs-toggle="modal" data-bs-target="#mdlRegEmployee">
            <i class="fa-solid fa-plus"></i> Register Employee
        </button>
    </div>

    {{-- ── Search ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                <h5 class="sc-title">Search Records</h5>
            </div>
        </div>
        <div class="sc-body" style="padding: 20px 22px;">
            <div class="row">
                <div class="col-lg-4">
                    <form action='' id="frmSearch">
                        <div class="form-floating mb-0">
                            <input class="form-control" id="txtSearchEmp" name="search" type="text" placeholder="Search Employee"/>
                            <label for="txtSearchEmp">Search Last Name</label>
                            <span class="text-danger small error-text search_error"></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Archive Records ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-box-archive"></i></div>
                <h5 class="sc-title">Archived Records</h5>
            </div>
        </div>
        <div class="sc-body">
            <div class="chart-area overflow-auto;">
                <div class="table-responsive fixTableHead" style="max-height: 75vh; overflow-y: auto;">
                    <table class="table table-hover align-middle archive-table table-scroll sticky mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="ps-4">No</th>
                                <th scope="col">Agency Name</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="tblEmployee" class="border-top-0">

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

  <!-- Modal -->
    <div class="modal fade" id="mdlRegEmployee" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header dragable_touch">
                    <h5 class="modal-title title" id="staticBackdropLabel"><label for=""> Employee Registration </label></h5>
                    <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="card  mb-3 rounded">
                        <div class="card-body ">

                            <form action="" id="frmRegEmp">
                                <div class="sub-divider"><span>Personal Information</span></div>
                                <div class="row mb-3">

                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtFname" name="fname" type="text" placeholder="First Name"/>
                                            <label for="txtFname">First Name <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text fname_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtLname" name="lname" type="text" placeholder="Last Name"/>
                                            <label for="txtLname">Last Name <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text lname_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="form-floating mb-1">
                                            {{-- <select  class="form-control" name="position" id="selPosition"  >
                                                <option value="">Choose...</option>
                                                <option value="1">programmer</option>
                                                <option value="2">IT Supervisor</option>
                                                <option value="3">Agent</option>
                                                <option value="4">IT Support Specialist</option>
                                                <option value="5">Graphic Designer</option>
                                            </select> --}}
                                            <select class="form-select" aria-label="Default select example" id="selPosition" name="position">
                                                <option value="">Choose...</option>
                                                 @if(count($result)>0)
                                                     @foreach($result as $results)
                                                     <option value='{{ $results->id }}'>{{ $results->pos_desc }}</option>
                                                     @endforeach
                                                 @else

                                                 @endif
                                             </select>
                                            <label for="selPosition" class="text-muted"> Position<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text position_error"></span>
                                        </div>
                                    </div>

                                </div>

                                <div class="sub-divider"><span>Employment Date</span></div>
                                <div class="row mb-3">
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtDateFrom" name="datefrom" type="date" placeholder="Date From"/>
                                            <label for="txtFrom">From <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text datefrom_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtDateTo" name="dateto" type="date" placeholder="Date To"/>
                                            <label for="txtTo">To <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text dateto_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="sub-divider"><span>Status &amp; Clearance</span></div>
                                <div class="row mb-3">
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-1">
                                            <select  class="form-control" name="status" id="selStatus"  >
                                                <option value="">Choose...</option>
                                                <option value="1">Back End</option>
                                                <option value="2">IC</option>
                                            </select>
                                            {{-- <select class="form-select" aria-label="Default select example" id="selPosition" name="position">
                                                <option value="">Choose...</option>
                                                 @if(count($result)>0)
                                                     @foreach($result as $results)
                                                     <option value='{{ $results->id }}'>{{ $results->pos_desc }}</option>
                                                     @endforeach
                                                 @else

                                                 @endif
                                             </select> --}}

                                            <label for="selStatus" class="text-muted"> Status<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text status_error"></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-1">
                                            <select  class="form-control" name="clearance" id="selClearance"  >
                                                <option value="">Choose...</option>
                                                <option value="1">Yes</option>
                                                <option value="2">No</option>
                                            </select>
                                            <label for="selClearance" class="text-muted"> Clearance<label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text clearance_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="sub-divider"><span>Separation Details</span></div>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtReason" name="reason" type="text" placeholder="Reason for Leaving "/>
                                            <label for="txtReason">Reason for Leaving <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text reason_error"></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label class="field-label" for="txtDerogatory">Derogatory Records</label>
                                            <textarea rows="4" class="form-control" id="txtDerogatory" name="derogatory"></textarea>
                                            <span class="text-danger small error-text derogatory_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtSalary" name="salary" type="number" placeholder="Salary"/>
                                            <label for="txtSalary">Salary <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text salary_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtResignation" name="resignation" type="text" placeholder="Resignation"/>
                                            <label for="txtResignation">Pending Resignation <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text resignation_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label class="field-label" for="txtRemarks">Additional Remarks</label>
                                            <textarea rows="4" class="form-control" id="txtRemarks" name="remarks"></textarea>
                                            <span class="text-danger small error-text remarks_error"></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="txtVerify" name="verify" type="text" placeholder="Verify"/>
                                            <label for="txtVerify">Verified By <label for="" class="text-danger">*</label></label>
                                            <span class="text-danger small error-text verify_error"></span>
                                        </div>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button  id="btnSaveEmployee" type="button" class="btn-submit-archive">Save Entries</button>
                </div>
            </div>
        </div>
    </div>

<script src="{{ asset('js/settings/archive.js') }}" defer></script>
@endsection
