/* Notices & Memos — HR admin. jQuery + axios + SweetAlert2 (global). */
$(document).ready(function () {

    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && window.axios) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function toast(t, i) { if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 2200, showConfirmButton: false, icon: i || 'success', title: t }); }
    function fmtDate(d) { if (!d) return '—'; var dt = new Date(d); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }); }

    /* ── Employees for picker ── */
    function loadEmployees() {
        axios.get('/notices/employees').then(function (res) {
            var opts = '<option value="">Select employee…</option>';
            (res.data.data || []).forEach(function (e) {
                opts += '<option value="' + esc(e.empid) + '">' + esc(e.name) + ' — ' + esc(e.dept) + '</option>';
            });
            $('#selEmployee').html(opts);
        });
    }

    /* ── Notices table ── */
    function loadNotices() {
        axios.get('/notices/list', { params: { type: $('#fType').val(), status: $('#fStatus').val(), search: $('#fSearch').val() } })
            .then(function (res) {
                var rows = res.data.data || [];
                if (!rows.length) { $('#tblNotices').html('<tr class="empty-row"><td colspan="8">No notices found.</td></tr>'); return; }
                $('#tblNotices').html(rows.map(function (n) {
                    var typeBadge = n.type === 'disciplinary'
                        ? '<span class="badge-soft b-disc">Disciplinary</span>'
                        : '<span class="badge-soft b-memo">Memo</span>';
                    var statusBadge = n.status === 'void'
                        ? '<span class="badge-soft b-void">Void</span>'
                        : '<span class="badge-soft b-active">Active</span>';
                    var cat = n.category ? '<span class="badge-soft b-cat">' + esc(n.category) + '</span>' : '<span class="text-muted">—</span>';
                    return '<tr>' +
                        '<td><strong>' + esc(n.employee_name || n.employee_id) + '</strong><div class="text-muted" style="font-size:.68rem;">' + esc(n.employee_id) + '</div></td>' +
                        '<td>' + typeBadge + '</td>' +
                        '<td>' + esc(n.title) + '</td>' +
                        '<td>' + cat + '</td>' +
                        '<td>' + fmtDate(n.issued_at) + '</td>' +
                        '<td class="text-muted" style="font-size:.78rem;">' + esc(n.issued_by || '—') + '</td>' +
                        '<td>' + statusBadge + '</td>' +
                        '<td class="text-end pe-4">' +
                            '<button class="btn-mini edit btn-edit me-1" data-json=\'' + JSON.stringify(n).replace(/'/g, '&#39;') + '\'><i class="fa-solid fa-pencil"></i></button>' +
                            '<button class="btn-mini del btn-del" data-id="' + n.id + '"><i class="fa-solid fa-trash"></i></button>' +
                        '</td></tr>';
                }).join(''));
            });
    }

    /* ── Recommendations ── */
    function loadRecs() {
        axios.get('/notices/recommendations').then(function (res) {
            var rows = res.data.data || [];
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

    /* ── Modal ── */
    function resetModal() {
        $('#noticeId').val(''); $('#selEmployee').val(''); $('#selType').val('memo');
        $('#selCategory').val(''); $('#txtIssuedAt').val(''); $('#txtTitle').val(''); $('#txtBody').val('');
        $('#selStatus').val('active'); $('.text-danger.small').text('');
        $('#catWrap').hide(); $('#statusWrap').hide(); $('#selEmployee').prop('disabled', false);
    }

    function syncTypeUI() { $('#catWrap').toggle($('#selType').val() === 'disciplinary'); }
    $(document).on('change', '#selType', syncTypeUI);

    $(document).on('click', '#btnIssueNotice', function () {
        resetModal(); $('#mdlTitle').text('Issue Notice');
        new bootstrap.Modal(document.getElementById('mdlNotice')).show();
    });

    $(document).on('click', '.btn-edit', function () {
        var n = JSON.parse($(this).attr('data-json').replace(/&#39;/g, "'"));
        resetModal(); $('#mdlTitle').text('Edit Notice');
        $('#noticeId').val(n.id);
        $('#selEmployee').val(n.employee_id).prop('disabled', true);
        $('#selType').val(n.type); $('#selCategory').val(n.category || '');
        $('#txtIssuedAt').val(n.issued_at ? n.issued_at.substring(0, 10) : '');
        $('#txtTitle').val(n.title); $('#txtBody').val(n.body);
        $('#selStatus').val(n.status); $('#statusWrap').show();
        syncTypeUI();
        new bootstrap.Modal(document.getElementById('mdlNotice')).show();
    });

    $(document).on('click', '#btnSaveNotice', function () {
        $('.text-danger.small').text('');
        var payload = {
            id: $('#noticeId').val() || null,
            employee_id: $('#selEmployee').val(),
            type: $('#selType').val(),
            category: $('#selCategory').val(),
            title: $('#txtTitle').val().trim(),
            body: $('#txtBody').val().trim(),
            issued_at: $('#txtIssuedAt').val() || null,
            status: $('#selStatus').val()
        };
        var $btn = $(this).prop('disabled', true);
        axios.post('/notices/save', payload).then(function (res) {
            if (res.data.status === 201) {
                $.each(res.data.error, function (k, v) { $('#err-' + k.replace('.', '-')).text(v[0]); });
                return;
            }
            if (res.data.status === 200) {
                bootstrap.Modal.getInstance(document.getElementById('mdlNotice')).hide();
                toast(res.data.msg, 'success');
                loadNotices(); loadRecs();
            } else { toast(res.data.msg || 'Error.', 'error'); }
        }).catch(function () { toast('Error saving notice.', 'error'); })
          .then(function () { $btn.prop('disabled', false); });
    });

    $(document).on('click', '.btn-del', function () {
        var id = $(this).data('id');
        Swal.fire({ title: 'Delete notice?', text: 'This permanently removes the notice.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Delete' })
            .then(function (r) {
                if (!r.isConfirmed) return;
                axios.post('/notices/delete', { id: id }).then(function (res) { toast(res.data.msg, res.data.status === 200 ? 'success' : 'error'); loadNotices(); loadRecs(); });
            });
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
    var t;
    $(document).on('input', '#fSearch', function () { clearTimeout(t); t = setTimeout(loadNotices, 300); });
    $(document).on('change', '#fType, #fStatus', loadNotices);

    loadEmployees(); loadNotices(); loadRecs();
});
