<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Employee Information Report</title>
    <style>
        @page { size: landscape; margin: 12mm; }
        * { box-sizing:border-box; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        body { font-family:'Segoe UI', Arial, sans-serif; color:#1e293b; margin:0; font-size:10px; }
        .toolbar { display:flex; gap:8px; justify-content:flex-end; margin-bottom:14px; }
        .btn { border:0; border-radius:6px; padding:7px 14px; font-weight:700; cursor:pointer; font-size:12px; }
        .btn-print { background:#008080; color:#fff; } .btn-close { background:#e5e7eb; color:#374151; }
        @media print { .toolbar { display:none; } body { padding:0; } }

        .head { display:flex; align-items:center; gap:14px; border-bottom:3px solid #008080; padding-bottom:12px; margin-bottom:6px; }
        .head img { height:54px; width:auto; }
        .head .org { font-size:18px; font-weight:800; color:#006666; letter-spacing:.3px; }
        .head .sub { font-size:12px; color:#475569; margin-top:1px; }
        .meta { font-size:10px; color:#64748b; margin:10px 0 14px; }
        .meta b { color:#334155; }

        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; white-space:nowrap; }
        thead th { background:#008080 !important; color:#fff !important; font-size:9px; text-transform:uppercase;
            letter-spacing:.3px; padding:6px 4px; text-align:center; border:none; }
        thead th:first-child { text-align:left; }
        tbody td { padding:4px; border-bottom:1px solid #e2e8f0; font-size:9px; text-align:center; vertical-align:middle; }
        tbody td:first-child { text-align:left; }
        tbody tr:nth-child(even) td { background:#f8fafc; }

        .note { margin-top:14px; font-size:9px; color:#94a3b8; font-style:italic; }
        .sign { margin-top:42px; display:flex; justify-content:space-between; font-size:11px; }
        .sign div { width:30%; text-align:center; }
        .sign .ln { border-top:1px solid #475569; margin-bottom:4px; padding-top:4px; }
        .endmark { margin-top:18px; text-align:center; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn btn-print" onclick="window.print()">&#x1F5A8; Print</button>
        <button class="btn btn-close" onclick="window.close()">Close</button>
    </div>

    <div class="head">
        <img src="{{ asset('img/kwatogslogo.jpg') }}" onerror="this.style.display='none'" alt="">
        <div>
            <div class="org">KWATOGS LOMI HOUSE</div>
            <div class="sub">Employee Information Report</div>
        </div>
    </div>

    <div class="meta">
        @php
            $f = $filters ?? [];
            $range = trim(($f['date_from'] ?? '') . ' to ' . ($f['date_to'] ?? ''), ' to');
        @endphp
        <b>Date Range:</b> {{ $range ?: 'All' }}
        &nbsp;&bull;&nbsp; <b>Classification:</b> {{ ($f['classification_id'] ?? 'all') === 'all' || empty($f['classification_id']) ? 'All' : $f['classification_id'] }}
        &nbsp;&bull;&nbsp; <b>Company:</b> {{ ($f['company_id'] ?? 'all') === 'all' || empty($f['company_id']) ? 'All' : $f['company_id'] }}
        &nbsp;&bull;&nbsp; <b>Records:</b> {{ $rows->count() }}
        &nbsp;&bull;&nbsp; <b>Generated:</b> {{ now()->format('M d, Y h:i A') }}
    </div>

    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>No</th><th>Emp ID</th><th>Employee Name</th><th>Suffix</th><th>Gender</th>
                <th>Citizenship</th><th>Date of Birth</th><th>Civil Status</th><th>Phone</th><th>Email</th>
                <th>Address</th><th>Company</th><th>Classification</th><th>Department</th><th>Position</th>
                <th>Immediate Superior</th><th>Status</th><th>Date Hired</th><th>Date Regular</th>
                <th>Basic Salary</th><th>Allowance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $employee)
                @php
                    $info = $employee->employeeInformation;
                    $i = $index + 1;
                @endphp
                <tr>
                    <td>{{ $i }}</td>
                    <td>{{ $employee->empID }}</td>
                    <td>{{ trim(($employee->user?->fname ?? '') . ' ' . ($employee->user?->lname ?? '')) }}</td>
                    <td>{{ $employee->user?->suffix ?? '' }}</td>
                    <td>{{ $info?->gender ?? '' }}</td>
                    <td>{{ $info?->citizenship ?? '' }}</td>
                    <td>{{ $info?->empBdate ? \Carbon\Carbon::parse($info->empBdate)->format('M d, Y') : '' }}</td>
                    <td>
                        @php
                            $cs = $info?->empCStatus;
                        @endphp
                        {{ $cs === '0' ? 'Single' : ($cs === '1' ? 'Married' : ($cs === '2' ? 'Divorced' : 'N/A')) }}
                    </td>
                    <td>{{ $info?->empPContact ?? '' }}</td>
                    <td>{{ $info?->empEmail ?? '' }}</td>
                    <td>{{ trim(implode(' ', array_filter([$info?->empAddStreet ?? '', $info?->empAddBrgyDesc ?? '', $info?->empAddCityDesc ?? '']))) }}</td>
                    <td>{{ $employee->company?->comp_name ?? '—' }}</td>
                    <td>{{ $employee->classification?->class_desc ?? '—' }}</td>
                    <td>{{ $employee->department?->dep_name ?? '—' }}</td>
                    <td>{{ $employee->position?->pos_desc ?? '—' }}</td>
                    <td>{{ trim(($employee->immediateSupervisor?->fname ?? '') . ' ' . ($employee->immediateSupervisor?->lname ?? '')) }}</td>
                    <td>{{ $employee->empStatus == '1' ? 'Employed' : 'Resigned' }}</td>
                    <td>{{ $employee->empDateHired ? $employee->empDateHired->format('M d, Y') : '' }}</td>
                    <td>{{ $employee->empDateRegular ? $employee->empDateRegular->format('M d, Y') : '' }}</td>
                    <td>{{ number_format($employee->empBasic ?? 0, 2) }}</td>
                    <td>{{ number_format($employee->empAllowance ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="21" style="text-align:center;color:#6b7280;padding:16px;">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="note">
        Employee information as recorded in the HR system. Status indicates whether the employee is currently employed or resigned.
        Basic salary and allowance amounts are in PHP.
    </div>

    <div class="sign">
        <div><div class="ln"></div>Prepared by</div>
        <div><div class="ln"></div>Checked & Verified by</div>
        <div><div class="ln"></div>Approved by</div>
    </div>

    <div class="endmark">*** End of Report ***</div>

    <script>
        window.onload = function () {
            setTimeout(function () { window.focus(); window.print(); window.close(); }, 400);
        };
    <\/script>
</body>
</html>