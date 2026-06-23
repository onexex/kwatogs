// PhilHealth contribution schedule editor
$(document).ready(function () {
    var formaction = 1;
    var updateID = '';
    var allRows = [];

    var num = function (n) {
        var v = parseFloat(n);
        if (isNaN(v)) return '—';
        return v.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    var esc = function (s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };

    get_all();

    function get_all() {
        axios.get('/getPhilhealth').then(function (res) {
            if (res.data.status !== 200) return;
            allRows = res.data.data || [];
            var years = res.data.years || [];
            var $sel = $('#selYear');
            var current = $sel.val();
            $sel.empty().append('<option value="all">All Years</option>');
            years.forEach(function (y) { $sel.append('<option value="' + y + '">' + y + '</option>'); });
            if (current && $sel.find('option[value="' + current + '"]').length) $sel.val(current);
            else if (years.length) $sel.val(String(years[0]));
            render();
        }).catch(function (e) { Swal.fire('Error', 'Could not load PhilHealth table: ' + e, 'error'); });
    }

    function render() {
        var yr = $('#selYear').val();
        var rows = (yr && yr !== 'all') ? allRows.filter(function (r) { return String(r.effective_year) === String(yr); }) : allRows;
        var html = '';
        rows.forEach(function (it) {
            html += '<tr>' +
                '<td class="ps-4"><span class="yr-badge">' + esc(it.effective_year) + '</span></td>' +
                '<td>' + num(it.range_from) + '</td>' +
                '<td>' + num(it.range_to) + '</td>' +
                '<td>' + num(it.premium_rate) + '</td>' +
                '<td>' + num(it.employee_share) + '</td>' +
                '<td>' + num(it.employer_share) + '</td>' +
                '<td>' + num(it.min_salary) + '</td>' +
                '<td>' + num(it.max_salary) + '</td>' +
                '<td class="pe-4 text-end">' +
                    '<button class="icon-action-btn btnEdit" data-id="' + it.id + '" data-bs-toggle="modal" data-bs-target="#mdlPHIC" title="Edit"><i class="fa-solid fa-pencil text-primary"></i></button> ' +
                    '<button class="icon-action-btn danger btnDelete" data-id="' + it.id + '" title="Delete"><i class="fa-solid fa-trash"></i></button>' +
                '</td></tr>';
        });
        if (!rows.length) html = '<tr><td colspan="9" class="text-center py-5 text-muted small">No brackets for this year. Click “Add Bracket” to create one.</td></tr>';
        $('#tblPHIC').empty().append(html);
        $('#rowCount').text(rows.length + ' bracket' + (rows.length === 1 ? '' : 's'));
    }

    $(document).on('change', '#selYear', render);

    $(document).on('click', '#btnCreatePHIC', function () {
        formaction = 1; updateID = '';
        $('#frmPHIC')[0].reset();
        $('span.error-text').text(''); $('#frmPHIC .form-control').removeClass('border border-danger');
        var yr = $('#selYear').val();
        $('#txtYear').val(yr && yr !== 'all' ? yr : new Date().getFullYear());
        $('#lblTitlePHIC').text('Add PhilHealth Bracket');
    });

    $(document).on('click', '.btnEdit', function () {
        formaction = 2; updateID = $(this).data('id');
        $('#lblTitlePHIC').text('Update PhilHealth Bracket');
        $('span.error-text').text(''); $('#frmPHIC .form-control').removeClass('border border-danger');
        var it = allRows.find(function (r) { return String(r.id) === String(updateID); });
        if (!it) return;
        $('#txtYear').val(it.effective_year);
        $('#txtRangeFrom').val(it.range_from);
        $('#txtRangeTo').val(it.range_to);
        $('#txtPremium').val(it.premium_rate);
        $('#txtEE').val(it.employee_share);
        $('#txtER').val(it.employer_share);
        $('#txtMin').val(it.min_salary);
        $('#txtMax').val(it.max_salary);
    });

    $(document).on('click', '#btnSavePHIC', function () {
        var formData = new FormData($('#frmPHIC')[0]);
        formData.append('formAction', formaction);
        formData.append('updateID', updateID);

        axios.post('/settings/Philhealth', formData).then(function (res) {
            var s = res.data.status;
            $('span.error-text').text(''); $('#frmPHIC .form-control').removeClass('border border-danger');
            if (s === 201) {
                $.each(res.data.error, function (field, val) {
                    $('#frmPHIC [name="' + field + '"]').addClass('border border-danger');
                    $('span.' + field + '_error').text(val[0]);
                });
                return;
            }
            if (s === 200) {
                $('#mdlPHIC').modal('hide');
                get_all();
                Swal.fire({ icon: 'success', title: 'Saved', text: res.data.msg, timer: 1600, showConfirmButton: false });
            } else {
                Swal.fire('Notice', res.data.msg || 'Could not save.', 'warning');
            }
        }).catch(function (e) { Swal.fire('Error', String(e), 'error'); });
    });

    $(document).on('click', '.btnDelete', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Delete this bracket?', text: 'This PhilHealth bracket will be removed.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Delete'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            var fd = new FormData(); fd.append('id', id);
            axios.post('/deletePhilhealth', fd).then(function (res) {
                get_all();
                Swal.fire({ icon: res.data.status === 200 ? 'success' : 'info', title: res.data.msg, timer: 1500, showConfirmButton: false });
            }).catch(function (e) { Swal.fire('Error', String(e), 'error'); });
        });
    });
});
