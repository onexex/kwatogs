@extends('layout.app')
@section('content')

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

    .otfile-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    .otfile-topbar {
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
    .otfile-topbar .page-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--slate);
        margin: 0;
        letter-spacing: -.2px;
    }
    .otfile-topbar .page-sub {
        font-size: .78rem;
        color: var(--muted);
        margin: 2px 0 0;
    }

    .btn-add-otfile {
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
    .btn-add-otfile:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,128,128,.35); color: #fff; }

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

    .otfile-table thead th {
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
    .otfile-table tbody td {
        font-size: 0.83rem;
        color: var(--slate);
        vertical-align: middle;
        padding: 12px 16px;
    }
    .otfile-table tbody tr:hover { background: var(--teal-light); }

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

    #mdlOTFile .modal-content {
        border-radius: var(--radius-card);
        border: none;
        overflow: hidden;
    }
    #mdlOTFile .modal-header {
        background: var(--teal);
        color: #fff;
        border-bottom: none;
        padding: 16px 22px;
    }
    #mdlOTFile .modal-header .modal-title { color: #fff; }
    #mdlOTFile .btn-close { filter: brightness(0) invert(1); }
    #mdlOTFile .modal-body { background: var(--bg); padding: 22px; }
    #mdlOTFile .modal-footer {
        background: var(--surface);
        border-top: 1px solid var(--border);
    }
    #mdlOTFile .card { border: none; box-shadow: none; background: transparent; }

    .btn-submit-otfile {
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
    .btn-submit-otfile:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,158,11,.4); color: #fff; }
</style>

<div class="otfile-shell">

    {{-- ── Top header ── --}}
    <div class="otfile-topbar">
        <div>
            <p class="page-title title">OT Filing Maintenance</p>
            <p class="page-sub">Configure overtime filing windows per company</p>
        </div>
        <button class="btn-add-otfile" name="btnCreateOTMaintinance" id="btnCreateOTMaintinance" data-bs-toggle="modal" data-bs-target="#mdlOTFile">
            <i class="fa fa-plus"></i> Filing Maintenance
        </button>
    </div>

    <!-- Content Row dar -->
    <div class="tblTitle col-lg-12">
        <p for="" id="lblOpt" class="text-danger mt-1 fs-3"></p>
    </div>

    {{-- ── OT Filing Records ── --}}
    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-wrench"></i></div>
                <h5 class="sc-title">OT Filing Records</h5>
            </div>
        </div>
        <div class="sc-body">
            <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                <table class="table table-hover align-middle otfile-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Company</th>
                            <th>File Before</th>
                            <th>File After</th>
                            <th>No of Days Before</th>
                            <th>No of Days After</th>
                            <th>Holiday</th>
                            <th>Tardy</th>
                            <th class="pe-4 text-end">Update</th>
                        </tr>
                    </thead>
                    <tbody id="tblOTFile">

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- modal  --}}

<div class="modal fade" id="mdlOTFile" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header dragable_touch">
                <h5 class="modal-title fst-italic lblActionDesc title" id="staticBackdropLabel">
                    <i class="fa-solid fa-wrench me-2"></i>
                    <label for=""> OT Maintenance </label>
                </h5>
                <button type="button" class="btn-close closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card mb-3 rounded">
                    <div class="card-body">

                        <form action="" id="frmOTFile">

                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <label class="field-label" for="txtCompany">Company <span class="req">*</span></label>
                                    <select  class="form-select" name="company" id="txtCompany"  >
                                        @if(count($companyData)>0)
                                            @foreach($companyData as $companyDatas)
                                            <option value='{{$companyDatas->comp_id }}'>{{$companyDatas->comp_name }}</option>
                                            @endforeach
                                        @else

                                        @endif
                                    </select>
                                    <span class="text-danger small error-text company_error"></span>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-lg-6">
                                    <label class="field-label" for="selBefore">File Before <span class="req">*</span></label>
                                    <select  class="form-select" name="before" id="selBefore">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                    <span class="text-danger small error-text before_error"></span>
                                </div>

                                <div class="col-lg-6">
                                    <label class="field-label" for="selAfter">File After <span class="req">*</span></label>
                                    <select  class="form-select" name="after" id="selAfter">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                    <span class="text-danger small error-text after_error"></span>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-lg-6">
                                    <label class="field-label" for="selHoliday">Is Holiday <span class="req">*</span></label>
                                    <select  class="form-select" name="holiday" id="selHoliday">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                    <span class="text-danger small error-text holiday_error"></span>
                                </div>

                                <div class="col-lg-6">
                                    <label class="field-label" for="selTardy">Is Tardy <span class="req">*</span></label>
                                    <select  class="form-select" name="tardy" id="selTardy">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                    <span class="text-danger small error-text tardy_error"></span>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-lg-6">
                                    <label class="field-label" for="txtDaysBefore">No of Days Before <span class="req">*</span></label>
                                    <input class="form-control" id="txtDaysBefore" name="daysBefore" type="number" placeholder="Days Before"/>
                                    <span class="text-danger small error-text daysBefore_error"></span>
                                </div>
                                <div class="col-lg-6">
                                    <label class="field-label" for="txtDaysAfter">No of Days After <span class="req">*</span></label>
                                    <input class="form-control" id="txtDaysAfter" name="daysAfter" type="number" placeholder="Days After"/>
                                    <span class="text-danger small error-text daysAfter_error"></span>
                                </div>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button  id="btnOTFile" type="button" class="btn-submit-otfile">Save Entries</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/otfiling.js') }}" defer></script>
@endsection
