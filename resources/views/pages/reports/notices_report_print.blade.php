<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Disciplinary Notices Summary</title>
    <style>
        * { box-sizing:border-box; }
        body { font-family:'Segoe UI', Arial, sans-serif; color:#1e293b; margin:24px 30px; font-size:11px; }
        .head { display:flex; align-items:center; gap:14px; border-bottom:3px solid #008080; padding-bottom:12px; margin-bottom:6px; }
        .head img { height:50px; }
        .head .org { font-size:17px; font-weight:800; color:#006666; }
        .head .sub { font-size:12px; color:#475569; }
        .meta { font-size:11px; color:#64748b; margin:10px 0 14px; }
        .meta b { color:#334155; }
        h3 { font-size:12px; color:#006666; text-transform:uppercase; letter-spacing:.5px; margin:20px 0 6px; }
        table { width:100%; border-collapse:collapse; margin-bottom:6px; }
        thead th { background:#008080; color:#fff; font-size:9.5px; text-transform:uppercase; padding:6px 8px; text-align:right; }
        thead th.l { text-align:left; } thead th.c { text-align:center; }
        tbody td { padding:5px 8px; border-bottom:1px solid #e2e8f0; font-size:10px; text-align:right; font-variant-numeric:tabular-nums; }
        tbody td.l { text-align:left; } tbody td.c { text-align:center; }
        tbody tr:nth-child(even) { background:#f8fafc; }
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
            <div class="sub">Disciplinary Notices Summary</div>
        </div>
    </div>

    <div class="meta"><b>Employees:</b> {{ $stats['employees'] }} &nbsp;&bull;&nbsp; <b>Disciplinary:</b> {{ $stats['disciplinary'] }} &nbsp;&bull;&nbsp; <b>Over-limit:</b> {{ $stats['over'] }} &nbsp;&bull;&nbsp; <b>At risk:</b> {{ $stats['at_risk'] }} &nbsp;&bull;&nbsp; <b>Generated:</b> {{ now()->format('M d, Y g:i A') }}</div>

    @if ($recommendations->count())
    <h3>Pending Suspension Recommendations</h3>
    <table>
        <thead><tr><th class="l">Employee</th><th class="c">Notices</th><th class="l">Reason</th><th class="l">Recommended</th></tr></thead>
        <tbody>
            @foreach ($recommendations as $r)
            <tr><td class="l"><strong>{{ strtoupper($r->employee_name) }}</strong> <span style="color:#94a3b8;">{{ $r->employee_id }}</span></td>
                <td class="c">{{ $r->notice_count }}</td><td class="l">{{ $r->reason }}</td>
                <td class="l">{{ $r->recommended_at ? \Illuminate\Support\Carbon::parse($r->recommended_at)->format('M d, Y') : '—' }}</td></tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <h3>Notices by Employee</h3>
    <table>
        <thead>
            <tr><th class="l">#</th><th class="l">Employee</th><th class="l">Department</th><th>Disciplinary</th><th>Active</th><th>Memos</th><th>Void</th><th class="c">Status</th></tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $r)
            <tr>
                <td class="l">{{ $i + 1 }}</td>
                <td class="l"><strong>{{ strtoupper($r->employee_name) }}</strong> <span style="color:#94a3b8;">{{ $r->employee_id }}</span></td>
                <td class="l">{{ $r->department_name }}</td>
                <td>{{ $r->disciplinary }}</td>
                <td>{{ $r->active_disciplinary }}</td>
                <td>{{ $r->memos }}</td>
                <td>{{ $r->voided }}</td>
                <td class="c">{{ $r->escalation === 'over' ? 'OVER-LIMIT' : ($r->escalation === 'at_risk' ? 'AT RISK' : 'OK') }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="l" style="text-align:center; padding:18px; color:#94a3b8;">No notices found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
