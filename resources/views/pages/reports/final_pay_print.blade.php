<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Final Pay Computation — {{ $year === 'all' ? 'All Years' : $year }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing:border-box; }
        body { font-family:'Segoe UI', Arial, sans-serif; color:#1e293b; margin:18px 22px; font-size:10px; }
        .head { display:flex; align-items:center; gap:12px; border-bottom:3px solid #008080; padding-bottom:10px; margin-bottom:6px; }
        .head img { height:46px; }
        .head .org { font-size:16px; font-weight:800; color:#006666; }
        .head .sub { font-size:11px; color:#475569; }
        .meta { font-size:10px; color:#64748b; margin:8px 0 12px; }
        .meta b { color:#334155; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#008080; color:#fff; font-size:9px; text-transform:uppercase; padding:5px 6px; text-align:right; }
        thead th.l { text-align:left; }
        tbody td { padding:5px 6px; border-bottom:1px solid #e2e8f0; font-size:9.5px; text-align:right; font-variant-numeric:tabular-nums; }
        tbody td.l { text-align:left; }
        tbody tr:nth-child(even) { background:#f8fafc; }
        tfoot td { padding:6px; border-top:2px solid #008080; background:#e0f2f1; font-weight:800; color:#006666; font-size:9.5px; text-align:right; }
        tfoot td.l { text-align:left; }
        .note { margin-top:14px; font-size:9px; color:#94a3b8; font-style:italic; }
        @media print { .noprint { display:none; } }
        .noprint { text-align:right; margin-bottom:8px; }
        .btn { background:#008080; color:#fff; border:none; padding:7px 16px; border-radius:6px; font-weight:700; cursor:pointer; }
    </style>
</head>
<body>
    <div class="noprint"><button class="btn" onclick="window.print()">Print</button></div>

    <div class="head">
        <img src="{{ asset('img/kwatogslogo.jpg') }}" onerror="this.style.display='none'" alt="">
        <div>
            <div class="org">{{ $letterhead }}</div>
            <div class="sub">Final Pay / Last Pay Computation (Estimate) — {{ $year === 'all' ? 'All Years' : $year }}</div>
        </div>
    </div>

    <div class="meta"><b>Separated employees:</b> {{ $rows->count() }} &nbsp;&bull;&nbsp; <b>Est. total final pay:</b> {{ number_format($stats['estimated'], 2) }} &nbsp;&bull;&nbsp; <b>Generated:</b> {{ now()->format('M d, Y g:i A') }}</div>

    <table>
        <thead>
            <tr>
                <th class="l">#</th><th class="l">Employee</th><th class="l">Department</th><th class="l">Separated</th>
                <th>Years</th><th>Daily Rate</th><th>Basic Earned</th><th>13th (Pro-rated)</th><th>Leave Bal</th><th>Leave Conv.</th><th>Est. Final Pay</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $r)
            <tr>
                <td class="l">{{ $i + 1 }}</td>
                <td class="l"><strong>{{ strtoupper($r->employee_name) }}</strong> <span style="color:#94a3b8;">{{ $r->employee_id }}</span></td>
                <td class="l">{{ $r->department_name }}</td>
                <td class="l">{{ $r->separation_date ? \Illuminate\Support\Carbon::parse($r->separation_date)->format('M d, Y') : '—' }}</td>
                <td>{{ $r->years_rendered !== null ? number_format($r->years_rendered, 2) : '—' }}</td>
                <td>{{ number_format($r->daily_rate, 2) }}</td>
                <td>{{ number_format($r->basic_earned, 2) }}</td>
                <td>{{ number_format($r->prorated_13th, 2) }}</td>
                <td>{{ number_format($r->leave_balance, 2) }}</td>
                <td>{{ number_format($r->leave_conversion, 2) }}</td>
                <td><strong>{{ number_format($r->estimated_final, 2) }}</strong></td>
            </tr>
            @empty
            <tr><td colspan="11" class="l" style="text-align:center; padding:16px; color:#94a3b8;">No separated employees for this filter.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td class="l" colspan="7">TOTAL ({{ $rows->count() }})</td>
                <td>{{ number_format($stats['th13'], 2) }}</td>
                <td></td>
                <td>{{ number_format($stats['leave_conv'], 2) }}</td>
                <td>{{ number_format($stats['estimated'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="note">
        Estimate of the derivable components only. Pro-rated 13th month = basic earned in the separation year ÷ 12.
        Leave conversion = remaining leave balance × daily rate (monthly basic ÷ 26). Last unpaid salary, tax refund,
        and outstanding deductions/loans are policy-specific and must be added manually before releasing final pay.
    </div>
</body>
</html>
