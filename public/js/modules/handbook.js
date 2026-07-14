/* Employee Handbook — HR admin: author/reorder sections, manage attachments, view read receipts. */
$(document).ready(function () {

    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && window.axios) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function fmtDate(d) { if (!d) return '—'; var dt = new Date(d); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); }
    function toast(t, i) { if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 2400, showConfirmButton: false, icon: i || 'success', title: t }); }

    var sections = [];
    var mdlSection = new bootstrap.Modal(document.getElementById('mdlSection'));
    var mdlReceipts = new bootstrap.Modal(document.getElementById('mdlReceipts'));

    function rowHtml(s, idx) {
        var status = s.is_published
            ? '<span class="badge-soft b-pub">Published</span>'
            : '<span class="badge-soft b-draft">Draft</span>';
        var ack = s.requires_ack
            ? '<span class="badge-soft b-ack">Required</span> <a href="#" class="rr-link small" data-id="' + s.id + '">' + s.ack_count + ' read</a>'
            : '<span class="text-muted small">—</span>';
        var doc = s.has_doc
            ? '<span class="badge-soft b-doc"><i class="fa-solid fa-file-' + (s.doc_isPdf ? 'pdf' : 'image') + ' me-1"></i>Yes</span>'
            : '<span class="text-muted small">—</span>';
        return '<tr draggable="true" data-id="' + s.id + '">' +
            '<td><i class="fa-solid fa-grip-vertical grip"></i></td>' +
            '<td>' + idx + '</td>' +
            '<td><div class="stitle">' + esc(s.title) + '</div><div class="text-muted small">Updated ' + fmtDate(s.updated_at) + (s.updated_by ? ' · ' + esc(s.updated_by) : '') + '</div></td>' +
            '<td>' + status + '</td>' +
            '<td>' + ack + '</td>' +
            '<td>' + doc + '</td>' +
            '<td style="text-align:right;"><span class="row-actions">' +
                '<button class="edit" data-id="' + s.id + '" title="Edit"><i class="fa-solid fa-pen"></i></button>' +
                '<button class="del" data-id="' + s.id + '" title="Delete"><i class="fa-solid fa-trash"></i></button>' +
            '</span></td>' +
        '</tr>';
    }

    function render() {
        if (!sections.length) {
            $('#sectionRows').html('<tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-book"></i><div>No sections yet. Click "New Section" to start the handbook.</div></div></td></tr>');
            return;
        }
        $('#sectionRows').html(sections.map(function (s, i) { return rowHtml(s, i + 1); }).join(''));
    }

    /* ── Single-PDF handbook (master document) ── */
    function renderMaster(m) {
        var ackToggle = '<label class="switchrow" style="margin:0;"><input type="checkbox" class="form-check-input m-0" id="mack"' + (m && m.requires_ack ? ' checked' : '') + '><span class="txt">Requires acknowledgement</span></label>';
        if (!m) {
            $('#masterBody').html(
                '<div class="row g-3 align-items-end">' +
                    '<div class="col-md-4"><label class="form-label">Title</label><input class="form-control" id="mtitle" placeholder="Employee Handbook"></div>' +
                    '<div class="col-md-5"><label class="form-label">Handbook PDF *</label><input type="file" class="form-control" id="mfile" accept=".pdf"></div>' +
                    '<div class="col-md-3">' + ackToggle + '</div>' +
                '</div>' +
                '<div class="text-danger small mt-2" id="merr"></div>' +
                '<div class="mt-3"><button class="btn-teal" id="btnSaveMaster"><i class="fa-solid fa-upload"></i> Upload Handbook</button></div>'
            );
            return;
        }
        $('#masterBody').html(
            '<div class="d-flex align-items-center flex-wrap gap-2 mb-3">' +
                '<span class="badge-soft b-doc"><i class="fa-solid fa-file-pdf me-1"></i>' + esc(m.attachment_name || 'handbook.pdf') + '</span>' +
                '<a href="/handbook/' + m.id + '/doc" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye me-1"></i>View</a>' +
                (m.requires_ack ? '<a href="#" class="rr-link small ms-1" data-id="' + m.id + '"><i class="fa-solid fa-clipboard-check me-1"></i>' + m.ack_count + ' acknowledged</a>' : '') +
                '<span class="text-muted small ms-auto">Updated ' + fmtDate(m.updated_at) + (m.updated_by ? ' · ' + esc(m.updated_by) : '') + '</span>' +
            '</div>' +
            '<div class="row g-3 align-items-end">' +
                '<div class="col-md-4"><label class="form-label">Title</label><input class="form-control" id="mtitle" value="' + esc(m.title) + '"></div>' +
                '<div class="col-md-5"><label class="form-label">Replace PDF (optional)</label><input type="file" class="form-control" id="mfile" accept=".pdf"></div>' +
                '<div class="col-md-3">' + ackToggle + '</div>' +
            '</div>' +
            '<div class="text-danger small mt-2" id="merr"></div>' +
            '<div class="mt-3 d-flex gap-2">' +
                '<button class="btn-teal" id="btnSaveMaster"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>' +
                '<button class="btn btn-outline-danger btn-sm" id="btnDelMaster"><i class="fa-solid fa-trash me-1"></i>Remove Handbook</button>' +
            '</div>'
        );
    }

    $(document).on('click', '#btnSaveMaster', function () {
        var $btn = $(this);
        $('#merr').text('');
        var fd = new FormData();
        fd.append('doc_title', $('#mtitle').val() || '');
        fd.append('requires_ack', $('#mack').is(':checked') ? 1 : 0);
        var f = document.getElementById('mfile').files[0];
        if (f) fd.append('attachment', f);
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Saving…');
        axios.post('/handbook/master/save', fd).then(function (res) {
            if (res.data.status === 201) {
                var e = res.data.error || {};
                $('#merr').text((e.attachment && e.attachment[0]) || (e.doc_title && e.doc_title[0]) || 'Please check your input.');
                $btn.prop('disabled', false).html('<i class="fa-solid fa-upload"></i> Upload Handbook');
                return;
            }
            if (res.data.status === 200) { toast(res.data.msg, 'success'); load(); }
            else { toast(res.data.msg || 'Could not save.', 'warning'); $btn.prop('disabled', false); }
        }).catch(function () {
            toast('Error saving handbook.', 'error');
            $btn.prop('disabled', false);
        });
    });

    $(document).on('click', '#btnDelMaster', function () {
        var go = function () {
            axios.post('/handbook/master/delete', {}).then(function (res) {
                if (res.data.status === 200) { toast(res.data.msg, 'success'); load(); }
                else { toast(res.data.msg || 'Could not remove.', 'warning'); }
            }).catch(function () { toast('Error removing handbook.', 'error'); });
        };
        if (!window.Swal) { if (confirm('Remove the handbook document?')) go(); return; }
        Swal.fire({
            title: 'Remove handbook document?', text: 'Employees will no longer see the uploaded PDF.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Remove'
        }).then(function (r) { if (r.isConfirmed) go(); });
    });

    function load() {
        return axios.get('/handbook/list').then(function (res) {
            sections = res.data.data || [];
            renderMaster(res.data.master || null);
            render();
        }).catch(function () {
            $('#sectionRows').html('<tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><div>Could not load sections.</div></div></td></tr>');
        });
    }

    /* ── Editor ── */
    function clearErrors() { $('#e_title,#e_body,#e_attachment').text(''); }

    $('#btnNew').on('click', function () {
        clearErrors();
        document.getElementById('sectionForm').reset();
        $('#f_id').val('');
        $('#f_published').prop('checked', true);
        $('#f_ack').prop('checked', false);
        $('#curDoc').hide();
        $('#mdlTitle').html('<i class="fa-solid fa-book-open me-2"></i>New Section');
        mdlSection.show();
    });

    $(document).on('click', '.edit', function () {
        var id = $(this).data('id'), s = null;
        for (var i = 0; i < sections.length; i++) { if (sections[i].id === id) { s = sections[i]; break; } }
        if (!s) return;
        clearErrors();
        document.getElementById('sectionForm').reset();
        $('#f_id').val(s.id);
        $('#f_title').val(s.title);
        $('#f_body').val(s.body || '');
        $('#f_published').prop('checked', !!s.is_published);
        $('#f_ack').prop('checked', !!s.requires_ack);
        if (s.has_doc) { $('#curDocName').text(s.attachment_name || 'document'); $('#f_removeDoc').prop('checked', false); $('#curDoc').show(); }
        else { $('#curDoc').hide(); }
        $('#mdlTitle').html('<i class="fa-solid fa-pen me-2"></i>Edit Section');
        mdlSection.show();
    });

    $('#btnSave').on('click', function () {
        clearErrors();
        var $btn = $(this);
        var fd = new FormData(document.getElementById('sectionForm'));
        // Checkboxes: send explicit 0 when unchecked so booleans persist.
        fd.set('is_published', $('#f_published').is(':checked') ? 1 : 0);
        fd.set('requires_ack', $('#f_ack').is(':checked') ? 1 : 0);
        if (!$('#f_removeDoc').is(':checked')) fd.delete('remove_doc');

        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Saving…');
        axios.post('/handbook/save', fd).then(function (res) {
            if (res.data.status === 201) {
                var e = res.data.error || {};
                if (e.title) $('#e_title').text(e.title[0]);
                if (e.body) $('#e_body').text(e.body[0]);
                if (e.attachment) $('#e_attachment').text(e.attachment[0]);
                return;
            }
            if (res.data.status === 200) {
                mdlSection.hide();
                toast(res.data.msg, 'success');
                load();
            } else {
                toast(res.data.msg || 'Could not save.', 'warning');
            }
        }).catch(function () {
            toast('Error saving section.', 'error');
        }).finally(function () {
            $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk"></i> Save Section');
        });
    });

    /* ── Delete ── */
    $(document).on('click', '.del', function () {
        var id = $(this).data('id'), s = null;
        for (var i = 0; i < sections.length; i++) { if (sections[i].id === id) { s = sections[i]; break; } }
        var name = s ? s.title : 'this section';
        if (!window.Swal) { if (!confirm('Delete this section?')) return; doDelete(id); return; }
        Swal.fire({
            title: 'Delete section?', html: 'This will permanently remove <b>' + esc(name) + '</b> and its read receipts.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Delete'
        }).then(function (r) { if (r.isConfirmed) doDelete(id); });
    });

    function doDelete(id) {
        axios.post('/handbook/delete', { id: id }).then(function (res) {
            if (res.data.status === 200) { toast(res.data.msg, 'success'); load(); }
            else { toast(res.data.msg || 'Could not delete.', 'warning'); }
        }).catch(function () { toast('Error deleting section.', 'error'); });
    }

    /* ── Drag to reorder ── */
    var dragId = null;
    $(document).on('dragstart', '#sectionRows tr', function (e) { dragId = $(this).data('id'); e.originalEvent.dataTransfer.effectAllowed = 'move'; });
    $(document).on('dragover', '#sectionRows tr', function (e) { e.preventDefault(); $('#sectionRows tr').removeClass('drag-over'); $(this).addClass('drag-over'); });
    $(document).on('dragleave', '#sectionRows tr', function () { $(this).removeClass('drag-over'); });
    $(document).on('drop', '#sectionRows tr', function (e) {
        e.preventDefault();
        $('#sectionRows tr').removeClass('drag-over');
        var targetId = $(this).data('id');
        if (dragId == null || dragId === targetId) return;
        var from = sections.findIndex(function (s) { return s.id === dragId; });
        var to = sections.findIndex(function (s) { return s.id === targetId; });
        if (from < 0 || to < 0) return;
        var moved = sections.splice(from, 1)[0];
        sections.splice(to, 0, moved);
        render();
        axios.post('/handbook/reorder', { order: sections.map(function (s) { return s.id; }) })
            .then(function () { toast('Order saved.', 'success'); })
            .catch(function () { toast('Could not save order.', 'error'); load(); });
    });

    /* ── Read receipts ── */
    $(document).on('click', '.rr-link', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('#rrBody').html('<div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i></div>');
        mdlReceipts.show();
        axios.get('/handbook/acknowledgements', { params: { section_id: id } }).then(function (res) {
            var d = res.data;
            var pending = d.total - d.acked;
            var rows = (d.data || []).map(function (r) {
                return '<div class="rr-row"><span>' + esc(r.name) + '</span>' +
                    (r.acknowledged_at
                        ? '<span class="yes"><i class="fa-solid fa-circle-check me-1"></i>' + fmtDate(r.acknowledged_at) + '</span>'
                        : '<span class="no">Not yet</span>') +
                '</div>';
            }).join('');
            $('#rrBody').html(
                '<div class="fw-bold mb-2">' + esc(d.section.title) + '</div>' +
                '<div class="rr-summary">' +
                    '<div class="rr-chip">Total employees: <b>' + d.total + '</b></div>' +
                    '<div class="rr-chip">Acknowledged: <b style="color:var(--success)">' + d.acked + '</b></div>' +
                    '<div class="rr-chip">Pending: <b style="color:#b45309">' + pending + '</b></div>' +
                '</div>' +
                '<div class="rr-list">' + (rows || '<div class="rr-row"><span class="text-muted">No active employees.</span></div>') + '</div>'
            );
        }).catch(function () {
            $('#rrBody').html('<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><div>Could not load receipts.</div></div>');
        });
    });

    load();
});
