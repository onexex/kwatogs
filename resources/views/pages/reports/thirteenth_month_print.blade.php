<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>13th Month Pay {{ $year }}</title>
    <style>
        * { box-sizing:border-box; }
        body { font-family:'Segoe UI', Arial, sans-serif; color:#1e293b; margin:28px 34px; font-size:12px; }
        .head { display:flex; align-items:center; gap:14px; border-bottom:3px solid #008080; padding-bottom:12px; margin-bottom:6px; }
        .head img { height:54px; width:auto; }
        .head .org { font-size:18px; font-weight:800; color:#006666; letter-spacing:.3px; }
        .head .sub { font-size:12px; color:#475569; margin-top:1px; }
        .meta { font-size:11px; color:#64748b; margin:10px 0 14px; }
        .meta b { color:#334155; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#008080; color:#fff; font-size:10.5px; text-transform:uppercase; letter-spacing:.4px;
            padding:7px 8px; text-align:left; }
        thead th.r { text-align:right; } thead th.c { text-align:center; }
        tbody td { padding:6px 8px; border-bottom:1px solid #e2e8f0; font-size:11px; }
        tbody td.r { text-align:right; } tbody td.c { text-align:center; }
        tbody tr:nth-child(even) { background:#f8fafc; }
        tfoot td { padding:9px 8px; border-top:2px solid #008080; background:#e0f2f1; font-weight:800; color:#006666; font-size:11.5px; }
        tfoot td.r { text-align:right; }
        .note { margin-top:16px; font-size:10px; color:#94a3b8; font-style:italic; }
        .sign { margin-top:46px; display:flex; justify-content:space-between; font-size:11px; }
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
            <div class="sub">13th Month Pay &mdash; Coverage: {{ $coverage }}</div>
        </div>
    </div>

    <div class="meta">
        <b>Computation:</b> Total basic salary earned ({{ $coverage }}) &divide; 12.
        &nbsp;&bull;&nbsp; <b>Employees:</b> {{ $rows->count() }}
        &nbsp;&bull;&nbsp; <b>Generated:</b> {{ now()->format('M d, Y g:i A') }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:34px;">#</th>
                <th>Employee</th>
                <th>Department</th>
                <th>Company</th>
                <th class="c">Status</th>
                <th class="c">Months</th>
                <th class="r">Total Basic Earned</th>
                <th class="r">13th Month Pay</th>
                <th class="r">Taxable Excess</th>
                <th class="c">Released</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ strtoupper($r->employee_name) }}</strong><br><span style="color:#94a3b8;">{{ $r->employee_id }}</span></td>
                <td>{{ $r->department_name }}</td>
                <td>{{ $r->company_name }}</td>
                <td class="c">{{ $r->status_label ?? '—' }}</td>
                <td class="c">{{ $r->months }}/12</td>
                <td class="r">{{ number_format($r->total_basic, 2) }}</td>
                <td class="r"><strong>{{ number_format($r->thirteenth, 2) }}</strong></td>
                <td class="r">{{ $r->taxable > 0 ? number_format($r->taxable, 2) : '—' }}</td>
                <td class="c">{{ $r->released ? ('Yes'.($r->released_at ? ' '.$r->released_at : '')) : 'No' }}</td>
            </tr>
            @empty
            <tr><td colspan="10" style="text-align:center; padding:20px; color:#94a3b8;">No records within this coverage.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="r">GRAND TOTAL</td>
                <td class="r">{{ number_format($totalBasic, 2) }}</td>
                <td class="r">{{ number_format($total13th, 2) }}</td>
                <td class="r">{{ number_format($totalTaxable ?? 0, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="note">
        13th month pay is mandated under Presidential Decree No. 851. Amounts are based on basic salary actually earned and exclude
        overtime, allowances, holiday premiums and night differential. Figures are pro-rated for employees who did not work the full year.
    </div>

    <div class="sign">
        <div><div class="ln"></div>Prepared by</div>
        <div><div class="ln"></div>Checked &amp; Verified by</div>
        <div><div class="ln"></div>Approved by</div>
    </div>
</body>
</html>
