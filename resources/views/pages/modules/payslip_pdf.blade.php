{{--
    Table-based payslip layout used ONLY for the emailed PDF attachment
    (rendered through TCPDF::writeHTML(), which does not support CSS
    grid/flexbox — unlike the on-screen payslip.blade.php, which uses both).
    Keep this in sync with payslip.blade.php's figures if that ever changes.
--}}
@php
    $emp     = $p->employee;
    $detail  = optional($emp)->empDetail;
    $company = optional($detail)->company;

    $fullName = $emp
        ? strtoupper(trim(($emp->lname ?? '') . ', ' . ($emp->fname ?? '')))
        : 'UNKNOWN';

    // Daily-paid employees (classification ≠ RGLR): basicPay stores only the daily
    // rate; the real regular pay (days × rate) is folded into gross_pay. Rebuild it
    // from the day count passed in by PayslipPdfService so Total Earnings foots and
    // the slip shows how many days were reported.
    $class      = optional($detail)->empClassification;
    $isDaily    = $class && $class !== 'RGLR';
    $daysWorked = $daysWorked ?? null;
    $dailyRate  = $dailyRate ?? (float) $p->basicPay;

    if ($isDaily && $daysWorked !== null) {
        $basic      = $daysWorked * $dailyRate;
        $basicLabel = $daysWorked.' day'.($daysWorked == 1 ? '' : 's').' × '.number_format($dailyRate, 2);
    } else {
        $basic      = (float) $p->basicPay;
        $basicLabel = 'Semi-monthly';
    }
    $holiday = (float) $p->holiday_pay;
    $ot      = (float) $p->overtime_pay;
    $nd      = (float) $p->night_diff_pay;
    $allow   = (float) $p->allowances;

    // HR one-time pay adjustments (frozen in the computation log). Additions show
    // under Earnings, deductions under Deductions; both fold into the totals so the
    // slip foots to NET PAY RECEIVABLE. Keep in sync with payslip.blade.php.
    $adjustments = $adjustments ?? [];
    $adjAdd      = collect($adjustments)->where('kind', 'addition');
    $adjDed      = collect($adjustments)->where('kind', 'deduction');

    $earnings = $basic + $holiday + $ot + $nd + $allow + $adjAdd->sum('amount');

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

    $listedDed = $absut + $ob + $op + $sss + $phil + $pag + $tax + $sssLoan + $pagLoan + $compLoan + $cashAdv + $charges + $adjDed->sum('amount');
    $takehome  = (float) $p->pay_rec;
    $residual  = round($earnings - $listedDed - $takehome, 2);
    $totalDed  = $listedDed + max($residual, 0);

    $peso = fn($n) => number_format((float) $n, 2);
@endphp
<table width="100%" cellpadding="4" cellspacing="0" style="font-family: helvetica; font-size: 10pt; color: #1f2937;">
    <tr>
        <td width="60%" style="font-size: 13pt; font-weight: bold;">
            {{ optional($detail)->department->dep_name ?? ($company->comp_name ?? config('app.name', 'Company')) }}
            <br><span style="font-size: 9pt; color: #6b7280; font-weight: normal;">Payslip</span>
        </td>
        <td width="40%" align="right" style="font-size: 11pt; font-weight: bold; color: #008080;">
            PAYSLIP
            <br><span style="font-size: 9pt; color: #6b7280; font-weight: normal;">Pay Date: {{ \Carbon\Carbon::parse($p->pay_date)->format('M d, Y') }}</span>
            <br><span style="font-size: 9pt; color: #6b7280; font-weight: normal;">Cut-off: {{ \Carbon\Carbon::parse($p->payroll_start_date)->format('M d') }} &ndash; {{ \Carbon\Carbon::parse($p->payroll_end_date)->format('M d, Y') }}</span>
        </td>
    </tr>
</table>
<hr style="border: 1px solid #008080;">
<table width="100%" cellpadding="3" cellspacing="0" style="font-size: 10pt;">
    <tr>
        <td width="50%"><span style="color:#6b7280;">Employee:</span> <b>{{ $fullName }}</b></td>
        <td width="50%"><span style="color:#6b7280;">Employee ID:</span> <b>{{ $p->employee_id }}</b></td>
    </tr>
    <tr>
        <td width="50%"><span style="color:#6b7280;">Department:</span> <b>{{ optional($detail)->department->dep_name ?? '-' }}</b></td>
        <td width="50%"><span style="color:#6b7280;">Position:</span> <b>{{ optional($detail)->position->pos_desc ?? '-' }}</b></td>
    </tr>
</table>
<br>
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td width="50%" valign="top">
            <table width="100%" cellpadding="4" cellspacing="0" style="font-size: 9.5pt;">
                <tr><td colspan="2" style="border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 8.5pt; text-transform: uppercase;">EARNINGS</td></tr>
                <tr><td>Basic Pay ({{ $basicLabel }})</td><td align="right">{{ $peso($basic) }}</td></tr>
                <tr><td>Holiday Pay</td><td align="right">{{ $peso($holiday) }}</td></tr>
                <tr><td>Overtime Pay</td><td align="right">{{ $peso($ot) }}</td></tr>
                <tr><td>Night Differential</td><td align="right">{{ $peso($nd) }}</td></tr>
                <tr><td>Allowance</td><td align="right">{{ $peso($allow) }}</td></tr>
                @foreach ($adjAdd as $a)<tr><td>Adjustment: {{ $a['label'] }}</td><td align="right">{{ $peso($a['amount']) }}</td></tr>@endforeach
                <tr><td style="border-top: 1px solid #e5e7eb; font-weight: bold;">Total Earnings</td><td align="right" style="border-top: 1px solid #e5e7eb; font-weight: bold;">{{ $peso($earnings) }}</td></tr>
            </table>
        </td>
        <td width="50%" valign="top">
            <table width="100%" cellpadding="4" cellspacing="0" style="font-size: 9.5pt;">
                <tr><td colspan="2" style="border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 8.5pt; text-transform: uppercase;">DEDUCTIONS</td></tr>
                <tr><td>Absences / Tardy / Undertime</td><td align="right">{{ $peso($absut) }}</td></tr>
                @if ($ob > 0)<tr><td>Over-break</td><td align="right">{{ $peso($ob) }}</td></tr>@endif
                @if ($op > 0)<tr><td>Out-pass</td><td align="right">{{ $peso($op) }}</td></tr>@endif
                <tr><td>SSS Contribution</td><td align="right">{{ $peso($sss) }}</td></tr>
                <tr><td>PhilHealth</td><td align="right">{{ $peso($phil) }}</td></tr>
                <tr><td>Pag-IBIG</td><td align="right">{{ $peso($pag) }}</td></tr>
                <tr><td>Withholding Tax</td><td align="right">{{ $peso($tax) }}</td></tr>
                @if ($sssLoan > 0)<tr><td>SSS Loan</td><td align="right">{{ $peso($sssLoan) }}</td></tr>@endif
                @if ($pagLoan > 0)<tr><td>Pag-IBIG Loan</td><td align="right">{{ $peso($pagLoan) }}</td></tr>@endif
                @if ($compLoan > 0)<tr><td>Company Loan</td><td align="right">{{ $peso($compLoan) }}</td></tr>@endif
                @if ($cashAdv > 0)<tr><td>Cash Advance</td><td align="right">{{ $peso($cashAdv) }}</td></tr>@endif
                @if ($charges > 0)<tr><td>Charges / Penalty</td><td align="right">{{ $peso($charges) }}</td></tr>@endif
                @foreach ($adjDed as $a)<tr><td>Adjustment: {{ $a['label'] }}</td><td align="right">{{ $peso($a['amount']) }}</td></tr>@endforeach
                @if ($residual > 0.005)<tr><td>Other Deductions</td><td align="right">{{ $peso($residual) }}</td></tr>@endif
                <tr><td style="border-top: 1px solid #e5e7eb; font-weight: bold;">Total Deductions</td><td align="right" style="border-top: 1px solid #e5e7eb; font-weight: bold;">{{ $peso($totalDed) }}</td></tr>
            </table>
        </td>
    </tr>
</table>
<br>
<table width="100%" cellpadding="3" cellspacing="0" style="font-size: 10pt;">
    <tr><td>Gross Pay</td><td align="right">{{ $peso($p->gross_pay) }}</td></tr>
    <tr><td>Net Pay (after govt. premiums &amp; tax)</td><td align="right">{{ $peso($p->net_pay) }}</td></tr>
    <tr><td style="border-top: 1px dashed #e5e7eb; font-size: 12pt; font-weight: bold; color: #008080;">NET PAY RECEIVABLE</td><td align="right" style="border-top: 1px dashed #e5e7eb; font-size: 12pt; font-weight: bold; color: #008080;">PHP {{ $peso($takehome) }}</td></tr>
</table>
<br><br>
<table width="100%" cellpadding="3" cellspacing="0" style="font-size: 8.5pt; color: #6b7280;">
    <tr>
        <td width="50%" style="border-top: 1px solid #9ca3af;" align="center">Prepared by</td>
        <td width="50%" style="border-top: 1px solid #9ca3af;" align="center">Received by: {{ $fullName }}</td>
    </tr>
</table>
