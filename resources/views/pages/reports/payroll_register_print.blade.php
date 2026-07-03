<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payroll Register — {{ $label }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing:border-box; }
        body { font-family:'Segoe UI', Arial, sans-serif; color:#1e293b; margin:16px 20px; font-size:10px; }
        .head { display:flex; align-items:center; gap:12px; border-bottom:3px solid #008080; padding-bottom:10px; margin-bottom:6px; }
        .head img { height:44px; }
        .head .org { font-size:16px; font-weight:800; color:#006666; }
        .head .sub { font-size:11px; color:#475569; }
        .meta { font-size:10px; color:#64748b; margin:8px 0 12px; }
        .meta b { color:#334155; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#008080; color:#fff; font-size:8.5px; text-transform:uppercase; letter-spacing:.2px; padding:5px 4px; text-align:right; }
        thead th.l { text-align:left; }
        tbody td { padding:4px; border-bottom:1px solid #e2e8f0; font-size:9px; text-align:right; font-variant-numeric:tabular-nums; }
        tbody td.l { text-align:left; }
        tbody tr:nth-child(even) { background:#f8fafc; }
        tfoot td { padding:6px 4px; border-top:2px solid #008080; background:#e0f2f1; font-weight:800; color:#006666; font-size:9px; text-align:right; }
        tfoot td.l { text-align:left; }
        .sign { margin-top:34px; display:flex; justify-content:space-between; font-size:10px; }
        .sign div { width:30%; text-align:center; }
        .sign .ln { border-top:1px solid #475569; margin-bottom:4px; padding-top:4px; }
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
            <div class="sub">Payroll Register</div>
        </div>
    </div>

    <div class="meta"><b>{{ $label }}</b> &nbsp;&bull;&nbsp; <b>Employees:</b> {{ $rows->count() }} &nbsp;&bull;&nbsp; <b>Generated:</b> {{ now()->format('M d, Y g:i A') }}</div>

    <table>
        <thead>
            <tr>
                <th class="l">#</th>
                <th class="l">Employee</th>
                <th>Basic</th><th>Allow</th><th>OT</th><th>Holiday</th><th>N.Diff</th><th>Gross</th>
                <th>Late/UT</th><th>SSS</th><th>PhIC</th><th>HDMF</th><th>Tax</th><th>Loans</th><th>Other</th><th>Tot.Ded</th><th>Net</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $r)
            <tr>
                <td class="l">{{ $i + 1 }}</td>
                <td class="l"><strong>{{ strtoupper($r->employee_name) }}</strong> <span style="color:#94a3b8;">{{ $r->employee_id }}</span></td>
                <td>{{ number_format($r->basic_salary, 2) }}</td>
                <td>{{ number_format($r->allowances, 2) }}</td>
                <td>{{ number_format($r->overtime_pay, 2) }}</td>
                <td>{{ number_format($r->holiday_pay, 2) }}</td>
                <td>{{ number_format($r->night_diff_pay, 2) }}</td>
                <td><strong>{{ number_format($r->gross_pay, 2) }}</strong></td>
                <td>{{ number_format($r->tardiness, 2) }}</td>
                <td>{{ number_format($r->sss_contribution, 2) }}</td>
                <td>{{ number_format($r->philhealth_contribution, 2) }}</td>
                <td>{{ number_format($r->pagibig_contribution, 2) }}</td>
                <td>{{ number_format($r->withholding_tax, 2) }}</td>
                <td>{{ number_format($r->loans, 2) }}</td>
                <td>{{ number_format($r->other_ded, 2) }}</td>
                <td>{{ number_format($r->total_ded, 2) }}</td>
                <td><strong>{{ number_format($r->net, 2) }}</strong></td>
            </tr>
            @empty
            <tr><td colspan="17" class="l" style="text-align:center; padding:16px; color:#94a3b8;">No payroll rows.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td class="l" colspan="2">TOTAL ({{ $rows->count() }})</td>
                <td>{{ number_format($totals['basic_salary'], 2) }}</td>
                <td>{{ number_format($totals['allowances'], 2) }}</td>
                <td>{{ number_format($totals['overtime_pay'], 2) }}</td>
                <td>{{ number_format($totals['holiday_pay'], 2) }}</td>
                <td>{{ number_format($totals['night_diff_pay'], 2) }}</td>
                <td>{{ number_format($totals['gross_pay'], 2) }}</td>
                <td>{{ number_format($totals['tardiness'], 2) }}</td>
                <td>{{ number_format($totals['sss_contribution'], 2) }}</td>
                <td>{{ number_format($totals['philhealth_contribution'], 2) }}</td>
                <td>{{ number_format($totals['pagibig_contribution'], 2) }}</td>
                <td>{{ number_format($totals['withholding_tax'], 2) }}</td>
                <td>{{ number_format($totals['loans'], 2) }}</td>
                <td>{{ number_format($totals['other_ded'], 2) }}</td>
                <td>{{ number_format($totals['total_ded'], 2) }}</td>
                <td>{{ number_format($totals['net'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="sign">
        <div><div class="ln"></div>Prepared by</div>
        <div><div class="ln"></div>Checked &amp; Verified by</div>
        <div><div class="ln"></div>Approved by</div>
    </div>
</body>
</html>
