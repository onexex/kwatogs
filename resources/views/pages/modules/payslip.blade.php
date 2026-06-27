<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payslip {{ \Carbon\Carbon::parse($payDate)->format('M d, Y') }}</title>
    <style>
        :root { --teal:#008080; --ink:#1f2937; --muted:#6b7280; --line:#e5e7eb; }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: var(--ink); background:#f3f4f6; margin:0; padding:24px; }

        .toolbar { max-width: 800px; margin: 0 auto 16px; display:flex; gap:8px; justify-content:flex-end; }
        .btn { border:0; border-radius:8px; padding:8px 16px; font-weight:700; cursor:pointer; font-size:13px; }
        .btn-print { background:var(--teal); color:#fff; }
        .btn-close { background:#e5e7eb; color:#374151; }

        .payslip {
            max-width: 800px; margin: 0 auto 20px; background:#fff; border:1px solid var(--line);
            border-radius:10px; padding:26px 30px; box-shadow:0 1px 4px rgba(0,0,0,.06);
        }

        .ps-head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid var(--teal); padding-bottom:14px; margin-bottom:16px; }
        .ps-company { display:flex; gap:12px; align-items:center; }
        .ps-company img { height:46px; }
        .ps-company .name { font-size:18px; font-weight:800; letter-spacing:.3px; }
        .ps-company .sub { font-size:12px; color:var(--muted); }
        .ps-title { text-align:right; }
        .ps-title .t { font-size:16px; font-weight:800; color:var(--teal); letter-spacing:1px; }
        .ps-title .p { font-size:12px; color:var(--muted); margin-top:2px; }

        .ps-emp { display:grid; grid-template-columns:1fr 1fr; gap:4px 24px; font-size:13px; margin-bottom:16px; }
        .ps-emp .lbl { color:var(--muted); }
        .ps-emp b { font-weight:700; }

        .cols { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
        .col h4 { margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:.6px; color:var(--muted); border-bottom:1px solid var(--line); padding-bottom:6px; }
        .ln { display:flex; justify-content:space-between; font-size:13px; padding:4px 0; }
        .ln .v { font-variant-numeric: tabular-nums; }
        .sub-tot { display:flex; justify-content:space-between; font-size:13px; font-weight:700; border-top:1px solid var(--line); margin-top:6px; padding-top:6px; }

        .settle { margin-top:18px; border-top:2px solid var(--line); padding-top:12px; }
        .settle .row { display:flex; justify-content:space-between; font-size:13px; padding:3px 0; }
        .settle .net { font-size:16px; font-weight:800; color:var(--teal); border-top:1px dashed var(--line); margin-top:6px; padding-top:8px; }

        .sign { display:grid; grid-template-columns:1fr 1fr; gap:40px; margin-top:34px; font-size:12px; color:var(--muted); }
        .sign .slot { border-top:1px solid #9ca3af; padding-top:4px; text-align:center; }

        /* ── Computation waterfall (matches Payroll Logs) ── */
        table.calc { width:100%; border-collapse:collapse; margin-top:4px; }
        table.calc td { padding:5px 12px; font-size:13px; border-bottom:1px solid #f3f4f6; vertical-align:baseline; }
        table.calc td.lbl  { color:var(--ink); }
        table.calc td.note { color:var(--muted); font-size:11px; text-align:right; white-space:nowrap; }
        table.calc td.amt  { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; width:150px; font-weight:600; }
        table.calc td.amt.add { color:#0f766e; }
        table.calc td.amt.sub { color:#b91c1c; }
        table.calc tr.sec td { background:#f8fafc; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); padding-top:10px; padding-bottom:4px; }
        table.calc tr.mile td { background:#e0f2f1; font-weight:800; border-top:2px solid var(--teal); border-bottom:2px solid var(--teal); }
        table.calc tr.mile td.lbl { color:#006666; text-transform:uppercase; font-size:13px; letter-spacing:.3px; }
        table.calc tr.mile td.amt { color:#006666; font-size:14px; }
        table.calc tr.grand td { background:var(--teal); color:#fff; font-weight:800; }
        table.calc tr.grand td.lbl { text-transform:uppercase; letter-spacing:.4px; }
        table.calc tr.grand td.amt { color:#fff; font-size:15px; }

        @media print {
            body { background:#fff; padding:0; }
            .toolbar { display:none; }
            .payslip { box-shadow:none; border:0; border-radius:0; margin:0; max-width:none; padding:18px 22px; page-break-after: always; }
            .payslip:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <button class="btn btn-print" onclick="window.print()">🖨 Print</button>
        <button class="btn btn-close" onclick="window.close()">Close</button>
    </div>

    @php
        // Shared formatters + waterfall row builders (defined once for all slips).
        $peso = fn ($n) => number_format((float) $n, 2);
        $psRow = function ($label, $amount, $sign = 'add', $note = '') use ($peso) {
            $cls = $sign === 'sub' ? 'sub' : 'add';
            $pre = $sign === 'sub' ? '&minus; ' : '+ ';
            return '<tr><td class="lbl">'.e($label).'</td><td class="note">'.e($note).'</td>'
                 . '<td class="amt '.$cls.'">'.$pre.$peso($amount).'</td></tr>';
        };
        $psMile = function ($label, $amount, $grand = false) use ($peso) {
            $tr = $grand ? 'grand' : 'mile';
            return '<tr class="'.$tr.'"><td class="lbl">'.e($label).'</td><td></td>'
                 . '<td class="amt">'.$peso($amount).'</td></tr>';
        };
        $psSec = fn ($label) => '<tr class="sec"><td colspan="3">'.e($label).'</td></tr>';
    @endphp

    @forelse ($payrolls as $p)
        @php
            $emp     = $p->employee;
            $detail  = optional($emp)->empDetail;
            $company = optional($detail)->company;

            $fullName = $emp
                ? strtoupper(trim(($emp->lname ?? '') . ', ' . ($emp->fname ?? '')))
                : 'UNKNOWN';

            // Earnings
            $basic   = (float) $p->basicPay;
            $holiday = (float) $p->holiday_pay;
            $ot      = (float) $p->overtime_pay;
            $nd      = (float) $p->night_diff_pay;
            $allow   = (float) $p->allowances;

            // Deductions
            $absut   = (float) $p->abs_ut_deduction;
            $ob      = (float) $p->overBreakDeduction;
            $op      = (float) $p->outPassDeduction;
            $sss     = (float) $p->sss_contribution;
            $phil    = (float) $p->philhealth_contribution;
            $pag     = (float) $p->pagibig_contribution;
            $tax     = (float) $p->withholding_tax;
            $sssLoan = (float) $p->sss_loan;
            $pagLoan = (float) $p->pagibig_loan;
            $compLoan= (float) $p->company_loan;
            $cashAdv = (float) $p->cash_advance;
            $charges = (float) $p->penalty_amount;

            // ── Computation waterfall — foots to the stored milestones ──
            // Gross = (basic+holiday+OT+ND) − attendance deductions.  (Allowance is
            // NOT in gross; it is added back at the receivable stage, same as the engine.)
            $grossComp     = $basic + $holiday + $ot + $nd;
            $attDed        = $absut + $ob + $op;
            $grossVal      = (float) $p->gross_pay;
            $grossResidual = round($grossComp - $attDed - $grossVal, 2); // un-itemised custom deductions
            $gov           = $sss + $phil + $pag + $tax;
            $netVal        = (float) $p->net_pay;
            $netResidual   = round($grossVal - $gov - $netVal, 2);       // clamp / rounding
            $loans         = $sssLoan + $pagLoan + $compLoan + $cashAdv + $charges;
            $recVal        = (float) $p->pay_rec;
            $tailResidual  = round($recVal - ($netVal - $loans + $allow), 2); // pay adjustments / other

            // Compact allowance derivation, sourced from the computation log (payrolls
            // stores net only). Blank when no matching log row — the slip then shows
            // the plain Allowance line, no derivation, no error.
            $allowNote = '';
            $alw = ($allowanceByKey ?? collect())->get($p->employee_id.'|'.\Carbon\Carbon::parse($p->pay_date)->format('Y-m-d'));
            if ($alw && !empty($alw['allowance'])) {
                $a = $alw['allowance'];
                $parts = [];
                if (isset($a['days_paid'], $a['daily_rate'])) {
                    $parts[] = rtrim(rtrim(number_format((float) $a['days_paid'], 2), '0'), '.').'d × '.$peso($a['daily_rate']);
                }
                if ((float) ($a['late_ut_deduction'] ?? 0) > 0.005)    { $parts[] = '- '.$peso($a['late_ut_deduction']).' late/UT'; }
                if ((float) ($a['over_break_deduction'] ?? 0) > 0.005) { $parts[] = '- '.$peso($a['over_break_deduction']).' o.break'; }
                $allowNote = implode(' ', $parts);
            }

            $ps  = '<table class="calc"><tbody>';
            $ps .= $psSec('Earnings');
            $ps .= $psRow('Basic Pay (Semi-monthly)', $basic, 'add');
            if ($holiday > 0.005) $ps .= $psRow('Holiday Pay', $holiday, 'add');
            if ($ot > 0.005)      $ps .= $psRow('Overtime Pay', $ot, 'add');
            if ($nd > 0.005)      $ps .= $psRow('Night Differential', $nd, 'add');

            if ($absut > 0.005 || $ob > 0.005 || $op > 0.005 || abs($grossResidual) > 0.005) {
                $ps .= $psSec('Less: Attendance Deductions');
                if ($absut > 0.005) $ps .= $psRow('Absences / Tardy / Undertime', $absut, 'sub');
                if ($ob > 0.005)    $ps .= $psRow('Over-break', $ob, 'sub');
                if ($op > 0.005)    $ps .= $psRow('Out-pass', $op, 'sub');
                if ($grossResidual > 0.005)      $ps .= $psRow('Other Deductions', $grossResidual, 'sub');
                elseif ($grossResidual < -0.005) $ps .= $psRow('Other Earnings', abs($grossResidual), 'add');
            }
            $ps .= $psMile('Gross Pay', $grossVal);

            $ps .= $psSec('Less: Government Contributions');
            if ($gov > 0.005) {
                if ($sss > 0.005)  $ps .= $psRow('SSS Contribution', $sss, 'sub');
                if ($phil > 0.005) $ps .= $psRow('PhilHealth', $phil, 'sub');
                if ($pag > 0.005)  $ps .= $psRow('Pag-IBIG', $pag, 'sub');
                if ($tax > 0.005)  $ps .= $psRow('Withholding Tax', $tax, 'sub');
            } else {
                $ps .= '<tr><td class="lbl" colspan="3" style="color:#9ca3af;font-style:italic;">No statutory deductions this cut-off (deducted end-of-month).</td></tr>';
            }
            if (abs($netResidual) > 0.005) $ps .= $psRow('Adjustment', abs($netResidual), $netResidual < 0 ? 'add' : 'sub');
            $ps .= $psMile('Net Pay', $netVal);

            if ($loans > 0.005 || $allow > 0.005 || abs($tailResidual) > 0.005) {
                $ps .= $psSec('Less: Loans / Plus: Allowance');
                if ($sssLoan > 0.005)  $ps .= $psRow('SSS Loan', $sssLoan, 'sub');
                if ($pagLoan > 0.005)  $ps .= $psRow('Pag-IBIG Loan', $pagLoan, 'sub');
                if ($compLoan > 0.005) $ps .= $psRow('Company Loan', $compLoan, 'sub');
                if ($cashAdv > 0.005)  $ps .= $psRow('Cash Advance', $cashAdv, 'sub');
                if ($charges > 0.005)  $ps .= $psRow('Charges / Penalty', $charges, 'sub');
                if ($allow > 0.005)    $ps .= $psRow('Allowance', $allow, 'add', $allowNote);
                if (abs($tailResidual) > 0.005) $ps .= $psRow('Adjustments / Other', abs($tailResidual), $tailResidual < 0 ? 'sub' : 'add');
            }
            $ps .= $psMile('Net Pay Receivable', $recVal, true);
            $ps .= '</tbody></table>';
        @endphp

        <div class="payslip">
            <div class="ps-head">
                <div class="ps-company">
                    <img src="{{ asset('img/kwatogslogo.jpg') }}" alt="logo">
                    <div>
                        <div class="name">{{ $company->comp_name ?? config('app.name', 'Company') }}</div>
                        <div class="sub">Payslip</div>
                    </div>
                </div>
                <div class="ps-title">
                    <div class="t">PAYSLIP</div>
                    <div class="p">Pay Date: {{ \Carbon\Carbon::parse($p->pay_date)->format('M d, Y') }}</div>
                    <div class="p">
                        Cut-off: {{ \Carbon\Carbon::parse($p->payroll_start_date)->format('M d') }}
                        &ndash; {{ \Carbon\Carbon::parse($p->payroll_end_date)->format('M d, Y') }}
                    </div>
                </div>
            </div>

            <div class="ps-emp">
                <div><span class="lbl">Employee:</span> <b>{{ $fullName }}</b></div>
                <div><span class="lbl">Employee ID:</span> <b>{{ $p->employee_id }}</b></div>
                <div><span class="lbl">Department:</span> <b>{{ optional($detail)->department->dep_name ?? '—' }}</b></div>
                <div><span class="lbl">Position:</span> <b>{{ optional($detail)->position->pos_desc ?? '—' }}</b></div>
            </div>

            {!! $ps !!}

            <div class="sign">
                <div class="slot">Prepared by</div>
                <div class="slot">Received by: {{ $fullName }}</div>
            </div>
        </div>
    @empty
        <div class="payslip">
            <p style="text-align:center;color:#6b7280;margin:20px 0;">
                No payroll records found for {{ \Carbon\Carbon::parse($payDate)->format('M d, Y') }}.
            </p>
        </div>
    @endforelse

</body>
</html>
