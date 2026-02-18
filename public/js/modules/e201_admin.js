$(document).ready(function() {
    // 1. Sidebar Search Logic
  // Listen for typing in the search bar
    $("#empSearch").on("keyup", function() {
        let value = $(this).val().toLowerCase().trim();

        // Loop through each row in the employee list
        $("#employeeList .emp-row").each(function() {
            // Get all text inside the row (Name + Position + ID)
            let rowText = $(this).text().toLowerCase();

            // If the search value is found in the text
            if (rowText.indexOf(value) > -1) {
                $(this).removeClass("search-hidden"); // Show it
            } else {
                $(this).addClass("search-hidden");    // Hide it
            }
        });
    });

    // 2. Fetch Employee Data on Click
    $('.emp-row').on('click', function() {
        const id = $(this).data('id');
        
        // Highlight active row
        $('.emp-row').removeClass('active');
        $(this).addClass('active');

        // Show loading/transition
        $('#emptyState').addClass('d-none');
        $('#dossierContent').removeClass('d-none');

        $.ajax({
            url: `/admin/e201/fetch/${id}`, // Ensure this route exists in web.php
            type: 'GET',
            success: function(response) {
                renderDossier(response.data);
                // alert(response.image_url);
                 if (response.image_url) {

                
                        // Use a cache-buster (?t=...) to ensure the "previous" image is cleared
                        $('#view_img').attr('src', response.image_url );
                    } else {
                        $('#view_img').attr('src', '/img/undraw_profile.svg');
                    }
            }
        });
    });

    function renderDossier(user) {
        $status = user.status == 1 ? 'ACTIVE' : 'INACTIVE';
        $statusClass = user.status == 1 ? 'bg-success' : 'bg-danger';
        // 1. Root User Data
        $('#view_name').text(`${user.lname}, ${user.fname} ${user.mname ?? ''}`);
        $('#view_status').text($status);
        $('#view_status').addClass($statusClass)
        $('#view_email').text(user.email);
        $('#view_empid_val').text(user.empID);
        $('#editEmployee').attr('href', '/admin/e201/edit/' + user.id);

        // 2. Relationship Data (empDetail)
        const detail = user.emp_detail; // This matches your public function empDetail()
        const d = user.emp_detail;

        if (detail) {
            // Position and Dept
            const pos = detail.position ? detail.position.pos_desc : 'N/A';
            const dept = detail.department ? detail.department.dep_name : 'N/A';
            $('#view_job_title').text(`${pos} | ${dept}`);
            
            // Employment Info
            $('#view_hired').text(detail.empDateHired ? moment(detail.empDateHired).format('MMM DD, YYYY') : '---');
            $('#view_emp_status').text(detail.empStatus == 1 ? 'Employed' : 'Resigned');
            $('#view_class').text(detail.classification.class_desc);
            // Currency Formatting
            const basic = parseFloat(detail.empBasic || 0);
            const basicAllowance = parseFloat(detail.empAllowance || 0);
            $('#view_salary').text(basic.toLocaleString('en-US', { minimumFractionDigits: 2 }));

            // Statutory
            $('#view_allowance').text(basicAllowance.toLocaleString('en-US', { minimumFractionDigits: 2 }));

            $('#view_sss').text(detail.empSSS ?? '---');
            $('#view_phil').text(detail.empPhilhealth ?? '---');
            $('#view_pagibig').text(detail.empPagibig ?? '---');
            $('#view_tin').text(detail.empTIN ?? '---');
            
            // Company
            $('#view_company').text(detail.company ? detail.company.comp_name : '---');

            
        }

       // 1. Reset all education fields first
            $('#view_educ_tertiary, #view_grad_tertiary, #view_educ_secondary, #view_grad_secondary, #view_educ_primary, #view_grad_primary').text('---');

            // 2. Loop through the education array
            if (user.education && user.education.length > 0) {
                user.education.forEach(edu => {
                    const level = edu.schoolLevel.toLowerCase();

                    if (level === 'tertiary') {
                        $('#view_educ_tertiary').text(edu.schoolName);
                        $('#view_grad_tertiary').text(edu.yearGraduated);
                    } 
                    else if (level === 'secondary') {
                        $('#view_educ_secondary').text(edu.schoolName);
                        $('#view_grad_secondary').text(edu.yearGraduated);
                    }
                    else if (level === 'primary') {
                        $('#view_educ_primary').text(edu.schoolName);
                        $('#view_grad_primary').text(edu.yearGraduated);
                    }
                });
            }
    }

});