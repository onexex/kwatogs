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

    // 1. Sidebar Search Logic
    $('#empSearchInput').on('input', function() {
        const query = $(this).val().toLowerCase().trim();

        $rows.each(function() {
            const searchKey = $(this).data('search-key') || '';
            if (searchKey.includes(query)) {
                $(this).removeClass('d-none').addClass('d-flex');
            } else {
                $(this).removeClass('d-flex').addClass('d-none');
            }
        });
    });

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

        // Prefill clearance checkboxes + reference notes from the stashed state.
        $('.us-cl-check').prop('checked', false);
        $('.us-cl-ref').val('');
        let cl = {};
        try { cl = JSON.parse($btn.attr('data-clearance') || '{}'); } catch (e) { cl = {}; }
        $.each(cl.flags || {}, function(k, v) { $('#us_cl_' + k).prop('checked', !!v); });
        $.each(cl.refs || {}, function(k, v) { $('#us_clref_' + k).val(v || ''); });

        toggleUsFields();
        refreshUsYearsPreview();
        $usModal.modal('show');
    });

    $('#us_emp_status, #us_flag_status').on('change', toggleUsFields);
    $('#us_separation_date').on('change input', refreshUsYearsPreview);

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

        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Saving...');

        $.ajax({
            url: `/admin/e201/update-status/${id}`,
            type: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: {
                emp_status: empStatus,
                separation_date: isExit ? $('#us_separation_date').val() : '',
                separation_reason: isExit ? $('#us_separation_reason').val() : '',
                flag_status: flagStatus,
                flag_reason: flagStatus ? $('#us_flag_reason').val() : '',
                clearance: clearance,
                clearance_refs: clearance_refs,
            },
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

    function renderDossier(user) {
        const detail = user.emp_detail;

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
                        return '<div class="value-text mb-1">' + icon + i.label + ref + '</div>';
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