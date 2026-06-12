@extends('layout.app')
@section('content')

<style>
    :root {
        --teal:         #008080;
        --teal-dark:    #006666;
        --teal-light:   #e0f2f1;
        --slate:        #334155;
        --slate-light:  #64748b;
        --muted:        #94a3b8;
        --bg:           #f1f5f9;
        --surface:      #ffffff;
        --border:       #e2e8f0;
        --danger:       #ef4444;
        --success:      #10b981;
        --radius-card:  14px;
        --radius-input: 8px;
        --shadow-card:  0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }

    .backup-shell {
        background: var(--bg);
        min-height: 100vh;
        padding: 24px 28px 60px;
        margin: -1.5rem -1.5rem 0;
    }

    .backup-topbar {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        padding: 16px 22px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .backup-topbar h4 {
        margin: 0;
        color: var(--slate);
        font-weight: 700;
    }

    .backup-topbar p {
        margin: 4px 0 0;
        color: var(--slate-light);
        font-size: 0.875rem;
    }

    .btn-teal {
        background: var(--teal);
        border: none;
        color: #fff;
        border-radius: var(--radius-input);
        padding: 10px 18px;
        font-weight: 600;
        transition: background .15s;
    }

    .btn-teal:hover {
        background: var(--teal-dark);
        color: #fff;
    }

    .backup-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-card);
        padding: 22px;
    }

    table.backup-table th {
        color: var(--slate-light);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        border-bottom: 2px solid var(--border);
    }

    table.backup-table td {
        vertical-align: middle;
        border-bottom: 1px solid var(--border);
        color: var(--slate);
    }

    .badge-size {
        background: var(--teal-light);
        color: var(--teal-dark);
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.8rem;
    }

    .empty-state {
        text-align: center;
        padding: 50px 0;
        color: var(--muted);
    }
</style>

<div class="backup-shell">
    <div class="backup-topbar">
        <div>
            <h4><i class="fa-solid fa-database me-2"></i>Database Backup</h4>
            <p>Create, download, and manage database backups.</p>
        </div>
        @can('databasebackupcreate')
            <form action="{{ route('database-backup.store') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-teal">
                    <i class="fa-solid fa-circle-plus me-1"></i> Create Backup
                </button>
            </form>
        @endcan
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="backup-card">
        @if (count($backups) > 0)
            <div class="table-responsive">
                <table class="table backup-table">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Size</th>
                            <th>Created At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($backups as $backup)
                            <tr>
                                <td><i class="fa-solid fa-file-zipper me-2 text-muted"></i>{{ basename($backup['path']) }}</td>
                                <td><span class="badge-size">{{ number_format($backup['size'] / 1024 / 1024, 2) }} MB</span></td>
                                <td>{{ \Carbon\Carbon::createFromTimestamp($backup['last_modified'])->format('M d, Y h:i A') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('database-backup.download', basename($backup['path'])) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa-solid fa-download"></i> Download
                                    </a>
                                    @can('databasebackuprestore')
                                        <form action="{{ route('database-backup.restore', basename($backup['path'])) }}" method="POST" class="d-inline" onsubmit="return confirm('Restoring will overwrite the current database with this backup. Continue?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                <i class="fa-solid fa-rotate-left"></i> Restore
                                            </button>
                                        </form>
                                    @endcan
                                    @can('databasebackupdelete')
                                        <form action="{{ route('database-backup.destroy', basename($backup['path'])) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this backup file?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="fa-solid fa-database fa-2x mb-2"></i>
                <p>No backups found. Click "Create Backup" to generate one.</p>
            </div>
        @endif
    </div>
</div>

@endsection
