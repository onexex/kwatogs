<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payroll Logs</title>
    <style>
        :root { --teal:#008080; --ink:#1f2937; --muted:#6b7280; --line:#e5e7eb; }
        * { box-sizing: border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; color:var(--ink); margin:0; padding:18px; font-size:11px; }
        .toolbar { display:flex; gap:8px; justify-content:flex-end; margin-bottom:10px; }
        .btn { border:0; border-radius:6px; padding:7px 14px; font-weight:700; cursor:pointer; font-size:12px; }
        .btn-print { background:var(--teal); color:#fff; } .btn-close { background:#e5e7eb; color:#374151; }
        .hd { text-align:center; margin-bottom:10px; }
        .hd img { height:42px; }
        .hd .t { font-weight:800; color:var(--teal); font-size:15px; margin-top:2px; }
        .log { border-top:2px solid var(--teal); padding:8px 0 10px; margin-bottom:8px; page-break-inside:avoid; }
        .log .emp { font-weight:800; font-size:12px; text-transform:uppercase; }
        .log .meta { font-size:10px; color:var(--muted); margin:1px 0 7px; }
        .cols { column-count:3; column-gap:10px; }
        .grp { break-inside:avoid; border:1px solid var(--line); border-radius:4px; margin-bottom:6px; display:inline-block; width:100%; }
        .grp-h { background:#f1f5f9; font-weight:700; font-size:9px; text-transform:uppercase; letter-spacing:.3px; color:#475569; padding:3px 7px; border-bottom:1px solid var(--line); }
        table.kv { width:100%; border-collapse:collapse; }
        table.kv td { padding:2px 7px; font-size:10px; border-bottom:1px solid #f3f4f6; vertical-align:top; }
        table.kv td.k { color:#475569; text-transform:capitalize; padding-right:6px; }
        table.kv td.v { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
        @media print {
            body { padding:0; font-size:10px; }
            .toolbar { display:none; }
            .cols { column-count:3; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-print" onclick="window.print()">Print</button>
        <button class="btn btn-close" onclick="window.close()">Close</button>
    </div>

    <div class="hd">
        <img src="{{ asset('img/kwatogslogo.jpg') }}" alt="logo">
        <div class="t">Payroll Computation Logs</div>
    </div>

    @php
        // Keys in the computation breakdown that represent money (comma + 2 decimals).
        // Counts, minutes, hours and dates are deliberately left as-is.
        $moneyKeys = [
            'basic_monthly','daily_rate','hourly_rate','daily_allowance','allowance_hourly',
            'x_hourly_rate','x_daily','deduction','amount','total_pay','pay','gross','net',
            'late_ut_deduction','total_deductions','basic_pay','gross_pay','holiday_pay',
            'sss','philhealth','pagibig','tax','gov_dues','taxable',
            'company','charges','cash_adv','other','sss_loan','pagibig_loan',
            'gross_taxed','net_after_tax','total','net_pay','pay_receivable',
        ];
        $isMoneyKey = fn ($k) => in_array(strtolower((string) $k), $moneyKeys, true);

        $render = function ($data) use (&$render, $isMoneyKey) {
            $h = '<table class="kv"><tbody>';
            foreach ((array) $data as $k => $v) {
                $label = ucwords(str_replace('_', ' ', (string) $k));
                if (is_array($v)) {
                    $h .= '<tr><td class="k" colspan="2" style="font-weight:700;background:#fafafa;">' . e($label) . '</td></tr>';
                    $h .= '<tr><td colspan="2" style="padding:0;">' . $render($v) . '</td></tr>';
                } else {
                    if (is_bool($v)) {
                        $val = $v ? 'yes' : 'no';
                    } elseif ($isMoneyKey($k) && is_numeric($v)) {
                        $val = number_format((float) $v, 2);
                    } else {
                        $val = $v;
                    }
                    $h .= '<tr><td class="k">' . e($label) . '</td><td class="v">' . e($val) . '</td></tr>';
                }
            }
            return $h . '</tbody></table>';
        };
    @endphp

    @forelse ($logs as $log)
        @php $bd = $log->breakdown ?? []; @endphp
        <div class="log">
            <div class="emp">{{ $log->employee_name ?? $log->employee_id }}</div>
            <div class="meta">
                {{ $log->employee_id }} &middot; {{ $log->classification ?? '-' }} &middot;
                {{ \Carbon\Carbon::parse($log->pay_date)->format('M d, Y') }} &middot;
                Cut-off {{ optional($log->payroll_start_date)->format('M d') }}&ndash;{{ optional($log->payroll_end_date)->format('M d, Y') }} &middot;
                Gross {{ number_format($log->gross_pay, 2) }} &middot; Net {{ number_format($log->net_pay, 2) }} &middot;
                <b>Receivable {{ number_format($log->pay_rec, 2) }}</b>
            </div>

            @if (!empty($bd) && is_array($bd))
                <div class="cols">
                    @foreach ($bd as $section => $val)
                        @if (in_array($section, ['employee_id','name'])) @continue @endif
                        <div class="grp">
                            <div class="grp-h">{{ ucwords(str_replace('_', ' ', $section)) }}</div>
                            @if (is_array($val))
                                {!! $render($val) !!}
                            @else
                                <table class="kv"><tbody><tr><td class="v">{{ $isMoneyKey($section) && is_numeric($val) ? number_format((float) $val, 2) : $val }}</td></tr></tbody></table>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="meta">No breakdown stored.</div>
            @endif
        </div>
    @empty
        <div style="text-align:center;color:#6b7280;margin:24px 0;">No payroll logs match the selected filters.</div>
    @endforelse
</body>
</html>
