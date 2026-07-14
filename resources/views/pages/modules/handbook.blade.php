@extends('layout.app')
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8;
        --bg:#f1f5f9; --surface:#ffffff; --border:#e2e8f0;
        --danger:#ef4444; --warning:#f59e0b; --success:#15803d;
        --radius-card:14px; --shadow-card:0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    }
    .ha-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .ha-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .ha-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .ha-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .btn-teal { background:var(--teal); color:#fff; border:none; border-radius:8px; padding:9px 18px; font-size:.82rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
    .btn-teal:hover { background:var(--teal-dark); color:#fff; }

    .ha-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); box-shadow:var(--shadow-card); overflow:hidden; }
    .ha-table { width:100%; border-collapse:collapse; }
    .ha-table th { text-align:left; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.4px; color:var(--slate-light); padding:12px 16px; border-bottom:1px solid var(--border); background:#fafbfc; }
    .ha-table td { padding:13px 16px; border-bottom:1px solid var(--border); font-size:.85rem; color:var(--slate); vertical-align:middle; }
    .ha-table tr:last-child td { border-bottom:none; }
    .ha-table tr.drag-over td { background:var(--teal-light); }
    .grip { cursor:grab; color:var(--muted); }
    .stitle { font-weight:700; }
    .badge-soft { display:inline-block; border-radius:20px; padding:3px 10px; font-size:.68rem; font-weight:800; white-space:nowrap; }
    .b-pub { background:#dcfce7; color:var(--success); } .b-draft { background:#f1f5f9; color:var(--slate-light); } .b-ack { background:#fef3c7; color:#b45309; } .b-doc { background:var(--teal-light); color:var(--teal-dark); }
    .row-actions button { background:none; border:1px solid var(--border); border-radius:7px; width:32px; height:32px; cursor:pointer; color:var(--slate-light); margin-left:4px; }
    .row-actions button:hover { border-color:var(--teal-mid); color:var(--teal); }
    .row-actions button.del:hover { border-color:#fca5a5; color:var(--danger); }
    .empty-state { text-align:center; padding:60px 20px; color:var(--muted); }
    .empty-state i { font-size:2.4rem; color:var(--teal-light); margin-bottom:12px; }

    .form-label { font-size:.76rem; font-weight:700; color:var(--slate); margin-bottom:5px; }
    .form-control, .form-select { font-size:.86rem; }
    .hint { font-size:.72rem; color:var(--muted); }
    .switchrow { display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--border); border-radius:10px; background:#fafbfc; }
    .switchrow .txt { font-size:.82rem; font-weight:700; color:var(--slate); }
    .switchrow .sub { font-size:.7rem; color:var(--muted); font-weight:400; }

    /* Read-receipts */
    .rr-summary { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:12px; }
    .rr-chip { background:#fafbfc; border:1px solid var(--border); border-radius:10px; padding:8px 14px; font-size:.78rem; color:var(--slate); }
    .rr-chip b { font-size:1rem; }
    .rr-list { max-height:340px; overflow:auto; border:1px solid var(--border); border-radius:10px; }
    .rr-row { display:flex; align-items:center; justify-content:space-between; padding:9px 14px; border-bottom:1px solid var(--border); font-size:.82rem; }
    .rr-row:last-child { border-bottom:none; }
    .rr-row .yes { color:var(--success); font-weight:700; } .rr-row .no { color:var(--muted); }
</style>

<div class="ha-shell">
    <div class="ha-topbar">
        <div>
            <p class="page-title"><i class="fa-solid fa-book-open me-2" style="color:var(--teal);"></i> Employee Handbook</p>
            <p class="page-sub">Author the sections employees read on their Handbook page. Drag to reorder.</p>
        </div>
        <button class="btn-teal" id="btnNew"><i class="fa-solid fa-plus"></i> New Section</button>
    </div>

    {{-- Single-PDF handbook (master document) --}}
    <div class="ha-card" id="masterCard" style="margin-bottom:16px;">
        <div style="padding:15px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <div style="flex:1;min-width:220px;">
                <div class="fw-bold" style="color:var(--slate);"><i class="fa-solid fa-file-pdf me-2" style="color:var(--teal);"></i>Handbook Document <span class="text-muted fw-normal">— single PDF</span></div>
                <div class="text-muted small">Upload one PDF for the whole handbook. Employees read it in a view-only, watermarked viewer. Optional — you can also (or instead) build sections below.</div>
            </div>
        </div>
        <div style="padding:16px 20px;" id="masterBody">
            <div class="text-muted small"><i class="fa-solid fa-spinner fa-spin me-2"></i>Loading…</div>
        </div>
    </div>

    <div class="ha-card">
        <div style="padding:13px 20px;border-bottom:1px solid var(--border);">
            <div class="fw-bold" style="color:var(--slate);font-size:.92rem;"><i class="fa-solid fa-list-ol me-2" style="color:var(--teal);"></i>Handbook Sections</div>
            <div class="text-muted small">Optional chapters shown as a reading list. Drag to reorder.</div>
        </div>
        <table class="ha-table">
            <thead>
                <tr>
                    <th style="width:36px;"></th>
                    <th style="width:44px;">#</th>
                    <th>Section</th>
                    <th style="width:120px;">Status</th>
                    <th style="width:150px;">Acknowledgement</th>
                    <th style="width:120px;">Attachment</th>
                    <th style="width:120px;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody id="sectionRows">
                <tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><div>Loading…</div></div></td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Editor modal --}}
<div class="modal fade" id="mdlSection" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header" style="background:var(--teal);color:#fff;">
                <h6 class="modal-title" id="mdlTitle"><i class="fa-solid fa-book-open me-2"></i>New Section</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="sectionForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="f_id">
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" class="form-control" name="title" id="f_title" maxlength="200" required>
                        <div class="text-danger small mt-1" id="e_title"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea class="form-control" name="body" id="f_body" rows="12" placeholder="Write the section content here…"></textarea>
                        <div class="hint mt-1">Basic HTML is supported (headings, <code>&lt;p&gt;</code>, <code>&lt;ul&gt;/&lt;li&gt;</code>, <code>&lt;b&gt;</code>, <code>&lt;a&gt;</code>). Line breaks are preserved.</div>
                        <div class="text-danger small mt-1" id="e_body"></div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="switchrow">
                                <input type="checkbox" class="form-check-input m-0" name="is_published" id="f_published" checked>
                                <span><span class="txt">Published</span><br><span class="sub">Visible to employees</span></span>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="switchrow">
                                <input type="checkbox" class="form-check-input m-0" name="requires_ack" id="f_ack">
                                <span><span class="txt">Requires acknowledgement</span><br><span class="sub">Employees must confirm they read it</span></span>
                            </label>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Supporting document (optional — PDF or image)</label>
                        <input type="file" class="form-control" name="attachment" id="f_doc" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="hint mt-1">Viewed inline on the employee side (no download). Max 10 MB.</div>
                        <div class="text-danger small mt-1" id="e_attachment"></div>
                        <div id="curDoc" class="mt-2" style="display:none;">
                            <span class="badge-soft b-doc"><i class="fa-solid fa-paperclip me-1"></i><span id="curDocName"></span></span>
                            <label class="ms-2 small text-danger" style="cursor:pointer;"><input type="checkbox" name="remove_doc" id="f_removeDoc" value="1"> remove</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-teal" id="btnSave"><i class="fa-solid fa-floppy-disk"></i> Save Section</button>
            </div>
        </div>
    </div>
</div>

{{-- Read-receipts modal --}}
<div class="modal fade" id="mdlReceipts" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header" style="background:var(--teal);color:#fff;">
                <h6 class="modal-title"><i class="fa-solid fa-clipboard-check me-2"></i>Read Receipts</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="rrBody"><div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i></div></div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/modules/handbook.js') }}?v={{ @filemtime(public_path('js/modules/handbook.js')) ?: time() }}" defer></script>
@endsection
