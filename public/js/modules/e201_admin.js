$(document).ready(function() {
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
                
                if (response.image_url) {
                    $('#view_img').attr('src', response.image_url);
                } else {
                    $('#view_img').attr('src', '/img/undraw_profile.svg');
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

    function renderDossier(user) {
        const detail = user.emp_detail;

        // The badge above the name reflects EMPLOYMENT status:
        // ACTIVE only when currently Employed (empStatus 1). Resigned (0) and
        // End Of Contract (2) — or any missing detail — show as INACTIVE.
        const empStatusVal = detail ? String(detail.empStatus) : '';
        const isActive = empStatusVal === '1';
        const statusText = isActive ? 'ACTIVE' : 'INACTIVE';
        const statusClass = isActive ? 'bg-success' : 'bg-danger';

        $('#view_name').text(`${user.lname}, ${user.fname} ${user.mname ?? ''}`);
        $('#view_status').text(statusText).removeClass('bg-success bg-danger bg-secondary').addClass(statusClass);
        $('#view_email').text(user.email);
        $('#view_empid_val').text(user.empID);
        $('#view_username').text(user.username || '---');
        $('#editEmployee').attr('href', '/admin/e201/edit/' + user.id);
        $('#resetPasswordBtn')
            .attr('data-id', user.id)
            .attr('data-name', `${user.fname} ${user.lname}`);

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