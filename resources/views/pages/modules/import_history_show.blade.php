@extends('layout.app', ['title' => $moduleLabel.' Import Batch #'.$batch->id])
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8; --bg:#f1f5f9;
        --surface:#fff; --border:#e2e8f0; --danger:#ef4444; --success:#10b981;
        --radius-card:14px; --radius-input:8px; --shadow-card:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);
    }
    .ai-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .ai-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:20px; display:flex;
        align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
    .ai-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .ai-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .btn-ghost { background:var(--surface); color:var(--slate-light); border:1.5px solid var(--border);
        border-radius:var(--radius-input); padding:10px 20px; font-size:.82rem; font-weight:700; cursor:pointer;
        transition:all .2s; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
    .btn-ghost:hover { background:var(--teal-light); border-color:var(--teal-mid); color:var(--teal); }
    .btn-danger { background:var(--danger); color:#fff; border:none; border-radius:var(--radius-input); padding:10px 20px;
        font-size:.82rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
    .btn-danger:hover { background:#dc2626; }
    .sc { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); margin-bottom:20px; overflow:hidden; }
    .sc-head { display:flex; align-items:center; gap:10px; padding:14px 22px; border-bottom:1px solid var(--border);
        background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .sc-icon { width:30px; height:30px; border-radius:8px; background:var(--teal-light); color:var(--teal);
        display:flex; align-items:center; justify-content:center; font-size:.78rem; }
    .sc-title { font-size:.78rem; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; margin:0; }
    .meta-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; padding:18px 22px; }
    .meta { border:1px solid var(--border); border-radius:10px; padding:12px 14px; }
    .meta .l { font-size:.68rem; text-transform:uppercase; letter-spacing:.4px; color:var(--slate-light); font-weight:700; }
    .meta .v { font-size:.92rem; font-weight:700; color:var(--slate); margin-top:3px; }
    table.imp { width:100%; border-collapse:collapse; }
    table.imp th { font-size:.7rem; text-transform:uppercase; letter-spacing:.4px; color:var(--slate-light);
        font-weight:700; text-align:left; padding:12px 16px; border-bottom:1px solid var(--border); white-space:nowrap; }
    table.imp td { font-size:.84rem; color:var(--slate); padding:10px 16px; border-bottom:1px solid #f1f5f9; white-space:nowrap; }
    table.imp tr:hover td { background:#fafdfd; }
    .empty { text-align:center; padding:40px 20px; color:var(--muted); font-size:.86rem; }
</style>

<div class="ai-shell">
    <div class="ai-topbar">
        <div>
            <p class="page-title">{{ $moduleLabel }} Import Batch #{{ $batch->id }}</p>
            <p class="page-sub">{{ $batch->filename ?: 'Imported file' }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route($routePrefix) }}" class="btn-ghost">
                <i class="fa fa-arrow-left"></i> Back to History
            </a>
            <button class="btn-danger" onclick="deleteBatch('{{ route($routePrefix.'.destroy', $batch->id) }}')">
                <i class="fa fa-trash"></i> Roll Back This Import
            </button>
        </div>
    </div>

    <div class="sc">
        <div class="meta-grid">
            <div class="meta"><div class="l">Rows</div><div class="v">{{ $batch->row_count }}</div></div>
            <div class="meta"><div class="l">Created</div><div class="v">{{ $batch->inserted }}</div></div>
            <div class="meta"><div class="l">Updated</div><div class="v">{{ $batch->updated }}</div></div>
            <div class="meta"><div class="l">Date Range</div><div class="v">
                @if($batch->date_from && $batch->date_to)
                    {{ \Carbon\Carbon::parse($batch->date_from)->format('M d') }} – {{ \Carbon\Carbon::parse($batch->date_to)->format('M d, Y') }}
                @else — @endif
            </div></div>
            <div class="meta"><div class="l">Imported By</div><div class="v">{{ $batch->user_name ?: '—' }}</div></div>
            <div class="meta"><div class="l">When</div><div class="v">{{ $batch->created_at ? $batch->created_at->format('M d, Y g:i A') : '—' }}</div></div>
        </div>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-list"></i></div>
            <h5 class="sc-title">Rows In This Import ({{ count($rows) }})</h5>
        </div>

        @if(empty($rows))
            <div class="empty"><i class="fa fa-inbox fa-2x mb-2 d-block"></i> No rows found for this import.</div>
        @else
        <div style="overflow-x:auto;">
            <table class="imp">
                <thead>
                    <tr>
                        @foreach($columns as $col)
                            <th>{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                    <tr>
                        @foreach($row as $i => $cell)
                            <td @if($i === 0) style="font-weight:600; white-space:normal;" @endif>{{ $cell }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<script>
const HISTORY_URL = "{{ route($routePrefix) }}";
function deleteBatch(url) {
    Swal.fire({
        title: 'Roll back this import?',
        html: 'This deletes the records this import created.<br>You can re-upload a corrected file afterwards.',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete', cancelButtonText: 'Cancel'
    }).then(res => {
        if (!res.isConfirmed) return;
        Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        axios.delete(url)
            .then(r => {
                Swal.fire({ icon: 'success', title: 'Done', text: r.data.message, timer: 1800, showConfirmButton: false })
                    .then(() => window.location.href = HISTORY_URL);
            })
            .catch(err => {
                Swal.close();
                Swal.fire('Cannot delete', err.response?.data?.message || 'Unable to delete this import.', 'error');
            });
    });
}
</script>
@endsection
