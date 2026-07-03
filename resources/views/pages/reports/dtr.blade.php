@extends('layout.app', ['title' => 'Daily Time Record'])
@section('content')

@include('pages.reports.partials.styles')

<div class="rpt-shell">
    <div class="rpt-topbar">
        <p class="page-title"><i class="fa fa-clock me-2" style="color:var(--teal)"></i>Daily Time Record (DTR)</p>
        <p class="page-sub">Per-employee monthly time record &mdash; daily time-in/out, hours, late, undertime and status.</p>
    </div>

    <div class="sc">
        <div class="sc-body" style="padding:16px 22px;">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="field-label">Employee</label>
                    <select id="fltEmp" class="form-select form-select-sm">
                        <option value="">— Select employee —</option>
                        @foreach ($employees as $e)
                            <option value="{{ $e->empID }}">{{ strtoupper($e->name) }} @if($e->dept) · {{ $e->dept }} @endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Month</label>
                    <select id="fltMonth" class="form-select form-select-sm"></select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="field-label">Year</label>
                    <select id="fltYear" class="form-select form-select-sm">
                        @foreach ($years as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex gap-2 justify-content-end">
                    <button class="btn-filter" id="btnFilter"><i class="fa fa-search me-1"></i> Load</button>
                    <button class="btn-ghost" id="btnExport" title="Export to Excel"><i class="fa fa-file-excel me-1"></i> Excel</button>
                    <button class="btn-ghost" id="btnPrint" title="Print"><i class="fa fa-print me-1"></i> Print</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-3 flex-wrap">
        <div class="stat"><div class="l">Days Present</div><div class="v" id="kPresent">0</div></div>
        <div class="stat"><div class="l">Days Absent</div><div class="v" id="kAbsent">0</div></div>
        <div class="stat"><div class="l">Total Hours</div><div class="v" id="kHours">0.00</div></div>
        <div class="stat"><div class="l">Late (min)</div><div class="v" id="kLate">0</div></div>
        <div class="stat"><div class="l">Undertime (min)</div><div class="v" id="kUT">0</div></div>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-clock"></i></div>
            <h5 class="sc-title" id="lblHead">Daily Time Record</h5>
            <span class="pill ms-auto" id="lblPeriod">—</span>
        </div>
        <div class="table-responsive" style="max-height:64vh; overflow:auto;">
            <table class="table table-hover align-middle mb-0 rpt-table">
                <thead>
                    <tr>
                        <th class="ps-3">Date</th>
                        <th>Day</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th class="text-end">Hours</th>
                        <th class="text-end">Late</th>
                        <th class="text-end">UT</th>
                        <th class="text-end">N.Diff</th>
                        <th class="text-center pe-3">Status</th>
                    </tr>
                </thead>
                <tbody id="tbl"><tr><td colspan="9" class="text-center text-muted py-4">Select an employee and click Load.</td></tr></tbody>
                <tfoot id="tfoot"></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
$(function () {
    const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const now = new Date();
    MONTHS.forEach((m, i) => $('#fltMonth').append(`<option value="${i + 1}" ${i === now.getMonth() ? 'selected' : ''}>${m}</option>`));

    const params = () => ({ employee_id: $('#fltEmp').val(), month: $('#fltMonth').val(), year: $('#fltYear').val() });

    function load() {
        if (!$('#fltEmp').val()) { $('#tbl').html('<tr><td colspan="9" class="text-center text-muted py-4">Select an employee first.</td></tr>'); return; }
        $('#tbl').html('<tr><td colspan="9" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></td></tr>');
        $('#tfoot').html('');
        axios.get('/reports/dtr/fetch', { params: params() }).then(res => {
            const d = res.data, days = d.days || [], t = d.totals || {};
            $('#kPresent').text(t.present || 0); $('#kAbsent').text(t.absent || 0);
            $('#kHours').text(Number(t.hours || 0).toFixed(2)); $('#kLate').text(t.late || 0); $('#kUT').text(t.undertime || 0);
            $('#lblPeriod').text(d.label || '—');
            $('#lblHead').text(d.employee ? (d.employee.name + ' · ' + d.employee.empID) : 'Daily Time Record');
            let h = '';
            days.forEach(r => {
                const bg = r.is_weekend ? 'style="background:#fbfcfe"' : '';
                let badge = '<span class="text-muted">—</span>';
                if ((r.status || '').toLowerCase() === 'present') badge = '<span class="pill" style="background:#dcfce7;color:#166534">Present</span>';
                else if ((r.status || '').toLowerCase() === 'absent') badge = '<span class="pill" style="background:#fee2e2;color:#991b1b">Absent</span>';
                h += `<tr ${bg}>
                    <td class="ps-3">${new Date(r.date).toLocaleDateString('en-PH', {month:'short', day:'2-digit'})}</td>
                    <td class="text-muted">${r.day}</td>
                    <td class="mono">${r.time_in || '—'}</td>
                    <td class="mono">${r.time_out || '—'}</td>
                    <td class="text-end mono">${r.hours ? Number(r.hours).toFixed(2) : '—'}</td>
                    <td class="text-end mono">${r.late || ''}</td>
                    <td class="text-end mono">${r.undertime || ''}</td>
                    <td class="text-end mono">${r.night_diff || ''}</td>
                    <td class="text-center pe-3">${badge}</td>
                </tr>`;
            });
            $('#tbl').html(h || '<tr><td colspan="9" class="text-center text-muted py-4">No days in range.</td></tr>');
            $('#tfoot').html(`<tr><td colspan="4" class="text-end">TOTAL — ${t.present||0} present, ${t.absent||0} absent</td><td class="text-end mono">${Number(t.hours||0).toFixed(2)}</td><td class="text-end mono">${t.late||0}</td><td class="text-end mono">${t.undertime||0}</td><td class="text-end mono">${t.night_diff||0}</td><td class="pe-3"></td></tr>`);
        }).catch(() => $('#tbl').html('<tr><td colspan="9" class="text-center text-danger py-4">Failed to load.</td></tr>'));
    }

    $('#btnFilter').on('click', load);
    $('#fltEmp,#fltMonth,#fltYear').on('change', load);
    $('#btnExport').on('click', () => { if ($('#fltEmp').val()) window.location = '/reports/dtr/export?' + new URLSearchParams(params()).toString(); });
    $('#btnPrint').on('click', () => { if ($('#fltEmp').val()) window.open('/reports/dtr/print?' + new URLSearchParams(params()).toString(), '_blank'); });
});
</script>
@endsection
