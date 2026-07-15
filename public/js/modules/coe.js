/* Certificate of Employment — HR admin. jQuery + axios + SweetAlert2 (global).
   Inbox-style workspace: left list rail + right detail pane. */
$(document).ready(function () {

    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && window.axios) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function toast(t, i) { if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 2400, showConfirmButton: false, icon: i || 'success', title: t }); }
    function fmtDate(d) { if (!d) return '—'; var dt = new Date(d); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }); }
    function fmtDateTime(d) { if (!d) return '—'; var dt = new Date(d); return isNaN(dt) ? esc(d) : dt.toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }); }

    /* ── Signatories (configured in Settings → COE Signatories) ── */
    // No draw-each-time pad: HR picks a configured signatory; its e-signature is
    // frozen onto the certificate server-side. Cached once and reused by both modals.
    var signatoryCache = null;

    function loadSignatories(selectId, previewId) {
        var $sel = $('#' + selectId);
        function fill() {
            if (!signatoryCache.length) {
                $sel.html('<option value="">No signatories — add one in Manage Signatories</option>');
                return;
            }
            var opts = '<option value="">Select signatory…</option>';
            signatoryCache.forEach(function (s) {
                opts += '<option value="' + s.id + '">' + esc(s.name) + (s.title ? ' — ' + esc(s.title) : '') + '</option>';
            });
            $sel.html(opts);
        }
        if (signatoryCache) { fill(); return; }
        $sel.html('<option value="">Loading signatories…</option>');
        axios.get('/coe/signatories/active').then(function (res) {
            signatoryCache = res.data.data || [];
            fill();
        }).catch(function () { $sel.html('<option value="">Could not load signatories</option>'); });
    }

    function showSigPreview(selectId, previewId) {
        var s = (signatoryCache || []).filter(function (x) { return String(x.id) === $('#' + selectId).val(); })[0];
        var $p = $('#' + previewId);
        if (s && s.signature) { $p.find('img').attr('src', s.signature); $p.show(); }
        else { $p.hide(); }
    }

    $(document).on('change', '#apSignatory', function () { showSigPreview('apSignatory', 'apSigPreview'); });
    $(document).on('change', '#isSignatory', function () { showSigPreview('isSignatory', 'isSigPreview'); });

    /* ── Workspace state ── */
    var allRows = [];          // full set from /coe/list (server-side search applied)
    var activeFilter = 'all';  // pill filter (client-side)
    var activeId = null;       // selected request id

    function statusBadge(s) {
        if (s === 'approved') return '<span class="badge-soft b-approved">Approved</span>';
        if (s === 'rejected') return '<span class="badge-soft b-rejected">Rejected</span>';
        return '<span class="badge-soft b-pending">Pending</span>';
    }
    function statusIcon(s) {
        if (s === 'approved') return 'fa-circle-check';
        if (s === 'rejected') return 'fa-circle-xmark';
        return 'fa-hourglass-half';
    }

    function filteredRows() {
        if (activeFilter === 'all') return allRows;
        return allRows.filter(function (r) { return r.status === activeFilter; });
    }

    function updateCounts() {
        var c = { all: allRows.length, pending: 0, approved: 0, rejected: 0 };
        allRows.forEach(function (r) { if (c[r.status] != null) c[r.status]++; });
        $('#cAll').text(c.all); $('#cPending').text(c.pending);
        $('#cApproved').text(c.approved); $('#cRejected').text(c.rejected);
    }

    function renderList() {
        var rows = filteredRows();
        if (!rows.length) {
            $('#coeList').html('<div class="empty-state"><i class="fa-solid fa-inbox"></i><div>No requests found.</div></div>');
            return;
        }
        $('#coeList').html(rows.map(function (r) {
            var name = r.employee_name || r.employee_id;
            var sub = esc(r.employee_id) + (r.certificate_no ? ' · ' + esc(r.certificate_no) : '');
            return '<div class="crow ' + r.status + (String(r.id) === String(activeId) ? ' active' : '') + '" data-id="' + r.id + '">' +
                '<div class="dot"><i class="fa-solid ' + statusIcon(r.status) + '"></i></div>' +
                '<div class="rmain">' +
                    '<div class="rtitle">' + esc(name) + '</div>' +
                    '<div class="rmeta">' + esc(r.purpose) + '</div>' +
                    '<div class="rmeta">' + sub + '</div>' +
                '</div>' +
            '</div>';
        }).join(''));
    }

    function actionsFor(r) {
        if (r.status === 'pending') {
            return '<button class="coe-btn" style="background:var(--success);color:#fff;" id="cdApprove"><i class="fa-solid fa-check"></i> Approve &amp; Sign</button>' +
                   '<button class="coe-btn ghost" id="cdReject" style="color:#b91c1c;"><i class="fa-solid fa-xmark"></i> Reject</button>';
        }
        if (r.status === 'approved') {
            return '<button class="coe-btn primary" id="cdPreview"><i class="fa-solid fa-eye"></i> Preview</button>' +
                   '<a class="coe-btn ghost" href="/coe/' + r.id + '/pdf"><i class="fa-solid fa-download"></i> Download PDF</a>';
        }
        return '';
    }

    function field(k, v) {
        return '<div class="cd-field"><div class="k">' + k + '</div><div class="v">' + v + '</div></div>';
    }

    function renderDetail(r) {
        if (!r) {
            $('#coeDetail').html('<div class="cd-empty"><i class="fa-solid fa-file-lines"></i><div>Select a request from the list to review it.</div></div>');
            return;
        }
        var name = r.employee_name || r.employee_id;
        var html = '<div class="cd-head">' +
                '<div class="cd-badges">' + statusBadge(r.status) +
                    (r.certificate_no ? '<span class="badge-soft" style="background:var(--teal-light);color:var(--teal-dark);">' + esc(r.certificate_no) + '</span>' : '') +
                '</div>' +
                '<h4 class="cd-title">' + esc(name) + '</h4>' +
                '<div class="cd-sub">Employee ID: ' + esc(r.employee_id) + '</div>' +
            '</div>' +
            '<div class="cd-body">' +
                '<div class="cd-grid">' +
                    field('Purpose', esc(r.purpose)) +
                    field('Copies', esc(r.copies)) +
                    field('Date needed', r.date_needed ? fmtDate(r.date_needed) : '—') +
                    field('Include salary', r.include_salary ? 'Yes' : 'No') +
                    field('Requested', fmtDateTime(r.created_at)) +
                    field('Reviewed by', esc(r.reviewed_by || '—')) +
                    field('Reviewed at', r.reviewed_at ? fmtDateTime(r.reviewed_at) : '—') +
                    (r.signatory_name ? field('Signatory', esc(r.signatory_name) + (r.signatory_title ? ' — ' + esc(r.signatory_title) : '')) : '') +
                '</div>' +
                (r.remarks ? '<div class="cd-note"><strong>Remarks:</strong>\n' + esc(r.remarks) + '</div>' : '') +
                (r.status === 'rejected' && r.rejection_reason ? '<div class="cd-reject"><b>Rejection reason</b>' + esc(r.rejection_reason) + '</div>' : '') +
            '</div>';
        var actions = actionsFor(r);
        if (actions) html += '<div class="cd-foot">' + actions + '</div>';
        $('#coeDetail').html(html);
    }

    function selectRow(id) {
        activeId = id;
        var r = allRows.filter(function (x) { return String(x.id) === String(id); })[0];
        $('.crow').removeClass('active');
        $('.crow[data-id="' + id + '"]').addClass('active');
        renderDetail(r);
    }

    function loadList(keepSelection) {
        return axios.get('/coe/list', { params: { search: $('#coeSearch').val() } })
            .then(function (res) {
                allRows = res.data.data || [];
                updateCounts();
                renderList();
                // Re-select the active row if it still exists, else auto-open the first.
                var still = keepSelection && allRows.some(function (x) { return String(x.id) === String(activeId); });
                var visible = filteredRows();
                if (still) { selectRow(activeId); }
                else if (visible.length) { selectRow(visible[0].id); }
                else { activeId = null; renderDetail(null); }
            });
    }

    /* ── List interactions ── */
    $(document).on('click', '.crow', function () { selectRow($(this).data('id')); });

    $(document).on('click', '.coe-pills .pill', function () {
        $('.coe-pills .pill').removeClass('active');
        $(this).addClass('active');
        activeFilter = $(this).data('filter');
        renderList();
        var visible = filteredRows();
        if (visible.some(function (x) { return String(x.id) === String(activeId); })) { selectRow(activeId); }
        else if (visible.length) { selectRow(visible[0].id); }
        else { activeId = null; renderDetail(null); }
    });

    var t;
    $(document).on('input', '#coeSearch', function () { clearTimeout(t); t = setTimeout(function () { loadList(true); }, 300); });

    /* ── Approve (opens modal for the selected request) ── */
    $(document).on('click', '#cdApprove', function () {
        var r = allRows.filter(function (x) { return String(x.id) === String(activeId); })[0];
        if (!r) return;
        $('#apId').val(r.id);
        $('#apName').text(r.employee_name || r.employee_id);
        $('#apEmpId').text(r.employee_id);
        $('#apPurpose').text(r.purpose);
        $('#apCopies').text(r.copies);
        $('#apIncludeSalary').prop('checked', !!r.include_salary);
        $('.text-danger.small').text('');
        $('#apSigPreview').hide();
        loadSignatories('apSignatory', 'apSigPreview');
        new bootstrap.Modal(document.getElementById('mdlApprove')).show();
    });

    $(document).on('click', '#btnConfirmApprove', function () {
        $('.text-danger.small').text('');
        if (!$('#apSignatory').val()) { $('#err-signatory_id').text('Please choose a signatory.'); return; }
        var payload = {
            id: $('#apId').val(),
            signatory_id: $('#apSignatory').val(),
            include_salary: $('#apIncludeSalary').is(':checked')
        };
        var $btn = $(this).prop('disabled', true);
        axios.post('/coe/approve', payload).then(function (res) {
            if (res.data.status === 201) {
                $.each(res.data.error, function (k, v) { $('#err-' + k.replace('.', '-')).text(v[0]); });
                return;
            }
            if (res.data.status === 200) {
                bootstrap.Modal.getInstance(document.getElementById('mdlApprove')).hide();
                toast(res.data.msg, 'success');
                loadList(true);
            } else { toast(res.data.msg || 'Error.', 'error'); }
        }).catch(function () { toast('Error approving request.', 'error'); })
          .then(function () { $btn.prop('disabled', false); });
    });

    /* ── Reject ── */
    $(document).on('click', '#cdReject', function () {
        var id = activeId;
        if (!id) return;
        Swal.fire({
            title: 'Reject COE request?', input: 'text', inputPlaceholder: 'Reason (required)',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Reject',
            inputValidator: function (v) { if (!v) return 'A reason is required.'; }
        }).then(function (r) {
            if (!r.isConfirmed) return;
            axios.post('/coe/reject', { id: id, reason: r.value }).then(function (res) {
                toast(res.data.msg, res.data.status === 200 ? 'success' : 'error'); loadList(true);
            });
        });
    });

    /* ── Issue COE for a separated employee ── */
    var separatedCache = [];

    $(document).on('click', '#btnIssueCoe', function () {
        $('#isPurpose').val(''); $('#isCopies').val(1); $('#isIncludeSalary').prop('checked', false);
        $('.text-danger.small').text('');
        $('#isClearancePanel').hide(); $('#isClearanceList').html('');
        $('#isSigPreview').hide();
        $('#btnConfirmIssue').prop('disabled', true);
        $('#isEmployee').html('<option value="">Loading…</option>');
        loadSignatories('isSignatory', 'isSigPreview');
        axios.get('/coe/separated-employees').then(function (res) {
            separatedCache = res.data.data || [];
            var opts = '<option value="">Select separated employee…</option>';
            separatedCache.forEach(function (e) {
                opts += '<option value="' + esc(e.empid) + '">' + esc(e.name) + ' — ' + esc(e.status) + (e.complete ? '' : ' (not ready)') + '</option>';
            });
            $('#isEmployee').html(opts);
        }).catch(function () { $('#isEmployee').html('<option value="">Could not load employees</option>'); });
        new bootstrap.Modal(document.getElementById('mdlIssue')).show();
    });

    $(document).on('change', '#isEmployee', function () {
        var id = $(this).val();
        var emp = separatedCache.filter(function (e) { return e.empid === id; })[0];
        if (!emp) { $('#isClearancePanel').hide(); $('#btnConfirmIssue').prop('disabled', true); return; }
        var html = (emp.clearance || []).map(function (c) {
            var icon = c.done ? '<i class="fa-solid fa-circle-check text-success me-1"></i>' : '<i class="fa-solid fa-circle-xmark text-danger me-1"></i>';
            var ref = c.reference ? ' <span class="text-muted">— ' + esc(c.reference) + '</span>' : '';
            return '<div>' + icon + esc(c.label) + ref + '</div>';
        }).join('');
        if (!emp.complete) {
            var reasons = (emp.missing || []).map(function (m) { return '<div>&bull; ' + esc(m) + '</div>'; }).join('');
            html += '<div class="text-danger mt-2" style="font-size:.78rem;"><b>Cannot issue — outstanding requirements:</b>' + reasons
                 + '<div class="text-muted mt-1">Complete these in E-201 (clearance via Update Status; profile via Edit Employee) first.</div></div>';
        }
        $('#isClearanceList').html(html);
        $('#isClearancePanel').show();
        $('#btnConfirmIssue').prop('disabled', !emp.complete);
    });

    $(document).on('click', '#btnConfirmIssue', function () {
        $('.text-danger.small').text('');
        if (!$('#isSignatory').val()) { $('#err-signatory_id_issue').text('Please choose a signatory.'); return; }
        var payload = {
            employee_id: $('#isEmployee').val(),
            purpose: $('#isPurpose').val().trim(),
            copies: $('#isCopies').val(),
            include_salary: $('#isIncludeSalary').is(':checked'),
            signatory_id: $('#isSignatory').val()
        };
        var $btn = $(this).prop('disabled', true);
        axios.post('/coe/issue', payload).then(function (res) {
            if (res.data.status === 201) {
                $.each(res.data.error, function (k, v) {
                    var id = '#err-' + k.replace('.', '-');
                    if (k === 'signatory_id') id = '#err-signatory_id_issue';
                    $(id).text(v[0]);
                });
                return;
            }
            if (res.data.status === 200) {
                bootstrap.Modal.getInstance(document.getElementById('mdlIssue')).hide();
                toast(res.data.msg, 'success');
                loadList(true);
            } else {
                var msg = res.data.msg || 'Error.';
                if (res.data.missing && res.data.missing.length) msg += '\n• ' + res.data.missing.join('\n• ');
                Swal.fire({ icon: 'warning', title: 'Cannot issue', text: msg });
            }
        }).catch(function () { toast('Error issuing COE.', 'error'); })
          .then(function () { $btn.prop('disabled', false); });
    });

    /* ── Preview (inline PDF in a modal) ── */
    $(document).on('click', '#cdPreview', function () {
        var r = allRows.filter(function (x) { return String(x.id) === String(activeId); })[0];
        if (!r) return;
        $('#coePreviewTitle').text(r.certificate_no ? 'Preview — ' + r.certificate_no : 'Certificate Preview');
        $('#coePreviewDownload').attr('href', '/coe/' + r.id + '/pdf');
        $('#coePreviewFrame').attr('src', '/coe/' + r.id + '/pdf?preview=1');
        new bootstrap.Modal(document.getElementById('mdlPreview')).show();
    });
    // Release the embedded PDF when the modal closes.
    $(document).on('hidden.bs.modal', '#mdlPreview', function () {
        $('#coePreviewFrame').attr('src', 'about:blank');
    });

    /* ── Deep link from the HR Attention panel: /pages/modules/coe?status=pending ──
       Prime the client-side pill before the first load so the list opens pre-filtered. */
    (function applyUrlFilter() {
        var focus = (new URLSearchParams(window.location.search).get('status') || '').toLowerCase();
        if (['pending', 'approved', 'rejected'].indexOf(focus) === -1) return;
        activeFilter = focus;
        $('.coe-pills .pill').removeClass('active');
        $('.coe-pills .pill[data-filter="' + focus + '"]').addClass('active');
    })();

    loadList();
});
