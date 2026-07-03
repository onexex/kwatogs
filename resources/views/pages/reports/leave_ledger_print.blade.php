<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Ledger — {{ $year }}</title>
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
        thead th { background:#008080; color:#fff; font-size:9.5px; text-transform:uppercase; padding:6px 8px; text-align:right; }
        thead th.l { text-align:left; }
        tbody td { padding:5px 8px; border-bottom:1px solid #e2e8f0; font-size:10px; text-align:right; font-variant-numeric:tabular-nums; }
        tbody td.l { text-align:left; }
        tbody tr:nth-child(even) { background:#f8fafc; }
        tfoot td { padding:7px 8px; border-top:2px solid #008080; background:#e0f2f1; font-weight:800; color:#006666; font-size:10.5px; text-align:right; }
        tfoot td.l { text-align:left; }
        .sign { margin-top:40px; display:flex; justify-content:space-between; font-size:11px; }
        .sign div { width:30%; text-align:center; }
        .sign .ln { border-top:1px solid #475569; margin-bottom:4px; padding-top:4px; }
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
            <div class="sub">Leave Ledger / Balance — {{ $year }}</div>
        </div>
    </div>

    <div class="meta"><b>Ledger lines:</b> {{ $rows->count() }} &nbsp;&bull;&nbsp; <b>Allocated:</b> {{ number_format($stats['allocated'], 2) }} &nbsp;&bull;&nbsp; <b>Balance:</b> {{ number_format($stats['balance'], 2) }} &nbsp;&bull;&nbsp; <b>Generated:</b> {{ now()->format('M d, Y g:i A') }}</div>

    <table>
        <thead>
            <tr>
                <th class="l">#</th><th class="l">Employee</th><th class="l">Department</th><th class="l">Leave Type</th>
                <th>Allocated</th><th>Used</th><th>Balance</th><th>Filed (days)</th><th>Filed (hrs)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $r)
            <tr>
                <td class="l">{{ $i + 1 }}</td>
                <td class="l"><strong>{{ strtoupper($r->employee_name) }}</strong> <span style="color:#94a3b8;">{{ $r->employee_id }}</span></td>
                <td class="l">{{ $r->department_name }}</td>
                <td class="l">{{ $r->leave_type }}</td>
                <td>{{ $r->has_alloc ? number_format($r->allocated, 2) : '—' }}</td>
                <td>{{ $r->has_alloc ? number_format($r->used, 2) : '—' }}</td>
                <td>{{ $r->has_alloc ? number_format($r->balance, 2) : '—' }}</td>
                <td>{{ number_format($r->filed_days, 2) }}</td>
                <td>{{ number_format($r->filed_hours, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="9" class="l" style="text-align:center; padding:18px; color:#94a3b8;">No leave credits or filed leave for {{ $year }}.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td class="l" colspan="4">TOTAL ({{ $rows->count() }})</td>
                <td>{{ number_format($stats['allocated'], 2) }}</td>
                <td>{{ number_format($stats['used'], 2) }}</td>
                <td>{{ number_format($stats['balance'], 2) }}</td>
                <td>{{ number_format($stats['filed'], 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="sign">
        <div><div class="ln"></div>Prepared by</div>
        <div><div class="ln"></div>Verified by</div>
        <div><div class="ln"></div>Approved by</div>
    </div>
</body>
</html>
