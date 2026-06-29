/* COE Signatories — settings CRUD with image upload. jQuery + axios + SweetAlert2. */
$(document).ready(function () {

    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && window.axios) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function toast(t, i) { if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 2400, showConfirmButton: false, icon: i || 'success', title: t }); }

    function load() {
        axios.get('/coe/signatories/list').then(function (res) {
            var rows = res.data.data || [];
            if (!rows.length) { $('#tblSig').html('<tr class="empty-row"><td colspan="5">No signatories yet. Add one to start issuing COEs.</td></tr>'); return; }
            $('#tblSig').html(rows.map(function (s) {
                var badge = s.is_active ? '<span class="badge-soft b-active">Active</span>' : '<span class="badge-soft b-inactive">Inactive</span>';
                var img = s.signature ? '<img class="sig-thumb" src="' + esc(s.signature) + '">' : '<span class="text-muted">—</span>';
                return '<tr>' +
                    '<td>' + img + '</td>' +
                    '<td><strong>' + esc(s.name) + '</strong></td>' +
                    '<td>' + (s.title ? esc(s.title) : '<span class="text-muted">—</span>') + '</td>' +
                    '<td>' + badge + '</td>' +
                    '<td class="text-end pe-4">' +
                        '<button class="btn-mini edit btn-edit me-1" data-json=\'' + JSON.stringify(s).replace(/'/g, '&#39;') + '\'><i class="fa-solid fa-pencil"></i></button>' +
                        '<button class="btn-mini del btn-del" data-id="' + s.id + '"><i class="fa-solid fa-trash"></i></button>' +
                    '</td></tr>';
            }).join(''));
        });
    }

    function resetModal() {
        $('#sigId').val(''); $('#sigName').val(''); $('#sigTitle').val(''); $('#sigFile').val('');
        $('#sigActive').prop('checked', true); $('.text-danger.small').text('');
        $('#sigCurrentWrap').hide(); $('#sigImgReq').show();
    }

    $(document).on('click', '#btnAddSig', function () {
        resetModal(); $('#sigMdlTitle').text('Add Signatory');
        new bootstrap.Modal(document.getElementById('mdlSig')).show();
    });

    $(document).on('click', '.btn-edit', function () {
        var s = JSON.parse($(this).attr('data-json').replace(/&#39;/g, "'"));
        resetModal(); $('#sigMdlTitle').text('Edit Signatory');
        $('#sigId').val(s.id); $('#sigName').val(s.name); $('#sigTitle').val(s.title || '');
        $('#sigActive').prop('checked', !!s.is_active);
        $('#sigImgReq').hide(); // image optional on edit
        if (s.signature) { $('#sigCurrent').attr('src', s.signature); $('#sigCurrentWrap').show(); }
        new bootstrap.Modal(document.getElementById('mdlSig')).show();
    });

    $(document).on('click', '#btnSaveSig', function () {
        $('.text-danger.small').text('');
        var fd = new FormData();
        if ($('#sigId').val()) fd.append('id', $('#sigId').val());
        fd.append('name', $('#sigName').val().trim());
        fd.append('title', $('#sigTitle').val().trim());
        fd.append('is_active', $('#sigActive').is(':checked') ? 1 : 0);
        var file = document.getElementById('sigFile').files[0];
        if (file) fd.append('signature', file);

        var $btn = $(this).prop('disabled', true);
        axios.post('/coe/signatories/save', fd).then(function (res) {
            if (res.data.status === 201) {
                $.each(res.data.error, function (k, v) { $('#err-' + k.replace('.', '-')).text(v[0]); });
                return;
            }
            if (res.data.status === 200) {
                bootstrap.Modal.getInstance(document.getElementById('mdlSig')).hide();
                toast(res.data.msg, 'success'); load();
            } else { toast(res.data.msg || 'Error.', 'error'); }
        }).catch(function () { toast('Error saving signatory.', 'error'); })
          .then(function () { $btn.prop('disabled', false); });
    });

    $(document).on('click', '.btn-del', function () {
        var id = $(this).data('id');
        Swal.fire({ title: 'Delete signatory?', text: 'Past certificates keep their frozen signature.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Delete' })
            .then(function (r) {
                if (!r.isConfirmed) return;
                axios.post('/coe/signatories/delete', { id: id }).then(function (res) {
                    toast(res.data.msg, res.data.status === 200 ? 'success' : 'error'); load();
                });
            });
    });

    load();
});
