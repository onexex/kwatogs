@extends('layout.app')
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8; --bg:#f1f5f9;
        --surface:#ffffff; --border:#e2e8f0; --danger:#ef4444; --success:#10b981; --warning:#f59e0b;
        --radius-card:14px; --radius-input:8px; --shadow-card:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);
    }
    .ctb-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .ctb-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
    .ctb-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; letter-spacing:-.2px; }
    .ctb-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .ctb-tools { display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap; }
    .btn-add { background:var(--teal); color:#fff; border:none; border-radius:8px; padding:10px 20px; font-size:.82rem; font-weight:700; letter-spacing:.3px; cursor:pointer; box-shadow:0 4px 14px rgba(0,128,128,.25); transition:all .2s; display:inline-flex; align-items:center; gap:8px; }
    .btn-add:hover { background:var(--teal-dark); transform:translateY(-1px); box-shadow:0 6px 20px rgba(0,128,128,.35); color:#fff; }
    .sc { background:var(--surface); border-radius:var(--radius-card); border:1px solid var(--border); box-shadow:var(--shadow-card); margin-bottom:20px; overflow:hidden; }
    .sc-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:14px 22px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .sc-head-left { display:flex; align-items:center; gap:10px; }
    .sc-icon { width:30px; height:30px; border-radius:8px; background:var(--teal-light); color:var(--teal); display:flex; align-items:center; justify-content:center; font-size:.78rem; flex-shrink:0; }
    .sc-title { font-size:.78rem; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; margin:0; }
    .field-label { font-size:.7rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px; display:block; }
    .field-label .req { color:var(--danger); margin-left:2px; }
    .form-control, .form-select { border:1.5px solid var(--border); border-radius:var(--radius-input); font-size:.875rem; color:var(--slate); background:#fafbfc; transition:border-color .15s, box-shadow .15s; padding:.55rem .85rem; }
    .form-control:focus, .form-select:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(0,128,128,.1); background:#fff; outline:none; }
    .sub-divider { display:flex; align-items:center; gap:10px; margin:6px 0 18px; }
    .sub-divider span { font-size:.73rem; font-weight:700; color:var(--teal); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; }
    .sub-divider::after { content:''; flex-grow:1; height:1px; background:var(--border); }
    .ctb-table thead th { position:sticky; top:0; z-index:10; background:var(--surface); font-size:.68rem; font-weight:700; color:var(--slate-light); text-transform:uppercase; letter-spacing:.4px; border-bottom:2px solid var(--border); white-space:nowrap; padding:12px 14px; }
    .ctb-table tbody td { font-size:.82rem; color:var(--slate); vertical-align:middle; padding:11px 14px; white-space:nowrap; }
    .ctb-table tbody tr:hover { background:var(--teal-light); }
    .yr-badge { font-size:.68rem; font-weight:800; background:var(--teal-light); color:var(--teal-dark); border:1px solid var(--teal-mid); padding:3px 10px; border-radius:999px; }
    .icon-action-btn { width:32px; height:32px; border-radius:8px; border:1.5px solid var(--border); background:var(--surface); display:inline-flex; align-items:center; justify-content:center; transition:all .15s; cursor:pointer; }
    .icon-action-btn:hover { border-color:var(--teal-mid); background:var(--teal-light); }
    .icon-action-btn.danger:hover { border-color:var(--danger); background:#fff5f5; color:var(--danger); }
    #mdlPHIC .modal-content { border-radius:var(--radius-card); border:none; overflow:hidden; }
    #mdlPHIC .modal-header { background:var(--teal); color:#fff; border-bottom:none; padding:16px 22px; }
    #mdlPHIC .modal-header .modal-title, #mdlPHIC .modal-header .modal-title label { color:#fff; }
    #mdlPHIC .btn-close { filter:brightness(0) invert(1); }
    #mdlPHIC .modal-body { background:var(--bg); padding:22px; }
    #mdlPHIC .modal-footer { background:var(--surface); border-top:1px solid var(--border); }
    .btn-submit { background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%); color:#fff; border:none; border-radius:10px; padding:10px 26px; font-size:.82rem; font-weight:700; letter-spacing:.4px; text-transform:uppercase; cursor:pointer; box-shadow:0 4px 14px rgba(245,158,11,.3); transition:all .2s; }
    .btn-submit:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(245,158,11,.4); color:#fff; }
    .hint { font-size:.72rem; color:var(--slate-light); background:#fff8e6; border:1px solid #fde68a; border-radius:8px; padding:8px 12px; margin-top:14px; }
</style>

<div class="ctb-shell">
    <div class="ctb-topbar">
        <div>
            <p class="page-title">PhilHealth Contribution Table</p>
            <p class="page-sub">Premium brackets and shares used by payroll for PhilHealth deductions</p>
        </div>
        <div class="ctb-tools">
            <div>
                <label class="field-label" for="selYear">Effective Year</label>
                <select class="form-select form-select-sm" id="selYear" style="min-width:140px;"></select>
            </div>
            <button class="btn-add" id="btnCreatePHIC" data-bs-toggle="modal" data-bs-target="#mdlPHIC">
                <i class="fa-solid fa-plus"></i> Add Bracket
            </button>
        </div>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-head-left">
                <div class="sc-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                <h5 class="sc-title">PhilHealth Contribution Brackets</h5>
            </div>
            <span class="yr-badge" id="rowCount">0 brackets</span>
        </div>
        <div class="table-responsive" style="max-height:75vh; overflow-y:auto;">
            <table class="table table-hover align-middle ctb-table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Year</th>
                        <th>Salary From</th>
                        <th>Salary To</th>
                        <th>Premium Rate %</th>
                        <th>EE Share %</th>
                        <th>ER Share %</th>
                        <th>Min Salary</th>
                        <th>Max Salary</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody id="tblPHIC"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="mdlPHIC" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-heart-pulse me-2"></i><label class="lblActionDesc" id="lblTitlePHIC">Add PhilHealth Bracket</label></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="frmPHIC">
                    <div class="sub-divider"><span>Bracket</span></div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="field-label">Effective Year <span class="req">*</span></label>
                            <input class="form-control" id="txtYear" name="effective_year" type="number" placeholder="e.g. 2026"/>
                            <span class="text-danger small error-text effective_year_error"></span>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Salary From <span class="req">*</span></label>
                            <input class="form-control" id="txtRangeFrom" name="range_from" type="number" step="0.01" placeholder="-"/>
                            <span class="text-danger small error-text range_from_error"></span>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Salary To <span class="req">*</span></label>
                            <input class="form-control" id="txtRangeTo" name="range_to" type="number" step="0.01" placeholder="-"/>
                            <span class="text-danger small error-text range_to_error"></span>
                        </div>
                    </div>

                    <div class="sub-divider"><span>Premium &amp; Shares</span></div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="field-label">Premium Rate % <span class="req">*</span></label>
                            <input class="form-control" id="txtPremium" name="premium_rate" type="number" step="0.01" placeholder="e.g. 5"/>
                            <span class="text-danger small error-text premium_rate_error"></span>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">EE Share % <span class="req">*</span></label>
                            <input class="form-control" id="txtEE" name="employee_share" type="number" step="0.01" placeholder="e.g. 2.5"/>
                            <span class="text-danger small error-text employee_share_error"></span>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">ER Share % <span class="req">*</span></label>
                            <input class="form-control" id="txtER" name="employer_share" type="number" step="0.01" placeholder="e.g. 2.5"/>
                            <span class="text-danger small error-text employer_share_error"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">Min Salary (floor) <span class="req">*</span></label>
                            <input class="form-control" id="txtMin" name="min_salary" type="number" step="0.01" placeholder="-"/>
                            <span class="text-danger small error-text min_salary_error"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">Max Salary (ceiling) <span class="req">*</span></label>
                            <input class="form-control" id="txtMax" name="max_salary" type="number" step="0.01" placeholder="-"/>
                            <span class="text-danger small error-text max_salary_error"></span>
                        </div>
                    </div>
                    <div class="hint"><i class="fa-solid fa-circle-info me-1"></i> Premium = capped salary × Premium Rate%. The EE% and ER% are points of the premium rate (they should add up to it). Payroll uses the bracket with the <b>highest effective year</b> matching the salary.</div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button id="btnSavePHIC" type="button" class="btn-submit">Save</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/settings/philhealth.js') }}" defer></script>
@endsection
