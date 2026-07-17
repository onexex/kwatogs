<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tardiness & Absences — {{ $label }}</title>
    <style>
        * { box-sizing:border-box; }
        body { font-family:'Segoe UI', Arial, sans-serif; color:#1e293b; margin:24px 30px; font-size:11px; }
        .head { display:flex; align-items:center; gap:14px; border-bottom:3px solid #008080; padding-bottom:12px; margin-bottom:6px; }
        .head img { height:50px; }
        .head .org { font-size:17px; font-weight:800; color:#006666; }
        .head .sub { font-size:12px; color:#475569; }
        .meta { font-size:11px; color:#64748b; margin:10px 0 14px; }
        .meta b { color:#334155; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#008080; color:#fff; font-size:9.5px; text-transform:uppercase; padding:6px 6px; text-align:right; }
        thead th.l { text-align:left; }
        tbody td { padding:5px 6px; border-bottom:1px solid #e2e8f0; font-size:10px; text-align:right; font-variant-numeric:tabular-nums; }
        tbody td.l { text-align:left; }
        tbody tr:nth-child(even) { background:#f8fafc; }
        tfoot td { padding:7px 6px; border-top:2px solid #008080; background:#e0f2f1; font-weight:800; color:#006666; font-size:10.5px; text-align:right; }
        tfoot td.l { text-align:left; }
        .sign { margin-top:40px; display:flex; justify-content:space-between; font-size:11px; }
        .sign > div { width:30%; text-align:center; }
        .sign .cap { margin-bottom:28px; }
        .sign .ln { border-top:1px solid #475569; }
        @media print { body { margin:12mm; } .noprint { display:none; } }
        .noprint { text-align:right; margin-bottom:10px; }
        .btn { background:#008080; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-weight:700; cursor:pointer; }
    </style>
</head>
<body>
    <div class="noprint"><button class="btn" onclick="window.print()">Print</button></div>

    <div class="head">
        <img src="{{ asset('img/kwatogslogo.jpg') }}" onerror="this.style.display='none'" alt="">
        <div>
            <div class="org">{{ $letterhead }}</div>
            <div class="sub">Tardiness &amp; Absences Summary — {{ $label }}</div>
        </div>
    </div>

    <div class="meta"><b>Employees flagged:</b> {{ $rows->count() }} &nbsp;&bull;&nbsp; <b>Total late:</b> {{ $stats['late_mins'] }} min &nbsp;&bull;&nbsp; <b>Absences:</b> {{ $stats['absences'] }} &nbsp;&bull;&nbsp; <b>Generated:</b> {{ now()->format('M d, Y g:i A') }}</div>

    <table>
        <thead>
            <tr>
                <th class="l">#</th><th class="l">Employee</th><th class="l">Department</th>
                <th>Late (min)</th><th>Late Days</th><th>UT (min)</th><th>UT Days</th><th>Absences</th><th>Over-Break</th><th>Out-Pass</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $r)
            <tr>
                <td class="l">{{ $i + 1 }}</td>
                <td class="l"><strong>{{ strtoupper($r->employee_name) }}</strong> <span style="color:#94a3b8;">{{ $r->employee_id }}</span></td>
                <td class="l">{{ $r->department_name }}</td>
                <td>{{ $r->late_mins }}</td>
                <td>{{ $r->late_days }}</td>
                <td>{{ $r->ut_mins }}</td>
                <td>{{ $r->ut_days }}</td>
                <td>{{ $r->absent_days }}</td>
                <td>{{ $r->over_break }}</td>
                <td>{{ $r->outpass }}</td>
            </tr>
            @empty
            <tr><td colspan="10" class="l" style="text-align:center; padding:18px; color:#94a3b8;">No tardiness or absences for this period.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td class="l" colspan="3">TOTAL ({{ $rows->count() }})</td>
                <td>{{ $stats['late_mins'] }}</td><td></td><td>{{ $stats['ut_mins'] }}</td><td></td>
                <td>{{ $stats['absences'] }}</td><td>{{ $stats['over_break'] }}</td><td>{{ $stats['outpass'] }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="sign">
        <div><div class="cap">Prepared by</div><div class="ln"></div></div>
        <div><div class="cap">Reviewed by</div><div class="ln"></div></div>
        <div><div class="cap">Noted by</div><div class="ln"></div></div>
    </div>
</body>
</html>
