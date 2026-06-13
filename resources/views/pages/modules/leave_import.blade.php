@extends('layout.app', ['title' => 'Leave Import'])
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
    .sc { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); margin-bottom:20px; overflow:hidden; }
    .sc-head { display:flex; align-items:center; gap:10px; padding:14px 22px; border-bottom:1px solid var(--border);
        background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .sc-icon { width:30px; height:30px; border-radius:8px; background:var(--teal-light); color:var(--teal);
        display:flex; align-items:center; justify-content:center; font-size:.78rem; }
    .sc-title { font-size:.78rem; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; margin:0; }
    .sc-body { padding:22px; }
    .btn-teal { background:var(--teal); color:#fff; border:none; border-radius:var(--radius-input); padding:10px 20px;
        font-size:.82rem; font-weight:700; cursor:pointer; box-shadow:0 4px 14px rgba(0,128,128,.25); transition:all .2s;
        display:inline-flex; align-items:center; gap:8px; }
    .btn-teal:hover { background:var(--teal-dark); transform:translateY(-1px); color:#fff; }
    .btn-ghost { background:var(--surface); color:var(--slate-light); border:1.5px solid var(--border);
        border-radius:var(--radius-input); padding:10px 20px; font-size:.82rem; font-weight:700; cursor:pointer;
        transition:all .2s; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
    .btn-ghost:hover { background:var(--teal-light); border-color:var(--teal-mid); color:var(--teal); }
    .ai-note { font-size:.8rem; color:var(--slate); background:var(--teal-light); border:1px solid #b8e0dc;
        border-radius:var(--radius-input); padding:12px 14px; margin-bottom:18px; line-height:1.6; }
    .ai-note b { color:var(--teal-dark); }
    .ai-note code { background:#d6efea; color:#055; padding:1px 6px; border-radius:4px; font-size:.75rem; }
    .ai-drop { border:2px dashed var(--border); border-radius:var(--radius-card); padding:30px; text-align:center;
        background:#fcfdfe; transition:all .15s; cursor:pointer; }
    .ai-drop.drag { border-color:var(--teal); background:var(--teal-light); }
    .ai-drop i { font-size:2rem; color:var(--teal-mid); }
    .ai-file-name { font-weight:700; color:var(--slate); margin-top:8px; }
    .stat-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:12px; margin-bottom:14px; }
    .stat { border:1px solid var(--border); border-radius:10px; padding:14px; text-align:center; }
    .stat .n { font-size:1.5rem; font-weight:800; }
    .stat .l { font-size:.7rem; text-transform:uppercase; letter-spacing:.4px; color:var(--slate-light); font-weight:700; }
    .stat.ok .n { color:var(--success); } .stat.up .n { color:var(--teal); } .stat.skip .n { color:var(--danger); }
    .err-list { max-height:260px; overflow:auto; border:1px solid #fecaca; border-radius:10px; background:#fef2f2; padding:10px 14px; }
    .err-list div { font-size:.8rem; color:#991b1b; padding:3px 0; border-bottom:1px solid #fde2e2; }
</style>

<div class="ai-shell">
    <div class="ai-topbar">
        <div>
            <p class="page-title">Leave Import</p>
            <p class="page-sub">Bulk-import approved leaves so they reflect in attendance and payroll</p>
        </div>
        <a href="{{ route('leave-import.template') }}" class="btn-ghost">
            <i class="fa fa-download"></i> Download Template
        </a>
    </div>

    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-calendar-check"></i></div>
            <h5 class="sc-title">Upload File</h5>
        </div>
        <div class="sc-body">
            <div class="ai-note">
                <b>How it works:</b> one row = one leave (a date range). The system creates the leave record plus a
                per-day breakdown the payroll reads (8 hrs/day, 4 hrs for a single half-day).
                <b>Leave Type</b> can be the type name (e.g. <code>Vacation Leave</code>) or its ID.
                <b>Leave Kind</b> = <code>Paid</code> or <code>UnPaid</code>. Status defaults to <b>APPROVEDBYCFO</b> so payroll counts it.
                Employees matched by Employee ID. Re-importing the same employee + dates + type updates instead of duplicating.
                <br><b>Note:</b> this does not auto-deduct leave-credit balances.
            </div>

            <form id="aiForm">
                <div class="ai-drop" id="aiDrop">
                    <i class="fa fa-cloud-arrow-up"></i>
                    <div class="mt-2" style="color:var(--slate-light);font-size:.85rem;">Drag &amp; drop your file here, or click to browse</div>
                    <div class="ai-file-name" id="aiFileName"></div>
                    <input type="file" id="aiFile" name="file" accept=".xlsx,.csv,.txt" hidden>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="submit" class="btn-teal" id="aiSubmit"><i class="fa fa-upload"></i> Import</button>
                </div>
            </form>
        </div>
    </div>

    <div class="sc" id="aiResults" style="display:none;">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-clipboard-check"></i></div>
            <h5 class="sc-title">Import Result</h5>
        </div>
        <div class="sc-body">
            <div class="stat-grid">
                <div class="stat ok"><div class="n" id="rInserted">0</div><div class="l">Created</div></div>
                <div class="stat up"><div class="n" id="rUpdated">0</div><div class="l">Updated</div></div>
                <div class="stat skip"><div class="n" id="rSkipped">0</div><div class="l">Skipped</div></div>
            </div>
            <div id="rErrorsWrap" style="display:none;">
                <div style="font-weight:700;color:var(--danger);font-size:.8rem;margin-bottom:6px;">
                    <i class="fa fa-triangle-exclamation me-1"></i> Rows skipped (fix and re-import):
                </div>
                <div class="err-list" id="rErrors"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    const drop = document.getElementById('aiDrop');
    const fileInput = document.getElementById('aiFile');
    const fileName = document.getElementById('aiFileName');

    drop.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => { fileName.textContent = fileInput.files[0]?.name || ''; });
    ['dragover','dragenter'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('drag'); }));
    ['dragleave','drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('drag'); }));
    drop.addEventListener('drop', e => {
        if (e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; fileName.textContent = e.dataTransfer.files[0].name; }
    });

    $('#aiForm').on('submit', function (e) {
        e.preventDefault();
        if (!fileInput.files.length) { Swal.fire('No file', 'Please choose a file first.', 'warning'); return; }
        const fd = new FormData();
        fd.append('file', fileInput.files[0]);
        Swal.fire({ title: 'Importing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        axios.post("{{ route('leave-import.upload') }}", fd)
            .then(res => {
                Swal.close();
                const d = res.data;
                $('#rInserted').text(d.inserted); $('#rUpdated').text(d.updated); $('#rSkipped').text(d.skipped);
                if (d.errors && d.errors.length) {
                    $('#rErrors').html(d.errors.map(x => `<div>${x}</div>`).join(''));
                    $('#rErrorsWrap').show();
                } else { $('#rErrorsWrap').hide(); }
                $('#aiResults').show();
                Swal.fire({ icon: d.skipped ? 'warning' : 'success', title: 'Done', text: d.message, timer: 2200, showConfirmButton: false });
            })
            .catch(err => {
                Swal.close();
                Swal.fire('Import failed', err.response?.data?.message || 'Unable to import the file.', 'error');
            });
    });
});
</script>
@endsection
