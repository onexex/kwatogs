<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Leave Report</title>
    <style>
        @page { size: landscape; margin: 12mm; }
        * { box-sizing:border-box; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        body { font-family:'Segoe UI', Arial, sans-serif; color:#1e293b; margin:0; font-size:11px; }
        .toolbar { display:flex; gap:8px; justify-content:flex-end; margin-bottom:14px; }
        .btn { border:0; border-radius:6px; padding:7px 14px; font-weight:700; cursor:pointer; font-size:12px; }
        .btn-print { background:#008080; color:#fff; } .btn-close { background:#e5e7eb; color:#374151; }
        @media print { .toolbar { display:none; } body { padding:0; } }

        .head { display:flex; align-items:center; gap:14px; border-bottom:3px solid #008080; padding-bottom:12px; margin-bottom:6px; }
        .head img { height:54px; width:auto; }
        .head .org { font-size:18px; font-weight:800; color:#006666; letter-spacing:.3px; }
        .head .sub { font-size:12px; color:#475569; margin-top:1px; }
        .meta { font-size:11px; color:#64748b; margin:10px 0 14px; }
        .meta b { color:#334155; }

        table { width:100%; border-collapse:collapse; }
        thead th { background:#008080 !important; color:#fff !important; font-size:10px; text-transform:uppercase;
            letter-spacing:.3px; padding:7px 6px; text-align:center; border:none; }
        thead th:nth-child(1) { text-align:left; }
        tbody td { padding:5px 6px; border-bottom:1px solid #e2e8f0; font-size:10.5px; text-align:center; vertical-align:middle; }
        tbody td:nth-child(1) { text-align:left; }
        tbody tr:nth-child(even) td { background:#f8fafc; }
        tfoot td { background:#e0f2f1; font-weight:700; color:#006666; padding:7px 6px; border-top:2px solid #008080; font-size:10.5px; }
        td.num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }

        .note { margin-top:14px; font-size:9.5px; color:#94a3b8; font-style:italic; }
        .sign { margin-top:42px; display:flex; justify-content:space-between; font-size:11px; }
        .sign > div { width:30%; text-align:center; }
        .sign .cap { margin-bottom:28px; }
        .sign .ln { border-top:1px solid #475569; }
        .endmark { margin-top:18px; text-align:center; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-print" onclick="window.print()">🖨 Print</button>
        <button class="btn btn-close" onclick="window.close()">Close</button>
    </div>

    <div class="head">
        <img src="{{ asset('img/kwatogslogo.jpg') }}" onerror="this.style.display='none'" alt="">
        <div>
            <div class="org">DEMO</div>
            <div class="sub">Leave Report</div>
        </div>
    </div>

    <div class="meta">
        @php
            $f = $filters ?? [];
            $range = trim(($f['date_from'] ?? '') . ' to ' . ($f['date_to'] ?? ''), ' to');
        @endphp
        <b>Date Range:</b> {{ $range ?: 'All' }}
        &nbsp;&bull;&nbsp; <b>Status:</b> {{ ($f['status'] ?? 'all') === 'all' ? 'All' : $f['status'] }}
        &nbsp;&bull;&nbsp; <b>Records:</b> {{ $rows->count() }}
        &nbsp;&bull;&nbsp; <b>Generated:</b> {{ now()->format('M d, Y h:i A') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Employee</th><th>ID</th><th>Dept</th><th>Leave Type</th><th>Start</th><th>End</th>
                <th class="num">Days</th><th>Kind</th><th>Reason</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td>{{ $r->employee_name }}</td>
                    <td>{{ $r->employee_id }}</td>
                    <td>{{ $r->department_name ?? '—' }}</td>
                    <td>{{ $r->leave_type ?? '—' }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->start_date)->format('M d, Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->end_date)->format('M d, Y') }}</td>
                    <td class="num">{{ number_format($r->total_hrs / 8, 2) }}</td>
                    <td>{{ (int) $r->leave_kind === 0 ? 'Paid' : 'Unpaid' }}</td>
                    <td>{{ $r->reason }}</td>
                    <td>{{ $r->status }}</td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align:center;color:#6b7280;padding:16px;">No records found.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" style="text-align:right;text-transform:uppercase;letter-spacing:.4px;">Total Days</td>
                <td class="num">{{ number_format($totalDays, 2) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

    <div class="note">
        Leave days are computed from the start and end dates. Leave kind indicates whether the leave is paid or unpaid.
        Status reflects the current approval stage of each leave application.
    </div>

    <div class="sign">
        <div><div class="cap">Prepared by</div><div class="ln"></div></div>
        <div><div class="cap">Checked & Verified by</div><div class="ln"></div></div>
        <div><div class="cap">Approved by</div><div class="ln"></div></div>
    </div>

    <div class="endmark">*** End of Report ***</div>

    <script>
        window.onload = function () {
            setTimeout(function () { window.focus(); window.print(); window.close(); }, 400);
        };
    <\/script>
</body>
</html>