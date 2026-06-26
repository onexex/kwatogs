@extends('layout.app', ['title' => $moduleLabel.' Import History'])
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
    .sc { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); margin-bottom:20px; overflow:hidden; }
    .sc-head { display:flex; align-items:center; gap:10px; padding:14px 22px; border-bottom:1px solid var(--border);
        background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .sc-icon { width:30px; height:30px; border-radius:8px; background:var(--teal-light); color:var(--teal);
        display:flex; align-items:center; justify-content:center; font-size:.78rem; }
    .sc-title { font-size:.78rem; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; margin:0; }
    .ai-note { font-size:.8rem; color:var(--slate); background:var(--teal-light); border:1px solid #b8e0dc;
        border-radius:var(--radius-input); padding:12px 14px; margin:18px 22px 0; line-height:1.6; }
    .ai-note b { color:var(--teal-dark); }
    table.imp { width:100%; border-collapse:collapse; }
    table.imp th { font-size:.7rem; text-transform:uppercase; letter-spacing:.4px; color:var(--slate-light);
        font-weight:700; text-align:left; padding:12px 16px; border-bottom:1px solid var(--border); white-space:nowrap; }
    table.imp td { font-size:.84rem; color:var(--slate); padding:12px 16px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    table.imp tr:hover td { background:#fafdfd; }
    .pill { display:inline-block; font-size:.72rem; font-weight:700; padding:3px 9px; border-radius:999px; }
    .pill.ok { background:#dcfce7; color:#166534; }
    .pill.up { background:var(--teal-light); color:var(--teal-dark); }
    .btn-mini { border:none; border-radius:7px; padding:6px 12px; font-size:.76rem; font-weight:700; cursor:pointer;
        display:inline-flex; align-items:center; gap:6px; text-decoration:none; }
    .btn-mini.view { background:var(--teal-light); color:var(--teal-dark); }
    .btn-mini.view:hover { background:var(--teal-mid); color:#fff; }
    .btn-mini.del { background:#fef2f2; color:#991b1b; }
    .btn-mini.del:hover { background:var(--danger); color:#fff; }
    .empty { text-align:center; padding:46px 20px; color:var(--muted); font-size:.86rem; }
</style>

<div class="ai-shell">
    <div class="ai-topbar">
        <div>
            <p class="page-title">{{ $moduleLabel }} Import History</p>
            <p class="page-sub">Pull up a past import and roll it back as a unit, then re-upload the corrected file</p>
        </div>
        <a href="{{ route($importRoute) }}" class="btn-ghost">
            <i class="fa fa-arrow-left"></i> Back to Import
        </a>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-clock-rotate-left"></i></div>
            <h5 class="sc-title">Past Imports</h5>
        </div>

        <div class="ai-note">
            <b>How to fix an imported row:</b> open the import that contains the wrong entry, click <b>Delete</b> to
            roll it back (removes the records it created), then return to <b>{{ $moduleLabel }} Import</b>
            and re-upload the corrected file. Deletion is blocked if those dates are already in a computed payroll.
        </div>

        @if($batches->isEmpty())
            <div class="empty"><i class="fa fa-inbox fa-2x mb-2 d-block"></i> No imports yet.</div>
        @else
        <div style="overflow-x:auto;">
            <table class="imp">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>File</th>
                        <th>Date Range</th>
                        <th>Rows</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Imported By</th>
                        <th>When</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batches as $b)
                    <tr id="batch-row-{{ $b->id }}">
                        <td>{{ $b->id }}</td>
                        <td style="font-weight:600;">{{ $b->filename ?: '—' }}</td>
                        <td>
                            @if($b->date_from && $b->date_to)
                                {{ \Carbon\Carbon::parse($b->date_from)->format('M d, Y') }}
                                @if($b->date_from != $b->date_to)
                                    – {{ \Carbon\Carbon::parse($b->date_to)->format('M d, Y') }}
                                @endif
                            @else — @endif
                        </td>
                        <td>{{ $b->row_count }}</td>
                        <td><span class="pill ok">{{ $b->inserted }}</span></td>
                        <td><span class="pill up">{{ $b->updated }}</span></td>
                        <td>{{ $b->user_name ?: '—' }}</td>
                        <td>{{ $b->created_at ? $b->created_at->format('M d, Y g:i A') : '—' }}</td>
                        <td style="text-align:right; white-space:nowrap;">
                            <a href="{{ route($routePrefix.'.show', $b->id) }}" class="btn-mini view">
                                <i class="fa fa-eye"></i> View
                            </a>
                            <button class="btn-mini del" onclick="deleteBatch('{{ route($routePrefix.'.destroy', $b->id) }}', {{ $b->id }})">
                                <i class="fa fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<script>
function deleteBatch(url, id) {
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
                Swal.close();
                document.getElementById('batch-row-' + id)?.remove();
                Swal.fire({ icon: 'success', title: 'Done', text: r.data.message, timer: 2200, showConfirmButton: false });
            })
            .catch(err => {
                Swal.close();
                Swal.fire('Cannot delete', err.response?.data?.message || 'Unable to delete this import.', 'error');
            });
    });
}
</script>
@endsection
