/* My COE — employee workspace: list rail + reading pane. Request a certificate,
   track status, preview and download once approved. */
$(document).ready(function () {

    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && window.axios) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function toast(t, i) { if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 2400, showConfirmButton: false, icon: i || 'success', title: t }); }
    function fmtDate(d) { if (!d) return '—'; var dt = new Date(d); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }); }

    var allReqs = [];
    var statusFilter = 'all';
    var searchTerm = '';
    var selectedId = null;

    function statusBadge(s) {
        if (s === 'approved') return '<span class="badge-soft b-approved">Approved</span>';
        if (s === 'rejected') return '<span class="badge-soft b-rejected">Rejected</span>';
        return '<span class="badge-soft b-pending">Pending review</span>';
    }
    function statusIcon(s) {
        if (s === 'approved') return 'fa-circle-check';
        if (s === 'rejected') return 'fa-circle-xmark';
        return 'fa-hourglass-half';
    }

    // Compact row in the left rail.
    function rowHtml(r) {
        return '<div class="crow ' + esc(r.status) + (r.id === selectedId ? ' active' : '') + '" data-id="' + r.id + '">' +
            '<div class="dot"><i class="fa-solid ' + statusIcon(r.status) + '"></i></div>' +
            '<div class="rmain">' +
                '<div class="rtitle">' + esc(r.purpose) + '</div>' +
                '<div class="rmeta">' + fmtDate(r.created_at) +
                    (r.certificate_no ? ' <span>&middot; ' + esc(r.certificate_no) + '</span>' : '') +
                '</div>' +
            '</div>' +
        '</div>';
    }

    // Full request in the reading pane.
    function detailHtml(r) {
        var needed = '<span><i class="fa-solid fa-calendar-check"></i>Needed ' + fmtDate(r.date_needed) + '</span>';
        var actions = '';
        if (r.status === 'approved') {
            actions = '<div class="cd-actions">' +
                '<button class="btn btn-outline-teal btn-sm btn-preview" data-id="' + r.id + '" data-cert="' + esc(r.certificate_no || '') + '"><i class="fa-solid fa-eye me-1"></i> Preview</button>' +
                '<a class="btn btn-teal btn-sm" href="/coe/' + r.id + '/pdf"><i class="fa-solid fa-download me-1"></i> Download PDF</a>' +
            '</div>';
        }
        var reject = (r.status === 'rejected' && r.rejection_reason)
            ? '<div class="cd-reject"><i class="fa-solid fa-circle-info me-1"></i><strong>Rejection reason:</strong> ' + esc(r.rejection_reason) + '</div>'
            : '';
        var remarks = r.remarks
            ? '<div class="cd-section"><div class="cd-label">Your Remarks</div><div class="cd-remarks">' + esc(r.remarks) + '</div></div>'
            : '';
        return '<div class="cd-head">' +
                '<div class="cd-badges">' + statusBadge(r.status) +
                    (r.certificate_no ? '<span class="badge-soft b-approved" style="background:var(--teal-light);color:var(--teal-dark);">Ref ' + esc(r.certificate_no) + '</span>' : '') +
                '</div>' +
                '<h2 class="cd-title">' + esc(r.purpose) + '</h2>' +
                '<div class="cd-meta">' +
                    '<span><i class="fa-solid fa-copy"></i>' + esc(r.copies) + ' cop' + (r.copies > 1 ? 'ies' : 'y') + '</span>' +
                    needed +
                    '<span><i class="fa-solid fa-paper-plane"></i>Requested ' + fmtDate(r.created_at) + '</span>' +
                    (r.reviewed_at ? '<span><i class="fa-solid fa-user-check"></i>Reviewed ' + fmtDate(r.reviewed_at) + '</span>' : '') +
                '</div>' +
            '</div>' +
            reject +
            remarks +
            actions;
    }

    function selectReq(id) {
        var r = null;
        for (var i = 0; i < allReqs.length; i++) { if (allReqs[i].id === id) { r = allReqs[i]; break; } }
        if (!r) return;
        selectedId = id;
        $('.crow').removeClass('active');
        $('.crow[data-id="' + id + '"]').addClass('active');
        $('#coeDetail').html(detailHtml(r));
    }

    function visibleRows() {
        return allReqs.filter(function (r) {
            if (statusFilter !== 'all' && r.status !== statusFilter) return false;
            if (searchTerm) {
                var hay = ((r.purpose || '') + ' ' + (r.remarks || '') + ' ' + (r.certificate_no || '')).toLowerCase();
                if (hay.indexOf(searchTerm) === -1) return false;
            }
            return true;
        });
    }

    function render() {
        if (!allReqs.length) {
            $('#myCoeList').html('<div class="empty-state"><i class="fa-solid fa-file-circle-plus"></i><div>You have no COE requests yet.</div></div>');
            $('#coeDetail').html('<div class="cd-empty"><i class="fa-solid fa-file-signature"></i><div>No requests yet. Use “Request COE” to submit one.</div></div>');
            return;
        }
        var rows = visibleRows();
        if (!rows.length) {
            $('#myCoeList').html('<div class="empty-state"><i class="fa-solid fa-filter-circle-xmark"></i><div>No requests match this filter.</div></div>');
            return;
        }
        $('#myCoeList').html(rows.map(rowHtml).join(''));

        // Keep the current selection if still visible; otherwise open the first row.
        var stillVisible = false;
        for (var i = 0; i < rows.length; i++) { if (rows[i].id === selectedId) { stillVisible = true; break; } }
        if (!stillVisible) { selectReq(rows[0].id); }
        else { $('.crow[data-id="' + selectedId + '"]').addClass('active'); }
    }

    function updateCounts() {
        var p = allReqs.filter(function (r) { return r.status === 'pending'; }).length;
        var a = allReqs.filter(function (r) { return r.status === 'approved'; }).length;
        var j = allReqs.filter(function (r) { return r.status === 'rejected'; }).length;
        $('#cAll').text(allReqs.length); $('#cPending').text(p); $('#cApproved').text(a); $('#cRejected').text(j);
        $('#sTotal').text(allReqs.length); $('#sPending').text(p); $('#sApproved').text(a); $('#sRejected').text(j);
    }

    function loadList() {
        return axios.get('/mycoe/list').then(function (res) {
            allReqs = res.data.data || [];
            updateCounts();
            render();
        }).catch(function () {
            $('#myCoeList').html('<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><div>Could not load your requests.</div></div>');
        });
    }

    $(document).on('click', '.crow', function () { selectReq($(this).data('id')); });

    $(document).on('click', '.pill', function () {
        $('.pill').removeClass('active');
        $(this).addClass('active');
        statusFilter = $(this).data('filter');
        render();
    });

    var t;
    $(document).on('input', '#coeSearch', function () {
        var v = $(this).val().toLowerCase();
        clearTimeout(t);
        t = setTimeout(function () { searchTerm = v; render(); }, 200);
    });

    /* ── Preview (inline PDF in a modal) ── */
    $(document).on('click', '.btn-preview', function () {
        var id = $(this).data('id');
        var cert = $(this).data('cert');
        $('#coePreviewTitle').text(cert ? 'Preview — ' + cert : 'Certificate Preview');
        $('#coePreviewDownload').attr('href', '/coe/' + id + '/pdf');
        $('#coePreviewFrame').attr('src', '/coe/' + id + '/pdf?preview=1');
        new bootstrap.Modal(document.getElementById('mdlPreview')).show();
    });
    // Release the embedded PDF when the modal closes.
    $(document).on('hidden.bs.modal', '#mdlPreview', function () {
        $('#coePreviewFrame').attr('src', 'about:blank');
    });

    $(document).on('click', '#btnRequestCoe', function () {
        if ($(this).is(':disabled')) return;
        $('#txtPurpose').val(''); $('#txtCopies').val(1); $('#txtDateNeeded').val(''); $('#txtRemarks').val('');
        $('.text-danger.small').text('');
        new bootstrap.Modal(document.getElementById('mdlRequest')).show();
    });

    $(document).on('click', '#btnSubmitRequest', function () {
        $('.text-danger.small').text('');
        var payload = {
            purpose: $('#txtPurpose').val().trim(),
            copies: $('#txtCopies').val(),
            date_needed: $('#txtDateNeeded').val() || null,
            remarks: $('#txtRemarks').val().trim() || null
        };
        var $btn = $(this).prop('disabled', true);
        axios.post('/mycoe/store', payload).then(function (res) {
            if (res.data.status === 201) {
                $.each(res.data.error, function (k, v) { $('#err-' + k.replace('.', '-')).text(v[0]); });
                return;
            }
            if (res.data.status === 200) {
                bootstrap.Modal.getInstance(document.getElementById('mdlRequest')).hide();
                toast(res.data.msg, 'success');
                // A pending request now exists — block a duplicate until HR reviews it.
                $('#btnRequestCoe').prop('disabled', true);
                loadList();
            } else {
                var msg = res.data.msg || 'Error.';
                if (res.data.missing && res.data.missing.length) msg += '\n• ' + res.data.missing.join('\n• ');
                Swal.fire({ icon: 'warning', title: 'Cannot submit', text: msg });
            }
        }).catch(function () { toast('Error submitting request.', 'error'); })
          .then(function () { $btn.prop('disabled', false); });
    });

    loadList();
});
