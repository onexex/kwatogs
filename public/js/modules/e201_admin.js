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

    function renderDossier(user) {
        const statusText = user.status == 1 ? 'ACTIVE' : 'INACTIVE';
        const statusClass = user.status == 1 ? 'bg-success' : 'bg-danger';
        
        $('#view_name').text(`${user.lname}, ${user.fname} ${user.mname ?? ''}`);
        $('#view_status').text(statusText).removeClass('bg-success bg-danger').addClass(statusClass);
        $('#view_email').text(user.email);
        $('#view_empid_val').text(user.empID);
        $('#editEmployee').attr('href', '/admin/e201/edit/' + user.id);

        const detail = user.emp_detail;

        if (detail) {
            const pos = detail.position ? detail.position.pos_desc : 'N/A';
            const dept = detail.department ? detail.department.dep_name : 'N/A';
            $('#view_job_title').text(`${pos} | ${dept}`);
            
            // Drop Moment.js dependency if possible
            const hiredDate = detail.empDateHired 
                ? new Date(detail.empDateHired).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })
                : '---';
            
            $('#view_hired').text(hiredDate);
            $('#view_emp_status').text(detail.empStatus == 1 ? 'Employed' : 'Resigned');
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