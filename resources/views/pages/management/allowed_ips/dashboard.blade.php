@extends('layout.app', ['title' => 'IP Restriction Dashboard'])

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

.ip-dash-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .75rem;
    margin-bottom: 1.5rem;
}
.ip-dash-header h4 {
    color: var(--slate);
    font-weight: 700;
    margin: 0;
}
.ip-dash-header .breadcrumb {
    margin: 0;
    font-size: .85rem;
    color: var(--muted);
}
.ip-dash-header .breadcrumb a { color: var(--teal); text-decoration: none; }

/* Stat cards */
.stat-card {
    background: #fff;
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    padding: 1.4rem 1.6rem;
    display: flex;
    align-items: center;
    gap: 1.1rem;
    border-left: 4px solid transparent;
    transition: transform .18s;
}
.stat-card:hover { transform: translateY(-2px); }
.stat-card.teal   { border-left-color: var(--teal); }
.stat-card.blue   { border-left-color: #3b82f6; }
.stat-card.red    { border-left-color: #ef4444; }
.stat-card.green  { border-left-color: #22c55e; }

.stat-icon {
    width: 52px; height: 52px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.stat-card.teal  .stat-icon { background: var(--teal-light); color: var(--teal); }
.stat-card.blue  .stat-icon { background: #eff6ff; color: #3b82f6; }
.stat-card.red   .stat-icon { background: #fef2f2; color: #ef4444; }
.stat-card.green .stat-icon { background: #f0fdf4; color: #22c55e; }

.stat-val {
    font-size: 2rem;
    font-weight: 800;
    color: var(--slate);
    line-height: 1;
}
.stat-lbl {
    font-size: .82rem;
    color: var(--muted);
    margin-top: .2rem;
}

/* Quick nav cards */
.quick-nav {
    background: #fff;
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    padding: 1.2rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    color: inherit;
    transition: box-shadow .18s, transform .18s;
}
.quick-nav:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,.12);
    transform: translateY(-2px);
    color: var(--slate);
    text-decoration: none;
}
.quick-nav-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    background: var(--teal-light);
    color: var(--teal);
}
.quick-nav-title { font-weight: 600; font-size: .95rem; color: var(--slate); }
.quick-nav-desc  { font-size: .8rem; color: var(--muted); }
</style>

<div class="container-fluid px-4 py-3" style="background:var(--bg); min-height:100vh;">

    {{-- Header --}}
    <div class="ip-dash-header">
        <div>
            <h4><i class="fa fa-shield-alt me-2" style="color:var(--teal)"></i>IP Restriction Dashboard</h4>
            <nav class="breadcrumb">
                <a href="{{ url('/pages/home') }}">Home</a>
                <span class="mx-1">/</span>
                <span>IP Restriction Dashboard</span>
            </nav>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('allowed-ips.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-list me-1"></i>Manage IPs
            </a>
            <a href="{{ route('allowed-ips.logs') }}" class="btn btn-sm" style="background:var(--teal);color:#fff">
                <i class="fa fa-history me-1"></i>View Logs
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card teal">
                <div class="stat-icon"><i class="fa fa-list"></i></div>
                <div>
                    <div class="stat-val" id="stat-total">—</div>
                    <div class="stat-lbl">Total IPs in Allowlist</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
                <div>
                    <div class="stat-val" id="stat-active">—</div>
                    <div class="stat-lbl">Active IPs</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card red">
                <div class="stat-icon"><i class="fa fa-ban"></i></div>
                <div>
                    <div class="stat-val" id="stat-blocked">—</div>
                    <div class="stat-lbl">Blocked Attempts Today</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card green">
                <div class="stat-icon"><i class="fa fa-sign-in-alt"></i></div>
                <div>
                    <div class="stat-val" id="stat-allowed">—</div>
                    <div class="stat-lbl">Successful Logins Today</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick navigation --}}
    <div class="row g-3">
        <div class="col-sm-6 col-lg-3">
            <a href="{{ route('allowed-ips.index') }}" class="quick-nav">
                <div class="quick-nav-icon"><i class="fa fa-list-ul"></i></div>
                <div>
                    <div class="quick-nav-title">Manage Allowlist</div>
                    <div class="quick-nav-desc">Add, edit, or remove IPs</div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="{{ route('allowed-ips.create') }}" class="quick-nav">
                <div class="quick-nav-icon"><i class="fa fa-plus"></i></div>
                <div>
                    <div class="quick-nav-title">Add New IP</div>
                    <div class="quick-nav-desc">Whitelist a new address</div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="{{ route('allowed-ips.logs') }}" class="quick-nav">
                <div class="quick-nav-icon"><i class="fa fa-history"></i></div>
                <div>
                    <div class="quick-nav-title">Access Logs</div>
                    <div class="quick-nav-desc">View allowed & blocked events</div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="{{ route('allowed-ips.export') }}" class="quick-nav">
                <div class="quick-nav-icon"><i class="fa fa-file-excel"></i></div>
                <div>
                    <div class="quick-nav-title">Export IPs</div>
                    <div class="quick-nav-desc">Download as Excel</div>
                </div>
            </a>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    function loadStats() {
        axios.get('{{ route("allowed-ips.stats") }}')
            .then(function (res) {
                var d = res.data;
                document.getElementById('stat-total').textContent   = d.totalIps;
                document.getElementById('stat-active').textContent  = d.activeIps;
                document.getElementById('stat-blocked').textContent = d.blockedToday;
                document.getElementById('stat-allowed').textContent = d.allowedToday;
            })
            .catch(function () {
                ['stat-total','stat-active','stat-blocked','stat-allowed'].forEach(function (id) {
                    document.getElementById(id).textContent = '—';
                });
            });
    }

    document.addEventListener('DOMContentLoaded', loadStats);
})();
</script>
@endpush
