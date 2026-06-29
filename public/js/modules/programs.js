/* Programs Management — tenure-milestone benefits.
 * Drives the milestone config table, the eligibility/grant tables, and the
 * add/edit modal. Uses jQuery + axios + SweetAlert2 (all loaded globally). */
$(document).ready(function () {

    // Ensure CSRF header is present for axios POSTs (defensive).
    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && window.axios) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');
    }

    var reachedData = [];
    var currentFilter = 'all';

    function esc(s) {
        return $('<div>').text(s == null ? '' : s).html();
    }

    function toast(title, icon) {
        if (window.Swal) {
            Swal.fire({ toast: true, position: 'top-end', timer: 2200, showConfirmButton: false, icon: icon || 'success', title: title });
        }
    }

    function benefitChips(list) {
        if (!list || !list.length) return '<span class="text-muted small">&mdash;</span>';
        return list.map(function (b) { return '<span class="chip">' + esc(b) + '</span>'; }).join('');
    }

    /* ── Eligibility (reached + upcoming + stats) ─────────────────────── */
    function loadEligibility() {
        axios.get('/programs/eligibility').then(function (res) {
            var d = res.data.data;
            $('#statPrograms').text(d.stats.programs);
            $('#statPending').text(d.stats.pendingCount);
            $('#statGranted').text(d.stats.grantedCount);
            $('#statUpcoming').text(d.stats.upcomingCount);

            reachedData = d.reached || [];
            renderReached();
            renderUpcoming(d.upcoming || []);
        }).catch(function () {
            $('#tblReached').html('<tr class="empty-row"><td colspan="7">Failed to load eligibility.</td></tr>');
        });
    }

    function renderReached() {
        var rows = reachedData.filter(function (r) {
            return currentFilter === 'all' || r.status === currentFilter;
        });

        if (!rows.length) {
            $('#tblReached').html('<tr class="empty-row"><td colspan="7">No employees in this view yet.</td></tr>');
            return;
        }

        var html = rows.map(function (r) {
            var statusBadge = r.status === 'granted'
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
                '<td>' + esc(r.program) + '<div class="text-muted" style="font-size:.68rem;">' + r.years + ' yr threshold</div></td>' +
                '<td>' + benefitChips(r.benefits) + '</td>' +
                '<td>' + statusBadge + grantedMeta + '</td>' +
                '<td class="text-end pe-4">' + action + '</td>' +
                '</tr>';
        }).join('');

        $('#tblReached').html(html);
    }

    function renderUpcoming(list) {
        if (!list.length) {
            $('#tblUpcoming').html('<tr class="empty-row"><td colspan="6">No upcoming anniversaries in the next 60 days.</td></tr>');
            return;
        }
        var html = list.map(function (r) {
            return '<tr>' +
                '<td><strong>' + esc(r.name) + '</strong><div class="text-muted" style="font-size:.68rem;">' + esc(r.employee_id) + '</div></td>' +
                '<td>' + esc(r.dept) + '</td>' +
                '<td><span class="tenure-pill">' + r.tenure.toFixed(2) + ' yrs</span></td>' +
                '<td>' + esc(r.program) + '<div class="text-muted" style="font-size:.68rem;">' + r.years + ' yr threshold</div></td>' +
                '<td>' + benefitChips(r.benefits) + '</td>' +
                '<td><span class="badge-soft badge-pending">in ' + r.days + ' day' + (r.days === 1 ? '' : 's') + '</span><div class="text-muted" style="font-size:.68rem;margin-top:3px;">' + esc(r.date) + '</div></td>' +
                '</tr>';
        }).join('');
        $('#tblUpcoming').html(html);
    }

    /* ── Milestone config table ──────────────────────────────────────── */
    function loadPrograms() {
        axios.get('/programs/list').then(function (res) {
            var list = res.data.data || [];
            if (!list.length) {
                $('#tblPrograms').html('<tr class="empty-row"><td colspan="5">No milestones yet. Click <strong>Add Milestone</strong> to create one.</td></tr>');
                return;
            }
            var html = list.map(function (p) {
                var benefits = (p.benefits || []).map(function (b) {
                    return '<span class="chip">' + esc(b.name) + (b.description ? ' (' + esc(b.description) + ')' : '') + '</span>';
                }).join('') || '<span class="text-muted small">&mdash;</span>';

                var status = p.is_active
                    ? '<span class="badge-soft badge-granted">Active</span>'
                    : '<span class="badge-soft badge-inactive">Inactive</span>';

                return '<tr>' +
                    '<td><strong>' + esc(p.title) + '</strong>' + (p.description ? '<div class="text-muted" style="font-size:.68rem;">' + esc(p.description) + '</div>' : '') + '</td>' +
                    '<td><span class="tenure-pill">' + parseFloat(p.years_required) + ' yrs</span></td>' +
                    '<td>' + benefits + '</td>' +
                    '<td>' + status + '</td>' +
                    '<td class="text-end pe-4">' +
                        '<button class="btn-mini edit btn-edit me-1" data-id="' + p.id + '"><i class="fa-solid fa-pencil"></i></button>' +
                        '<button class="btn-mini del btn-del" data-id="' + p.id + '" data-title="' + esc(p.title) + '"><i class="fa-solid fa-trash"></i></button>' +
                    '</td>' +
                    '</tr>';
            }).join('');
            $('#tblPrograms').html(html);

            // cache for edit
            window.__programs = list;
        }).catch(function () {
            $('#tblPrograms').html('<tr class="empty-row"><td colspan="5">Failed to load milestones.</td></tr>');
        });
    }

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
        var p = (window.__programs || []).find(function (x) { return x.id == id; });
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
                loadPrograms();
                loadEligibility();
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
                loadPrograms();
                loadEligibility();
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
                loadEligibility();
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
                loadEligibility();
            });
        });
    });

    /* ── Filter pills ────────────────────────────────────────────────── */
    $(document).on('click', '.pill', function () {
        $('.pill').removeClass('active');
        $(this).addClass('active');
        currentFilter = $(this).data('filter');
        renderReached();
    });

    // Initial load
    loadPrograms();
    loadEligibility();
});
