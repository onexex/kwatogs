@extends('layout.app', ['title' => 'Apply Employee Overtime'])
@section('content')

<style>
    :root {
        --teal:        #008080;
        --teal-dark:   #006666;
        --teal-mid:    #4db6ac;
        --teal-light:  #e0f2f1;
        --slate:       #334155;
        --slate-light: #64748b;
        --muted:       #94a3b8;
        --bg:          #f1f5f9;
        --border:      #e2e8f0;
        --radius-card: 14px;
        --shadow-card: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }

    .aot-shell { background: var(--bg); min-height: 100vh; padding: 24px 28px 60px; margin: -1.5rem -1.5rem 0; }

    /* ── Topbar + stat chips ── */
    .aot-topbar {
        background: #fff; border: 1px solid var(--border); border-radius: var(--radius-card);
        box-shadow: var(--shadow-card); padding: 16px 22px; margin-bottom: 16px;
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;
    }
    .aot-topbar .page-title { font-size: 1.1rem; font-weight: 700; color: var(--slate); margin: 0; }
    .aot-topbar .page-sub   { font-size: .78rem; color: var(--muted); margin: 2px 0 0; }

    .aot-stats { display: flex; gap: 10px; flex-wrap: wrap; }
    .aot-stat  { display: flex; align-items: center; gap: 9px; background: #fafbfc; border: 1px solid var(--border); border-radius: 10px; padding: 8px 14px; }
    .aot-stat .ic { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: .8rem; }
    .aot-stat .ic.t { background: var(--teal-light); color: var(--teal); }
    .aot-stat .ic.w { background: #fef9c3; color: #854d0e; }
    .aot-stat .ic.a { background: #dcfce7; color: #15803d; }
    .aot-stat .ic.d { background: #fee2e2; color: #b91c1c; }
    .aot-stat .n { font-size: 1.05rem; font-weight: 800; color: var(--slate); line-height: 1; }
    .aot-stat .l { font-size: .64rem; font-weight: 700; color: var(--slate-light); text-transform: uppercase; letter-spacing: .4px; margin-top: 2px; }

    /* ── Workspace grid: filing form + records ── */
    .aot-workspace { display: grid; grid-template-columns: 400px 1fr; gap: 16px; align-items: start; }
    @media (max-width: 992px) { .aot-workspace { grid-template-columns: 1fr; } }

    .sc { background: #fff; border: 1px solid var(--border); border-radius: var(--radius-card); box-shadow: var(--shadow-card); overflow: hidden; }
    .sc-head { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid var(--border); background: linear-gradient(to right, #fafcff, #f8fbfa); }
    .sc-head-left { display: flex; align-items: center; gap: 10px; }
    .sc-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--teal-light); color: var(--teal); display: flex; align-items: center; justify-content: center; font-size: .85rem; }
    .sc-title { font-size: .88rem; font-weight: 700; color: var(--slate); margin: 0; }
    .sc-sub   { font-size: .7rem; color: var(--muted); margin: 1px 0 0; }
    .sc-body  { padding: 20px; }

    .aot-form-pane { position: sticky; top: 16px; }

    .field-label { font-size: .75rem; font-weight: 700; color: var(--slate-light); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 5px; display: block; }
    .form-control, .form-select { border-radius: 8px; border: 1.5px solid var(--border); font-size: .85rem; color: var(--slate); }
    .form-control:focus, .form-select:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(0,128,128,.12); }

    .sub-divider { display: flex; align-items: center; gap: 10px; margin: 4px 0 14px; }
    .sub-divider span { font-size: .72rem; font-weight: 700; color: var(--teal); text-transform: uppercase; letter-spacing: .4px; white-space: nowrap; }
    .sub-divider::after { content: ''; flex-grow: 1; height: 1px; background: var(--border); }

    .aot-note { display: flex; gap: 9px; align-items: flex-start; background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 10px 12px; margin-bottom: 16px; }
    .aot-note i { color: #d97706; margin-top: 1px; }
    .aot-note p { margin: 0; font-size: .74rem; color: #92400e; line-height: 1.45; }

    .btn-submit {
        background: var(--teal); color: #fff; border: none; border-radius: 10px;
        padding: 10px 28px; font-size: .85rem; font-weight: 700; letter-spacing: .3px;
        box-shadow: 0 4px 14px rgba(0,128,128,.25); transition: all .2s; cursor: pointer;
    }
    .btn-submit:hover { background: var(--teal-dark); transform: translateY(-1px); color: #fff; }

    /* ── Records pane ── */
    .aot-records { display: flex; flex-direction: column; max-height: calc(100vh - 150px); }
    .aot-rec-head { padding: 12px 16px; border-bottom: 1px solid var(--border); background: linear-gradient(to right, #fafcff, #f8fbfa); }
    .aot-rec-head-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
    .aot-pills { display: flex; gap: 6px; flex-wrap: wrap; }
    .aot-pill {
        font-size: .72rem; font-weight: 700; color: var(--slate-light); background: #fff;
        border: 1.5px solid var(--border); border-radius: 20px; padding: 5px 13px; cursor: pointer; transition: all .12s;
    }
    .aot-pill:hover { border-color: var(--teal-mid); color: var(--teal); }
    .aot-pill.active { background: var(--teal); border-color: var(--teal); color: #fff; }
    .aot-pill-mine { margin-left: auto; border-style: dashed; }
    .aot-pill-mine.active { background: var(--slate); border-color: var(--slate); border-style: solid; color: #fff; }
    .aot-search { width: 100%; border: 1.5px solid var(--border); border-radius: 8px; padding: 8px 12px; font-size: .82rem; color: var(--slate); background: #fafbfc; }
    .aot-search:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(0,128,128,.1); background: #fff; outline: none; }

    .aot-list { overflow-y: auto; flex: 1; }
    .orow { display: flex; gap: 12px; padding: 13px 16px; border-bottom: 1px solid var(--border); cursor: pointer; transition: background .12s; border-left: 3px solid transparent; }
    .orow:hover { background: var(--teal-light); }
    .orow.active { background: var(--teal-light); border-left-color: var(--teal); }
    .orow .dot { width: 36px; height: 36px; border-radius: 9px; background: var(--teal-light); color: var(--teal); display: flex; align-items: center; justify-content: center; font-size: .82rem; flex-shrink: 0; }
    .orow .rmain { min-width: 0; flex: 1; }
    .orow .rtitle { font-size: .84rem; font-weight: 700; color: var(--slate); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .orow .rmeta { font-size: .71rem; color: var(--muted); margin-top: 3px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .orow .rmeta .sep { color: var(--border); }
    .orow .rright { display: flex; flex-direction: column; align-items: flex-end; gap: 5px; flex-shrink: 0; }
    .orow .rhrs { font-size: .8rem; font-weight: 800; color: var(--slate); }

    .aot-empty { text-align: center; color: var(--muted); padding: 48px 20px; font-size: .85rem; }
    .aot-empty i { font-size: 1.8rem; display: block; margin-bottom: 10px; opacity: .5; }

    .st-badge { font-size: .66rem; font-weight: 700; border-radius: 20px; padding: 3px 10px; letter-spacing: .3px; white-space: nowrap; }
    .badge-forapproval  { background: #fef9c3; color: #854d0e; }
    .badge-approved     { background: #dcfce7; color: #166534; }
    .badge-approvedbycfo{ background: #dbeafe; color: #1e40af; }
    .badge-disapproved  { background: #fee2e2; color: #991b1b; }
    .badge-canceled     { background: #f1f5f9; color: #64748b; }
    .daytype-badge { font-size: .66rem; font-weight: 700; background: #f1f5f9; color: #64748b; border-radius: 6px; padding: 3px 9px; text-transform: capitalize; }

    /* ── Detail modal ── */
    .aot-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 20px; }
    .aot-detail-grid .full { grid-column: 1 / -1; }
    .aot-dl-label { font-size: .68rem; font-weight: 700; color: var(--slate-light); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 3px; }
    .aot-dl-value { font-size: .88rem; color: var(--slate); font-weight: 600; }
    .aot-modal-head { background: linear-gradient(135deg, var(--teal), var(--teal-dark)); color: #fff; }
    .aot-modal-head .modal-title { font-weight: 700; font-size: 1rem; }
    .aot-modal-head .btn-close { filter: invert(1) grayscale(1) brightness(2); }
</style>

<div class="aot-shell">

    {{-- Topbar with stat chips --}}
    <div class="aot-topbar">
        <div>
            <p class="page-title">Apply Employee Overtime</p>
            <p class="page-sub">
                Modules &middot;
                {{ $isManager
                    ? 'File overtime on behalf of an employee'
                    : 'File overtime for your department — submissions go to an approver' }}
            </p>
        </div>
        <div class="aot-stats">
            <div class="aot-stat"><div class="ic t"><i class="fa-solid fa-layer-group"></i></div><div><div class="n">{{ $stats['total'] }}</div><div class="l">Total</div></div></div>
            <div class="aot-stat"><div class="ic w"><i class="fa-solid fa-hourglass-half"></i></div><div><div class="n">{{ $stats['forapproval'] }}</div><div class="l">For Approval</div></div></div>
            <div class="aot-stat"><div class="ic a"><i class="fa-solid fa-circle-check"></i></div><div><div class="n">{{ $stats['approved'] }}</div><div class="l">Approved</div></div></div>
            <div class="aot-stat"><div class="ic d"><i class="fa-solid fa-circle-xmark"></i></div><div><div class="n">{{ $stats['disapproved'] }}</div><div class="l">Disapproved</div></div></div>
        </div>
    </div>

    <div class="aot-workspace">

        {{-- ── Left: filing form ── --}}
        <div class="sc aot-form-pane">
            <div class="sc-head">
                <div class="sc-head-left">
                    <div class="sc-icon" id="formIcon"><i class="fa-solid fa-user-clock"></i></div>
                    <div>
                        <h5 class="sc-title" id="formTitle">New Overtime Filing</h5>
                        <p class="sc-sub" id="formSub">{{ $isManager ? 'Saved as approved' : 'Routed to an approver' }}</p>
                    </div>
                </div>
            </div>
            <div class="sc-body">
                @unless($isManager)
                <div class="aot-note" id="noteSupervisor">
                    <i class="fa-solid fa-circle-info"></i>
                    <p>Overtime you file is submitted <b>For Approval</b> and appears in Pending Overtime Requests. You can only file for employees in your department.</p>
                </div>
                @endunless

                <div class="aot-note d-none" id="noteEdit" style="background:#eff6ff;border-color:#bfdbfe;">
                    <i class="fa-solid fa-pen" style="color:#2563eb;"></i>
                    <p style="color:#1e40af;">Editing a filing that is still <b>For Approval</b>. It stays For Approval after saving and the hours, rate and pay are recomputed.</p>
                </div>

                <form id="frmAdminOT">
                    @csrf
                    <input type="hidden" id="editId" value="">

                    <div class="sub-divider"><span>Employee</span></div>
                    <div class="mb-3">
                        <label class="field-label">Employee <span class="text-danger">*</span></label>
                        <select class="form-select" id="selEmployee" name="employee_id" required>
                            <option value="">— Select Employee —</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->empID }}">
                                    {{ strtoupper($emp->lname) }}, {{ strtoupper($emp->fname) }}
                                    &mdash; {{ $emp->empDetail->position->pos_desc ?? 'No Position' }}
                                </option>
                            @endforeach
                        </select>
                        <span class="text-danger small error-text" id="err_employee"></span>
                    </div>

                    <div class="sub-divider"><span>Overtime Details</span></div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="field-label">Date From <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="txtDateFrom" name="dateFrom" required>
                            <span class="text-danger small error-text" id="err_dateFrom"></span>
                        </div>
                        <div class="col-6">
                            <label class="field-label">Date To <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="txtDateTo" name="dateTo" required>
                            <span class="text-danger small error-text" id="err_dateTo"></span>
                        </div>
                        <div class="col-6">
                            <label class="field-label">Time From <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="txtTimeFrom" name="timeFrom" required>
                            <span class="text-danger small error-text" id="err_timeFrom"></span>
                        </div>
                        <div class="col-6">
                            <label class="field-label">Time To <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="txtTimeTo" name="timeTo" required>
                            <span class="text-danger small error-text" id="err_timeTo"></span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="field-label">Purpose <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="txtPurpose" name="purpose" rows="2" placeholder="Reason for overtime..." required></textarea>
                        <span class="text-danger small error-text" id="err_purpose"></span>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn-submit" id="btnSubmitOT">
                            <i class="fa-solid fa-paper-plane me-2"></i>{{ $isManager ? 'Submit Overtime' : 'Submit for Approval' }}
                        </button>
                        <button type="button" class="btn btn-light d-none" id="btnCancelEdit"
                                style="border-radius:10px;font-weight:700;font-size:.8rem;padding:9px 28px;color:var(--slate-light);border:1.5px solid var(--border);">
                            <i class="fa-solid fa-xmark me-2"></i>Cancel Edit
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── Right: records ── --}}
        <div class="sc aot-records">
            <div class="aot-rec-head">
                <div class="aot-rec-head-top">
                    <div class="sc-head-left">
                        <div class="sc-icon"><i class="fa fa-history"></i></div>
                        <h5 class="sc-title">Overtime Records</h5>
                    </div>
                    <span class="text-muted small" id="recCount"></span>
                </div>
                <div class="aot-pills mb-2">
                    <span class="aot-pill active" data-filter="all">All</span>
                    <span class="aot-pill" data-filter="FORAPPROVAL">For Approval</span>
                    <span class="aot-pill" data-filter="APPROVED">Approved</span>
                    <span class="aot-pill" data-filter="DISAPPROVED">Disapproved</span>
                    <span class="aot-pill" data-filter="CANCELED">Canceled</span>
                    <span class="aot-pill aot-pill-mine" id="pillMine"><i class="fa-solid fa-user-check me-1"></i>Mine only</span>
                </div>
                <input type="text" class="aot-search" id="recSearch" placeholder="Search employee, purpose or day type...">
            </div>

            <div class="aot-list" id="recList">
                @forelse($overtimes as $ot)
                    @php
                        $from = \Carbon\Carbon::parse($ot->date_from . ' ' . $ot->time_in);
                        $to   = \Carbon\Carbon::parse($ot->date_to   . ' ' . $ot->time_out);
                        $empName = ucwords(strtolower(optional($ot->employee->user)->fname . ' ' . optional($ot->employee->user)->lname));
                        $statusClass = match($ot->status) {
                            'APPROVED'      => 'badge-approved',
                            'APPROVEDBYCFO' => 'badge-approvedbycfo',
                            'DISAPPROVED'   => 'badge-disapproved',
                            'CANCELED'      => 'badge-canceled',
                            default         => 'badge-forapproval',
                        };
                        $statusLabel = $ot->status === 'FORAPPROVAL' ? 'FOR APPROVAL' : str_replace('_', ' ', $ot->status);
                        $dayType = str_replace('_', ' ', $ot->day_type);
                        $filedByName = $ot->filedBy
                            ? ucwords(strtolower($ot->filedBy->fname . ' ' . $ot->filedBy->lname))
                            : '';
                        // Editable only while still FOR APPROVAL — an approved row may
                        // already have fed payroll. Mirrors the guard in update().
                        $isEditable = $ot->status === 'FORAPPROVAL'
                            && ($isManager || $ot->filed_by == auth()->id());
                    @endphp
                    <div class="orow"
                         data-status="{{ $ot->status }}"
                         data-mine="{{ $ot->filed_by == auth()->id() ? '1' : '0' }}"
                         data-editable="{{ $isEditable ? '1' : '0' }}"
                         data-id="{{ $ot->id }}"
                         data-empid="{{ optional($ot->employee->user)->empID }}"
                         data-rawdatefrom="{{ \Carbon\Carbon::parse($ot->date_from)->format('Y-m-d') }}"
                         data-rawdateto="{{ \Carbon\Carbon::parse($ot->date_to)->format('Y-m-d') }}"
                         data-rawtimein="{{ \Carbon\Carbon::parse($ot->time_in)->format('H:i') }}"
                         data-rawtimeout="{{ \Carbon\Carbon::parse($ot->time_out)->format('H:i') }}"
                         data-search="{{ strtolower($empName . ' ' . $ot->purpose . ' ' . $dayType) }}"
                         data-employee="{{ $empName }}"
                         data-filed="{{ \Carbon\Carbon::parse($ot->created_at)->format('M d, Y h:i A') }}"
                         data-from="{{ $from->format('M d, Y h:i A') }}"
                         data-to="{{ $to->format('M d, Y h:i A') }}"
                         data-hours="{{ $ot->total_hrs }}"
                         data-daytype="{{ $dayType }}"
                         data-rate="{{ $ot->day_type_computation }}"
                         data-pay="{{ number_format((float) $ot->total_pay, 2) }}"
                         data-purpose="{{ $ot->purpose }}"
                         data-filedby="{{ $filedByName }}"
                         data-statuslabel="{{ $statusLabel }}"
                         data-statusclass="{{ $statusClass }}">
                        <div class="dot"><i class="fa-solid fa-user-clock"></i></div>
                        <div class="rmain">
                            <div class="rtitle">{{ $empName ?: 'Unknown employee' }}</div>
                            <div class="rmeta">
                                <span><i class="fa-regular fa-calendar me-1"></i>{{ $from->format('M d, Y') }}</span>
                                <span class="sep">|</span>
                                <span>{{ $from->format('h:i A') }} – {{ $to->format('h:i A') }}</span>
                                <span class="sep">|</span>
                                <span class="daytype-badge">{{ $dayType }}</span>
                            </div>
                        </div>
                        <div class="rright">
                            <span class="rhrs">{{ $ot->total_hrs }} hr(s)</span>
                            <span class="st-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>
                    </div>
                @empty
                    <div class="aot-empty" id="recEmptyServer">
                        <i class="fa-regular fa-folder-open"></i>
                        No overtime records found.
                    </div>
                @endforelse
                <div class="aot-empty d-none" id="recEmptyFilter">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    No records match your filter.
                </div>
            </div>

            @if($overtimes->hasPages())
            <div class="d-flex justify-content-end px-3 py-3 border-top">
                {{ $overtimes->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Record detail modal --}}
<div class="modal fade" id="mdlOtDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:14px;overflow:hidden;">
            <div class="modal-header aot-modal-head">
                <h5 class="modal-title"><i class="fa-solid fa-user-clock me-2"></i>Overtime Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding:22px;">
                <div class="aot-detail-grid">
                    <div class="full">
                        <div class="aot-dl-label">Employee</div>
                        <div class="aot-dl-value" id="d_employee">—</div>
                    </div>
                    <div>
                        <div class="aot-dl-label">Status</div>
                        <div><span class="st-badge" id="d_status">—</span></div>
                    </div>
                    <div>
                        <div class="aot-dl-label">Day Type</div>
                        <div><span class="daytype-badge" id="d_daytype">—</span></div>
                    </div>
                    <div>
                        <div class="aot-dl-label">Time In</div>
                        <div class="aot-dl-value" id="d_from">—</div>
                    </div>
                    <div>
                        <div class="aot-dl-label">Time Out</div>
                        <div class="aot-dl-value" id="d_to">—</div>
                    </div>
                    <div>
                        <div class="aot-dl-label">Payable Hours</div>
                        <div class="aot-dl-value" id="d_hours">—</div>
                    </div>
                    <div>
                        <div class="aot-dl-label">OT Rate</div>
                        <div class="aot-dl-value" id="d_rate">—</div>
                    </div>
                    <div>
                        <div class="aot-dl-label">Estimated Pay</div>
                        <div class="aot-dl-value" id="d_pay">—</div>
                    </div>
                    <div>
                        <div class="aot-dl-label">Filed On</div>
                        <div class="aot-dl-value" id="d_filed">—</div>
                    </div>
                    <div>
                        <div class="aot-dl-label">Filed By</div>
                        <div class="aot-dl-value" id="d_filedby">—</div>
                    </div>
                    <div class="full">
                        <div class="aot-dl-label">Purpose</div>
                        <div class="aot-dl-value" id="d_purpose" style="font-weight:500;line-height:1.5;">—</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border);padding:12px 22px;">
                <span class="text-muted d-none" id="d_lockNote" style="font-size:.72rem;margin-right:auto;">
                    <i class="fa-solid fa-lock me-1"></i>Only records still For Approval can be edited.
                </span>
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal" style="border-radius:8px;font-weight:600;">Close</button>
                <button type="button" class="btn-submit d-none" id="btnEditOt" style="padding:7px 18px;font-size:.8rem;">
                    <i class="fa-solid fa-pen me-2"></i>Edit
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── Filing form ── */
    const form    = document.getElementById('frmAdminOT');
    const btn     = document.getElementById('btnSubmitOT');
    const btnHtml = btn.innerHTML;
    const errors  = ['employee', 'dateFrom', 'dateTo', 'timeFrom', 'timeTo', 'purpose'];

    const editId       = document.getElementById('editId');
    const formTitle    = document.getElementById('formTitle');
    const formSub      = document.getElementById('formSub');
    const formIcon     = document.getElementById('formIcon');
    const btnCancel    = document.getElementById('btnCancelEdit');
    const noteEdit     = document.getElementById('noteEdit');
    const noteSup      = document.getElementById('noteSupervisor');
    const formPane     = document.querySelector('.aot-form-pane');

    const defaults = {
        title: formTitle.textContent,
        sub:   formSub.textContent,
        icon:  formIcon.innerHTML,
        btn:   btnHtml
    };

    function clearErrors() {
        errors.forEach(k => document.getElementById('err_' + k).textContent = '');
    }

    function enterEditMode(d) {
        editId.value = d.id;
        document.getElementById('selEmployee').value = d.empid || '';
        document.getElementById('txtDateFrom').value = d.rawdatefrom;
        document.getElementById('txtDateTo').value   = d.rawdateto;
        document.getElementById('txtTimeFrom').value = d.rawtimein;
        document.getElementById('txtTimeTo').value   = d.rawtimeout;
        document.getElementById('txtPurpose').value  = d.purpose || '';

        clearErrors();
        formTitle.textContent = 'Edit Overtime Filing';
        formSub.textContent   = d.employee || '';
        formIcon.innerHTML    = '<i class="fa-solid fa-pen"></i>';
        btn.innerHTML         = '<i class="fa-solid fa-floppy-disk me-2"></i>Save Changes';
        btnCancel.classList.remove('d-none');
        noteEdit.classList.remove('d-none');
        if (noteSup) noteSup.classList.add('d-none');

        formPane.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function exitEditMode() {
        editId.value = '';
        form.reset();
        clearErrors();
        formTitle.textContent = defaults.title;
        formSub.textContent   = defaults.sub;
        formIcon.innerHTML    = defaults.icon;
        btn.innerHTML         = defaults.btn;
        btnCancel.classList.add('d-none');
        noteEdit.classList.add('d-none');
        if (noteSup) noteSup.classList.remove('d-none');
    }

    btnCancel.addEventListener('click', exitEditMode);

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const isEdit = !!editId.value;
        const url    = isEdit
            ? '{{ url("admin/overtime") }}/' + editId.value + '/update'
            : '{{ route("admin.overtime.store") }}';

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>'
                      + (isEdit ? 'Saving...' : 'Submitting...');

        const formData = new FormData(form);

        axios.post(url, formData)
            .then(function (response) {
                if (response.data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: isEdit ? 'Updated!' : 'Filed!',
                        text: response.data.message,
                        timer: 2500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.data.message });
                }
            })
            .catch(function (err) {
                if (err.response && err.response.status === 422) {
                    const errs = err.response.data.errors;
                    if (errs.employee_id) document.getElementById('err_employee').textContent = errs.employee_id[0];
                    if (errs.dateFrom)    document.getElementById('err_dateFrom').textContent  = errs.dateFrom[0];
                    if (errs.dateTo)      document.getElementById('err_dateTo').textContent    = errs.dateTo[0];
                    if (errs.timeFrom)    document.getElementById('err_timeFrom').textContent  = errs.timeFrom[0];
                    if (errs.timeTo)      document.getElementById('err_timeTo').textContent    = errs.timeTo[0];
                    if (errs.purpose)     document.getElementById('err_purpose').textContent   = errs.purpose[0];
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
                }
            })
            .finally(function () {
                btn.disabled = false;
                // Restore the label for whichever mode we're still in — a failed
                // save must not relabel the button back to "Submit".
                btn.innerHTML = editId.value
                    ? '<i class="fa-solid fa-floppy-disk me-2"></i>Save Changes'
                    : defaults.btn;
            });
    });

    // Mirror date_from → date_to automatically
    document.getElementById('txtDateFrom').addEventListener('change', function () {
        document.getElementById('txtDateTo').value = this.value;
    });

    /* ── Records: filter + search ── */
    const rows        = Array.from(document.querySelectorAll('.orow'));
    const statusPills = Array.from(document.querySelectorAll('.aot-pill[data-filter]'));
    const pillMine    = document.getElementById('pillMine');
    const search      = document.getElementById('recSearch');
    const emptyFilter = document.getElementById('recEmptyFilter');
    const recCount    = document.getElementById('recCount');
    let activeFilter  = 'all';
    let mineOnly      = false;

    function applyFilter() {
        const term = search.value.trim().toLowerCase();
        let shown = 0;

        rows.forEach(row => {
            const okStatus = activeFilter === 'all' || row.dataset.status === activeFilter;
            const okSearch = !term || row.dataset.search.includes(term);
            const okMine   = !mineOnly || row.dataset.mine === '1';
            const visible  = okStatus && okSearch && okMine;
            row.classList.toggle('d-none', !visible);
            if (visible) shown++;
        });

        if (rows.length) {
            recCount.textContent = shown + ' of ' + rows.length + ' shown';
            emptyFilter.classList.toggle('d-none', shown !== 0);
        }
    }

    // Status pills are single-select among themselves.
    statusPills.forEach(pill => pill.addEventListener('click', function () {
        statusPills.forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        activeFilter = this.dataset.filter;
        applyFilter();
    }));

    // "Mine only" is an independent toggle.
    if (pillMine) {
        pillMine.addEventListener('click', function () {
            mineOnly = !mineOnly;
            this.classList.toggle('active', mineOnly);
            applyFilter();
        });
    }

    search.addEventListener('input', applyFilter);
    if (rows.length) applyFilter();

    /* ── Records: detail modal ── */
    const detailModalEl = document.getElementById('mdlOtDetail');
    const detailModal   = new bootstrap.Modal(detailModalEl);
    const btnEditOt     = document.getElementById('btnEditOt');
    const lockNote      = document.getElementById('d_lockNote');
    let   activeRow     = null;

    // Only FOR APPROVAL rows the viewer is allowed to touch expose an Edit
    // button; the server re-checks both conditions in update().
    btnEditOt.addEventListener('click', function () {
        if (!activeRow) return;
        detailModal.hide();
        enterEditMode(activeRow.dataset);
    });

    rows.forEach(row => row.addEventListener('click', function () {
        rows.forEach(r => r.classList.remove('active'));
        this.classList.add('active');

        const d = this.dataset;
        document.getElementById('d_employee').textContent = d.employee || 'Unknown employee';
        document.getElementById('d_from').textContent     = d.from;
        document.getElementById('d_to').textContent       = d.to;
        document.getElementById('d_hours').textContent    = d.hours + ' hr(s)';
        document.getElementById('d_rate').textContent     = d.rate ? ('×' + d.rate) : '—';
        document.getElementById('d_pay').textContent      = '₱' + d.pay;
        document.getElementById('d_filed').textContent    = d.filed;
        document.getElementById('d_filedby').textContent  = d.filedby || '—';
        document.getElementById('d_purpose').textContent  = d.purpose || '—';

        const dt = document.getElementById('d_daytype');
        dt.textContent = d.daytype;

        const st = document.getElementById('d_status');
        st.textContent = d.statuslabel;
        st.className = 'st-badge ' + d.statusclass;

        activeRow = this;
        const editable = d.editable === '1';
        btnEditOt.classList.toggle('d-none', !editable);
        lockNote.classList.toggle('d-none', editable || d.status === 'FORAPPROVAL');

        detailModal.show();
    }));
});
</script>
@endsection
