<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Headcount & Turnover — {{ $year }}</title>
    <style>
        * { box-sizing:border-box; }
        body { font-family:'Segoe UI', Arial, sans-serif; color:#1e293b; margin:24px 30px; font-size:11px; }
        .head { display:flex; align-items:center; gap:14px; border-bottom:3px solid #008080; padding-bottom:12px; margin-bottom:6px; }
        .head img { height:50px; }
        .head .org { font-size:17px; font-weight:800; color:#006666; }
        .head .sub { font-size:12px; color:#475569; }
        .cards { display:flex; gap:10px; margin:12px 0 6px; }
        .card { flex:1; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; background:#f8fafc; }
        .card .l { font-size:9px; text-transform:uppercase; color:#64748b; font-weight:700; }
        .card .v { font-size:16px; font-weight:800; color:#006666; }
        h3 { font-size:12px; color:#006666; text-transform:uppercase; letter-spacing:.5px; margin:20px 0 6px; }
        table { width:100%; border-collapse:collapse; margin-bottom:6px; }
        thead th { background:#008080; color:#fff; font-size:9.5px; text-transform:uppercase; padding:6px 8px; text-align:left; }
        thead th.r { text-align:right; }
        tbody td { padding:5px 8px; border-bottom:1px solid #e2e8f0; font-size:10px; }
        tbody td.r { text-align:right; font-variant-numeric:tabular-nums; }
        tbody tr:nth-child(even) { background:#f8fafc; }
        tfoot td { padding:6px 8px; border-top:2px solid #008080; background:#e0f2f1; font-weight:800; color:#006666; font-size:10.5px; }
        tfoot td.r { text-align:right; }
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
            <div class="sub">Headcount / Manpower &amp; Turnover — {{ $year }}</div>
        </div>
    </div>

    <div class="cards">
        <div class="card"><div class="l">Active Headcount</div><div class="v">{{ $stats['active'] }}</div></div>
        <div class="card"><div class="l">New Hires</div><div class="v">{{ $stats['new_hires'] }}</div></div>
        <div class="card"><div class="l">Separations</div><div class="v">{{ $stats['separations'] }}</div></div>
        <div class="card"><div class="l">Turnover Rate</div><div class="v">{{ $stats['turnover'] }}%</div></div>
    </div>

    <h3>Headcount by Department</h3>
    <table>
        <thead><tr><th>Department</th><th class="r">Active Heads</th></tr></thead>
        <tbody>
            @foreach ($byDept as $r)<tr><td>{{ $r->department_name }}</td><td class="r">{{ $r->headcount }}</td></tr>@endforeach
        </tbody>
        <tfoot><tr><td>TOTAL ACTIVE</td><td class="r">{{ $stats['active'] }}</td></tr></tfoot>
    </table>

    <h3>Headcount by Classification</h3>
    <table>
        <thead><tr><th>Classification</th><th class="r">Heads</th></tr></thead>
        <tbody>@foreach ($byClass as $r)<tr><td>{{ $r->classification }}</td><td class="r">{{ $r->headcount }}</td></tr>@endforeach</tbody>
    </table>

    <h3>New Hires ({{ $year }})</h3>
    <table>
        <thead><tr><th>Emp ID</th><th>Name</th><th>Department</th><th>Position</th><th>Date Hired</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($newHires as $h)
            <tr><td>{{ $h->employee_id }}</td><td>{{ strtoupper($h->name) }}</td><td>{{ $h->department }}</td><td>{{ $h->position }}</td>
                <td>{{ $h->hired ? \Illuminate\Support\Carbon::parse($h->hired)->format('M d, Y') : '—' }}</td><td>{{ $statusLabel($h->empStatus) }}</td></tr>
            @empty
            <tr><td colspan="6" style="text-align:center; color:#94a3b8; padding:12px;">No new hires this year.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3>Separations ({{ $year }})</h3>
    <table>
        <thead><tr><th>Emp ID</th><th>Name</th><th>Department</th><th>Separation Date</th><th>Reason</th><th>Type</th><th class="r">Years</th></tr></thead>
        <tbody>
            @forelse ($separations as $s)
            <tr><td>{{ $s->employee_id }}</td><td>{{ strtoupper($s->name) }}</td><td>{{ $s->department }}</td>
                <td>{{ $s->sep_date ? \Illuminate\Support\Carbon::parse($s->sep_date)->format('M d, Y') : '—' }}</td>
                <td>{{ $s->reason ?: '—' }}</td><td>{{ $statusLabel($s->empStatus) }}</td>
                <td class="r">{{ $s->years_rendered !== null ? number_format((float) $s->years_rendered, 2) : '—' }}</td></tr>
            @empty
            <tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:12px;">No separations recorded this year.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
