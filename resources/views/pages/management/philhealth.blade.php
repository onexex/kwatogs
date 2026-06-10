@extends('layout.app')
@section('content')

<style>
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

    .ph-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    .ph-topbar {
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
    .ph-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .ph-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }

    .btn-add-ph {
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
    .btn-add-ph:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

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

    .ph-table thead th {
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
    .ph-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .ph-table tbody tr:hover { background: var(--teal-light); }

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
    #mdlPhilhealth .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #mdlPhilhealth .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #mdlPhilhealth .modal-header .modal-title,
    #mdlPhilhealth .modal-header .modal-title label { color: #fff; }
    #mdlPhilhealth .btn-close { filter: brightness(0) invert(1); }
    #mdlPhilhealth .modal-body { background: var(--bg); padding: 22px; }
    #mdlPhilhealth .modal-body .card {
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        box-shadow: none;
    }
    #mdlPhilhealth .modal-footer {
        background: var(--surface);
        border-top: 1px solid var(--border);
    }

    .btn-submit-ph {
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
    .btn-submit-ph:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,.4); color: #fff; }
</style>

<div class="ph-shell">

    {{-- ── Top header ── --}}
    <div class="ph-topbar">
        <div>
            <p class="page-title" id="jobTitle">PhilHealth</p>
            <p class="page-sub">Manage PhilHealth salary brackets and contribution rates</p>
        </div>
        <button class="btn-add-ph" id="btnAddPhilhealth" data-bs-toggle="modal" data-bs-target="#mdlPhilhealth">
            <i class="fa-solid fa-plus"></i> PhilHealth
        </button>
    </div>

    {{-- ── PhilHealth Records ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-kit-medical"></i></div>
                <h5 class="sc-title">PhilHealth Contribution Brackets</h5>
            </div>
        </div>
        <div class="sc-body">
            <div class="table-responsive fixTableHead" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover table-scroll sticky ph-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" scope="col">PHSB</th>
                            <th scope="col">Salary From</th>
                            <th scope="col">Salary To</th>
                            <th scope="col">PHEE</th>
                            <th scope="col">PHER</th>
                            <th class="pe-4 text-end" scope="col">Update</th>
                        </tr>
                    </thead>
                    <tbody id="tblPhilhealth">

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="mdlPhilhealth" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header dragable_touch">
                <h5 class="modal-title lblActionDesc" id="staticBackdropLabel">
                    <i class="fa-solid fa-kit-medical me-2"></i>
                    <label for="">Philhealth</label>
                </h5>
                <button type="button" class="btn-close text-white closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="card mb-3 rounded">
                    <div class="card-body">

                        <form action="" id="frmPhilhealth">

                            <div class="sub-divider"><span>Bracket Identifier</span></div>
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <label class="field-label" for="numPHSB">PHSB <span class="req">*</span></label>
                                    <input class="form-control" id="numPHSB" name="PHSB" type="number" placeholder="PHSB"/>
                                    <span class="text-danger small error-text PHSB_error"></span>
                                </div>
                            </div>

                            <div class="sub-divider"><span>Salary Range</span></div>
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <label class="field-label" for="numFrom">Salary From <span class="req">*</span></label>
                                    <input class="form-control" id="numFrom" name="from" type="number" placeholder="From"/>
                                    <span class="text-danger small error-text from_error"></span>
                                </div>

                                <div class="col-lg-6">
                                    <label class="field-label" for="numTo">Salary To <span class="req">*</span></label>
                                    <input class="form-control" id="numTo" name="to" type="number" placeholder="To"/>
                                    <span class="text-danger small error-text to_error"></span>
                                </div>
                            </div>

                            <div class="sub-divider"><span>Contribution Rates</span></div>
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <label class="field-label" for="numPHEE">PHEE <span class="req">*</span></label>
                                    <input class="form-control" id="numPHEE" name="PHEE" type="number" placeholder="PHEE"/>
                                    <span class="text-danger small error-text PHEE_error"></span>
                                </div>
                                <div class="col-lg-6">
                                    <label class="field-label" for="numPHER">PHER <span class="req">*</span></label>
                                    <input class="form-control" id="numPHER" name="PHER" type="number" placeholder="PHER"/>
                                    <span class="text-danger small error-text PHER_error"></span>
                                </div>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btnSavePhilhealth" type="button" class="btn-submit-ph">Save Entries</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/philhealth.js') }}" defer></script>
@endsection
