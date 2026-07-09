/* Applicant Tracking / Recruitment — HR admin. */
$(document).ready(function () {

    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && window.axios) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function toast(t, i) {
        if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 2400, showConfirmButton: false, icon: i || 'success', title: t });
    }

    var modalEl = document.getElementById('mdlApplicant');
    var modal = modalEl ? new bootstrap.Modal(modalEl) : null;

    /* ── Load / render list ── */
    function load() {
        axios.get('/applicants/list', {
            params: { status: $('#fStatus').val(), department_id: $('#fDept').val(), search: $('#fSearch').val() }
        }).then(function (res) {
            var rows = (res.data && res.data.data) || [];
            if (!rows.length) {
                $('#tblApplicants').html('<tr class="empty-row"><td colspan="8">No applicants found.</td></tr>');
                return;
            }
            $('#tblApplicants').html(rows.map(rowHtml).join(''));
        }).catch(function () { toast('Failed to load applicants.', 'error'); });
    }

    function rowHtml(a) {
        var contact = [];
        if (a.mobile) contact.push(esc(a.mobile));
        if (a.email) contact.push(esc(a.email));

        var resume = a.resume_path
            ? '<a class="btn-mini" href="/' + esc(a.resume_path) + '" target="_blank" title="Résumé"><i class="fa-solid fa-file-lines"></i></a> '
            : '';

        var actions = '';
        if (a.status === 'hired') {
            var emp = a.hired_empID ? ' (' + esc(a.hired_empID) + ')' : '';
            actions = resume + '<span class="badge-soft hired">Hired' + emp + '</span>';
        } else {
            actions += resume;
            actions += '<button class="btn-mini" onclick="aplEdit(' + a.id + ')">Edit</button> ';
            actions += '<a class="btn-mini hire" href="/applicants/' + a.id + '/hire" title="Complete onboarding">Hire</a> ';
            if (a.status === 'rejected') {
                actions += '<button class="btn-mini" onclick="aplRestore(' + a.id + ')">Restore</button> ';
            } else {
                actions += '<button class="btn-mini" onclick="aplReject(' + a.id + ')">Reject</button> ';
            }
            actions += '<button class="btn-mini del" onclick="aplDelete(' + a.id + ')"><i class="fa-solid fa-trash"></i></button>';
        }

        var badge = '<span class="badge-soft ' + esc(a.status) + '">' + esc(a.status === 'pool' ? 'Pool' : a.status === 'hired' ? 'Hired' : 'Not Pursued') + '</span>';

        return '<tr>' +
            '<td><strong>' + esc(a.full_name || (a.last_name + ', ' + a.first_name)) + '</strong></td>' +
            '<td>' + esc(a.desired_position) + '</td>' +
            '<td>' + esc(a.department_name || '—') + '</td>' +
            '<td>' + (contact.length ? contact.join('<br>') : '—') + '</td>' +
            '<td>' + esc(a.source || '—') + '</td>' +
            '<td>' + esc(a.applied_at || '—') + '</td>' +
            '<td>' + badge + '</td>' +
            '<td style="white-space:nowrap;">' + actions + '</td>' +
            '</tr>';
    }

    /* ── Add ── */
    document.getElementById('btnAdd').addEventListener('click', function () {
        document.getElementById('frmApplicant').reset();
        $('#apId').val('');
        $('#apErr').text('');
        $('#apResumeCurrent').text('');
        $('#mdlApplicantTitle').text('New Applicant');
        if (modal) modal.show();
    });

    /* ── Edit ── */
    window.aplEdit = function (id) {
        axios.get('/applicants/list', { params: { status: '' } }).then(function (res) {
            var a = ((res.data && res.data.data) || []).find(function (x) { return x.id == id; });
            if (!a) {
                // fall back to a rejected-inclusive fetch
                axios.get('/applicants/list', { params: { status: 'rejected' } }).then(function (r2) {
                    var a2 = ((r2.data && r2.data.data) || []).find(function (x) { return x.id == id; });
                    if (a2) fillForm(a2);
                });
                return;
            }
            fillForm(a);
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
                load();
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
                .then(function (res) { toast(res.data.msg || 'Updated.', res.data.status === 200 ? 'success' : 'error'); load(); });
        });
    };

    window.aplRestore = function (id) {
        axios.post('/applicants/status', { id: id, status: 'pool' })
            .then(function (res) { toast(res.data.msg || 'Restored to pool.', res.data.status === 200 ? 'success' : 'error'); load(); });
    };

    /* ── Delete ── */
    window.aplDelete = function (id) {
        Swal.fire({
            title: 'Delete applicant?', text: 'This permanently removes the record and any résumé file.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#ef4444'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            axios.post('/applicants/delete', { id: id })
                .then(function (res) { toast(res.data.msg || 'Deleted.', res.data.status === 200 ? 'success' : 'error'); load(); });
        });
    };

    /* ── Filters ── */
    var searchTimer;
    $('#fSearch').on('input', function () { clearTimeout(searchTimer); searchTimer = setTimeout(load, 300); });
    $('#fStatus, #fDept').on('change', load);

    load();
});
