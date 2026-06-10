@extends('layout.app')
@section('content')

<style>
    /* ── Design tokens (shared with Edit Employee / Leave / Overtime / Loan) ── */
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
    .joblevel-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    /* ── Top header bar ──────────────────────────────────────── */
    .joblevel-topbar {
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
    .joblevel-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .joblevel-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }

    .btn-add-joblevel {
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
    .btn-add-joblevel:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

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
    .joblevel-table thead th {
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
    .joblevel-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .joblevel-table tbody tr:hover { background: var(--teal-light); }

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
    #mdlJobLevel .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #mdlJobLevel .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #mdlJobLevel .modal-header .modal-title { color: #fff; }
    #mdlJobLevel .modal-header .modal-title i, #mdlJobLevel .modal-header .modal-title label { color: #fff; }
    #mdlJobLevel .btn-close { filter: brightness(0) invert(1); }
    #mdlJobLevel .modal-body { background: var(--bg); padding: 22px; }
    #mdlJobLevel .modal-footer {
        background: var(--surface);
        border-top: 1px solid var(--border);
    }
    #mdlJobLevel .card { border: none; box-shadow: none; background: transparent; }
    #mdlJobLevel .card-body { padding: 0; }

    .btn-submit-joblevel {
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
    .btn-submit-joblevel:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,.4); color: #fff; }

    .btn-cancel-joblevel {
        background: var(--surface);
        color: var(--slate-light);
        border: 1.5px solid var(--border);
        border-radius: 10px;
        padding: 10px 22px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        cursor: pointer;
        transition: all .2s;
    }
    .btn-cancel-joblevel:hover { background: var(--bg); }
</style>

<div class="joblevel-shell">

    {{-- ── Top header ── --}}
    <div class="joblevel-topbar">
        <div>
            <p class="page-title" id="jobTitle">Job Levels</p>
            <p class="page-sub">Manage job level classifications used across the system</p>
        </div>
        <button class="btn-add-joblevel" id="btnAddJobLevel" data-bs-toggle="modal" data-bs-target="#mdlJobLevel">
            <i class="fa-solid fa-layer-group"></i> Add Job Level
        </button>
    </div>

    {{-- ── Job Level Records ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-layer-group"></i></div>
                <h5 class="sc-title">Job Level Records</h5>
            </div>
        </div>
        <div class="sc-body">
            <div class="table-responsive fixTableHead" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover align-middle joblevel-table table-scroll sticky mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="ps-4">Job Level ID</th>
                            <th scope="col">Job Level</th>
                            <th scope="col" class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tblJobLevels">
                        <!-- <tr>
                            <td>1</td>
                            <td>Superuser</td>
                            <td>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#mdlJobLevel" id="btnUpdateJobLevel">
                                    <i class="fa-solid fa-pencil"></i>
                                </button>
                            </td>
                        </tr> -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="mdlJobLevel" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header dragable_touch">
                <h5 class="modal-title title lblActionDesc" id="staticBackdropLabel">
                    <i class="fa-solid fa-layer-group me-2"></i>
                    <label for="">Job Level</label>
                </h5>
                <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="card mb-3 rounded">
                    <div class="card-body">

                        <form action="" id="frmJobLevels">
                            <div class="sub-divider"><span>Job Level Details</span></div>
                            <div class="row g-3">
                                <!-- <div class="col-lg-6">
                                    <div class="form-floating mb-3">
                                        <select  class="form-control" name="company" id="selCompany" placeholder="Company">

                                        </select>
                                        <label for="selCompany">Company <label for="" class="text-danger">*</label></label>
                                        <span class="text-danger small error-text company_error"></span>
                                    </div>
                                </div> -->

                                <div class="col-lg-12">
                                    <label class="field-label" for="txtJobLevel">Job Level <span class="req">*</span></label>
                                    <input class="form-control" id="txtJobLevel" name="job" type="text" placeholder="Job Level"/>
                                    <span class="text-danger small error-text job_error"></span>
                                </div>
                            </div>

                            <!-- <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-floating mb-3">
                                        <input class="form-control" id="txtJobLevelID" name="jobID" type="text" placeholder="Job Level ID"/>
                                        <label for="txtJobLevelID">Job Level ID<label for="" class="text-danger">*</label></label>
                                        <span class="text-danger small error-text jobID_error"></span>
                                    </div>
                                </div>
                            </div> -->

                        </form>

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btnSaveJobLevel" type="button" class="btn-submit-joblevel">Save Entries</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/joblevels.js') }}" defer></script>
@endsection
