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
            $earnings = $basic + $holiday + $ot + $nd + $allow;

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
            $charges = (float) $p->penalty_amount;

            $listedDed = $absut + $ob + $op + $sss + $phil + $pag + $tax + $sssLoan + $pagLoan + $compLoan + $charges;
            $takehome  = (float) $p->pay_rec;
            // Residual (manual/custom deductions or rounding) so the slip always foots to pay receivable
            $residual  = round($earnings - $listedDed - $takehome, 2);
            $totalDed  = $listedDed + max($residual, 0);

            $peso = fn($n) => number_format((float) $n, 2);
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

            <div class="cols">
                <div class="col">
                    <h4>Earnings</h4>
                    <div class="ln"><span>Basic Pay (Semi-monthly)</span><span class="v">{{ $peso($basic) }}</span></div>
                    <div class="ln"><span>Holiday Pay</span><span class="v">{{ $peso($holiday) }}</span></div>
                    <div class="ln"><span>Overtime Pay</span><span class="v">{{ $peso($ot) }}</span></div>
                    <div class="ln"><span>Night Differential</span><span class="v">{{ $peso($nd) }}</span></div>
                    <div class="ln"><span>Allowance</span><span class="v">{{ $peso($allow) }}</span></div>
                    <div class="sub-tot"><span>Total Earnings</span><span>{{ $peso($earnings) }}</span></div>
                </div>

                <div class="col">
                    <h4>Deductions</h4>
                    <div class="ln"><span>Absences / Tardy / Undertime</span><span class="v">{{ $peso($absut) }}</span></div>
                    @if ($ob > 0)<div class="ln"><span>Over-break</span><span class="v">{{ $peso($ob) }}</span></div>@endif
                    @if ($op > 0)<div class="ln"><span>Out-pass</span><span class="v">{{ $peso($op) }}</span></div>@endif
                    <div class="ln"><span>SSS Contribution</span><span class="v">{{ $peso($sss) }}</span></div>
                    <div class="ln"><span>PhilHealth</span><span class="v">{{ $peso($phil) }}</span></div>
                    <div class="ln"><span>Pag-IBIG</span><span class="v">{{ $peso($pag) }}</span></div>
                    <div class="ln"><span>Withholding Tax</span><span class="v">{{ $peso($tax) }}</span></div>
                    @if ($sssLoan > 0)<div class="ln"><span>SSS Loan</span><span class="v">{{ $peso($sssLoan) }}</span></div>@endif
                    @if ($pagLoan > 0)<div class="ln"><span>Pag-IBIG Loan</span><span class="v">{{ $peso($pagLoan) }}</span></div>@endif
                    @if ($compLoan > 0)<div class="ln"><span>Company Loan / Cash Adv.</span><span class="v">{{ $peso($compLoan) }}</span></div>@endif
                    @if ($charges > 0)<div class="ln"><span>Charges / Penalty</span><span class="v">{{ $peso($charges) }}</span></div>@endif
                    @if ($residual > 0.005)<div class="ln"><span>Other Deductions</span><span class="v">{{ $peso($residual) }}</span></div>@endif
                    <div class="sub-tot"><span>Total Deductions</span><span>{{ $peso($totalDed) }}</span></div>
                </div>
            </div>

            <div class="settle">
                <div class="row"><span>Gross Pay</span><span>{{ $peso($p->gross_pay) }}</span></div>
                <div class="row"><span>Net Pay (after govt. premiums &amp; tax)</span><span>{{ $peso($p->net_pay) }}</span></div>
                <div class="row net"><span>NET PAY RECEIVABLE</span><span>₱ {{ $peso($takehome) }}</span></div>
            </div>

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
