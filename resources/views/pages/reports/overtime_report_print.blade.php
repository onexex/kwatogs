<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Overtime Report</title>
    <style>
        :root { --teal:#008080; --ink:#1f2937; --muted:#6b7280; --line:#e5e7eb; }
        * { box-sizing: border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; color:var(--ink); margin:0; padding:22px; font-size:12px; }
        .toolbar { display:flex; gap:8px; justify-content:flex-end; margin-bottom:14px; }
        .btn { border:0; border-radius:6px; padding:7px 14px; font-weight:700; cursor:pointer; font-size:12px; }
        .btn-print { background:var(--teal); color:#fff; } .btn-close { background:#e5e7eb; color:#374151; }
        h2 { margin:0 0 2px; color:var(--teal); font-size:18px; text-align:center; }
        .meta { text-align:center; color:var(--muted); font-size:11px; margin-bottom:12px; }
        table { width:100%; border-collapse:collapse; }
        th, td { border:1px solid var(--line); padding:5px 7px; text-align:left; }
        th { background:#f8fafc; text-transform:uppercase; font-size:10px; letter-spacing:.4px; color:#475569; }
        td.num, th.num { text-align:right; font-variant-numeric:tabular-nums; }
        tfoot td { font-weight:700; background:#f8fafc; }
        @media print { .toolbar { display:none; } body { padding:0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-print" onclick="window.print()">🖨 Print</button>
        <button class="btn btn-close" onclick="window.close()">Close</button>
    </div>

    <div style="text-align:center;margin-bottom:4px;"><img src="{{ asset('img/kwatogslogo.jpg') }}" alt="logo" style="height:48px;"></div>
    <h2>Overtime Report</h2>
    <div class="meta">
        @php
            $f = $filters ?? [];
            $range = trim(($f['date_from'] ?? '') . ' to ' . ($f['date_to'] ?? ''), ' to');
        @endphp
        {{ $range ? 'Period: '.$range.'  |  ' : '' }}
        Status: {{ ($f['status'] ?? 'all') === 'all' ? 'All' : $f['status'] }}
        &nbsp;|&nbsp; Records: {{ $rows->count() }}
        &nbsp;|&nbsp; Generated: {{ now()->format('M d, Y h:i A') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Employee</th><th>ID</th><th>Dept</th><th>Date From</th><th>Date To</th>
                <th class="num">Hours</th><th class="num">Pay</th><th>Purpose</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td>{{ $r->employee_name }}</td>
                    <td>{{ $r->employee_id }}</td>
                    <td>{{ $r->department_name ?? '—' }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->date_from)->format('M d, Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->date_to)->format('M d, Y') }}</td>
                    <td class="num">{{ number_format($r->total_hrs, 2) }}</td>
                    <td class="num">{{ number_format($r->total_pay, 2) }}</td>
                    <td>{{ $r->purpose }}</td>
                    <td>{{ $r->status }}</td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center;color:#6b7280;padding:16px;">No records found.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;">TOTAL</td>
                <td class="num">{{ number_format($totalHrs, 2) }}</td>
                <td class="num">{{ number_format($totalPay, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
