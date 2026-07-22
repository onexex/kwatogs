$(document).ready(function() {
    // Offboarding clearance catalog — mirrors App\Services\OffboardingClearanceService::ITEMS.
    // Column on emp_details is always 'cl_' + key. applies: '0'=Resigned, '2'=End of Contract.
    const CL_ITEMS = [
        { key: 'resignation_letter', label: 'Resignation Letter',             applies: ['0'] },
        { key: 'office_notice',      label: 'Signed Notice from Office',      applies: ['2'] },
        { key: 'clearance_form',     label: 'Clearance Form',                 applies: ['0', '2'] },
        { key: 'company_items',      label: 'Return of Company-Issued Items', applies: ['0', '2'] },
        { key: 'quitclaim',          label: 'Signed/Received Quitclaim',      applies: ['0', '2'] },
    ];

    const $rows = $('.emp-row');
    const $sidePanel = $('#sidePanel');
    const $backBtn = $('#btnBackToList');
    const $mainDetails = $('#mainDetails');

    // 1. Sidebar Search Logic (combined text search + optional URL status/payroll filter)
    const urlParams = new URLSearchParams(window.location.search);
    const statusFilter  = urlParams.get('status');   // '1' | '0' | '2'
    const payrollFilter = (urlParams.get('payroll') || '').toUpperCase(); // 'CASH' | 'CARD'
    const deptFilter    = urlParams.get('dept');      // department id
    const focusFilter   = (urlParams.get('focus') || '').toLowerCase(); // HR-attention deep link

    const STATUS_LABELS  = { '1': 'Active', '0': 'Resigned', '2': 'End of Contract' };
    const PAYROLL_LABELS = { CASH: 'Cash payroll', CARD: 'Card payroll' };
    // HR-attention deep links (from the "Needs your attention" panel). Each key matches a
    // token emitted in the row's data-flags attribute (see e201.blade.php).
    const FOCUS_LABELS   = {
        missingdocs: 'Missing government docs',
        passport:    'Passport expiring soon',
        sanitarycard:'Sanitary card renewal',
        regularize:  'Upcoming regularization',
        birthday:    'Birthday this week',
        hireanniv:   'Work anniversary this week',
    };

    let activeStatus  = STATUS_LABELS[statusFilter]  ? statusFilter  : null;
    let activePayroll = PAYROLL_LABELS[payrollFilter] ? payrollFilter : null;
    let activeDept    = (deptFilter && ('' + deptFilter).trim() !== '') ? ('' + deptFilter).trim() : null;
    let activeFocus   = FOCUS_LABELS[focusFilter] ? focusFilter : null;

    // Resolve a readable department name from the first matching row.
    // NOTE: read raw attributes with .attr() (strings) — .data() coerces "0"/"1"
    // to numbers, and 0 (Resigned) is falsy, which would break the comparison.
    let activeDeptName = '';
    if (activeDept) {
        const $match = $rows.filter(function() { return (this.getAttribute('data-dept') || '') === activeDept; }).first();
        activeDeptName = ($match.attr('data-deptname') || 'Department') + '';
    }

    function hasFilter() { return activeStatus || activePayroll || activeDept || activeFocus; }

    function applyFilters() {
        const query = ($('#empSearchInput').val() || '').toLowerCase().trim();
        let shown = 0;
        $rows.each(function() {
            const el = this;
            const matchesSearch  = (el.getAttribute('data-search-key') || '').includes(query);
            const matchesStatus  = !activeStatus  || (el.getAttribute('data-status')  || '') === activeStatus;
            const matchesPayroll = !activePayroll || (el.getAttribute('data-payroll') || '').toUpperCase() === activePayroll;
            const matchesDept    = !activeDept    || (el.getAttribute('data-dept')    || '') === activeDept;
            const matchesFocus   = !activeFocus   || (el.getAttribute('data-flags')   || '').split(' ').indexOf(activeFocus) !== -1;
            if (matchesSearch && matchesStatus && matchesPayroll && matchesDept && matchesFocus) {
                el.classList.remove('d-none'); el.classList.add('d-flex');
                shown++;
            } else {
                el.classList.remove('d-flex'); el.classList.add('d-none');
            }
        });
        return shown;
    }

    function renderFilterChip() {
        const $chip = $('#e201FilterChip');
        if (!hasFilter()) { $chip.removeClass('d-flex').addClass('d-none'); return; }
        const parts = [];
        if (activeStatus)  parts.push(STATUS_LABELS[activeStatus]);
        if (activePayroll) parts.push(PAYROLL_LABELS[activePayroll]);
        if (activeDept)    parts.push(activeDeptName);
        if (activeFocus)   parts.push(FOCUS_LABELS[activeFocus]);
        $('#e201FilterLabel').text(parts.join(' · '));
        $chip.removeClass('d-none').addClass('d-flex');
    }

    $('#empSearchInput').on('input', function() {
        const shown = applyFilters();
        if (hasFilter()) $('#e201FilterCount').text('(' + shown + ')');
    });

    $('#e201FilterClear').on('click', function(e) {
        e.preventDefault();
        activeStatus = null;
        activePayroll = null;
        activeDept = null;
        activeFocus = null;
        renderFilterChip();
        applyFilters();
    });

    // Apply any URL-driven filter on load.
    if (hasFilter()) {
        renderFilterChip();
        const shown = applyFilters();
        $('#e201FilterCount').text('(' + shown + ')');
    }

    // 2. Fetch Employee Data & Update UI
    $rows.on('click', function() {
        const $thisRow = $(this);
        const id = $thisRow.data('id');
        
        // UI Updates
        $rows.removeClass('active-selection');
        $thisRow.addClass('active-selection');
        $('#emptyState').addClass('d-none'); // Make sure to add this ID to your HTML
        $('#dossierContent').removeClass('d-none');

        // Mobile Responsive Toggles
        if (window.innerWidth < 992) {
            $sidePanel.addClass('list-hidden-mobile');
            $backBtn.show();
        }

        // Scroll to top of details
        $mainDetails.scrollTop(0);

        // Fetch Data
        $.ajax({
            url: `/admin/e201/fetch/${id}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                renderDossier(response.data);
                
                const $img = $('#view_img');
                const $placeholder = $('#view_img_placeholder');

                function showPlaceholder(gender) {
                    $img.hide();
                    const g = (gender || '').toLowerCase();
                    const iconClass = g === 'female' ? 'fa-solid fa-circle-user' : 'fa-solid fa-circle-user';
                    const color     = g === 'female' ? '#e91e8c' : '#1976d2';
                    $placeholder
                        .html(`<i class="${iconClass}" style="font-size:3.5rem;color:${color};"></i>`)
                        .css('display', 'flex');
                }

                if (response.image_url) {
                    $img.off('error').on('error', function () {
                        showPlaceholder(response.gender);
                    });
                    $img.attr('src', response.image_url).show();
                    $placeholder.hide();
                } else {
                    showPlaceholder(response.gender);
                }
            },
            error: function() {
                alert('Failed to fetch employee data. Please try again.');
            }
        });
    });

    // 3. Back Button (Mobile)
    $backBtn.on('click', function() {
        $sidePanel.removeClass('list-hidden-mobile');
        $(this).hide();
    });

    // 4. Reset Password (admin "forgot password")
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    $('#resetPasswordBtn').on('click', function() {
        const $btn = $(this);
        const id = $btn.data('id');
        const name = $btn.data('name') || 'this employee';

        if (!id) {
            alert('Please select an employee first.');
            return;
        }

        const ok = confirm(
            `Reset the password for ${name}?\n\n` +
            `It will be set to the default temporary password (123456), and ` +
            `${name} will be forced to choose a new password on their next login.`
        );
        if (!ok) return;

        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Resetting...');

        $.ajax({
            url: `/admin/e201/reset-password/${id}`,
            type: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response) {
                alert(response.message || 'Password has been reset.');
            },
            error: function(xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Failed to reset password. Please try again.';
                alert(msg);
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // 5. Update Status (employment exit + flag) — mirrors the Reset Password AJAX pattern.
    const $usModal = $('#updateStatusModal');

    // Years between two ISO dates (matches Carbon::floatDiffInYears closely enough for preview).
    function yearsBetween(hiredStr, sepStr) {
        if (!hiredStr || !sepStr) return null;
        const hired = new Date(hiredStr);
        const sep = new Date(sepStr);
        if (isNaN(hired) || isNaN(sep) || sep < hired) return null;
        return ((sep - hired) / (365.25 * 24 * 60 * 60 * 1000)).toFixed(2);
    }

    function toggleUsFields() {
        const status = $('#us_emp_status').val();
        const isExit = status !== '1';
        $('#us_separation_fields').toggleClass('d-none', !isExit);
        $('#us_clearance_section').toggleClass('d-none', !isExit);
        // Show only the clearance items applicable to the chosen exit type.
        $('#us_clearance .cl-row').each(function() {
            const applies = String($(this).data('applies')).split(',');
            $(this).toggle(isExit && applies.indexOf(status) !== -1);
        });
        const hasFlag = $('#us_flag_status').val() !== '';
        $('#us_flag_fields').toggleClass('d-none', !hasFlag);
    }

    function refreshUsYearsPreview() {
        const hired = $('#updateStatusBtn').data('hired');
        const sep = $('#us_separation_date').val();
        const yrs = yearsBetween(hired, sep);
        $('#us_years_preview').text(yrs !== null ? `${yrs} yrs` : '—');
    }

    $('#updateStatusBtn').on('click', function() {
        const $btn = $(this);
        if (!$btn.data('id')) {
            alert('Please select an employee first.');
            return;
        }
        $('#us_error').addClass('d-none').text('');
        $('#us_emp_name').text($btn.data('name') || 'this employee');
        $('#us_emp_status').val(String($btn.data('emp-status') || '1'));
        $('#us_separation_date').val($btn.data('sep-date') || '');
        $('#us_separation_reason').val($btn.data('sep-reason') || '');
        $('#us_flag_status').val(String($btn.data('flag-status') || ''));
        $('#us_flag_reason').val($btn.data('flag-reason') || '');

        // Reset clearance UI, then prefill from the stashed state.
        $('.us-cl-check').prop('checked', false);
        $('.us-cl-ref').val('');
        $('.us-cl-file').val('');
        $('.us-cl-current').html('');
        $('#us_clearance .cl-row').removeClass('is-checked');
        $('#us_clearance .cl-pill').removeClass('attached').addClass('pending').text('Pending');

        let cl = {};
        try { cl = JSON.parse($btn.attr('data-clearance') || '{}'); } catch (e) { cl = {}; }
        $.each(cl.flags || {}, function(k, v) { $('#us_cl_' + k).prop('checked', !!v); });
        $.each(cl.refs || {}, function(k, v) { $('#us_clref_' + k).val(v || ''); });

        // A document already on file → surface it, AUTO-TICK the item, mark it "Attached".
        $.each(cl.docs || {}, function(k, v) {
            $('#us_clcur_' + k).html('<a href="/admin/e201/document/' + v.id + '/download" title="Current attachment"><i class="fa-solid fa-paperclip me-1"></i>' + $('<div>').text(v.name || 'attachment').html() + '</a>');
            $('#us_cl_' + k).prop('checked', true);
            $('#us_clpill_' + k).removeClass('pending').addClass('attached').text('Attached');
        });

        // Sync each row's highlight to its checkbox state.
        $('#us_clearance .us-cl-check').each(function() {
            $(this).closest('.cl-row').toggleClass('is-checked', this.checked);
        });

        toggleUsFields();
        refreshUsYearsPreview();
        $usModal.modal('show');
    });

    $('#us_emp_status, #us_flag_status').on('change', toggleUsFields);
    $('#us_separation_date').on('change input', refreshUsYearsPreview);

    // Clearance row UX: highlight follows the tick; choosing a proof file auto-ticks the item.
    $(document).on('change', '.us-cl-check', function() {
        $(this).closest('.cl-row').toggleClass('is-checked', this.checked);
    });
    $(document).on('change', '.us-cl-file', function() {
        const $row = $(this).closest('.cl-row');
        const key = $row.data('key');
        if (this.files && this.files.length) {
            $('#us_cl_' + key).prop('checked', true);
            $row.addClass('is-checked');
            $('#us_clpill_' + key).removeClass('pending').addClass('attached').text('New file');
        }
    });

    $('#us_save').on('click', function() {
        const $btn = $(this);
        const id = $('#updateStatusBtn').data('id');
        const empStatus = $('#us_emp_status').val();
        const isExit = empStatus !== '1';
        const flagStatus = $('#us_flag_status').val();

        // Client-side guards mirroring server validation.
        if (isExit && !$('#us_separation_date').val()) {
            $('#us_error').removeClass('d-none').text('Separation date is required.');
            return;
        }
        if (isExit && !$('#us_separation_reason').val().trim()) {
            $('#us_error').removeClass('d-none').text('Separation reason is required.');
            return;
        }
        if (flagStatus && !$('#us_flag_reason').val().trim()) {
            $('#us_error').removeClass('d-none').text('Flag reason is required.');
            return;
        }

        // Collect ticked clearance items (only the rows visible for this exit type) + refs.
        const clearance = [];
        const clearance_refs = {};
        if (isExit) {
            $('#us_clearance .cl-row:visible').each(function() {
                const key = $(this).data('key');
                if ($('#us_cl_' + key).is(':checked')) clearance.push(key);
                const ref = ($('#us_clref_' + key).val() || '').trim();
                if (ref) clearance_refs[key] = ref;
            });
        }

        // Build multipart so clearance proof files can ride along with the status change.
        const fd = new FormData();
        fd.append('emp_status', empStatus);
        fd.append('separation_date', isExit ? $('#us_separation_date').val() : '');
        fd.append('separation_reason', isExit ? $('#us_separation_reason').val() : '');
        fd.append('flag_status', flagStatus || '');
        fd.append('flag_reason', flagStatus ? $('#us_flag_reason').val() : '');
        clearance.forEach(function(k) { fd.append('clearance[]', k); });
        Object.keys(clearance_refs).forEach(function(k) { fd.append('clearance_refs[' + k + ']', clearance_refs[k]); });
        if (isExit) {
            $('#us_clearance .cl-row:visible').each(function() {
                const key = $(this).data('key');
                const f = $('#us_clfile_' + key)[0];
                if (f && f.files.length) fd.append('clearance_files[' + key + ']', f.files[0]);
            });
        }

        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Saving...');

        $.ajax({
            url: `/admin/e201/update-status/${id}`,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response) {
                $usModal.modal('hide');
                alert(response.message || 'Status updated.');
                // Re-fetch the dossier so the badge/fields reflect the new state.
                $('.emp-row.active-selection').trigger('click');
            },
            error: function(xhr) {
                const msg = (xhr.responseJSON && (xhr.responseJSON.message
                    || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0])))
                    || 'Failed to update status. Please try again.';
                $('#us_error').removeClass('d-none').text(msg);
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // 6. Employment Documents — upload + delete (201 file).
    //    Same jQuery + X-CSRF-TOKEN idiom as the rest of this file (no axios/SweetAlert).
    //    After a change we re-trigger the active row so the dossier re-fetches, exactly
    //    like Update Status does.

    $('#btnUploadEmpDoc').on('click', function() {
        const $btn = $(this);
        const id = $btn.data('id');
        const $err = $('#ed_doc_error');
        $err.addClass('d-none').text('');

        if (!id) { $err.removeClass('d-none').text('Please select an employee first.'); return; }
        const fileInput = $('#ed_doc_file')[0];
        if (!fileInput || !fileInput.files.length) {
            $err.removeClass('d-none').text('Please choose a file to upload.');
            return;
        }

        const fd = new FormData();
        // Offboarding-requirement types carry a "cl:<item>" value → store as a Clearance
        // doc tagged to that requirement (which ticks it on the exit clearance).
        let docType = $('#ed_doc_type').val();
        let clearanceKey = '';
        if (docType.indexOf('cl:') === 0) { clearanceKey = docType.slice(3); docType = 'Clearance'; }
        fd.append('doc_type', docType);
        fd.append('label', $('#ed_doc_label').val());
        if (clearanceKey) fd.append('clearance_key', clearanceKey);
        fd.append('document', fileInput.files[0]);

        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Uploading...');

        $.ajax({
            url: `/admin/e201/documents/${id}`,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function() {
                $('#ed_doc_label').val('');
                $('#ed_doc_file').val('');
                $('.emp-row.active-selection').trigger('click');
            },
            error: function(xhr) {
                const msg = (xhr.responseJSON && (xhr.responseJSON.message
                    || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0])))
                    || 'Failed to upload document. Please try again.';
                $err.removeClass('d-none').text(msg);
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    $(document).on('click', '.btnDeleteEmpDoc', function() {
        const docId = $(this).data('id');
        if (!docId) return;
        if (!confirm('Delete this document? This permanently removes the file.')) return;

        $.ajax({
            url: `/admin/e201/document/${docId}`,
            type: 'POST',                 // POST + _method spoof so proxies that block DELETE still work
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { _method: 'DELETE' },
            success: function() {
                $('.emp-row.active-selection').trigger('click');
            },
            error: function(xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to delete document.';
                alert(msg);
            }
        });
    });

    function renderDossier(user) {
        const detail = user.emp_detail;

        // Employment documents (201 file). Delete is only offered when the current user
        // can manage documents — detected by the presence of the (@can-gated) upload button.
        const canManageDocs = $('#btnUploadEmpDoc').length > 0;
        $('#btnUploadEmpDoc').attr('data-id', user.id).data('id', user.id);
        const escHtml = s => $('<div>').text(s == null ? '' : String(s)).html();
        const docs = user.employment_documents || [];
        // Map clearance docs to checklist items. Explicitly-tagged docs win; an untagged
        // Clearance-type doc falls back to the generic "Clearance Form" item, so a plain
        // clearance upload still auto-ticks + links (no specific item needed).
        const clDocs = {};
        docs.forEach(function(d) { if (d.clearance_key && !clDocs[d.clearance_key]) clDocs[d.clearance_key] = { id: d.id, name: d.original_name }; });
        docs.forEach(function(d) { if (!d.clearance_key && d.doc_type === 'Clearance' && !clDocs['clearance_form']) clDocs['clearance_form'] = { id: d.id, name: d.original_name }; });
        if (docs.length) {
            const docRows = docs.map(function(d) {
                const uploaded = d.created_at
                    ? new Date(d.created_at).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })
                    : '';
                const by = d.uploaded_by ? '<div style="font-size:.7rem;">' + escHtml(d.uploaded_by) + '</div>' : '';
                const del = canManageDocs
                    ? ' <button type="button" class="btn btn-sm btn-light border btnDeleteEmpDoc" data-id="' + d.id + '" title="Delete"><i class="fa-solid fa-trash text-danger"></i></button>'
                    : '';
                const clItem = d.clearance_key ? (CL_ITEMS.find(function(i) { return i.key === d.clearance_key; }) || {}).label : '';
                const clTag = clItem ? '<div class="text-muted" style="font-size:.68rem;">' + escHtml(clItem) + '</div>' : '';
                return '<tr>'
                    + '<td><span class="badge text-white" style="background-color:#008080;">' + escHtml(d.doc_type || 'Other') + '</span>' + clTag + '</td>'
                    + '<td><div class="value-text">' + escHtml(d.label || d.original_name) + '</div>'
                    +     '<div class="text-muted" style="font-size:.72rem;">' + escHtml(d.original_name) + '</div></td>'
                    + '<td class="text-muted small">' + escHtml(uploaded) + by + '</td>'
                    + '<td class="text-end">'
                    +   '<a href="/admin/e201/document/' + d.id + '/download" class="btn btn-sm btn-light border" title="Download"><i class="fa-solid fa-download text-teal"></i></a>'
                    +   del
                    + '</td>'
                    + '</tr>';
            }).join('');
            $('#view_documents_list').html(docRows);
        } else {
            $('#view_documents_list').html('<tr><td colspan="4" class="text-center py-3 text-muted small">No documents uploaded.</td></tr>');
        }

        // The badge above the name reflects EMPLOYMENT status:
        // ACTIVE only when currently Employed (empStatus 1). Resigned (0) and
        // End Of Contract (2) — or any missing detail — show as INACTIVE.
        const empStatusVal = detail ? String(detail.empStatus) : '';
        const isActive = empStatusVal === '1';
        const statusText = isActive ? 'ACTIVE' : 'INACTIVE';
        const statusClass = isActive ? 'bg-success' : 'bg-danger';

        const toTitleCase = s => (s || '').toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
        const fullName = [user.lname, ',', user.fname, user.mname ?? ''].filter(Boolean).join(' ');
        $('#view_name').text(toTitleCase(fullName.replace(' ,', ',')));
        $('#view_status').text(statusText).removeClass('bg-success bg-danger bg-secondary').addClass(statusClass);
        $('#view_email').text(user.email);
        $('#view_empid_val').text(user.empID);
        $('#view_username').text(user.username || '---');
        $('#editEmployee').attr('href', '/admin/e201/edit/' + user.id);
        $('#resetPasswordBtn')
            .attr('data-id', user.id)
            .attr('data-name', `${user.fname} ${user.lname}`);

        // Flag badge (independent of employment state) — Blacklisted/Red Flag or hidden.
        const flagStatus = detail && detail.flag_status ? String(detail.flag_status) : '';
        const flagLabels = { 'redflag': 'RED FLAG', 'blacklist': 'BLACKLISTED' };
        const $flag = $('#view_flag');
        if (flagStatus && flagLabels[flagStatus]) {
            $flag.text(flagLabels[flagStatus])
                .removeClass('d-none bg-dark bg-warning bg-danger text-dark text-white')
                .addClass(flagStatus === 'blacklist' ? 'bg-danger text-white' : 'bg-warning text-dark');
        } else {
            $flag.addClass('d-none');
        }

        // Stash everything the Update Status modal needs to prefill itself.
        $('#updateStatusBtn')
            .attr('data-id', user.id)
            .attr('data-name', `${user.fname} ${user.lname}`)
            .attr('data-emp-status', detail ? (detail.empStatus ?? '1') : '1')
            .attr('data-hired', detail && detail.empDateHired ? detail.empDateHired : '')
            .attr('data-sep-date', detail && detail.separation_date ? detail.separation_date : '')
            .attr('data-sep-reason', detail && detail.separation_reason ? detail.separation_reason : '')
            .attr('data-flag-status', flagStatus)
            .attr('data-flag-reason', detail && detail.flag_reason ? detail.flag_reason : '')
            .attr('data-clearance', detail ? JSON.stringify({
                flags: {
                    resignation_letter: !!detail.cl_resignation_letter,
                    office_notice:      !!detail.cl_office_notice,
                    clearance_form:     !!detail.cl_clearance_form,
                    company_items:      !!detail.cl_company_items,
                    quitclaim:          !!detail.cl_quitclaim,
                },
                refs: detail.clearance_refs || {},
                docs: clDocs,
            }) : '{}');

        if (detail) {
            const pos = detail.position ? detail.position.pos_desc : 'N/A';
            const dept = detail.department ? detail.department.dep_name : 'N/A';
            $('#view_job_title').text(`${pos} | ${dept}`);
            
            // Drop Moment.js dependency if possible
            const hiredDate = detail.empDateHired 
                ? new Date(detail.empDateHired).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })
                : '---';
            
            $('#view_hired').text(hiredDate);
            const empStatusLabels = { '1': 'Employed', '0': 'Resigned', '2': 'End Of Contract' };
            $('#view_emp_status').text(empStatusLabels[String(detail.empStatus)] ?? '---');
            $('#view_class').text(detail.classification ? detail.classification.class_desc : '---');
            
            const basic = parseFloat(detail.empBasic || 0);
            const allowance = parseFloat(detail.empAllowance || 0);
            $('#view_salary').text(basic.toLocaleString('en-US', { minimumFractionDigits: 2 }));
            $('#view_allowance').text(allowance.toLocaleString('en-US', { minimumFractionDigits: 2 }));

            const ptype = (detail.empPayrollType || 'CASH').toString().toUpperCase();
            $('#view_payroll_type').text(ptype.charAt(0) + ptype.slice(1).toLowerCase());
            if (ptype === 'CARD') {
                $('#view_card_no').text(detail.empCardNo || '---');
                $('#view_card_no_wrap').show();
            } else {
                $('#view_card_no_wrap').hide();
            }

            $('#view_sss').text(detail.empSSS ?? '---');
            $('#view_phil').text(detail.empPhilhealth ?? '---');
            $('#view_pagibig').text(detail.empPagibig ?? '---');
            $('#view_tin').text(detail.empTIN ?? '---');
            $('#view_company').text(detail.company ? detail.company.comp_name : '---');

            // Health / sanitary card — expiry rendered red once past due.
            $('#view_sanitary_card').text(detail.empSanitaryCardNo ?? '---');
            const scExp = detail.empSanitaryCardExpDate ? new Date(detail.empSanitaryCardExpDate) : null;
            $('#view_sanitary_card_exp')
                .text(scExp ? scExp.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }) : '---')
                .toggleClass('text-danger fw-bold', !!scExp && scExp < new Date(new Date().toDateString()));

            // Years rendered + separation snapshot (only meaningful once separated).
            $('#view_years_rendered').text(
                (detail.years_rendered !== null && detail.years_rendered !== undefined && detail.years_rendered !== '')
                    ? parseFloat(detail.years_rendered).toFixed(2) + ' yrs'
                    : '---'
            );

            const sepDate = detail.separation_date
                ? new Date(detail.separation_date).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })
                : '';
            $('#view_separation_date').text(sepDate || '---');
            $('#view_sep_date_wrap').toggle(!!sepDate);

            $('#view_separation_reason').text(detail.separation_reason || '---');
            $('#view_sep_reason_wrap').toggle(!!detail.separation_reason);

            $('#view_flag_reason').text(detail.flag_reason || '---');
            $('#view_flag_reason_wrap').toggle(!!(detail.flag_status && detail.flag_reason));

            // Offboarding clearance card — only for separated employees.
            if (!isActive && empStatusVal) {
                const refs = detail.clearance_refs || {};
                const rows = CL_ITEMS
                    .filter(i => i.applies.indexOf(empStatusVal) !== -1)
                    .map(function(i) {
                        const done = !!detail['cl_' + i.key];
                        const icon = done
                            ? '<i class="fa-solid fa-circle-check text-success me-2"></i>'
                            : '<i class="fa-solid fa-circle-xmark text-danger me-2"></i>';
                        const ref = refs[i.key]
                            ? ' <span class="text-muted" style="font-size:.72rem;">— ' + $('<div>').text(refs[i.key]).html() + '</span>'
                            : '';
                        const att = clDocs[i.key]
                            ? ' <a href="/admin/e201/document/' + clDocs[i.key].id + '/download" class="ms-1" title="Download attached document"><i class="fa-solid fa-paperclip text-teal"></i></a>'
                            : '';
                        return '<div class="value-text mb-1">' + icon + i.label + ref + att + '</div>';
                    }).join('');
                $('#view_clearance_list').html(rows);
                const meta = detail.cleared_by
                    ? 'Last updated by ' + $('<div>').text(detail.cleared_by).html()
                        + (detail.cleared_at ? ' on ' + new Date(detail.cleared_at).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }) : '')
                    : '';
                $('#view_clearance_meta').text('').append(meta);
                $('#view_clearance_card').show();
            } else {
                $('#view_clearance_card').hide();
            }
        }

        // Reset and populate education...
        $('#view_educ_tertiary, #view_grad_tertiary, #view_educ_secondary, #view_grad_secondary, #view_educ_primary, #view_grad_primary').text('---');

        if (user.education && user.education.length > 0) {
            user.education.forEach(edu => {
                const level = edu.schoolLevel.toLowerCase();
                if (level === 'tertiary') {
                    $('#view_educ_tertiary').text(edu.schoolName);
                    $('#view_grad_tertiary').text(edu.yearGraduated);
                } else if (level === 'secondary') {
                    $('#view_educ_secondary').text(edu.schoolName);
                    $('#view_grad_secondary').text(edu.yearGraduated);
                } else if (level === 'primary') {
                    $('#view_educ_primary').text(edu.schoolName);
                    $('#view_grad_primary').text(edu.yearGraduated);
                }
            });
        }
    }
});