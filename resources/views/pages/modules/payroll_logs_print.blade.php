<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payroll Logs</title>
    <style>
        :root {
            --teal:#008080; --teal-dark:#006666; --teal-light:#e0f2f1;
            --ink:#1f2937; --slate:#334155; --muted:#64748b; --faint:#94a3b8;
            --line:#e2e8f0; --bg:#f1f5f9; --pos:#0f766e; --neg:#b91c1c;
        }
        * { box-sizing: border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; color:var(--ink); margin:0; padding:18px; font-size:11px; }
        /* Floated top-right so it doesn't reserve an empty row above the header. */
        .toolbar { position:absolute; top:14px; right:18px; display:flex; gap:8px; }
        .btn { border:0; border-radius:6px; padding:7px 14px; font-weight:700; cursor:pointer; font-size:12px; }
        .btn-print { background:var(--teal); color:#fff; } .btn-close { background:#e5e7eb; color:#374151; }

        /* ---- Report header ---- */
        .hd { display:flex; align-items:center; gap:12px; border-bottom:2px solid var(--teal); padding-bottom:10px; margin-bottom:12px; }
        .hd img { height:44px; }
        .hd .ttl { font-weight:800; color:var(--teal); font-size:16px; letter-spacing:.3px; }
        .hd .sub { font-size:10px; color:var(--muted); margin-top:1px; }
        .hd .gen { margin-left:auto; text-align:right; font-size:9px; color:var(--faint); }

        /* ---- Per-employee card ---- */
        /* NOTE: do NOT page-break-inside:avoid here — a card can be taller than a
           page (computation grid + daily table), which would force the whole card
           to the next page and leave the first page blank. Let it flow/break; the
           header and individual rows below keep their own break rules. */
        .emp-card { border:1px solid var(--line); border-radius:10px; margin-bottom:14px; }
        .emp-head { background:linear-gradient(90deg,var(--teal),var(--teal-dark)); color:#fff; padding:8px 12px; display:flex; align-items:center; gap:10px; page-break-inside:avoid; page-break-after:avoid; }
        .emp-head .nm { font-weight:800; font-size:13px; text-transform:uppercase; letter-spacing:.3px; }
        .emp-head .tag { background:rgba(255,255,255,.18); border-radius:20px; padding:1px 9px; font-size:9px; font-weight:700; }
        .emp-head .right { margin-left:auto; text-align:right; font-size:9px; line-height:1.4; opacity:.95; }

        .emp-body { padding:10px 12px; }

        /* attendance chips */
        .att { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px; }
        .chip { background:var(--teal-light); border:1px solid #b2dfdb; border-radius:6px; padding:3px 9px; font-size:9.5px; }
        .chip b { color:var(--teal-dark); font-size:11px; margin-right:3px; }
        .chip.warn { background:#fef2f2; border-color:#fecaca; }
        .chip.warn b { color:var(--neg); }

        /* ---- Detailed computation log (existing 3-col grid) ---- */
        details.detail { margin-top:10px; border-top:1px dashed var(--line); padding-top:8px; }
        details.detail > summary { cursor:pointer; font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.4px; color:var(--muted); margin-bottom:7px; list-style:none; }
        details.detail > summary::-webkit-details-marker { display:none; }
        details.detail > summary::before { content:'▸ '; }
        details.detail[open] > summary::before { content:'▾ '; }
        .cols { column-count:3; column-gap:10px; }
        .grp { break-inside:avoid; border:1px solid var(--line); border-radius:4px; margin-bottom:6px; display:inline-block; width:100%; }
        .grp-h { background:#f1f5f9; font-weight:700; font-size:9px; text-transform:uppercase; letter-spacing:.3px; color:#475569; padding:3px 7px; border-bottom:1px solid var(--line); }
        table.kv { width:100%; border-collapse:collapse; }
        table.kv td { padding:2px 7px; font-size:10px; border-bottom:1px solid #f3f4f6; vertical-align:top; }
        table.kv td.k { color:#475569; text-transform:capitalize; padding-right:6px; }
        table.kv td.v { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }

        /* ---- Daily attendance list (from payroll_details) ---- */
        .days { margin-top:10px; border-top:1px dashed var(--line); padding-top:8px; }
        .days-h { font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.4px; color:var(--muted); margin-bottom:6px; }
        table.days-t { width:100%; border-collapse:collapse; }
        table.days-t th { background:#f1f5f9; color:#475569; font-size:8.5px; text-transform:uppercase; letter-spacing:.3px; text-align:left; padding:3px 8px; border-bottom:1px solid var(--line); }
        table.days-t td { padding:3px 8px; font-size:10px; border-bottom:1px solid #f3f4f6; white-space:nowrap; }
        table.days-t td.num { text-align:right; font-variant-numeric:tabular-nums; }
        .badge { display:inline-block; border-radius:20px; padding:1px 8px; font-size:9px; font-weight:700; }
        .b-present { background:#ecfdf5; color:#047857; }
        .b-absent  { background:#fef2f2; color:#b91c1c; }
        .b-leave   { background:#eff6ff; color:#1d4ed8; }
        .b-ob      { background:#fff7ed; color:#c2410c; }
        .b-holiday { background:#f5f3ff; color:#6d28d9; }
        .b-ot      { background:#fefce8; color:#a16207; }

        @media print {
            body { padding:0; font-size:10px; }
            .toolbar { display:none; }
            details.detail[open] > summary::before { content:'▾ '; }
            details.detail:not([open]) { display:none; } /* hide collapsed detail in print */
            table.days-t tr, table.kv tr { page-break-inside:avoid; }
            .grp { page-break-inside:avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-print" onclick="window.print()">Print</button>
        <button class="btn btn-close" onclick="window.close()">Close</button>
    </div>

    @php
        // Keys in the computation breakdown that represent money (comma + 2 decimals).
        $moneyKeys = [
            'basic_monthly','daily_rate','hourly_rate','daily_allowance','allowance_hourly',
            'x_hourly_rate','x_daily','deduction','amount','total_pay','pay','gross','net',
            'late_ut_deduction','total_deductions','basic_pay','gross_pay','holiday_pay',
            'sss','philhealth','pagibig','tax','gov_dues','taxable',
            'company','charges','cash_adv','other','sss_loan','pagibig_loan',
            'gross_taxed','net_after_tax','total','net_pay','pay_receivable',
        ];
        $isMoneyKey = fn ($k) => in_array(strtolower((string) $k), $moneyKeys, true);
        $peso = fn ($n) => number_format((float) ($n ?? 0), 2);
        // safe nested getter
        $g = function ($arr, $path, $default = 0) {
            foreach (explode('.', $path) as $seg) {
                if (is_array($arr) && array_key_exists($seg, $arr)) { $arr = $arr[$seg]; }
                else { return $default; }
            }
            return $arr;
        };

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

        // Distinct pay dates in this print (used in the header line).
        $runDates = $logs->map(fn ($l) => \Carbon\Carbon::parse($l->pay_date)->format('Y-m-d'))->unique();
    @endphp

    <div class="hd">
        <img src="{{ asset('img/kwatogslogo.jpg') }}" alt="logo">
        <div>
            <div class="ttl">Payroll Computation Logs</div>
            <div class="sub">
                {{ $logs->count() }} employee{{ $logs->count() === 1 ? '' : 's' }}
                @if ($runDates->count() === 1)
                    &middot; Pay date {{ \Carbon\Carbon::parse($runDates->first())->format('M d, Y') }}
                @elseif ($runDates->count() > 1)
                    &middot; {{ $runDates->count() }} pay dates
                @endif
            </div>
        </div>
        <div class="gen">Generated {{ now()->format('M d, Y g:i A') }}</div>
    </div>

    @forelse ($logs as $log)
        @php
            $bd = $log->breakdown ?? [];

            // ---- pull summary figures from breakdown (with safe fallbacks) ----
            $sched   = $g($bd, 'attendance.scheduled_days', '-');
            $present = $g($bd, 'attendance.days_present', '-');
            $absent  = $g($bd, 'attendance.absent_days', 0);
            $restOt  = $g($bd, 'attendance.rest_day_ot_days', 0);
            $tardyMin = $g($bd, 'tardiness.total_minutes', 0);
            $utMin    = $g($bd, 'undertime.total_minutes', 0);
        @endphp
        <div class="emp-card">
            <div class="emp-head">
                <span class="nm">{{ $log->employee_name ?? $log->employee_id }}</span>
                <span class="tag">{{ $log->employee_id }}</span>
                @if (!empty($log->classification))<span class="tag">{{ $log->classification }}</span>@endif
                <div class="right">
                    Pay date {{ \Carbon\Carbon::parse($log->pay_date)->format('M d, Y') }}<br>
                    Cut-off {{ optional($log->payroll_start_date)->format('M d') }}&ndash;{{ optional($log->payroll_end_date)->format('M d, Y') }}
                    @if (!empty($log->department_name)) &middot; {{ $log->department_name }}@endif
                </div>
            </div>

            <div class="emp-body">
                {{-- Attendance snapshot --}}
                <div class="att">
                    <span class="chip"><b>{{ $present }}</b>Present</span>
                    <span class="chip"><b>{{ $sched }}</b>Scheduled</span>
                    @if ($absent > 0)<span class="chip warn"><b>{{ $absent }}</b>Absent</span>@endif
                    @if ($restOt > 0)<span class="chip"><b>{{ $restOt }}</b>Rest-day OT</span>@endif
                    @if ($tardyMin > 0)<span class="chip warn"><b>{{ $tardyMin }}</b>Tardy min</span>@endif
                    @if ($utMin > 0)<span class="chip warn"><b>{{ $utMin }}</b>UT min</span>@endif
                </div>

                {{-- Full computation detail (open by default so it prints) --}}
                @if (!empty($bd) && is_array($bd))
                    <details class="detail" open>
                        <summary>Full computation log</summary>
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
                    </details>
                @endif

                {{-- Daily attendance list (from payroll_details) --}}
                @php
                    $dayKey  = $log->employee_id.'|'.\Carbon\Carbon::parse($log->pay_date)->format('Y-m-d');
                    $dayRows = ($dayDetails ?? collect())->get($dayKey, collect());
                    // Rest-day OT dates for this employee come from the breakdown.
                    $restOtDates = collect($g($bd, 'attendance.rest_day_ot_dates', []))
                        ->map(fn ($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))->all();
                @endphp
                @if ($dayRows->isNotEmpty())
                    <div class="days">
                        <div class="days-h">Daily Attendance</div>
                        <table class="days-t">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Status</th>
                                    <th style="text-align:right;">Total Hrs</th>
                                    <th style="text-align:right;">Late (min)</th>
                                    <th style="text-align:right;">UT (min)</th>
                                    <th style="text-align:right;">Late Ded.</th>
                                    <th style="text-align:right;">UT Ded.</th>
                                    <th style="text-align:right;">ND Hrs</th>
                                    <th style="text-align:right;">ND Pay</th>
                                    <th style="text-align:right;">Holiday Pay</th>
                                    <th style="text-align:right;">Penalty</th>
                                    <th style="text-align:right;">Adjustment</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dayRows as $d)
                                    @php
                                        $dDate = \Carbon\Carbon::parse($d->date);
                                        $type  = strtolower($d->logsType ?? '');
                                        $badgeClass = match ($type) {
                                            'present' => 'b-present',
                                            'absent'  => 'b-absent',
                                            'leave'   => 'b-leave',
                                            'ob'      => 'b-ob',
                                            default   => 'b-present',
                                        };
                                        $isHoliday = (float) ($d->holiday_pay ?? 0) > 0;
                                        $isRestOt  = in_array($dDate->format('Y-m-d'), $restOtDates, true);
                                        $ot        = ($otByEmpDate ?? collect())->get($log->employee_id.'|'.$dDate->format('Y-m-d'));
                                        $otHrs     = $ot ? rtrim(rtrim(number_format($ot['hrs'], 2), '0'), '.') : null;
                                    @endphp
                                    <tr>
                                        <td>{{ $dDate->format('M d, Y') }}</td>
                                        <td>{{ $dDate->format('D') }}</td>
                                        <td>
                                            <span class="badge {{ $badgeClass }}">{{ $d->logsType ?: '—' }}</span>
                                            @if ($isHoliday)<span class="badge b-holiday">{{ $d->holiday_type ? $d->holiday_type.' Holiday' : 'Holiday' }}</span>@endif
                                            @if ($ot)<span class="badge b-ot">{{ $isRestOt ? 'Rest-day OT' : 'OT' }}{{ $otHrs ? ' '.$otHrs.'h' : '' }}</span>
                                            @elseif ($isRestOt)<span class="badge b-ot">Rest-day OT</span>@endif
                                        </td>
                                        <td class="num">{{ number_format((float) ($d->totalHours ?? 0), 2) }}</td>
                                        <td class="num">{{ (int) ($d->late_minutes ?? 0) }}</td>
                                        <td class="num">{{ (int) ($d->undertime_minutes ?? 0) }}</td>
                                        <td class="num">{{ number_format((float) ($d->late_deduction ?? 0), 2) }}</td>
                                        <td class="num">{{ number_format((float) ($d->undertime_deduction ?? 0), 2) }}</td>
                                        <td class="num">{{ number_format((float) ($d->night_diff_hours ?? 0), 2) }}</td>
                                        <td class="num">{{ number_format((float) ($d->night_diff_pay ?? 0), 2) }}</td>
                                        <td class="num">{{ $isHoliday ? number_format((float) $d->holiday_pay, 2) : '—' }}</td>
                                        <td class="num">{{ number_format((float) ($d->penalty_amount ?? 0), 2) }}</td>
                                        <td class="num">{{ number_format((float) ($d->adjustment_amount ?? 0), 2) }}</td>
                                        <td>{{ $d->remarks }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div style="text-align:center;color:#6b7280;margin:24px 0;">No payroll logs match the selected filters.</div>
    @endforelse
</body>
</html>
