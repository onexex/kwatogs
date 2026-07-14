/* Applicant Tracking / Recruitment — HR admin. Master-detail talent-pool workspace. */
$(document).ready(function () {

    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && window.axios) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function toast(t, i) {
        if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 2400, showConfirmButton: false, icon: i || 'success', title: t });
    }

    var modalEl = document.getElementById('mdlApplicant');
    var modal = modalEl ? new bootstrap.Modal(modalEl) : null;

    var rows = [];          // current list
    var selectedId = null;  // active applicant

    function statusLabel(s) { return s === 'pool' ? 'Pool' : s === 'hired' ? 'Hired' : 'Not Pursued'; }
    function initials(a) {
        var f = (a.first_name || '').trim(), l = (a.last_name || '').trim();
        return ((f[0] || '') + (l[0] || '')).toUpperCase() || '?';
    }
    function stars(n, big) {
        n = Number(n) || 0;
        var out = '';
        for (var i = 1; i <= 5; i++) {
            out += '<i class="fa-solid fa-star' + (i <= n ? '' : (big ? ' off' : '')) + '"' + (i > n && !big ? ' style="opacity:.25"' : '') + '></i>';
        }
        return out;
    }

    /* ── Load / render list ── */
    function load(keepSelection) {
        axios.get('/applicants/list', {
            params: { status: currentFilter(), department_id: $('#fDept').val(), search: $('#fSearch').val() }
        }).then(function (res) {
            rows = (res.data && res.data.data) || [];
            renderList();
            if (keepSelection && selectedId != null && rows.some(function (r) { return r.id == selectedId; })) {
                select(selectedId);
            } else {
                selectedId = null;
                renderDetailEmpty();
            }
        }).catch(function () { toast('Failed to load applicants.', 'error'); });
    }

    function renderList() {
        if (!rows.length) {
            $('#aplList').html('<div class="empty-state"><i class="fa-solid fa-user-slash"></i><div>No applicants found.</div></div>');
            return;
        }
        $('#aplList').html(rows.map(rowHtml).join(''));
    }

    function rowHtml(a) {
        var meta = [];
        if (a.department_name) meta.push(esc(a.department_name));
        if (a.applied_at) meta.push(esc(a.applied_at));
        var starHtml = a.rating ? '<span class="rstars">' + stars(a.rating) + '</span>' : '';
        return '<div class="arow' + (a.id == selectedId ? ' active' : '') + '" data-id="' + a.id + '">' +
            '<div class="av">' + esc(initials(a)) + '</div>' +
            '<div class="rmain">' +
                '<div class="rtitle">' + esc(a.full_name || (a.last_name + ', ' + a.first_name)) + '</div>' +
                '<div class="rpos">' + esc(a.desired_position || '—') + '</div>' +
                '<div class="rmeta">' +
                    '<span class="badge-soft ' + esc(a.status) + '">' + esc(statusLabel(a.status)) + '</span>' +
                    (meta.length ? '<span>' + meta.join(' · ') + '</span>' : '') +
                    starHtml +
                '</div>' +
            '</div>' +
        '</div>';
    }

    /* ── Select / render profile ── */
    function select(id) {
        selectedId = id;
        $('.arow').removeClass('active');
        $('.arow[data-id="' + id + '"]').addClass('active');
        var a = rows.find(function (r) { return r.id == id; });
        if (a) renderDetail(a);
    }

    function renderDetailEmpty() {
        $('#aplDetail').html('<div class="pd-empty"><i class="fa-solid fa-id-card"></i><div>Select an applicant from the list to view their profile.</div></div>');
    }

    function field(k, v, isHtml) {
        return '<div class="pd-field"><div class="k">' + esc(k) + '</div><div class="v">' + (isHtml ? v : (v ? esc(v) : '<span class="pd-muted">—</span>')) + '</div></div>';
    }

    function renderDetail(a) {
        var hired = a.status === 'hired';

        // Header
        var ratingHtml = a.rating
            ? '<span class="pd-stars">' + stars(a.rating, true) + '</span>'
            : '<span class="pd-muted" style="font-size:.78rem;">Not rated</span>';
        var head =
            '<div class="pd-head">' +
                '<div class="pd-avatar">' + esc(initials(a)) + '</div>' +
                '<div class="pd-htext">' +
                    '<h2 class="pd-name">' + esc(a.full_name || (a.last_name + ', ' + a.first_name)) + '</h2>' +
                    '<div class="pd-pos"><i class="fa-solid fa-briefcase me-1"></i>' + esc(a.desired_position || '—') + '</div>' +
                    '<div class="pd-hbadges">' +
                        '<span class="badge-soft ' + esc(a.status) + '">' + esc(statusLabel(a.status)) +
                            (hired && a.hired_empID ? ' · ' + esc(a.hired_empID) : '') + '</span>' +
                        ratingHtml +
                    '</div>' +
                '</div>' +
            '</div>';

        // Actions
        var acts = '<div class="pd-actions">';
        var resumeUrl = a.resume_path ? '/' + esc(a.resume_path) : '';
        if (hired) {
            if (resumeUrl) acts += '<a class="btn-act primary" href="' + resumeUrl + '" target="_blank"><i class="fa-solid fa-file-lines"></i> Résumé</a>';
            acts += '<span class="pd-muted" style="align-self:center;font-size:.82rem;"><i class="fa-solid fa-user-check me-1" style="color:var(--success)"></i>Hired' + (a.hired_at ? ' on ' + esc(a.hired_at) : '') + '</span>';
        } else {
            acts += '<button class="btn-act primary" onclick="aplEdit(' + a.id + ')"><i class="fa-solid fa-pen"></i> Edit</button>';
            acts += '<a class="btn-act hire" href="/applicants/' + a.id + '/hire"><i class="fa-solid fa-user-plus"></i> Hire</a>';
            if (a.status === 'rejected') {
                acts += '<button class="btn-act" onclick="aplRestore(' + a.id + ')"><i class="fa-solid fa-rotate-left"></i> Restore to Pool</button>';
            } else {
                acts += '<button class="btn-act warn" onclick="aplReject(' + a.id + ')"><i class="fa-solid fa-user-slash"></i> Not Pursue</button>';
            }
            acts += '<button class="btn-act del" onclick="aplDelete(' + a.id + ')"><i class="fa-solid fa-trash"></i> Delete</button>';
        }
        acts += '</div>';

        // Body grid
        var yrs = (a.years_experience != null && a.years_experience !== '')
            ? (Number(a.years_experience) + ' yr' + (Number(a.years_experience) === 1 ? '' : 's'))
            : null;

        var contact =
            '<div class="pd-sec">' +
                '<h4><i class="fa-solid fa-address-book"></i>Contact</h4>' +
                field('Mobile', a.mobile) +
                field('Email', a.email ? '<a href="mailto:' + esc(a.email) + '">' + esc(a.email) + '</a>' : '', true) +
                field('Source', a.source) +
            '</div>';

        var application =
            '<div class="pd-sec">' +
                '<h4><i class="fa-solid fa-clipboard-list"></i>Application</h4>' +
                field('Department', a.department_name) +
                field('Applied On', a.applied_at) +
                field('Highest Education', a.highest_education) +
                field('Experience', yrs) +
            '</div>';

        var qual = a.qualifications
            ? '<div class="pd-sec full"><h4><i class="fa-solid fa-star-half-stroke"></i>Skills &amp; Qualifications</h4><div class="pd-quals">' + esc(a.qualifications) + '</div></div>'
            : '';

        var resume =
            '<div class="pd-sec full"><h4><i class="fa-solid fa-file-lines"></i>Résumé</h4>' +
            (a.resume_path
                ? '<div class="pd-resume"><div class="ri"><i class="fa-solid fa-file-lines"></i></div>' +
                    '<div class="rn">Résumé on file</div>' +
                    '<a class="open" href="' + resumeUrl + '" target="_blank"><i class="fa-solid fa-up-right-from-square me-1"></i>Open</a></div>'
                : '<div class="pd-muted" style="font-size:.85rem;">No résumé uploaded.</div>') +
            '</div>';

        var notes = a.notes
            ? '<div class="pd-sec full"><h4><i class="fa-solid fa-note-sticky"></i>Notes</h4><div class="pd-notes">' + esc(a.notes) + '</div></div>'
            : '';

        var reject = (a.status === 'rejected' && a.rejection_reason)
            ? '<div class="pd-reject"><div class="k"><i class="fa-solid fa-circle-info me-1"></i>Not pursued</div><div class="v">' + esc(a.rejection_reason) + '</div></div>'
            : '';

        var body = '<div class="pd-grid">' + contact + application + qual + resume + notes + reject + '</div>';

        $('#aplDetail').html(head + acts + body);
    }

    /* Delegated row click. */
    $('#aplList').on('click', '.arow', function () { select($(this).data('id')); });

    /* ── Add ── */
    document.getElementById('btnAdd').addEventListener('click', function () {
        document.getElementById('frmApplicant').reset();
        $('#apId').val('');
        $('#apErr').text('');
        $('#apResumeCurrent').text('');
        $('#mdlApplicantTitle').text('New Applicant');
        if (modal) modal.show();
    });

    /* ── Edit — read from the already-loaded list, fall back to a fetch. ── */
    window.aplEdit = function (id) {
        var a = rows.find(function (x) { return x.id == id; });
        if (a) return fillForm(a);
        axios.get('/applicants/list', { params: { status: 'rejected' } }).then(function (r2) {
            var a2 = ((r2.data && r2.data.data) || []).find(function (x) { return x.id == id; });
            if (a2) fillForm(a2);
        });
    };

    function fillForm(a) {
        document.getElementById('frmApplicant').reset();
        $('#apErr').text('');
        $('#apId').val(a.id);
        $('#apFirst').val(a.first_name);
        $('#apMiddle').val(a.middle_name);
        $('#apLast').val(a.last_name);
        $('#apMobile').val(a.mobile);
        $('#apEmail').val(a.email);
        $('#apPosition').val(a.desired_position);
        $('#apEducation').val(a.highest_education || '');
        $('#apExperience').val(a.years_experience != null ? a.years_experience : '');
        $('#apQualifications').val(a.qualifications || '');
        $('#apDept').val(a.department_id || '');
        $('#apSource').val(a.source || '');
        $('#apApplied').val(a.applied_at || '');
        $('#apRating').val(a.rating || '');
        $('#apNotes').val(a.notes || '');
        $('#apResumeCurrent').html(a.resume_path
            ? 'Current: <a href="/' + esc(a.resume_path) + '" target="_blank">view résumé</a> — uploading replaces it.'
            : '');
        $('#mdlApplicantTitle').text('Edit Applicant');
        if (modal) modal.show();
    }

    /* ── Save ── */
    document.getElementById('frmApplicant').addEventListener('submit', function (e) {
        e.preventDefault();
        $('#apErr').text('');
        var fd = new FormData(this);
        axios.post('/applicants/save', fd).then(function (res) {
            if (res.data.status === 200) {
                toast(res.data.msg || 'Saved.');
                if (modal) modal.hide();
                if (res.data.id) selectedId = res.data.id;
                load(true);
            } else if (res.data.status === 201) {
                var msgs = Object.values(res.data.error || {}).map(function (v) { return v[0]; });
                $('#apErr').text(msgs.join(' '));
            } else {
                toast(res.data.msg || 'Could not save.', 'error');
            }
        }).catch(function () { toast('Request failed.', 'error'); });
    });

    /* ── Reject / Restore ── */
    window.aplReject = function (id) {
        Swal.fire({
            title: 'Not pursue this applicant?',
            input: 'textarea', inputLabel: 'Reason (optional)', inputPlaceholder: 'e.g. Position filled',
            showCancelButton: true, confirmButtonText: 'Confirm', confirmButtonColor: '#ef4444'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            axios.post('/applicants/status', { id: id, status: 'rejected', rejection_reason: r.value || '' })
                .then(function (res) { toast(res.data.msg || 'Updated.', res.data.status === 200 ? 'success' : 'error'); load(true); });
        });
    };

    window.aplRestore = function (id) {
        axios.post('/applicants/status', { id: id, status: 'pool' })
            .then(function (res) { toast(res.data.msg || 'Restored to pool.', res.data.status === 200 ? 'success' : 'error'); load(true); });
    };

    /* ── Delete ── */
    window.aplDelete = function (id) {
        Swal.fire({
            title: 'Delete applicant?', text: 'This permanently removes the record and any résumé file.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#ef4444'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            axios.post('/applicants/delete', { id: id })
                .then(function (res) {
                    toast(res.data.msg || 'Deleted.', res.data.status === 200 ? 'success' : 'error');
                    if (id == selectedId) selectedId = null;
                    load(true);
                });
        });
    };

    /* ── Filters ── */
    function currentFilter() { return $('.apl-pills .pill.active').data('filter') || ''; }
    $('.apl-pills').on('click', '.pill', function () {
        $('.apl-pills .pill').removeClass('active');
        $(this).addClass('active');
        load(true);
    });
    var searchTimer;
    $('#fSearch').on('input', function () { clearTimeout(searchTimer); searchTimer = setTimeout(function () { load(true); }, 300); });
    $('#fDept').on('change', function () { load(true); });

    load();
});
