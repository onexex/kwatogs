/* Notices & Memos — HR admin workspace. jQuery + axios + SweetAlert2 (global). */
$(document).ready(function () {

    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && window.axios) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function toast(t, i) { if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 2200, showConfirmButton: false, icon: i || 'success', title: t }); }
    function fmtDate(d) { if (!d) return '—'; var dt = new Date(d); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }); }
    function isSeen(n) { return !!n.read_at || n.is_read == 1; }

    /* Workspace state. */
    var allRows = [];          // last /notices/list payload (status + search filtered server-side)
    var noticesById = {};      // keyed by id — used by the reading pane + Review modal
    var activeType = '';       // '' | memo | disciplinary  (client-side pill filter)
    var activeId = null;       // currently opened notice
    var didAutoOpen = false;   // auto-open the first notice once, on first load

    /* HR Attention deep link (?focus=over|atrisk|recs|nte). 'nte' narrows the notice list
       to disciplinary notices whose explanation was submitted but HR hasn't decided yet;
       the others scroll/reveal the relevant section (see applyDeepFocus below). */
    var deepFocus = (new URLSearchParams(window.location.search).get('focus') || '').toLowerCase();
    var focusNte = (deepFocus === 'nte');
    function isNteToReview(n) { return n.requires_response == 1 && !!n.response_at && !n.response_decision; }
    function visibleRows() { var r = typeRows(activeType); if (focusNte) r = r.filter(isNteToReview); return r; }

    // NTE state pill for a disciplinary notice that requires a written explanation.
    function nteStatusBadge(n) {
        if (n.response_decision === 'accepted') return '<span class="badge-soft" style="background:#dcfce7;color:#15803d;" title="Explanation accepted"><i class="fa-solid fa-check me-1"></i>NTE: Accepted</span>';
        if (n.response_decision === 'further_action') return '<span class="badge-soft b-disc" title="For further action"><i class="fa-solid fa-gavel me-1"></i>NTE: Further action</span>';
        if (n.response_at) return '<span class="badge-soft" style="background:#e0e7ff;color:#4338ca;" title="Awaiting HR review"><i class="fa-solid fa-inbox me-1"></i>NTE: Responded</span>';
        var overdue = n.respond_by && new Date(n.respond_by) < new Date(new Date().toDateString());
        return overdue
            ? '<span class="badge-soft b-disc" title="No response by ' + fmtDate(n.respond_by) + '"><i class="fa-solid fa-clock me-1"></i>NTE: Overdue</span>'
            : '<span class="badge-soft b-cat" title="Awaiting employee explanation' + (n.respond_by ? ' by ' + fmtDate(n.respond_by) : '') + '"><i class="fa-solid fa-hourglass-half me-1"></i>NTE: Awaiting</span>';
    }
    // Compact NTE tag for the list row.
    function nteMiniTag(n) {
        if (n.requires_response != 1) return '';
        if (n.response_decision === 'accepted') return '<span class="mini-tag t-nte-rev"><i class="fa-solid fa-check"></i>NTE</span>';
        if (n.response_decision === 'further_action') return '<span class="mini-tag t-nte-od"><i class="fa-solid fa-gavel"></i>NTE</span>';
        if (n.response_at) return '<span class="mini-tag t-nte"><i class="fa-solid fa-inbox"></i>Responded</span>';
        var overdue = n.respond_by && new Date(n.respond_by) < new Date(new Date().toDateString());
        return overdue ? '<span class="mini-tag t-nte-od"><i class="fa-solid fa-clock"></i>Overdue</span>'
                       : '<span class="mini-tag t-nte"><i class="fa-solid fa-hourglass-half"></i>Awaiting</span>';
    }

    /* ── Employees for picker ── */
    var empCache = [];              // /notices/employees rows, reused by the multi-select list
    var pickedEmps = new Set();     // checked empids — survives search re-renders

    function loadEmployees() {
        axios.get('/notices/employees').then(function (res) {
            empCache = res.data.data || [];
            var opts = '<option value="">Select employee…</option>';
            empCache.forEach(function (e) {
                opts += '<option value="' + esc(e.empid) + '">' + esc(e.name) + ' — ' + esc(e.dept) + '</option>';
            });
            $('#selEmployee').html(opts);
            renderEmpList('');
        });
    }

    /* ── Bulk recipient pickers ── */
    function filteredEmps(filter) {
        var f = (filter || '').toLowerCase();
        return !f ? empCache : empCache.filter(function (e) {
            return (e.name || '').toLowerCase().indexOf(f) !== -1 || (e.dept || '').toLowerCase().indexOf(f) !== -1;
        });
    }

    function renderEmpList(filter) {
        var rows = filteredEmps(filter);
        if (!rows.length) { $('#empCheckList').html('<div class="recip-empty">No employees match.</div>'); syncEmpCount(); return; }
        $('#empCheckList').html(rows.map(function (e) {
            var checked = pickedEmps.has(e.empid) ? ' checked' : '';
            return '<label class="recip-row"><input type="checkbox" class="chk-emp" value="' + esc(e.empid) + '"' + checked + '><span>' + esc(e.name) + '</span><span class="dept">' + esc(e.dept) + '</span></label>';
        }).join(''));
        syncEmpCount();
    }

    function syncEmpCount() { $('#empPickCount').text(pickedEmps.size); }
    function syncDeptCount() {
        $('#deptPickCount').text($('.chk-dept:checked').length);
        $('.chk-dept').each(function () { $(this).closest('.recip-chip').toggleClass('checked', this.checked); });
    }

    $(document).on('change', '.chk-emp', function () {
        if (this.checked) pickedEmps.add(this.value); else pickedEmps.delete(this.value);
        syncEmpCount();
    });
    $(document).on('change', '.chk-dept', syncDeptCount);

    var empSearchT;
    $(document).on('input', '#txtEmpSearch', function () {
        clearTimeout(empSearchT);
        var v = this.value;
        empSearchT = setTimeout(function () { renderEmpList(v); }, 200);
    });
    $(document).on('click', '#btnEmpAll', function () {
        filteredEmps($('#txtEmpSearch').val()).forEach(function (e) { pickedEmps.add(e.empid); });
        renderEmpList($('#txtEmpSearch').val());
    });
    $(document).on('click', '#btnEmpClear', function () {
        pickedEmps.clear();
        renderEmpList($('#txtEmpSearch').val());
    });
    $(document).on('click', '#btnDeptAll', function () { $('.chk-dept').prop('checked', true); syncDeptCount(); });
    $(document).on('click', '#btnDeptClear', function () { $('.chk-dept').prop('checked', false); syncDeptCount(); });

    function syncRecipientUI() {
        var mode = $('#selRecipientMode').val();
        $('#empSingleWrap').toggle(mode === 'single');
        $('#empMultiWrap').toggle(mode === 'employees');
        $('#deptMultiWrap').toggle(mode === 'department');
        $('#allWrap').toggle(mode === 'all');
    }
    $(document).on('change', '#selRecipientMode', syncRecipientUI);

    /* ── Notice list (rail) ── */
    function loadNotices(keepDetail) {
        axios.get('/notices/list', { params: { status: $('#fStatus').val(), search: $('#fSearch').val() } })
            .then(function (res) {
                allRows = res.data.data || [];
                noticesById = {};
                allRows.forEach(function (n) { noticesById[n.id] = n; });
                renderList();
                if (keepDetail && activeId != null && noticesById[activeId]) {
                    renderDetail(noticesById[activeId]);
                } else if (activeId != null && !noticesById[activeId]) {
                    activeId = null; renderEmptyDetail();
                } else if (!didAutoOpen && allRows.length) {
                    didAutoOpen = true;
                    var first = visibleRows()[0];
                    if (first) { activeId = first.id; renderList(); renderDetail(first); }
                }
            });
    }

    function typeRows(t) { return t ? allRows.filter(function (n) { return n.type === t; }) : allRows; }

    function renderList() {
        // Pill counts reflect the current status/search scope.
        $('#cAll').text(allRows.length);
        $('#cMemo').text(allRows.filter(function (n) { return n.type === 'memo'; }).length);
        $('#cDisc').text(allRows.filter(function (n) { return n.type === 'disciplinary'; }).length);

        var rows = visibleRows();
        if (!rows.length) {
            $('#noticeList').html('<div class="list-empty"><i class="fa-solid fa-inbox"></i>No notices found.</div>');
            return;
        }
        $('#noticeList').html(rows.map(rowHtml).join(''));
    }

    function rowHtml(n) {
        var isDisc = n.type === 'disciplinary';
        var cls = 'nrow ' + (isDisc ? 'disc' : 'memo') + (n.id == activeId ? ' active' : '') + (n.status === 'void' ? ' voided' : '');
        var icon = isDisc ? 'fa-gavel' : 'fa-file-lines';
        var meta = [];
        if (n.category) meta.push('<span class="badge-soft b-cat">' + esc(n.category) + '</span>');
        if (n.status === 'void') meta.push('<span class="badge-soft b-void">Void</span>');
        if (n.attachment_name) meta.push('<span class="clip"><i class="fa-solid fa-paperclip"></i></span>');
        if (isSeen(n)) meta.push('<span class="mini-tag t-seen"><i class="fa-solid fa-lock"></i>Seen</span>');
        if (n.type === 'disciplinary' && n.requires_ack == 1) meta.push(n.acknowledged_at
            ? '<span class="mini-tag t-seen" style="background:#dcfce7;color:#15803d;"><i class="fa-solid fa-signature"></i>Acknowledged</span>'
            : '<span class="mini-tag t-seen" style="background:#fef3c7;color:#b45309;"><i class="fa-solid fa-signature"></i>Ack pending</span>');
        var nte = nteMiniTag(n); if (nte) meta.push(nte);
        return '<div class="' + cls + '" data-id="' + n.id + '">' +
            '<div class="dot"><i class="fa-solid ' + icon + '"></i></div>' +
            '<div class="rmain">' +
                '<div class="rtop"><span class="rname">' + esc(n.employee_name || n.employee_id) + '</span>' +
                    '<span class="rdate">' + fmtDate(n.issued_at) + '</span></div>' +
                '<div class="rtitle">' + esc(n.title) + '</div>' +
                (meta.length ? '<div class="rmeta">' + meta.join('') + '</div>' : '') +
            '</div></div>';
    }

    $(document).on('click', '.nrow', function () {
        var id = $(this).data('id');
        activeId = id;
        $('.nrow').removeClass('active');
        $(this).addClass('active');
        if (noticesById[id]) renderDetail(noticesById[id]);
    });

    /* ── Reading pane ── */
    function renderEmptyDetail() {
        $('#noticeDetail').html('<div class="nd-empty"><i class="fa-solid fa-envelope-open-text"></i><div>Select a notice from the list to view it and take action.</div></div>');
    }

    function renderDetail(n) {
        var isDisc = n.type === 'disciplinary';
        var seen = isSeen(n);

        var badges = [ isDisc ? '<span class="badge-soft b-disc"><i class="fa-solid fa-gavel me-1"></i>Disciplinary</span>'
                              : '<span class="badge-soft b-memo"><i class="fa-solid fa-file-lines me-1"></i>Memo</span>',
            (n.status === 'void' ? '<span class="badge-soft b-void">Void</span>' : '<span class="badge-soft b-active">Active</span>') ];
        if (n.category) badges.push('<span class="badge-soft b-cat">' + esc(n.category) + '</span>');
        if (n.requires_response == 1) badges.push(nteStatusBadge(n));
        if (seen) badges.push('<span class="badge-soft b-void" title="Read by employee' + (n.read_at ? ' on ' + fmtDate(n.read_at) : '') + ' — locked"><i class="fa-solid fa-lock me-1"></i>Seen</span>');
        if (isDisc && n.requires_ack == 1) badges.push(n.acknowledged_at
            ? '<span class="badge-soft" style="background:#dcfce7;color:#15803d;" title="Receipt acknowledged by employee on ' + fmtDate(n.acknowledged_at) + '"><i class="fa-solid fa-signature me-1"></i>Acknowledged</span>'
            : '<span class="badge-soft" style="background:#fef3c7;color:#b45309;" title="Employee has not yet acknowledged receipt"><i class="fa-solid fa-hourglass-half me-1"></i>Not acknowledged</span>');

        var meta = '<div class="mi"><i class="fa-solid fa-user"></i><span class="mk">Employee:</span>&nbsp;<strong>' + esc(n.employee_name || n.employee_id) + '</strong> <span class="text-muted">(' + esc(n.employee_id) + ')</span></div>' +
            '<div class="mi"><i class="fa-solid fa-calendar-day"></i><span class="mk">Issued:</span>&nbsp;' + fmtDate(n.issued_at) + '</div>' +
            '<div class="mi"><i class="fa-solid fa-user-tie"></i><span class="mk">Issued by:</span>&nbsp;' + esc(n.issued_by || '—') + '</div>' +
            (n.requires_response == 1 && n.respond_by ? '<div class="mi"><i class="fa-solid fa-hourglass-half"></i><span class="mk">Respond by:</span>&nbsp;' + fmtDate(n.respond_by) + '</div>' : '') +
            (isDisc && n.acknowledged_at ? '<div class="mi"><i class="fa-solid fa-signature"></i><span class="mk">Acknowledged:</span>&nbsp;' + fmtDate(n.acknowledged_at) + (n.acknowledged_ip ? ' <span class="text-muted">(IP ' + esc(n.acknowledged_ip) + ')</span>' : '') + '</div>' : '');

        var html = '<div class="nd-head">' +
                '<div class="nd-badges">' + badges.join('') + '</div>' +
                '<h2 class="nd-title">' + esc(n.title) + '</h2>' +
                '<div class="nd-meta">' + meta + '</div>' +
            '</div>' +
            '<div class="nd-body">' + esc(n.body) + '</div>';

        // Signed-memo attachment — HR issued it, so HR may open/download it.
        if (n.attachment_name) {
            html += '<div class="nd-section-h">Signed Memo</div>' +
                '<div class="nd-attach"><div class="ai"><i class="fa-solid fa-file-signature"></i></div>' +
                    '<div><div class="an">' + esc(n.attachment_name) + '</div><div class="as">Scanned / signed document</div></div>' +
                    '<a href="/notices/' + n.id + '/memo" target="_blank"><i class="fa-solid fa-up-right-from-square"></i> Open</a></div>';
        }

        // NTE: employee's explanation + HR decision.
        if (n.requires_response == 1) {
            if (n.response_at) {
                var decision = '';
                if (n.response_decision === 'accepted') decision = '<div class="nte-decision ok"><i class="fa-solid fa-circle-check"></i> Explanation accepted' + (n.response_review_note ? ' — ' + esc(n.response_review_note) : '') + '</div>';
                else if (n.response_decision === 'further_action') decision = '<div class="nte-decision warn"><i class="fa-solid fa-gavel"></i> For further action' + (n.response_review_note ? ' — ' + esc(n.response_review_note) : '') + '</div>';
                html += '<div class="nte-box submitted">' +
                    '<div class="nte-h"><i class="fa-solid fa-file-pen"></i> Employee\'s Explanation</div>' +
                    '<div class="nte-sub">Submitted ' + fmtDate(n.response_at) + (n.respond_by ? ' · deadline was ' + fmtDate(n.respond_by) : '') + '</div>' +
                    '<div class="nte-resp">' + esc(n.response_body || '(no explanation text)') + '</div>' +
                    (n.response_doc_name ? '<a class="nte-doc" href="/notices/' + n.id + '/response-doc" target="_blank"><i class="fa-solid fa-paperclip"></i>' + esc(n.response_doc_name) + '</a>' : '') +
                    decision +
                '</div>';
            } else {
                var overdue = n.respond_by && new Date(n.respond_by) < new Date(new Date().toDateString());
                html += '<div class="nte-box' + (overdue ? ' od' : '') + '">' +
                    '<div class="nte-h"><i class="fa-solid fa-hourglass-half"></i> Awaiting Employee Explanation</div>' +
                    '<div class="nte-sub">' + (overdue ? 'Overdue — was due ' + fmtDate(n.respond_by) : (n.respond_by ? 'Due ' + fmtDate(n.respond_by) : 'No deadline set')) + '</div>' +
                '</div>';
            }
        }

        // Action bar.
        var acts = '';
        var reviewBtn = (n.requires_response == 1 && n.response_at)
            ? '<button class="btn-act review" id="dReview"><i class="fa-solid fa-file-pen"></i> ' + (n.response_decision ? 'Re-review' : 'Review Explanation') + '</button>'
            : '';
        if (seen) {
            acts = '<span class="lock"><i class="fa-solid fa-lock"></i> Read by the employee — this notice is locked and can no longer be edited or deleted.</span>' +
                   '<div class="spacer"></div>' + reviewBtn;
        } else {
            acts = reviewBtn + '<div class="spacer"></div>' +
                '<button class="btn-act danger" id="dDelete" data-id="' + n.id + '"><i class="fa-solid fa-trash"></i> Delete</button>' +
                '<button class="btn-act primary" id="dEdit"><i class="fa-solid fa-pencil"></i> Edit</button>';
        }
        html += '<div class="nd-actions">' + acts + '</div>';

        $('#noticeDetail').html(html);
    }

    /* ── Recommendations ── */
    function loadRecs() {
        axios.get('/notices/recommendations').then(function (res) {
            var rows = res.data.data || [];
            $('#recCount').text(rows.length);
            if (!rows.length) { $('#recList').html('<div class="rec-row"><span class="text-muted">No pending suspension recommendations.</span></div>'); return; }
            $('#recList').html(rows.map(function (r) {
                return '<div class="rec-row">' +
                    '<div><div class="who text-capitalize">' + esc(r.name) + ' <span class="badge-soft b-disc ms-1">' + r.notice_count + ' notices</span></div>' +
                    '<div class="why">' + esc(r.reason) + '</div>' +
                    '<div class="text-muted" style="font-size:.68rem;margin-top:3px;">Recommended ' + esc(r.recommended_at || '') + '</div></div>' +
                    '<div class="text-nowrap">' +
                        '<button class="btn-mini warn btn-dismiss me-1" data-id="' + r.id + '"><i class="fa-solid fa-xmark"></i> Dismiss</button>' +
                        '<button class="btn-mini ok btn-action" data-id="' + r.id + '"><i class="fa-solid fa-check"></i> Mark Actioned</button>' +
                    '</div></div>';
            }).join(''));
        });
    }
    $(document).on('click', '#recToggle', function () { $('#recCard').toggleClass('collapsed'); });

    /* ── Modal ── */
    function resetModal() {
        $('#noticeId').val(''); $('#selEmployee').val(''); $('#selType').val('memo');
        $('#selCategory').val(''); $('#txtIssuedAt').val(''); $('#txtTitle').val(''); $('#txtBody').val('');
        $('#selStatus').val('active'); $('.text-danger.small').text('');
        $('#catWrap').hide(); $('#statusWrap').hide(); $('#selEmployee').prop('disabled', false);
        $('#recipientModeWrap').show();
        $('#selRecipientMode').val('single').find('option').prop('disabled', false);
        pickedEmps.clear(); $('#txtEmpSearch').val(''); renderEmpList('');
        $('.chk-dept').prop('checked', false); syncDeptCount();
        $('#fileAttachment').val(''); $('#attachCurrent').hide(); $('#attachCurrentName').text(''); $('#attachPreviewLink').attr('href', '#');
        $('#chkRequiresResponse').prop('checked', false); $('#respondByWrap').hide(); $('#txtRespondBy').val('');
        $('#chkRequiresAck').prop('checked', false);
        syncRecipientUI();
    }

    function syncTypeUI() {
        var disc = $('#selType').val() === 'disciplinary';
        $('#catWrap').toggle(disc);
        // Signed-memo attachment is memo-only; Notice-to-Explain is disciplinary-only.
        $('#attachWrap').toggle(!disc);
        $('#nteWrap').toggle(disc);
        if (!disc) { $('#chkRequiresResponse').prop('checked', false); $('#respondByWrap').hide(); $('#chkRequiresAck').prop('checked', false); }
        // Bulk modes are memo-only: disciplinary snaps back to a single recipient.
        if (disc && $('#selRecipientMode').val() !== 'single') {
            $('#selRecipientMode').val('single');
            syncRecipientUI();
        }
        $('#selRecipientMode option').not('[value="single"]').prop('disabled', disc);
    }
    $(document).on('change', '#selType', syncTypeUI);
    $(document).on('change', '#chkRequiresResponse', function () { $('#respondByWrap').toggle(this.checked); });

    $(document).on('click', '#btnIssueNotice', function () {
        resetModal(); $('#mdlTitle').text('Issue Notice');
        new bootstrap.Modal(document.getElementById('mdlNotice')).show();
    });

    // Populate + open the Issue/Edit modal for an existing notice.
    function openEditModal(n) {
        resetModal(); $('#mdlTitle').text('Edit Notice');
        $('#recipientModeWrap').hide();   // edit is always single-recipient
        $('#noticeId').val(n.id);
        $('#selEmployee').val(n.employee_id).prop('disabled', true);
        $('#selType').val(n.type); $('#selCategory').val(n.category || '');
        $('#txtIssuedAt').val(n.issued_at ? n.issued_at.substring(0, 10) : '');
        $('#txtTitle').val(n.title); $('#txtBody').val(n.body);
        $('#selStatus').val(n.status); $('#statusWrap').show();
        if (n.attachment_name) {
            $('#attachCurrentName').text(n.attachment_name);
            $('#attachPreviewLink').attr('href', '/notices/' + n.id + '/memo');
            $('#attachCurrent').show();
        }
        if (n.requires_response == 1) {
            $('#chkRequiresResponse').prop('checked', true);
            $('#txtRespondBy').val(n.respond_by ? String(n.respond_by).substring(0, 10) : '');
        }
        $('#chkRequiresAck').prop('checked', n.requires_ack == 1);
        syncTypeUI();
        $('#respondByWrap').toggle($('#chkRequiresResponse').is(':checked'));
        new bootstrap.Modal(document.getElementById('mdlNotice')).show();
    }

    // Reading-pane Edit button.
    $(document).on('click', '#dEdit', function () {
        if (activeId != null && noticesById[activeId]) openEditModal(noticesById[activeId]);
    });

    function bulkConfirmText(mode) {
        if (mode === 'all') return 'Send this memo to ALL active employees?';
        if (mode === 'department') {
            var d = $('.chk-dept:checked').length;
            return 'Send this memo to all active employees in ' + d + ' department' + (d === 1 ? '' : 's') + '?';
        }
        return 'Send this memo to ' + pickedEmps.size + ' selected employee' + (pickedEmps.size === 1 ? '' : 's') + '?';
    }

    function submitNotice($btn) {
        $('.text-danger.small').text('');
        var mode = $('#noticeId').val() ? 'single' : $('#selRecipientMode').val();

        // Multipart, because a memo may carry a scanned/signed file attachment.
        var fd = new FormData();
        if ($('#noticeId').val()) fd.append('id', $('#noticeId').val());
        fd.append('recipient_mode', mode);
        fd.append('employee_id', $('#selEmployee').val() || '');
        if (mode === 'employees') Array.from(pickedEmps).forEach(function (v) { fd.append('employee_ids[]', v); });
        if (mode === 'department') $('.chk-dept:checked').each(function () { fd.append('department_ids[]', this.value); });
        fd.append('type', $('#selType').val());
        fd.append('category', $('#selCategory').val() || '');
        fd.append('title', $('#txtTitle').val().trim());
        fd.append('body', $('#txtBody').val().trim());
        if ($('#txtIssuedAt').val()) fd.append('issued_at', $('#txtIssuedAt').val());
        fd.append('status', $('#selStatus').val() || 'active');
        var f = document.getElementById('fileAttachment').files[0];
        if (f) fd.append('attachment', f);
        fd.append('requires_response', $('#chkRequiresResponse').is(':checked') ? 1 : 0);
        if ($('#txtRespondBy').val()) fd.append('respond_by', $('#txtRespondBy').val());
        fd.append('requires_ack', $('#chkRequiresAck').is(':checked') ? 1 : 0);

        $btn.prop('disabled', true);
        axios.post('/notices/save', fd).then(function (res) {
            if (res.data.status === 201) {
                $.each(res.data.error, function (k, v) {
                    var $el = $('#err-' + k.replace('.', '-'));
                    if ($el.length) { $el.text(v[0]); } else { toast(v[0], 'error'); }
                });
                return;
            }
            if (res.data.status === 200) {
                bootstrap.Modal.getInstance(document.getElementById('mdlNotice')).hide();
                toast(res.data.msg, 'success');
                loadNotices(true); loadRecs();
            } else if (res.data.status === 202) {
                // Locked (e.g. employee read it meanwhile) — refresh so the row shows Seen.
                bootstrap.Modal.getInstance(document.getElementById('mdlNotice')).hide();
                toast(res.data.msg || 'This notice can no longer be edited.', 'error');
                loadNotices(true);
            } else { toast(res.data.msg || 'Error.', 'error'); }
        }).catch(function () { toast('Error saving notice.', 'error'); })
          .then(function () { $btn.prop('disabled', false); });
    }

    $(document).on('click', '#btnSaveNotice', function () {
        var $btn = $(this);
        var mode = $('#noticeId').val() ? 'single' : $('#selRecipientMode').val();
        if (mode === 'single') { submitNotice($btn); return; }
        Swal.fire({
            title: 'Bulk send?',
            text: bulkConfirmText(mode),
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#008080',
            confirmButtonText: 'Send'
        }).then(function (r) { if (r.isConfirmed) submitNotice($btn); });
    });

    $(document).on('click', '#dDelete', function () {
        var id = $(this).data('id');
        Swal.fire({ title: 'Delete notice?', text: 'This permanently removes the notice.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Delete' })
            .then(function (r) {
                if (!r.isConfirmed) return;
                axios.post('/notices/delete', { id: id }).then(function (res) {
                    toast(res.data.msg, res.data.status === 200 ? 'success' : 'error');
                    if (res.data.status === 200) { activeId = null; renderEmptyDetail(); }
                    loadNotices(res.data.status !== 200); loadRecs();
                });
            });
    });

    /* ── Review Explanation (NTE) ── */
    $(document).on('click', '#dReview', function () {
        var n = noticesById[activeId];
        if (!n) return;
        $('.text-danger.small').text('');
        $('#reviewId').val(n.id);
        $('#revNoticeTitle').text(n.title || '');
        $('#revNoticeMeta').text((n.employee_name || n.employee_id) + ' · ' + (n.category || 'Disciplinary'));
        $('#revResponseBody').text(n.response_body || '(no explanation text)');
        $('#revResponseAt').text('Submitted ' + fmtDate(n.response_at) + (n.respond_by ? ' · deadline was ' + fmtDate(n.respond_by) : ''));
        if (n.response_doc_name) {
            $('#revDocName').text(n.response_doc_name);
            $('#revDocLink').attr('href', '/notices/' + n.id + '/response-doc');
            $('#revResponseDoc').show();
        } else { $('#revResponseDoc').hide(); }
        $('#selDecision').val(n.response_decision || '');
        $('#txtReviewNote').val(n.response_review_note || '');
        new bootstrap.Modal(document.getElementById('mdlReview')).show();
    });

    $(document).on('click', '#btnSaveReview', function () {
        var $btn = $(this), id = $('#reviewId').val();
        $('.text-danger.small').text('');
        $btn.prop('disabled', true);
        axios.post('/notices/' + id + '/review', { decision: $('#selDecision').val(), note: $('#txtReviewNote').val() })
            .then(function (res) {
                if (res.data.status === 201) { $.each(res.data.error, function (k, v) { var $el = $('#err-' + k); if ($el.length) $el.text(v[0]); else toast(v[0], 'error'); }); return; }
                if (res.data.status === 200) {
                    bootstrap.Modal.getInstance(document.getElementById('mdlReview')).hide();
                    toast(res.data.msg, 'success'); loadNotices(true);
                } else { toast(res.data.msg || 'Error.', 'error'); }
            }).catch(function () { toast('Error saving decision.', 'error'); })
              .then(function () { $btn.prop('disabled', false); });
    });

    function resolveRec(id, action, label) {
        Swal.fire({ title: label + '?', input: 'text', inputPlaceholder: 'Note (optional)', icon: 'question', showCancelButton: true, confirmButtonText: label })
            .then(function (r) {
                if (!r.isConfirmed) return;
                axios.post('/notices/recommendation/resolve', { id: id, action: action, note: r.value || '' })
                    .then(function (res) { toast(res.data.msg, res.data.status === 200 ? 'success' : 'error'); loadRecs(); });
            });
    }
    $(document).on('click', '.btn-dismiss', function () { resolveRec($(this).data('id'), 'dismissed', 'Dismiss'); });
    $(document).on('click', '.btn-action', function () { resolveRec($(this).data('id'), 'actioned', 'Mark Actioned'); });

    /* ── Filters ── */
    $(document).on('click', '.pill', function () {
        $('.pill').removeClass('active');
        $(this).addClass('active');
        activeType = $(this).data('filter') || '';
        renderList();
    });
    var t;
    $(document).on('input', '#fSearch', function () { clearTimeout(t); t = setTimeout(function () { loadNotices(true); }, 300); });
    $(document).on('change', '#fStatus', function () { loadNotices(true); });

    /* ── HR Attention deep-link behaviors ── */
    function scrollPulse(sel) {
        var el = document.querySelector(sel);
        if (!el) return;
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        el.classList.add('attn-pulse');
        setTimeout(function () { el.classList.remove('attn-pulse'); }, 2400);
    }
    function renderFocusBanner() {
        if (!focusNte) { $('#ntcFocusFlash').empty(); return; }
        $('#ntcFocusFlash').html(
            '<div class="focus-flash">' +
                '<i class="fa-solid fa-file-pen"></i>' +
                '<span>Showing only <b>NTE explanations awaiting your review</b>.</span>' +
                '<button type="button" id="focusFlashClear" class="focus-flash-x">Show all</button>' +
            '</div>');
    }
    $(document).on('click', '#focusFlashClear', function () {
        focusNte = false;
        renderFocusBanner();
        renderList();
        var first = visibleRows()[0];
        if (first) { activeId = first.id; renderList(); renderDetail(first); }
    });
    // One-shot: scroll/reveal the section the panel pointed us at.
    (function applyDeepFocus() {
        if (deepFocus === 'over')        scrollPulse('#ntcOverBanner');
        else if (deepFocus === 'atrisk') { $('#ntcAtRiskBanner').show(); scrollPulse('#ntcAtRiskBanner'); }
        else if (deepFocus === 'recs')   { $('#recCard').removeClass('collapsed'); scrollPulse('#recCard'); }
        else if (deepFocus === 'nte')    renderFocusBanner();
    })();

    loadEmployees(); loadNotices(); loadRecs();
});
