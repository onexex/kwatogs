/* My COE — employee view. Request a certificate, track status, download approved. */
$(document).ready(function () {

    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && window.axios) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function toast(t, i) { if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 2400, showConfirmButton: false, icon: i || 'success', title: t }); }
    function fmtDate(d) { if (!d) return '—'; var dt = new Date(d); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }); }

    function statusBadge(s) {
        if (s === 'approved') return '<span class="badge-soft b-approved">Approved</span>';
        if (s === 'rejected') return '<span class="badge-soft b-rejected">Rejected</span>';
        return '<span class="badge-soft b-pending">Pending review</span>';
    }

    function cardHtml(r) {
        var actions = '';
        if (r.status === 'approved') {
            actions = '<div class="mt-2 d-flex gap-2 flex-wrap">' +
                '<button class="btn btn-outline-teal btn-sm btn-preview" data-id="' + r.id + '" data-cert="' + esc(r.certificate_no || '') + '"><i class="fa-solid fa-eye me-1"></i> Preview</button>' +
                '<a class="btn btn-teal btn-sm" href="/coe/' + r.id + '/pdf"><i class="fa-solid fa-download me-1"></i> Download PDF</a>' +
            '</div>';
        } else if (r.status === 'rejected' && r.rejection_reason) {
            actions = '<div class="cc-meta" style="color:var(--danger);margin-top:6px;"><i class="fa-solid fa-circle-info me-1"></i>Reason: ' + esc(r.rejection_reason) + '</div>';
        }
        var ref = r.certificate_no ? ' &middot; Ref ' + esc(r.certificate_no) : '';
        var needed = r.date_needed ? ' &middot; needed by ' + fmtDate(r.date_needed) : '';
        return '<div class="coe-card ' + esc(r.status) + '">' +
            '<div class="cc-top">' +
                '<div><h6 class="cc-purpose">' + esc(r.purpose) + '</h6>' +
                '<div class="cc-meta">' + esc(r.copies) + ' cop' + (r.copies > 1 ? 'ies' : 'y') + needed + ref + '</div>' +
                '<div class="cc-meta">Requested ' + fmtDate(r.created_at) + (r.reviewed_at ? ' &middot; reviewed ' + fmtDate(r.reviewed_at) : '') + '</div></div>' +
                '<div>' + statusBadge(r.status) + '</div>' +
            '</div>' +
            (r.remarks ? '<div class="cc-meta" style="margin-top:6px;">“' + esc(r.remarks) + '”</div>' : '') +
            actions +
        '</div>';
    }

    function loadList() {
        axios.get('/mycoe/list').then(function (res) {
            var rows = res.data.data || [];
            if (!rows.length) {
                $('#myCoeList').html('<div class="empty-state"><i class="fa-solid fa-file-circle-plus"></i><div>You have no COE requests yet.</div></div>');
                return;
            }
            $('#myCoeList').html(rows.map(cardHtml).join(''));
        }).catch(function () {
            $('#myCoeList').html('<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><div>Could not load your requests.</div></div>');
        });
    }

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
