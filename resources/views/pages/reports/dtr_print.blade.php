<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DTR — {{ $employee->name ?? '' }} — {{ $label }}</title>
    <style>
        * { box-sizing:border-box; }
        body { font-family:'Segoe UI', Arial, sans-serif; color:#1e293b; margin:24px 30px; font-size:11px; }
        .head { display:flex; align-items:center; gap:14px; border-bottom:3px solid #008080; padding-bottom:12px; margin-bottom:6px; }
        .head img { height:50px; }
        .head .org { font-size:17px; font-weight:800; color:#006666; }
        .head .sub { font-size:12px; color:#475569; }
        .emp { font-size:12px; margin:10px 0 12px; }
        .emp b { color:#334155; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#008080; color:#fff; font-size:9.5px; text-transform:uppercase; padding:6px 6px; text-align:right; }
        thead th.l { text-align:left; } thead th.c { text-align:center; }
        tbody td { padding:4px 6px; border-bottom:1px solid #e2e8f0; font-size:10px; text-align:right; font-variant-numeric:tabular-nums; }
        tbody td.l { text-align:left; } tbody td.c { text-align:center; }
        tbody tr.wk { background:#f8fafc; }
        tfoot td { padding:7px 6px; border-top:2px solid #008080; background:#e0f2f1; font-weight:800; color:#006666; font-size:10.5px; text-align:right; }
        tfoot td.l { text-align:left; }
        .sign { margin-top:44px; display:flex; justify-content:space-between; font-size:11px; }
        .sign > div { width:40%; text-align:center; }
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
            <div class="sub">Daily Time Record — {{ $label }}</div>
        </div>
    </div>

    @if ($employee)
    <div class="emp">
        <b>Employee:</b> {{ strtoupper($employee->name) }} ({{ $employee->empID }})
        &nbsp;&bull;&nbsp; <b>Department:</b> {{ $employee->department }}
        &nbsp;&bull;&nbsp; <b>Position:</b> {{ $employee->position }}
    </div>
    @else
    <div class="emp">No employee selected.</div>
    @endif

    <table>
        <thead>
            <tr>
                <th class="l">Date</th><th class="l">Day</th><th class="l">Time In</th><th class="l">Time Out</th>
                <th>Hours</th><th>Late</th><th>UT</th><th>N.Diff</th><th class="c">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($days as $d)
            <tr class="{{ $d['is_weekend'] ? 'wk' : '' }}">
                <td class="l">{{ \Illuminate\Support\Carbon::parse($d['date'])->format('M d') }}</td>
                <td class="l">{{ $d['day'] }}</td>
                <td class="l">{{ $d['time_in'] ?: '—' }}</td>
                <td class="l">{{ $d['time_out'] ?: '—' }}</td>
                <td>{{ $d['hours'] ? number_format($d['hours'], 2) : '—' }}</td>
                <td>{{ $d['late'] ?: '' }}</td>
                <td>{{ $d['undertime'] ?: '' }}</td>
                <td>{{ $d['night_diff'] ?: '' }}</td>
                <td class="c">{{ $d['status'] ? ucfirst($d['status']) : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="l" colspan="4">TOTAL — {{ $totals['present'] }} present, {{ $totals['absent'] }} absent</td>
                <td>{{ number_format($totals['hours'], 2) }}</td>
                <td>{{ $totals['late'] }}</td>
                <td>{{ $totals['undertime'] }}</td>
                <td>{{ $totals['night_diff'] }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="sign">
        <div><div class="cap">Employee Signature</div><div class="ln"></div></div>
        <div><div class="cap">Verified by (Supervisor / HR)</div><div class="ln"></div></div>
    </div>
</body>
</html>
