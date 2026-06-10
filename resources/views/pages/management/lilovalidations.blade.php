@extends('layout.app')
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

    .lilovalidations-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    .lilovalidations-topbar {
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
    .lilovalidations-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .lilovalidations-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }

    .btn-add-lilovalidation {
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
    .btn-add-lilovalidation:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

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

    .lilovalidations-table thead th {
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
    .lilovalidations-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .lilovalidations-table tbody tr:hover { background: var(--teal-light); }

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

    #mdlLiloVal .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #mdlLiloVal .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #mdlLiloVal .modal-header .modal-title { color: #fff; }
    #mdlLiloVal .btn-close { filter: brightness(0) invert(1); }
    #mdlLiloVal .modal-body { background: var(--bg); padding: 22px; }
    #mdlLiloVal .modal-footer {
        background: var(--surface);
        border-top: 1px solid var(--border);
    }
    #mdlLiloVal .card { border: none; box-shadow: none; background: transparent; }

    .btn-submit-lilovalidation {
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
    .btn-submit-lilovalidation:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,.4); color: #fff; }
</style>

<div class="lilovalidations-shell">

    {{-- ── Top header ── --}}
    <div class="lilovalidations-topbar">
        <div>
            <p class="page-title">Lilo Validation</p>
            <p class="page-sub">Configure late/undertime grace periods and deduction rules</p>
        </div>
        <button class="btn-add-lilovalidation" name="btnCreateMdl" id="btnCreateMdl" data-bs-toggle="modal" data-bs-target="#mdlLiloVal">
            <i class="fa fa-plus"></i> Lilo Validation
        </button>
    </div>

    {{-- ── Lilo Validation Records ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <h5 class="sc-title">Lilo Validation Records</h5>
            </div>
        </div>
        <div class="sc-body">
            <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover align-middle lilovalidations-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Grace Period</th>
                            <th>Managers Override</th>
                            <th>Managers Time</th>
                            <th>No Logout Has Deduction</th>
                            <th>Minute Deduction</th>
                            {{-- <th>Updated Time</th> --}}
                            <th class="pe-4 text-end">Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lilovalidations as $lilovalidation)
                            <tr>
                                <td class="ps-4">{{ $lilovalidation->lilo_gracePrd }}</td>
                                <td>{{ $lilovalidation->managersOverride }}</td>
                                <td>{{ $lilovalidation->managersTime }}</td>
                                <td>{{ $lilovalidation->no_logout_has_deduction == 0 ? 'No' : 'Yes' }}</td>
                                <td>{{ $lilovalidation->minute_deduction }}</td>
                                <td class="pe-4 text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" value='{{ $lilovalidation->id  }}' class="icon-action-btn" data-toggle="tooltip" data-placement="bottom" id="btnUpdateMdl" title="Schedule" data-bs-toggle="modal" data-bs-target="#mdlLiloVal">
                                            <i class="fa fa-pencil" style="color: var(--teal);"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lilo Validation -->
<div class="modal fade" id="mdlLiloVal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header dragable_touch">
                <h5 class="modal-title" id="staticBackdropLabel">
                    <i class="fa-solid fa-clock-rotate-left me-2"></i>
                    <label for="" class="" id="lblTitleGraceP">Lilo Validation</label>
                </h5>
                <button type="button" class="btn-close closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="card mb-3 rounded">
                    <div class="card-body">

                        <form action="" id="frmLiloVal">
                            <div class="row g-3">
                                <div class="col-lg-12">
                                    <label class="field-label" for="txtGracePeriod">Grace Period <span class="req">*</span></label>
                                    <input class="form-control" id="txtGracePeriod" name="graceperiod" type="number" placeholder="-"/>
                                    <span class="text-danger small error-text graceperiod_error"></span>
                                </div>
                                <div class="col-lg-12">
                                    <label class="field-label" for="txtMngrOverride">Managers Override <span class="req">*</span></label>
                                    <input class="form-control" id="txtMngrOverride" name="mngrsOverride" type="number" placeholder="-"/>
                                    <span class="text-danger small error-text mngrsOverride_error"></span>
                                </div>
                                <div class="col-lg-12">
                                    <label class="field-label" for="txtMngrTime">Managers Time <span class="req">*</span></label>
                                    <input class="form-control" id="txtMngrTime" name="mngrsTime" type="number" placeholder="-"/>
                                    <span class="text-danger small error-text mngrsTime_error"></span>
                                </div>
                                <div class="col-lg-12">
                                    <label class="field-label" for="no_logout_deduction">No Logout Has Deduction <span class="req">*</span></label>
                                    <select  class="form-select" name="no_logout_has_deduction" id="no_logout_deduction"  >
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                    <span class="text-danger small error-text status_error"></span>
                                </div>
                                <div class="col-lg-12">
                                    <label class="field-label" for="minute_deduction">Minute Deduction <span class="req">*</span></label>
                                    <input class="form-control" id="minute_deduction" name="minute_deduction" type="number" placeholder="-"/>
                                    <span class="text-danger small error-text mngrsTime_error"></span>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button  id="btnLiloVal" type="button" class="btn-submit-lilovalidation">Submit</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/liloValidations.js') }}"  deffer></script>

@endsection
