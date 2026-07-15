/* Programs Management — tenure-milestone benefits (workspace layout).
 * Left rail lists milestone programs; the right pane shows the selected
 * milestone's benefits, recipients (grant/revoke), and upcoming anniversaries.
 * Uses jQuery + axios + SweetAlert2 (all loaded globally). */
$(document).ready(function () {

    // Ensure CSRF header is present for axios POSTs (defensive).
    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && window.axios) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');
    }

    var PROGRAMS = [];      // milestone config (id, title, years_required, description, is_active, benefits[])
    var REACHED  = [];      // recipients who reached a milestone (program_id, status, …)
    var UPCOMING = [];      // upcoming anniversaries (program_id, …)
    var selectedId = null;  // currently open milestone
    var mFilter = 'all';    // rail filter: all | active | inactive
    var recipFilter = 'all';// detail recipients filter: all | pending | granted
    var search = '';

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

    function toast(title, icon) {
        if (window.Swal) {
            Swal.fire({ toast: true, position: 'top-end', timer: 2200, showConfirmButton: false, icon: icon || 'success', title: title });
        }
    }

    function benefitChips(list) {
        if (!list || !list.length) return '<span class="text-muted small">&mdash;</span>';
        return list.map(function (b) { return '<span class="chip">' + esc(b) + '</span>'; }).join('');
    }

    /* ── Data loading ────────────────────────────────────────────────── */
    function loadAll() {
        return Promise.all([
            axios.get('/programs/list'),
            axios.get('/programs/eligibility')
        ]).then(function (res) {
            PROGRAMS = res[0].data.data || [];

            var d = res[1].data.data || {};
            REACHED  = d.reached || [];
            UPCOMING = d.upcoming || [];
            var s = d.stats || {};
            $('#statPrograms').text(s.programs || 0);
            $('#statPending').text(s.pendingCount || 0);
            $('#statGranted').text(s.grantedCount || 0);
            $('#statUpcoming').text(s.upcomingCount || 0);

            // keep the same milestone open across refreshes when possible
            if (selectedId && !PROGRAMS.some(function (p) { return p.id == selectedId; })) {
                selectedId = null;
            }
            renderRail();
            renderDetail();
        }).catch(function () {
            $('#programList').html('<div class="list-empty"><i class="fa-solid fa-triangle-exclamation"></i>Failed to load milestones.</div>');
        });
    }

    // Recipient counts per program, for the rail meta line.
    function recipCounts(programId) {
        var pend = 0, grant = 0;
        REACHED.forEach(function (r) {
            if (r.program_id != programId) return;
            r.status === 'granted' ? grant++ : pend++;
        });
        return { pending: pend, granted: grant };
    }

    /* ── Left rail: milestone list ───────────────────────────────────── */
    function renderRail() {
        var active = PROGRAMS.filter(function (p) { return p.is_active; }).length;
        $('#cAll').text(PROGRAMS.length);
        $('#cActive').text(active);
        $('#cInactive').text(PROGRAMS.length - active);

        var rows = PROGRAMS.filter(function (p) {
            if (mFilter === 'active' && !p.is_active) return false;
            if (mFilter === 'inactive' && p.is_active) return false;
            if (search) {
                var hay = (p.title + ' ' + (p.description || '')).toLowerCase();
                if (hay.indexOf(search) === -1) return false;
            }
            return true;
        });

        if (!rows.length) {
            $('#programList').html('<div class="list-empty"><i class="fa-solid fa-award"></i>' +
                (PROGRAMS.length ? 'No milestones match your filter.' : 'No milestones yet. Click <strong>Add Milestone</strong>.') + '</div>');
            return;
        }

        var html = rows.map(function (p) {
            var c = recipCounts(p.id);
            var meta = [];
            meta.push('<span class="mini-tag t-off"><i class="fa-solid fa-gift"></i>' + (p.benefits || []).length + '</span>');
            if (c.pending) meta.push('<span class="mini-tag t-pend">' + c.pending + ' pending</span>');
            if (c.granted) meta.push('<span class="mini-tag t-grant">' + c.granted + ' granted</span>');
            if (!p.is_active) meta.push('<span class="mini-tag t-off">Inactive</span>');

            return '<div class="prow' + (p.id == selectedId ? ' active' : '') + (p.is_active ? '' : ' inactive') + '" data-id="' + p.id + '">' +
                '<div class="dot"><i class="fa-solid fa-medal"></i></div>' +
                '<div class="rmain">' +
                    '<div class="rtop"><span class="rname">' + esc(p.title) + '</span>' +
                        '<span class="ryrs">' + parseFloat(p.years_required) + ' yrs</span></div>' +
                    '<div class="rmeta">' + meta.join('') + '</div>' +
                '</div>' +
            '</div>';
        }).join('');
        $('#programList').html(html);
    }

    /* ── Right pane: selected milestone detail ───────────────────────── */
    function renderDetail() {
        if (!selectedId) {
            $('#programDetail').html('<div class="pd-empty"><i class="fa-solid fa-trophy"></i><div>Select a milestone to view its recipients and upcoming anniversaries.</div></div>');
            return;
        }
        var p = PROGRAMS.find(function (x) { return x.id == selectedId; });
        if (!p) { selectedId = null; renderDetail(); return; }

        var benefits = (p.benefits || []).map(function (b) {
            return '<span class="chip">' + esc(b.name) + (b.description ? ' (' + esc(b.description) + ')' : '') + '</span>';
        }).join('') || '<span class="text-muted small">No benefits defined.</span>';

        var statusBadge = p.is_active
            ? '<span class="badge-soft badge-granted"><i class="fa-solid fa-circle-check me-1"></i>Active</span>'
            : '<span class="badge-soft badge-inactive">Inactive</span>';

        // Recipients for this milestone.
        var recips = REACHED.filter(function (r) { return r.program_id == p.id; });
        var pend = recips.filter(function (r) { return r.status !== 'granted'; }).length;
        var grant = recips.filter(function (r) { return r.status === 'granted'; }).length;

        var shown = recips.filter(function (r) {
            return recipFilter === 'all' || r.status === recipFilter;
        });

        var recipRows = shown.length ? shown.map(function (r) {
            var badge = r.status === 'granted'
                ? '<span class="badge-soft badge-granted"><i class="fa-solid fa-check me-1"></i>Granted</span>'
                : '<span class="badge-soft badge-pending"><i class="fa-solid fa-hourglass-half me-1"></i>Pending</span>';
            var grantedMeta = r.status === 'granted' && r.granted_at
                ? '<div class="text-muted" style="font-size:.68rem;margin-top:3px;">' + esc(r.granted_at) + (r.granted_by ? ' &middot; ' + esc(r.granted_by) : '') + '</div>'
                : '';
            var action = r.status === 'granted'
                ? '<button class="btn-mini revoke btn-revoke" data-program="' + r.program_id + '" data-emp="' + esc(r.employee_id) + '"><i class="fa-solid fa-rotate-left"></i> Revert</button>'
                : '<button class="btn-mini grant btn-grant" data-program="' + r.program_id + '" data-emp="' + esc(r.employee_id) + '" data-name="' + esc(r.name) + '" data-program-name="' + esc(r.program) + '"><i class="fa-solid fa-gift"></i> Mark Granted</button>';
            return '<tr>' +
                '<td><strong>' + esc(r.name) + '</strong><div class="text-muted" style="font-size:.68rem;">' + esc(r.employee_id) + '</div></td>' +
                '<td>' + esc(r.dept) + '</td>' +
                '<td><span class="tenure-pill">' + r.tenure.toFixed(2) + ' yrs</span></td>' +
                '<td>' + badge + grantedMeta + '</td>' +
                '<td class="text-end">' + action + '</td>' +
            '</tr>';
        }).join('') : '<tr class="empty-row"><td colspan="5">No employees in this view yet.</td></tr>';

        // Upcoming anniversaries for this milestone.
        var up = UPCOMING.filter(function (r) { return r.program_id == p.id; });
        var upRows = up.length ? up.map(function (r) {
            return '<tr>' +
                '<td><strong>' + esc(r.name) + '</strong><div class="text-muted" style="font-size:.68rem;">' + esc(r.employee_id) + '</div></td>' +
                '<td>' + esc(r.dept) + '</td>' +
                '<td><span class="tenure-pill">' + r.tenure.toFixed(2) + ' yrs</span></td>' +
                '<td><span class="badge-soft badge-pending">in ' + r.days + ' day' + (r.days === 1 ? '' : 's') + '</span>' +
                    '<div class="text-muted" style="font-size:.68rem;margin-top:3px;">' + esc(r.date) + '</div></td>' +
            '</tr>';
        }).join('') : '<tr class="empty-row"><td colspan="4">No upcoming anniversaries in the next 60 days.</td></tr>';

        var html =
            '<div class="pd-head">' +
                '<div class="pd-badges">' +
                    statusBadge +
                    '<span class="tenure-pill">' + parseFloat(p.years_required) + ' yr threshold</span>' +
                    '<div class="pd-actions">' +
                        '<button class="btn-mini edit btn-edit" data-id="' + p.id + '"><i class="fa-solid fa-pencil"></i> Edit</button>' +
                        '<button class="btn-mini del btn-del" data-id="' + p.id + '" data-title="' + esc(p.title) + '"><i class="fa-solid fa-trash"></i></button>' +
                    '</div>' +
                '</div>' +
                '<h3 class="pd-title">' + esc(p.title) + '</h3>' +
                (p.description ? '<div class="pd-desc">' + esc(p.description) + '</div>' : '') +
            '</div>' +

            '<div class="pd-benefits">' +
                '<div class="pd-sec-h"><i class="fa-solid fa-gift"></i> Benefits <span class="cnt">' + (p.benefits || []).length + '</span></div>' +
                benefits +
            '</div>' +

            '<div class="pd-body">' +
                '<div class="pd-recip-tools">' +
                    '<div class="pd-sec-h" style="margin:0;"><i class="fa-solid fa-users"></i> Milestone Recipients <span class="cnt">' + recips.length + '</span></div>' +
                    '<div class="prog-pills">' +
                        '<button class="pill rpill' + (recipFilter === 'all' ? ' active' : '') + '" data-rfilter="all">All <span class="pc">' + recips.length + '</span></button>' +
                        '<button class="pill rpill' + (recipFilter === 'pending' ? ' active' : '') + '" data-rfilter="pending">Pending <span class="pc">' + pend + '</span></button>' +
                        '<button class="pill rpill' + (recipFilter === 'granted' ? ' active' : '') + '" data-rfilter="granted">Granted <span class="pc">' + grant + '</span></button>' +
                    '</div>' +
                '</div>' +
                '<div class="table-responsive" style="border:1px solid var(--border); border-radius:10px; overflow:hidden;">' +
                    '<table class="prog-table"><thead><tr>' +
                        '<th>Employee</th><th>Department</th><th>Tenure</th><th>Status</th><th class="text-end">Action</th>' +
                    '</tr></thead><tbody>' + recipRows + '</tbody></table>' +
                '</div>' +

                '<div class="pd-sec-h" style="margin:22px 0 10px;"><i class="fa-solid fa-calendar-day"></i> Upcoming Anniversaries <span class="cnt">' + up.length + '</span></div>' +
                '<div class="table-responsive" style="border:1px solid var(--border); border-radius:10px; overflow:hidden;">' +
                    '<table class="prog-table"><thead><tr>' +
                        '<th>Employee</th><th>Department</th><th>Current Tenure</th><th>When</th>' +
                    '</tr></thead><tbody>' + upRows + '</tbody></table>' +
                '</div>' +
            '</div>';

        $('#programDetail').html(html);
    }

    /* ── Rail interactions ───────────────────────────────────────────── */
    $(document).on('click', '.prow', function () {
        selectedId = $(this).data('id');
        recipFilter = 'all';
        renderRail();
        renderDetail();
    });

    $(document).on('click', '.pill[data-mfilter]', function () {
        $('.pill[data-mfilter]').removeClass('active');
        $(this).addClass('active');
        mFilter = $(this).data('mfilter');
        renderRail();
    });

    $(document).on('input', '#progSearch', function () {
        search = $(this).val().trim().toLowerCase();
        renderRail();
    });

    // Detail recipient filter (re-rendered each time detail paints).
    $(document).on('click', '.rpill', function () {
        recipFilter = $(this).data('rfilter');
        renderDetail();
    });

    /* ── Modal: benefit rows ─────────────────────────────────────────── */
    function benefitRow(name, desc) {
        return '<div class="benefit-row">' +
            '<input type="text" class="form-control bn-name" placeholder="Benefit (e.g. Bigas / Rice)" value="' + esc(name || '') + '">' +
            '<input type="text" class="form-control bn-desc" placeholder="Qty / details (optional)" value="' + esc(desc || '') + '">' +
            '<button type="button" class="btn-row-del bn-del"><i class="fa-solid fa-xmark"></i></button>' +
            '</div>';
    }

    function resetModal() {
        $('#programId').val('');
        $('#txtTitle').val('');
        $('#txtYears').val('');
        $('#txtDesc').val('');
        $('#chkActive').prop('checked', true);
        $('#benefitRows').html(benefitRow('', ''));
        $('.text-danger.small').text('');
    }

    $(document).on('click', '#btnAddProgram', function () {
        resetModal();
        $('#mdlTitle').text('Add Milestone');
        new bootstrap.Modal(document.getElementById('mdlProgram')).show();
    });

    $(document).on('click', '#btnAddBenefit', function () {
        $('#benefitRows').append(benefitRow('', ''));
    });

    $(document).on('click', '.bn-del', function () {
        if ($('.benefit-row').length > 1) {
            $(this).closest('.benefit-row').remove();
        } else {
            $(this).closest('.benefit-row').find('input').val('');
        }
    });

    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        var p = PROGRAMS.find(function (x) { return x.id == id; });
        if (!p) return;
        resetModal();
        $('#mdlTitle').text('Edit Milestone');
        $('#programId').val(p.id);
        $('#txtTitle').val(p.title);
        $('#txtYears').val(parseFloat(p.years_required));
        $('#txtDesc').val(p.description || '');
        $('#chkActive').prop('checked', !!p.is_active);
        var rows = (p.benefits || []).map(function (b) { return benefitRow(b.name, b.description); }).join('');
        $('#benefitRows').html(rows || benefitRow('', ''));
        new bootstrap.Modal(document.getElementById('mdlProgram')).show();
    });

    /* ── Save milestone ──────────────────────────────────────────────── */
    $(document).on('click', '#btnSaveProgram', function () {
        $('.text-danger.small').text('');
        var benefits = [];
        $('#benefitRows .benefit-row').each(function () {
            var name = $(this).find('.bn-name').val().trim();
            var desc = $(this).find('.bn-desc').val().trim();
            if (name) benefits.push({ name: name, description: desc });
        });

        var payload = {
            id: $('#programId').val() || null,
            title: $('#txtTitle').val().trim(),
            years_required: $('#txtYears').val(),
            description: $('#txtDesc').val().trim(),
            is_active: $('#chkActive').is(':checked') ? 1 : 0,
            benefits: benefits
        };

        var $btn = $(this).prop('disabled', true);
        axios.post('/programs/save', payload).then(function (res) {
            if (res.data.status === 201) {
                $.each(res.data.error, function (key, val) {
                    $('#err-' + key.replace('.', '-')).text(val[0]);
                });
                return;
            }
            if (res.data.status === 200) {
                bootstrap.Modal.getInstance(document.getElementById('mdlProgram')).hide();
                toast(res.data.msg, 'success');
                if (res.data.id) selectedId = res.data.id;
                loadAll();
            } else {
                toast(res.data.msg || 'Error saving.', 'error');
            }
        }).catch(function () {
            toast('Error saving milestone.', 'error');
        }).then(function () {
            $btn.prop('disabled', false);
        });
    });

    /* ── Delete milestone ────────────────────────────────────────────── */
    $(document).on('click', '.btn-del', function () {
        var id = $(this).data('id');
        var title = $(this).data('title');
        Swal.fire({
            title: 'Delete milestone?',
            html: 'This removes <strong>' + esc(title) + '</strong> and all its grant history. This cannot be undone.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444',
            confirmButtonText: 'Delete'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            axios.post('/programs/delete', { id: id }).then(function (res) {
                toast(res.data.msg, res.data.status === 200 ? 'success' : 'error');
                if (id == selectedId) selectedId = null;
                loadAll();
            });
        });
    });

    /* ── Grant / revoke ──────────────────────────────────────────────── */
    $(document).on('click', '.btn-grant', function () {
        var program = $(this).data('program');
        var emp = $(this).data('emp');
        var name = $(this).data('name');
        var programName = $(this).data('program-name');
        Swal.fire({
            title: 'Mark as granted?',
            html: 'Confirm the <strong>' + esc(programName) + '</strong> benefit was given to <strong>' + esc(name) + '</strong>.',
            input: 'text', inputPlaceholder: 'Note (optional)',
            icon: 'question', showCancelButton: true, confirmButtonColor: '#10b981',
            confirmButtonText: 'Mark Granted'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            axios.post('/programs/grant', { program_id: program, employee_id: emp, note: r.value || '' }).then(function (res) {
                toast(res.data.msg, 'success');
                loadAll();
            });
        });
    });

    $(document).on('click', '.btn-revoke', function () {
        var program = $(this).data('program');
        var emp = $(this).data('emp');
        Swal.fire({
            title: 'Revert to pending?',
            text: 'This removes the grant record for this milestone.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#f59e0b',
            confirmButtonText: 'Revert'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            axios.post('/programs/revoke', { program_id: program, employee_id: emp }).then(function (res) {
                toast(res.data.msg, 'success');
                loadAll();
            });
        });
    });

    /* ── Initial load ──────────────────────────────────────────────────
       Honor an HR Attention deep link: /pages/modules/programs?focus=pending
       (milestones with recipients still to be granted) or ?focus=upcoming
       (anniversaries in the next 60 days). Recipients/anniversaries render
       inside a selected milestone's detail pane, so we auto-open the milestone
       with the most matching people and prime its recipient filter. */
    loadAll().then(function () {
        var focus = (new URLSearchParams(window.location.search).get('focus') || '').toLowerCase();
        if (focus !== 'pending' && focus !== 'upcoming') return;

        var pool = focus === 'pending'
            ? REACHED.filter(function (r) { return r.status !== 'granted'; })
            : UPCOMING;
        if (!pool.length) return;

        // Land on the milestone with the largest matching group.
        var counts = {};
        pool.forEach(function (r) { counts[r.program_id] = (counts[r.program_id] || 0) + 1; });
        var bestId = null, best = -1;
        Object.keys(counts).forEach(function (pid) { if (counts[pid] > best) { best = counts[pid]; bestId = pid; } });
        if (bestId == null) return;

        selectedId = bestId;
        recipFilter = focus === 'pending' ? 'pending' : 'all';
        renderRail();
        renderDetail();
        var el = document.getElementById('programDetail');
        if (el && el.scrollIntoView) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});
