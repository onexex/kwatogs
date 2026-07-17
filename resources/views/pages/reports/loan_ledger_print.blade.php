<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Ledger — {{ $status }}</title>
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
        thead th { background:#008080; color:#fff; font-size:9.5px; text-transform:uppercase; letter-spacing:.3px; padding:6px 6px; text-align:right; }
        thead th.l { text-align:left; } thead th.c { text-align:center; }
        tbody td { padding:5px 6px; border-bottom:1px solid #e2e8f0; font-size:10px; text-align:right; font-variant-numeric:tabular-nums; }
        tbody td.l { text-align:left; } tbody td.c { text-align:center; }
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
            <div class="sub">Loan Ledger / Outstanding Balances</div>
        </div>
    </div>

    <div class="meta"><b>Status filter:</b> {{ $status }} &nbsp;&bull;&nbsp; <b>Loans:</b> {{ $rows->count() }} &nbsp;&bull;&nbsp; <b>As of:</b> {{ now()->format('M d, Y g:i A') }}</div>

    <table>
        <thead>
            <tr>
                <th class="l">#</th><th class="l">Employee</th><th class="l">Loan Type</th>
                <th>Principal</th><th>Paid</th><th>Balance</th><th>Monthly</th><th class="c">Recurring</th><th class="l">Term</th><th class="c">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $r)
            <tr>
                <td class="l">{{ $i + 1 }}</td>
                <td class="l"><strong>{{ strtoupper($r->employee_name) }}</strong> <span style="color:#94a3b8;">{{ $r->employee_id }}</span><br><span style="color:#94a3b8;">{{ $r->department_name }}</span></td>
                <td class="l">{{ $r->type_label }}</td>
                <td>{{ number_format($r->loan_amount, 2) }}</td>
                <td>{{ number_format($r->total_paid, 2) }}</td>
                <td><strong>{{ number_format($r->balance, 2) }}</strong></td>
                <td>{{ number_format($r->monthly_amortization, 2) }}</td>
                <td class="c">{{ $r->is_recurring ? 'Yes' : 'No' }}</td>
                <td class="l">{{ $r->start_date ? \Illuminate\Support\Carbon::parse($r->start_date)->format('M d, y') : '—' }} – {{ $r->end_date ? \Illuminate\Support\Carbon::parse($r->end_date)->format('M d, y') : '—' }}</td>
                <td class="c">{{ ucfirst($r->status) }}</td>
            </tr>
            @empty
            <tr><td colspan="10" class="l" style="text-align:center; padding:18px; color:#94a3b8;">No loans match this filter.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td class="l" colspan="3">TOTAL ({{ $rows->count() }})</td>
                <td>{{ number_format($stats['principal'], 2) }}</td>
                <td>{{ number_format($stats['paid'], 2) }}</td>
                <td>{{ number_format($stats['outstanding'], 2) }}</td>
                <td>{{ number_format($stats['monthly'], 2) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

    <div class="sign">
        <div><div class="cap">Prepared by</div><div class="ln"></div></div>
        <div><div class="cap">Checked &amp; Verified by</div><div class="ln"></div></div>
        <div><div class="cap">Approved by</div><div class="ln"></div></div>
    </div>
</body>
</html>
