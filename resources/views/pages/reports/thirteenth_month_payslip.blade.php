<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>13th Month Payslip — {{ $emp->employee_name }}</title>
    <style>
        :root { --teal:#008080; --ink:#1f2937; --muted:#6b7280; --line:#e5e7eb; }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: var(--ink); background:#f3f4f6; margin:0; padding:24px; }

        .toolbar { max-width: 560px; margin: 0 auto 16px; display:flex; gap:8px; justify-content:flex-end; }
        .btn { border:0; border-radius:8px; padding:8px 16px; font-weight:700; cursor:pointer; font-size:13px; }
        .btn-print { background:var(--teal); color:#fff; }
        .btn-close { background:#e5e7eb; color:#374151; }

        .payslip {
            max-width: 560px; margin: 0 auto 20px; background:#fff; border:1px solid var(--line);
            border-radius:10px; padding:26px 30px; box-shadow:0 1px 4px rgba(0,0,0,.06);
        }

        .ps-head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid var(--teal); padding-bottom:14px; margin-bottom:16px; }
        .ps-company { display:flex; gap:12px; align-items:center; }
        .ps-company img { height:46px; }
        .ps-company .name { font-size:17px; font-weight:800; letter-spacing:.3px; }
        .ps-company .sub  { font-size:12px; color:var(--muted); }
        .ps-company .addr { font-size:11px; color:var(--muted); margin-top:1px; }
        .ps-title { text-align:right; }
        .ps-title .t { font-size:14px; font-weight:800; color:var(--teal); letter-spacing:1px; }
        .ps-title .p { font-size:12px; color:var(--muted); margin-top:2px; }

        .ps-emp { display:grid; grid-template-columns:1fr 1fr; gap:4px 24px; font-size:13px; margin-bottom:16px; }
        .ps-emp .lbl { color:var(--muted); }
        .ps-emp b { font-weight:700; }

        .col h4 { margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:.6px; color:var(--muted); border-bottom:1px solid var(--line); padding-bottom:6px; }
        .ln { display:flex; justify-content:space-between; font-size:13px; padding:5px 0; }
        .ln .v { font-variant-numeric: tabular-nums; font-weight:700; }
        .ln.muted .v { color:var(--muted); font-weight:600; }

        .breakdown { font-size:11px; color:var(--muted); font-style:italic; text-align:right; margin:4px 0 0; }

        .settle { margin-top:18px; border-top:2px solid var(--line); padding-top:12px; }
        .settle .net { display:flex; justify-content:space-between; align-items:center; font-size:18px; font-weight:800; color:var(--teal); border-top:1px dashed var(--line); margin-top:6px; padding-top:10px; }

        .sign { margin-top:40px; font-size:12px; color:var(--muted); }
        .sign .slot { border-top:1px solid #9ca3af; padding-top:4px; text-align:center; width:70%; margin:0 auto; }

        @media print {
            body { background:#fff; padding:0; }
            .toolbar { display:none; }
            .payslip { box-shadow:none; border:0; border-radius:0; margin:0; max-width:none; padding:18px 22px; }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <button class="btn btn-print" onclick="window.print()">🖨 Print</button>
        <button class="btn btn-close" onclick="window.close()">Close</button>
    </div>

    <div class="payslip">
        <div class="ps-head">
            <div class="ps-company">
                <img src="{{ asset('img/kwatogslogo.jpg') }}" onerror="this.style.display='none'" alt="logo">
                <div>
                    <div class="name">{{ $emp->dep_name ?? ($emp->comp_name ?? config('app.name', 'Company')) }}</div>
                    <div class="sub">13th Month Pay</div>
                    @if($emp->dep_address)<div class="addr">{{ $emp->dep_address }}</div>@endif
                </div>
            </div>
            <div class="ps-title">
                <div class="t">13TH MONTH</div>
                <div class="p">Pay Date: {{ $payDate->format('M d, Y') }}</div>
                <div class="p">Coverage: {{ $coverage }}</div>
            </div>
        </div>

        <div class="ps-emp">
            <div><span class="lbl">Employee:</span> <b>{{ strtoupper($emp->employee_name) }}</b></div>
            <div><span class="lbl">Employee ID:</span> <b>{{ $emp->empID }}</b></div>
            <div><span class="lbl">Position:</span> <b>{{ $emp->pos_desc ?? '—' }}</b></div>
            <div><span class="lbl">Basic Rate:</span> <b>{{ number_format((float) ($emp->basic_rate ?? 0), 2) }}</b></div>
        </div>

        <div class="col">
            <h4>13th Month Computation</h4>
            <div class="ln muted"><span>Total Days Paid</span><span class="v">{{ $totalDays > 0 ? number_format($totalDays) : '—' }}</span></div>
            <div class="ln muted"><span>Total Tardiness (hrs)</span><span class="v">{{ $tardyHours > 0 ? number_format($tardyHours, 2) : '—' }}</span></div>
            <div class="ln"><span>Total Basic Earned ({{ $months }}/12 mo)</span><span class="v">{{ number_format($totalBasic, 2) }}</span></div>
            <div class="breakdown">13th Month Pay = Total Basic Earned &divide; 12</div>
        </div>

        <div class="settle">
            <div class="net"><span>NET PAY RECEIVABLE</span><span>&#8369; {{ number_format($thirteenth, 2) }}</span></div>
        </div>

        <div class="sign">
            <div class="slot">Signature over Printed Name</div>
        </div>
    </div>

</body>
</html>
