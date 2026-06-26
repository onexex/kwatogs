@extends('layout.app', ['title' => $moduleLabel.' Import Batch #'.$batch->id])
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8; --bg:#f1f5f9;
        --surface:#fff; --border:#e2e8f0; --danger:#ef4444; --success:#10b981; --warn:#f59e0b;
        --radius-card:14px; --radius-input:8px; --shadow-card:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);
    }
    .ai-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .ai-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:20px; display:flex;
        align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
    .ai-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .ai-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .btn-ghost { background:var(--surface); color:var(--slate-light); border:1.5px solid var(--border);
        border-radius:var(--radius-input); padding:10px 20px; font-size:.82rem; font-weight:700; cursor:pointer;
        transition:all .2s; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
    .btn-ghost:hover { background:var(--teal-light); border-color:var(--teal-mid); color:var(--teal); }
    .btn-danger { background:var(--danger); color:#fff; border:none; border-radius:var(--radius-input); padding:10px 20px;
        font-size:.82rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
    .btn-danger:hover { background:#dc2626; }
    .sc { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); margin-bottom:20px; overflow:hidden; }
    .sc-head { display:flex; align-items:center; gap:10px; padding:14px 22px; border-bottom:1px solid var(--border);
        background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .sc-icon { width:30px; height:30px; border-radius:8px; background:var(--teal-light); color:var(--teal);
        display:flex; align-items:center; justify-content:center; font-size:.78rem; }
    .sc-title { font-size:.78rem; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; margin:0; }
    .meta-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; padding:18px 22px; }
    .meta { border:1px solid var(--border); border-radius:10px; padding:12px 14px; }
    .meta .l { font-size:.68rem; text-transform:uppercase; letter-spacing:.4px; color:var(--slate-light); font-weight:700; }
    .meta .v { font-size:.92rem; font-weight:700; color:var(--slate); margin-top:3px; }
    table.imp { width:100%; border-collapse:collapse; }
    table.imp th { font-size:.7rem; text-transform:uppercase; letter-spacing:.4px; color:var(--slate-light);
        font-weight:700; text-align:left; padding:12px 16px; border-bottom:1px solid var(--border); white-space:nowrap; }
    table.imp td { font-size:.84rem; color:var(--slate); padding:10px 16px; border-bottom:1px solid #f1f5f9; white-space:nowrap; }
    table.imp tr:hover td { background:#fafdfd; }
    /* Flagged rows: anomalous figures that need a human to verify. */
    table.imp tr.flagged td { background:#fff7ed; }
    table.imp tr.flagged:hover td { background:#ffedd5; }
    table.imp tr.flagged td:first-child { box-shadow:inset 3px 0 0 var(--warn); }
    .badge-val { display:inline-flex; align-items:center; gap:5px; font-size:.7rem; font-weight:700;
        padding:3px 9px; border-radius:999px; white-space:nowrap; }
    .badge-val.warn { background:#fef3c7; color:#92400e; border:1px solid #fde68a; cursor:help; }
    .badge-val.ok { background:#ecfdf5; color:#047857; border:1px solid #d1fae5; }
    .btn-edit { background:var(--surface); color:var(--teal); border:1.5px solid var(--border); border-radius:6px;
        padding:4px 10px; font-size:.7rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center;
        gap:5px; transition:all .15s; white-space:nowrap; }
    .btn-edit:hover { background:var(--teal-light); border-color:var(--teal-mid); }
    /* edit modal */
    /* z-index 1050: below SweetAlert (1060) so its confirm dialog appears ABOVE this modal. */
    .edm-back { position:fixed; inset:0; background:rgba(15,23,42,.45); display:none; align-items:center;
        justify-content:center; z-index:1050; padding:20px; }
    .edm-back.show { display:flex; }
    .edm { background:var(--surface); border-radius:var(--radius-card); box-shadow:0 20px 60px rgba(0,0,0,.3);
        width:100%; max-width:440px; overflow:hidden; }
    .edm-head { padding:16px 20px; border-bottom:1px solid var(--border); background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .edm-head .t { font-size:.95rem; font-weight:700; color:var(--slate); margin:0; }
    .edm-head .s { font-size:.76rem; color:var(--muted); margin:2px 0 0; }
    .edm-body { padding:18px 20px; display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .edm-body .full { grid-column:1 / -1; }
    .edm label { display:block; font-size:.68rem; text-transform:uppercase; letter-spacing:.4px; color:var(--slate-light);
        font-weight:700; margin-bottom:5px; }
    .edm input, .edm select, .edm textarea { width:100%; border:1.5px solid var(--border); border-radius:var(--radius-input);
        padding:9px 11px; font-size:.85rem; color:var(--slate); background:#fff; }
    .edm input:focus, .edm select:focus, .edm textarea:focus { outline:none; border-color:var(--teal-mid); }
    .edm-foot { padding:14px 20px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:10px; }
    .btn-save { background:var(--teal); color:#fff; border:none; border-radius:var(--radius-input); padding:9px 20px;
        font-size:.82rem; font-weight:700; cursor:pointer; }
    .btn-save:hover { background:var(--teal-dark); }
    .val-summary { display:flex; align-items:center; gap:8px; font-size:.8rem; color:var(--slate-light);
        padding:10px 22px; border-top:1px solid var(--border); background:#fffbeb; }
    .val-summary b { color:#92400e; }
    .empty { text-align:center; padding:40px 20px; color:var(--muted); font-size:.86rem; }
</style>

<div class="ai-shell">
    <div class="ai-topbar">
        <div>
            <p class="page-title">{{ $moduleLabel }} Import Batch #{{ $batch->id }}</p>
            <p class="page-sub">{{ $batch->filename ?: 'Imported file' }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route($routePrefix) }}" class="btn-ghost">
                <i class="fa fa-arrow-left"></i> Back to History
            </a>
            <button class="btn-danger" onclick="deleteBatch('{{ route($routePrefix.'.destroy', $batch->id) }}')">
                <i class="fa fa-trash"></i> Roll Back This Import
            </button>
        </div>
    </div>

    <div class="sc">
        <div class="meta-grid">
            <div class="meta"><div class="l">Rows</div><div class="v">{{ $batch->row_count }}</div></div>
            <div class="meta"><div class="l">Created</div><div class="v">{{ $batch->inserted }}</div></div>
            <div class="meta"><div class="l">Updated</div><div class="v">{{ $batch->updated }}</div></div>
            <div class="meta"><div class="l">Date Range</div><div class="v">
                @if($batch->date_from && $batch->date_to)
                    {{ \Carbon\Carbon::parse($batch->date_from)->format('M d') }} – {{ \Carbon\Carbon::parse($batch->date_to)->format('M d, Y') }}
                @else — @endif
            </div></div>
            <div class="meta"><div class="l">Imported By</div><div class="v">{{ $batch->user_name ?: '—' }}</div></div>
            <div class="meta"><div class="l">When</div><div class="v">{{ $batch->created_at ? $batch->created_at->format('M d, Y g:i A') : '—' }}</div></div>
        </div>
    </div>

    @php $flaggedCount = collect($rows)->where('flag')->count(); @endphp
    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-list"></i></div>
            <h5 class="sc-title">Rows In This Import ({{ count($rows) }})</h5>
            @if($flaggedCount > 0)
                <span class="badge-val warn" style="margin-left:auto;">
                    <i class="fa fa-triangle-exclamation"></i> {{ $flaggedCount }} need{{ $flaggedCount === 1 ? 's' : '' }} validation
                </span>
            @endif
        </div>

        @if(empty($rows))
            <div class="empty"><i class="fa fa-inbox fa-2x mb-2 d-block"></i> No rows found for this import.</div>
        @else
        <div style="overflow-x:auto;">
            <table class="imp">
                <thead>
                    <tr>
                        @foreach($columns as $col)
                            <th>{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                    @php
                        $cells = $row['cells'] ?? $row;          // tolerate old flat-array shape
                        $flag  = $row['flag'] ?? null;
                        $rowId = $row['id'] ?? null;
                        $raw   = $row['raw'] ?? null;
                        $last  = count($cells) - 1;
                        $hasValidationCol = in_array('Validation', $columns, true);
                    @endphp
                    <tr class="{{ $flag ? 'flagged' : '' }}">
                        @foreach($cells as $i => $cell)
                            @if($hasValidationCol && $i === $last)
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        @if($flag)
                                            <span class="badge-val warn" title="{{ $flag }}">
                                                <i class="fa fa-triangle-exclamation"></i> Needs Validation
                                            </span>
                                        @else
                                            <span class="badge-val ok"><i class="fa fa-check"></i> OK</span>
                                        @endif
                                        @if($module === 'attendance' && $rowId && $raw)
                                            <button type="button" class="btn-edit"
                                                data-id="{{ $rowId }}"
                                                data-employee="{{ $raw['employee'] }}"
                                                data-date="{{ $raw['date'] }}"
                                                data-total="{{ $raw['total_hours'] }}"
                                                data-late="{{ $raw['mins_late'] }}"
                                                data-undertime="{{ $raw['mins_undertime'] }}"
                                                data-status="{{ $raw['status'] }}"
                                                data-remarks="{{ $raw['remarks'] }}">
                                                <i class="fa fa-pen"></i> Edit
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            @else
                                <td @if($i === 0) style="font-weight:600; white-space:normal;" @endif>{{ $cell }}</td>
                            @endif
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($flaggedCount > 0)
            <div class="val-summary">
                <i class="fa fa-circle-info"></i>
                <span><b>{{ $flaggedCount }}</b> row{{ $flaggedCount === 1 ? '' : 's' }} flagged for validation — hover the
                    <span class="badge-val warn" style="padding:1px 7px;">Needs Validation</span> badge to see why.</span>
            </div>
        @endif
        @endif
    </div>

    @if($module === 'attendance')
    <div class="edm-back" id="edmBack">
        <div class="edm">
            <form id="edmForm">
                <div class="edm-head">
                    <p class="t">Edit Attendance Row</p>
                    <p class="s" id="edmSub">—</p>
                </div>
                <div class="edm-body">
                    <div>
                        <label>Status</label>
                        <select name="status" id="edmStatus">
                            <option value="present">Present</option>
                            <option value="ob">OB</option>
                            <option value="leave">Leave</option>
                            <option value="absent">Absent</option>
                        </select>
                    </div>
                    <div>
                        <label>Total Hrs</label>
                        <input type="number" step="0.01" min="0" max="24" name="total_hours" id="edmTotal">
                    </div>
                    <div>
                        <label>Late (mins)</label>
                        <input type="number" step="1" min="0" max="1440" name="mins_late" id="edmLate">
                    </div>
                    <div>
                        <label>Undertime (mins)</label>
                        <input type="number" step="1" min="0" max="1440" name="mins_undertime" id="edmUndertime">
                    </div>
                    <div class="full">
                        <label>Remarks</label>
                        <textarea name="remarks" id="edmRemarks" rows="2" maxlength="255" placeholder="Optional note"></textarea>
                    </div>
                </div>
                <div class="edm-foot">
                    <button type="button" class="btn-ghost" onclick="closeEdm()">Cancel</button>
                    <button type="submit" class="btn-save"><i class="fa fa-floppy-disk"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

<script>
const HISTORY_URL = "{{ route($routePrefix) }}";
function deleteBatch(url) {
    Swal.fire({
        title: 'Roll back this import?',
        html: 'This deletes the records this import created.<br>You can re-upload a corrected file afterwards.',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete', cancelButtonText: 'Cancel'
    }).then(res => {
        if (!res.isConfirmed) return;
        Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        axios.delete(url)
            .then(r => {
                Swal.fire({ icon: 'success', title: 'Done', text: r.data.message, timer: 1800, showConfirmButton: false })
                    .then(() => window.location.href = HISTORY_URL);
            })
            .catch(err => {
                Swal.close();
                Swal.fire('Cannot delete', err.response?.data?.message || 'Unable to delete this import.', 'error');
            });
    });
}

@if($module === 'attendance')
// ── Inline edit of an attendance row ───────────────────────────────────
const ROW_UPDATE_TPL = "{{ route('attendance-import.history.row.update', ['id' => '__ID__']) }}";
let edmId = null;

document.addEventListener('click', e => {
    const btn = e.target.closest('.btn-edit');
    if (!btn) return;
    edmId = btn.dataset.id;
    document.getElementById('edmSub').textContent = btn.dataset.employee + ' · ' + btn.dataset.date;
    document.getElementById('edmStatus').value    = btn.dataset.status || 'present';
    document.getElementById('edmTotal').value     = btn.dataset.total;
    document.getElementById('edmLate').value      = btn.dataset.late;
    document.getElementById('edmUndertime').value = btn.dataset.undertime;
    document.getElementById('edmRemarks').value   = btn.dataset.remarks || '';
    document.getElementById('edmBack').classList.add('show');
});

function closeEdm() {
    document.getElementById('edmBack').classList.remove('show');
    edmId = null;
}
// dismiss on backdrop click
document.getElementById('edmBack').addEventListener('click', e => {
    if (e.target.id === 'edmBack') closeEdm();
});

// Hard rules — mirror the server's consistencyError() for instant feedback (server still
// has the final say). Returns an error string, or null when acceptable.
function edmHardError(p) {
    const status = p.status, hours = parseFloat(p.total_hours) || 0,
          late = parseInt(p.mins_late) || 0, ut = parseInt(p.mins_undertime) || 0;
    if (status === 'absent' || status === 'leave') {
        if (hours > 0)        return cap(status) + ' days cannot have worked hours. Set Total Hrs to 0, or change the status.';
        if (late > 0 || ut > 0) return cap(status) + ' days cannot have late/undertime. Clear those, or change the status.';
    }
    if ((status === 'present' || status === 'ob') && hours <= 0)
        return cap(status) + ' days must have worked hours greater than 0, or change the status to Absent/Leave.';
    if (late + ut > 1440) return 'Late + undertime cannot exceed 24 hours in a day.';
    return null;
}
// Soft, schedule-dependent heuristic — warn but allow (the original anomaly rule).
function edmSoftWarning(p) {
    const status = p.status, hours = parseFloat(p.total_hours) || 0,
          late = parseInt(p.mins_late) || 0, ut = parseInt(p.mins_undertime) || 0;
    if ((status === 'present' || status === 'ob') && hours >= 8 && (late > 0 || ut > 0)) {
        const bits = [];
        if (late > 0) bits.push(late + 'm late');
        if (ut > 0)   bits.push(ut + 'm undertime');
        return 'A full day (' + hours.toFixed(2) + ' hrs) usually shouldn\'t also have ' + bits.join(' + ') + '.';
    }
    return null;
}
function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

document.getElementById('edmForm').addEventListener('submit', e => {
    e.preventDefault();
    if (!edmId) return;
    const id = edmId;   // capture now — closeEdm()/backdrop could null edmId before the async save
    const payload = {
        status:         document.getElementById('edmStatus').value,
        total_hours:    document.getElementById('edmTotal').value,
        mins_late:      document.getElementById('edmLate').value,
        mins_undertime: document.getElementById('edmUndertime').value,
        remarks:        document.getElementById('edmRemarks').value,
    };

    // 1) Block impossible combinations outright.
    const hard = edmHardError(payload);
    if (hard) { Swal.fire('Invalid values', hard, 'error'); return; }

    // 2) Caution: this rewrites raw imported attendance. Surface any soft inconsistency too.
    const soft = edmSoftWarning(payload);
    Swal.fire({
        title: 'Save changes to raw data?',
        html: 'You are editing <b>raw imported attendance</b> that feeds payroll. This change is logged in the audit trail.'
            + (soft ? '<br><br><span style="color:#92400e;"><i class="fa fa-triangle-exclamation"></i> ' + soft + '</span>' : ''),
        icon: soft ? 'warning' : 'question',
        showCancelButton: true, confirmButtonColor: '#008080',
        confirmButtonText: 'Yes, save', cancelButtonText: 'Cancel'
    }).then(res => {
        if (!res.isConfirmed) return;
        Swal.fire({ title: 'Saving...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        axios.put(ROW_UPDATE_TPL.replace('__ID__', id), payload)
            .then(r => {
                Swal.fire({ icon: 'success', title: 'Saved', text: r.data.message, timer: 1400, showConfirmButton: false })
                    .then(() => window.location.reload());
            })
            .catch(err => {
                Swal.close();
                const msg = err.response?.data?.message
                    || Object.values(err.response?.data?.errors || {})[0]?.[0]
                    || 'Unable to save this row.';
                Swal.fire('Cannot save', msg, 'error');
            });
    });
});
@endif
</script>
@endsection
