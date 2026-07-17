<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payroll Journal — {{ $label }}</title>
    <style>
        * { box-sizing:border-box; }
        body { font-family:'Segoe UI', Arial, sans-serif; color:#1e293b; margin:28px 34px; font-size:12px; }
        .head { display:flex; align-items:center; gap:14px; border-bottom:3px solid #008080; padding-bottom:12px; margin-bottom:6px; }
        .head img { height:54px; }
        .head .org { font-size:18px; font-weight:800; color:#006666; }
        .head .sub { font-size:12px; color:#475569; }
        .meta { font-size:11px; color:#64748b; margin:10px 0 14px; }
        .meta b { color:#334155; }
        h3 { font-size:12px; color:#006666; text-transform:uppercase; letter-spacing:.5px; margin:22px 0 6px; }
        table { width:100%; border-collapse:collapse; margin-bottom:8px; }
        thead th { background:#008080; color:#fff; font-size:10px; text-transform:uppercase; padding:6px 8px; text-align:right; }
        thead th.l { text-align:left; }
        tbody td { padding:5px 8px; border-bottom:1px solid #e2e8f0; font-size:11px; text-align:right; font-variant-numeric:tabular-nums; }
        tbody td.l { text-align:left; }
        tbody tr:nth-child(even) { background:#f8fafc; }
        tfoot td { padding:7px 8px; border-top:2px solid #008080; background:#e0f2f1; font-weight:800; color:#006666; font-size:11px; text-align:right; }
        tfoot td.l { text-align:left; }
        .ind { padding-left:26px !important; color:#475569; }
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
            <div class="sub">Payroll Journal — GL Summary</div>
        </div>
    </div>

    <div class="meta"><b>{{ $label }}</b> &nbsp;&bull;&nbsp; <b>Generated:</b> {{ now()->format('M d, Y g:i A') }}</div>

    <h3>Summary by Department</h3>
    <table>
        <thead>
            <tr>
                <th class="l">Department</th><th>Heads</th><th>Gross</th><th>EE SSS</th><th>EE PhIC</th><th>EE HDMF</th>
                <th>W/Tax</th><th>Loans</th><th>ER Share</th><th>Net</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($byDept as $r)
            <tr>
                <td class="l">{{ $r->department_name }}</td>
                <td>{{ (int) $r->headcount }}</td>
                <td>{{ number_format($r->gross, 2) }}</td>
                <td>{{ number_format($r->ee_sss, 2) }}</td>
                <td>{{ number_format($r->ee_phic, 2) }}</td>
                <td>{{ number_format($r->ee_hdmf, 2) }}</td>
                <td>{{ number_format($r->wtax, 2) }}</td>
                <td>{{ number_format($r->loans, 2) }}</td>
                <td>{{ number_format($r->er_sss + $r->er_phic + $r->er_hdmf, 2) }}</td>
                <td>{{ number_format($r->net, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="l">TOTAL</td>
                <td>{{ (int) $grand->headcount }}</td>
                <td>{{ number_format($grand->gross, 2) }}</td>
                <td>{{ number_format($grand->ee_sss, 2) }}</td>
                <td>{{ number_format($grand->ee_phic, 2) }}</td>
                <td>{{ number_format($grand->ee_hdmf, 2) }}</td>
                <td>{{ number_format($grand->wtax, 2) }}</td>
                <td>{{ number_format($grand->loans, 2) }}</td>
                <td>{{ number_format($grand->er_sss + $grand->er_phic + $grand->er_hdmf, 2) }}</td>
                <td>{{ number_format($grand->net, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <h3>Journal Entry</h3>
    <table style="max-width:520px;">
        <thead><tr><th class="l">Account</th><th>Debit</th><th>Credit</th></tr></thead>
        <tbody>
            @foreach ($journal['debit'] as $acct => $amt)
            <tr><td class="l">{!! $acct !!}</td><td>{{ number_format($amt, 2) }}</td><td></td></tr>
            @endforeach
            @foreach ($journal['credit'] as $acct => $amt)
            <tr><td class="l ind">{!! $acct !!}</td><td></td><td>{{ number_format($amt, 2) }}</td></tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><td class="l">TOTAL</td><td>{{ number_format($journal['total_debit'], 2) }}</td><td>{{ number_format($journal['total_credit'], 2) }}</td></tr>
        </tfoot>
    </table>

    <div class="sign">
        <div><div class="cap">Prepared by</div><div class="ln"></div></div>
        <div><div class="cap">Reviewed by</div><div class="ln"></div></div>
        <div><div class="cap">Approved by</div><div class="ln"></div></div>
    </div>
</body>
</html>
