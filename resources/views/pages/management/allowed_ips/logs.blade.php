@extends('layout.app', ['title' => 'IP Access Logs'])

@section('content')
<style>
:root {
    --teal:       #008080;
    --teal-dark:  #006666;
    --teal-mid:   #4db6ac;
    --teal-light: #e0f2f1;
    --slate:      #334155;
    --slate-light:#64748b;
    --muted:      #94a3b8;
    --bg:         #f1f5f9;
    --border:     #e2e8f0;
    --radius-card:14px;
    --shadow-card:0 2px 12px rgba(0,0,0,.07);
}

.logs-card {
    background: #fff;
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    overflow: hidden;
}
.logs-card-header {
    background: var(--slate);
    color: #fff;
    padding: .9rem 1.4rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .5rem;
}
.logs-card-header h5 { margin: 0; font-weight: 600; font-size: 1rem; }

.filter-bar {
    background: var(--bg);
    border-bottom: 1px solid var(--border);
    padding: .85rem 1.4rem;
}

.badge-allowed {
    background: #d1fae5; color: #065f46;
    font-size: .75rem; border-radius: 20px; padding: .25rem .7rem;
    font-weight: 600;
}
.badge-blocked {
    background: #fee2e2; color: #991b1b;
    font-size: .75rem; border-radius: 20px; padding: .25rem .7rem;
    font-weight: 600;
}
.badge-login  {
    background: #eff6ff; color: #1e40af;
    font-size: .75rem; border-radius: 20px; padding: .25rem .7rem;
    font-weight: 600;
}
.badge-access {
    background: #fefce8; color: #854d0e;
    font-size: .75rem; border-radius: 20px; padding: .25rem .7rem;
    font-weight: 600;
}

.logs-table thead th {
    background: var(--bg);
    color: var(--slate-light);
    font-size: .78rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .05em;
    border-bottom: 2px solid var(--border);
    padding: .7rem 1rem;
    white-space: nowrap;
}
.logs-table tbody td {
    padding: .7rem 1rem;
    vertical-align: middle;
    font-size: .88rem;
    color: var(--slate);
    border-bottom: 1px solid var(--border);
}
.logs-table tbody tr:last-child td { border-bottom: none; }
.logs-table tbody tr:hover td { background: var(--teal-light); }

.ip-chip {
    font-family: 'Courier New', monospace;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: .15rem .5rem;
    font-size: .83rem;
    color: var(--slate);
}
</style>

<div class="container-fluid px-4 py-3" style="background:var(--bg); min-height:100vh;">

    {{-- Page header --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-0 fw-bold" style="color:var(--slate)">
                <i class="fa fa-history me-2" style="color:var(--teal)"></i>IP Access Logs
            </h4>
            <nav class="breadcrumb mb-0" style="font-size:.85rem; color:var(--muted);">
                <a href="{{ url('/pages/home') }}" style="color:var(--teal);text-decoration:none">Home</a>
                <span class="mx-1">/</span>
                <a href="{{ route('allowed-ips.dashboard') }}" style="color:var(--teal);text-decoration:none">IP Dashboard</a>
                <span class="mx-1">/</span>
                <span>Access Logs</span>
            </nav>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('allowed-ips.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-list me-1"></i>Manage IPs
            </a>
            <a href="{{ route('allowed-ips.logs.export', request()->query()) }}" class="btn btn-sm"
               style="background:var(--teal);color:#fff">
                <i class="fa fa-file-excel me-1"></i>Export Excel
            </a>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="logs-card mb-3">
        <div class="filter-bar">
            <form method="GET" action="{{ route('allowed-ips.logs') }}" class="row g-2 align-items-end">
                <div class="col-sm-4 col-md-3">
                    <label class="form-label mb-1" style="font-size:.8rem;color:var(--slate-light)">Search</label>
                    <input type="text" name="search" value="{{ $search ?? '' }}"
                           class="form-control form-control-sm"
                           placeholder="IP address or name…">
                </div>
                <div class="col-sm-4 col-md-2">
                    <label class="form-label mb-1" style="font-size:.8rem;color:var(--slate-light)">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        <option value="allowed" @selected(($status ?? '') === 'allowed')>Allowed</option>
                        <option value="blocked" @selected(($status ?? '') === 'blocked')>Blocked</option>
                    </select>
                </div>
                <div class="col-sm-4 col-md-2">
                    <label class="form-label mb-1" style="font-size:.8rem;color:var(--slate-light)">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}"
                           class="form-control form-control-sm">
                </div>
                <div class="col-sm-4 col-md-2">
                    <label class="form-label mb-1" style="font-size:.8rem;color:var(--slate-light)">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo ?? '' }}"
                           class="form-control form-control-sm">
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-sm"
                            style="background:var(--teal);color:#fff;min-width:70px">
                        <i class="fa fa-search me-1"></i>Filter
                    </button>
                    <a href="{{ route('allowed-ips.logs') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa fa-times me-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="logs-card">
        <div class="logs-card-header">
            <h5><i class="fa fa-table me-2"></i>Log Entries</h5>
            <small style="opacity:.75;">{{ $logs->total() }} record(s) found</small>
        </div>

        @if ($logs->isEmpty())
            <div class="text-center py-5" style="color:var(--muted)">
                <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                No log entries match your filters.
            </div>
        @else
            <div class="table-responsive">
                <table class="table logs-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee</th>
                            <th>IP Address</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>Date &amp; Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $i => $log)
                        <tr>
                            <td style="color:var(--muted); width:50px">
                                {{ ($logs->currentPage() - 1) * $logs->perPage() + $i + 1 }}
                            </td>
                            <td>
                                <span class="fw-500">{{ $log->user_name ?? '<em class="text-muted">Unknown</em>' }}</span>
                            </td>
                            <td><code class="ip-chip">{{ $log->ip_address }}</code></td>
                            <td>
                                @if ($log->action_type === 'login')
                                    <span class="badge-login">Login</span>
                                @else
                                    <span class="badge-access">Access</span>
                                @endif
                            </td>
                            <td>
                                @if ($log->status === 'allowed')
                                    <span class="badge-allowed"><i class="fa fa-check me-1"></i>Allowed</span>
                                @else
                                    <span class="badge-blocked"><i class="fa fa-ban me-1"></i>Blocked</span>
                                @endif
                            </td>
                            <td style="color:var(--slate-light); white-space:nowrap">
                                {{ $log->created_at?->format('M d, Y  H:i:s') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($logs->hasPages())
            <div class="d-flex justify-content-between align-items-center px-4 py-3"
                 style="border-top:1px solid var(--border); font-size:.85rem; color:var(--muted)">
                <span>
                    Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}
                </span>
                {{ $logs->links() }}
            </div>
            @endif
        @endif
    </div>

</div>
@endsection
