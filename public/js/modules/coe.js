/* Certificate of Employment — HR admin. jQuery + axios + SweetAlert2 (global). */
$(document).ready(function () {

    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && window.axios) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function toast(t, i) { if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 2400, showConfirmButton: false, icon: i || 'success', title: t }); }
    function fmtDate(d) { if (!d) return '—'; var dt = new Date(d); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }); }

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

    /* ── Table ── */
    function actionsFor(r) {
        if (r.status === 'pending') {
            return '<button class="btn-mini ok btn-approve me-1" data-json=\'' + JSON.stringify(r).replace(/'/g, '&#39;') + '\'><i class="fa-solid fa-check"></i> Approve</button>' +
                   '<button class="btn-mini warn btn-reject" data-id="' + r.id + '"><i class="fa-solid fa-xmark"></i> Reject</button>';
        }
        if (r.status === 'approved') {
            return '<a class="btn-mini dl" href="/coe/' + r.id + '/pdf"><i class="fa-solid fa-download"></i> PDF</a>';
        }
        return '<span class="text-muted" style="font-size:.74rem;">' + esc(r.rejection_reason || '—') + '</span>';
    }
    function statusBadge(s) {
        if (s === 'approved') return '<span class="badge-soft b-approved">Approved</span>';
        if (s === 'rejected') return '<span class="badge-soft b-rejected">Rejected</span>';
        return '<span class="badge-soft b-pending">Pending</span>';
    }

    function loadList() {
        axios.get('/coe/list', { params: { status: $('#fStatus').val(), search: $('#fSearch').val() } })
            .then(function (res) {
                var rows = res.data.data || [];
                if (!rows.length) { $('#tblCoe').html('<tr class="empty-row"><td colspan="7">No COE requests found.</td></tr>'); return; }
                $('#tblCoe').html(rows.map(function (r) {
                    return '<tr>' +
                        '<td><strong>' + esc(r.employee_name || r.employee_id) + '</strong><div class="text-muted" style="font-size:.68rem;">' + esc(r.employee_id) + (r.certificate_no ? ' &middot; ' + esc(r.certificate_no) : '') + '</div></td>' +
                        '<td>' + esc(r.purpose) + '</td>' +
                        '<td>' + esc(r.copies) + '</td>' +
                        '<td>' + (r.date_needed ? fmtDate(r.date_needed) : '<span class="text-muted">—</span>') + '</td>' +
                        '<td>' + statusBadge(r.status) + '</td>' +
                        '<td class="text-muted" style="font-size:.78rem;">' + esc(r.reviewed_by || '—') + '</td>' +
                        '<td class="text-end pe-4">' + actionsFor(r) + '</td>' +
                    '</tr>';
                }).join(''));
            });
    }

    /* ── Approve ── */
    $(document).on('click', '.btn-approve', function () {
        var r = JSON.parse($(this).attr('data-json').replace(/&#39;/g, "'"));
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
                loadList();
            } else { toast(res.data.msg || 'Error.', 'error'); }
        }).catch(function () { toast('Error approving request.', 'error'); })
          .then(function () { $btn.prop('disabled', false); });
    });

    /* ── Reject ── */
    $(document).on('click', '.btn-reject', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Reject COE request?', input: 'text', inputPlaceholder: 'Reason (required)',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Reject',
            inputValidator: function (v) { if (!v) return 'A reason is required.'; }
        }).then(function (r) {
            if (!r.isConfirmed) return;
            axios.post('/coe/reject', { id: id, reason: r.value }).then(function (res) {
                toast(res.data.msg, res.data.status === 200 ? 'success' : 'error'); loadList();
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
                opts += '<option value="' + esc(e.empid) + '">' + esc(e.name) + ' — ' + esc(e.status) + (e.complete ? '' : ' (clearance pending)') + '</option>';
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
            html += '<div class="text-danger mt-1" style="font-size:.78rem;"><b>Cannot issue:</b> complete the clearance in E-201 → Update Status first.</div>';
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
                loadList();
            } else {
                var msg = res.data.msg || 'Error.';
                if (res.data.missing && res.data.missing.length) msg += '\n• ' + res.data.missing.join('\n• ');
                Swal.fire({ icon: 'warning', title: 'Cannot issue', text: msg });
            }
        }).catch(function () { toast('Error issuing COE.', 'error'); })
          .then(function () { $btn.prop('disabled', false); });
    });

    /* ── Filters ── */
    var t;
    $(document).on('input', '#fSearch', function () { clearTimeout(t); t = setTimeout(loadList, 300); });
    $(document).on('change', '#fStatus', loadList);

    loadList();
});
