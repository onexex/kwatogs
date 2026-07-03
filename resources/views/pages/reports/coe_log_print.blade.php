<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>COE Issuance Log</title>
    <style>
        * { box-sizing:border-box; }
        body { font-family:'Segoe UI', Arial, sans-serif; color:#1e293b; margin:24px 30px; font-size:11px; }
        .head { display:flex; align-items:center; gap:14px; border-bottom:3px solid #008080; padding-bottom:12px; margin-bottom:6px; }
        .head img { height:50px; }
        .head .org { font-size:17px; font-weight:800; color:#006666; }
        .head .sub { font-size:12px; color:#475569; }
        .meta { font-size:11px; color:#64748b; margin:10px 0 14px; }
        .meta b { color:#334155; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#008080; color:#fff; font-size:9.5px; text-transform:uppercase; padding:6px 8px; text-align:left; }
        thead th.c { text-align:center; }
        tbody td { padding:5px 8px; border-bottom:1px solid #e2e8f0; font-size:10px; }
        tbody td.c { text-align:center; }
        tbody tr:nth-child(even) { background:#f8fafc; }
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
            <div class="sub">Certificate of Employment — Issuance Log</div>
        </div>
    </div>

    <div class="meta"><b>Total:</b> {{ $stats['total'] }} &nbsp;&bull;&nbsp; <b>Approved:</b> {{ $stats['approved'] }} &nbsp;&bull;&nbsp; <b>Pending:</b> {{ $stats['pending'] }} &nbsp;&bull;&nbsp; <b>Rejected:</b> {{ $stats['rejected'] }} &nbsp;&bull;&nbsp; <b>Generated:</b> {{ now()->format('M d, Y g:i A') }}</div>

    <table>
        <thead>
            <tr><th>Cert No.</th><th>Emp ID</th><th>Employee</th><th>Department</th><th>Purpose</th><th class="c">Copies</th><th class="c">Salary</th><th>Signatory</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse ($rows as $r)
            <tr>
                <td>{{ $r->certificate_no ?: '—' }}</td>
                <td>{{ $r->employee_id }}</td>
                <td><strong>{{ strtoupper($r->employee_name) }}</strong></td>
                <td>{{ $r->department_name }}</td>
                <td>{{ $r->purpose }}</td>
                <td class="c">{{ $r->copies }}</td>
                <td class="c">{{ $r->include_salary ? 'Yes' : 'No' }}</td>
                <td>{{ $r->signatory_name ?: '—' }}</td>
                <td>{{ ucfirst($r->status) }}</td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center; padding:18px; color:#94a3b8;">No COE requests found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
