// $(document).ready(function() {
//      //employee number fetch
//     //  empNumberGenerate();
//      loadprovince();
//      function empNumberGenerate(){
//          axios.post('/function/generateEmpid',)  
//          .then(function (response) {
//              //error response
//              if (response.data.status == 200) {
//                  $('#txtEmployeeNo').val(response.data.data);
//              }
//          })
//          .catch(function (error) {
//              dialog.alert({
//                  message: error
//              });  
//          })
//          .then(function () {});   
 
//      }
 
//     $(document).on('change', '#txtProvince ', function(e) {
//         var provCode = $(this).val();
//         axios.get('/get_city',{
//             params: {
//                 id: provCode
//               }
//           })    .then(function (response) {
//             if (response.data.status == 200) {
//                var bodyData = '';
//             //    bodyData += ("<option value=0>-</option>");
//                $.each(response.data.data, function(index, row) {
//                    bodyData += ("<option value=" + row.citymunCode + ">" + row.citymunDesc + "</option>");
//                })
//                $("#txtCity").empty();
//                $("#txtCity").append(bodyData);
//             }
//         })
//         .catch(function (error) {
//             dialog.alert({
//                 message: error
//             });  
//         })
//         .then(function () {}); 
//     });

//     $(document).on('change', '#txtCity ', function(e) {
//         var citycode = $(this).val();
//         axios.get('/get_brgy',{
//             params: {
//                 id: citycode
//               }
//           })    .then(function (response) {
//             if (response.data.status == 200) {
//                var bodyData = '';
//             //    bodyData += ("<option value=0>-</option>");
//                $.each(response.data.data, function(index, row) {
//                    bodyData += ("<option value=" + row.brgyCode + ">" + row.brgyDesc + "</option>");
//                })
//                $("#txtBrgy").empty();
//                $("#txtBrgy").append(bodyData);
//             }
//         })
//         .catch(function (error) {
//             dialog.alert({
//                 message: error
//             });  
//         })
//         .then(function () {}); 

//     });

//     function loadprovince(e) {

//         axios.post('/get_province',)  
//          .then(function (response) {
//              //error response
//              if (response.data.status == 200) {
//                 var bodyData = '';
//                 bodyData += ("<option value=0>-</option>");
//                 $.each(response.data.data, function(index, row) {
//                     bodyData += ("<option value=" + row.provCode + ">" + row.provDesc + "</option>");
//                 })
//                 $("#txtProvince").empty();
//                 $("#txtProvince").append(bodyData);
//              }
//          })
//          .catch(function (error) {
//              dialog.alert({
//                  message: error
//              });  
//          })
//          .then(function () {});  
//     }

//     function on_save(){
//         $('.spin').attr("disabled", "disabled");
//         $('.spin').attr('data-btn-text', $('.spin').text());
//         $('.spin').html('<span class="spinner"><i class="fa fa-spinner fa-spin"></i></span> Please Wait. Do not Refresh!');
//         $('.spin').addClass('active');
//     }
    
//     function on_done(){
//         $('.spin').html($('.spin').attr('data-btn-text'));
//         $('.spin').html('<span ><i class="fa fa-plus"></i></span> Save Entries');
//         $('.spin').removeClass('active');
//         $('.spin').removeAttr("disabled");
//     }

//     $(document).on('click', '#btnSaveAll', function(e) {
//         var btn = $(this);
//         var datas = $('#frmEnrolment');
        
//         // 1. Disable button & show loading state
//         btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

//         var city = $("#txtCity option:selected").text();
//         var brgy = $("#txtBrgy option:selected").text();
//         var prov = $("#txtProvince option:selected").text();

//         var formData = new FormData($(datas)[0]);
//         formData.append('citydesc', city);
//         formData.append('brgydesc', brgy);
//         formData.append('provdesc', prov);

//         axios.post('/enroll/save', formData)
//             .then(function (response) {
//                 // Reset validation visuals
//                 $('span.error-text').text("");
//                 $('input.border').removeClass('border border-danger');

//                 // Validation Error (201)
//                 if (response.data.status == 201) {
//                     $.each(response.data.error, function(prefix, val) {
//                         $('input[name=' + prefix + ']').addClass(" border border-danger");
//                         $('span.' + prefix + '_error').text(val[0]);
//                     });
                    
//                     Swal.fire({
//                         icon: 'error',
//                         title: 'Validation Error',
//                         text: 'Please check the required fields.'
//                     });
//                 }

//                 // Success (200)
//                 if (response.data.status == 200) {
//                     $('#frmEnrolment')[0].reset();
//                     // empNumberGenerate();
                    
//                     Swal.fire({
//                         icon: 'success',
//                         title: 'Success!',
//                         text: response.data.msg,
//                         timer: 2000
//                     });
//                 }

//                 // Warning or Other (202, 203)
//                 if (response.data.status == 202 || response.data.status == 203) {
//                     Swal.fire({
//                         icon: 'warning',
//                         title: 'Notice',
//                         text: response.data.msg
//                     });
//                 }
//             })
//             .catch(function (error) {
//                 Swal.fire({
//                     icon: 'error',
//                     title: 'System Error',
//                     text: 'Could not connect to the server.'
//                 });
//             })
//             .finally(function () {
//                 // 2. Re-enable button
//                 btn.prop('disabled', false).text('Save All');
//             });
//     });

//     // Email availability check Feb 18 2026 Mon
//     let emailCheckTimer;  
//     let currentRequest = null;  

//     $('#txtEmailAddress').on('keyup', function() {
//         const email = $(this).val().trim();
//         const errorSpan = $('.email_error');
//         const inputField = $(this);

//         // Reset state agad
//         errorSpan.text("").removeClass('text-danger text-success');
//         inputField.removeClass('is-invalid is-valid');

//         clearTimeout(emailCheckTimer);

//         if (email === "") return;

//         // 1. FORMAT CHECK (Regex)
//         if (!validateEmail(email)) {
//             errorSpan.text("Please enter a valid email format (e.g. name@domain.com)").addClass('text-danger');
//             inputField.addClass('is-invalid');
//             return; // STOP! Huwag nang mag-AJAX kung mali ang format.
//         }

//         // 2. ATOMIC AJAX CHECK (Dito lang pupunta kung valid ang format)
//         emailCheckTimer = setTimeout(function() {
//             if (currentRequest != null) currentRequest.abort();

//             currentRequest = $.ajax({
//                 url: '/registerCtrl/checkEmailAvailability',
//                 method: 'POST',
//                 headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
//                 data: { email: email },
//                 dataType: 'json',
//                 beforeSend: function() {
//                     errorSpan.text("Checking availability...").addClass('text-muted');
//                 },
//                 success: function(response) {
//                     if (response.exists) {
//                         errorSpan.text("This email is already taken.").removeClass('text-muted').addClass('text-danger');
//                         inputField.addClass('is-invalid');
//                     } else {
//                         errorSpan.text("Email is available!").removeClass('text-muted').addClass('text-success');
//                         inputField.addClass('is-valid');
//                     }
//                 }
//             });
//         }, 500);
//     });

//     // Helper function para sa email pattern
//     function validateEmail(email) {
//         const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
//         return re.test(email);
//     }

//     let timeout = null;

//     $('#txtfname, #txtLastName').on('keyup', function() {
//         clearTimeout(timeout);

//         timeout = setTimeout(function() {
//             let fname = $('#txtfname').val().trim();
//             let lname = $('#txtLastName').val().trim();

//             // Mag-check lang kung parehong may laman
//             if (fname !== '' && lname !== '') {
//                 $.ajax({
//                     url: '/check-fullname',
//                     method: 'GET',
//                     data: { firstname: fname, lastname: lname },
//                     beforeSend: function() {
//                         // Opsyonal: Pwedeng magpakita ng "Checking..." dito
//                     },
//                     success: function(res) {
//                         if (res.exists) {
//                             $('.firstname_error').text('Full name already exists in our records.');
//                             $('#txtfname, #txtLastName').addClass('is-invalid').removeClass('bg-light');
//                             $('#btnSaveAll').attr('disabled', true); // I-disable ang submit button
//                         } else {
//                             $('.firstname_error').text('');
//                             $('#txtfname, #txtLastName').removeClass('is-invalid').addClass('bg-light');
//                             $('#btnSaveAll').attr('disabled', false);
//                         }
//                     }
//                 });
//             }
//         }, 500); // 500ms delay (Debounce)
//     });
 

// });

$(document).ready(function () {

    // ─────────────────────────────────────────────────────────
    // 1. INIT
    // ─────────────────────────────────────────────────────────
    loadProvince();

    // ─────────────────────────────────────────────────────────
    // 2. STEPPER — visited state per tab
    // ─────────────────────────────────────────────────────────
    const stepOrder = [
        'home-tab',
        'educational-tab',
        'employment-tab',
        'compliance-tab',
        'profile-pic-tab',
    ];

    // Tab-to-pane mapping used for error counting
    const tabPaneMap = [
        { btnId: 'home-tab',        pane: '#home-tab-pane',       label: 'General Info'   },
        { btnId: 'educational-tab', pane: '#educational-tab-pane', label: 'Educational'    },
        { btnId: 'employment-tab',  pane: '#employment-tab-pane',  label: 'Employment'     },
        { btnId: 'compliance-tab',  pane: '#complaince',           label: 'Compliance'     },
        { btnId: 'profile-pic-tab', pane: '#profile-tab-pane',     label: 'Profile Photo'  },
    ];

    document.querySelectorAll('.step-btn').forEach((btn) => {
        btn.addEventListener('shown.bs.tab', function () {
            const activeId  = this.id;
            const activeIdx = stepOrder.indexOf(activeId);

            document.querySelectorAll('.step-btn').forEach((b) => {
                const idx = stepOrder.indexOf(b.id);
                b.classList.remove('active');

                // Mark steps before the current one as visited (show checkmark)
                // BUT only if they have no errors — errors keep showing the ✗
                if (idx < activeIdx && !b.classList.contains('has-error')) {
                    b.classList.add('visited');
                    const circle = b.querySelector('.step-circle');
                    if (circle && !circle.dataset.checked) {
                        circle.innerHTML       = '<i class="fa fa-check" style="font-size:.6rem"></i>';
                        circle.dataset.checked = '1';
                    }
                }
            });

            this.classList.add('active', 'visited');
        });
    });

    // ─────────────────────────────────────────────────────────
    // 3. ERROR BADGES — show/clear on stepper tabs
    // ─────────────────────────────────────────────────────────

    /**
     * For each tab, count visible error-text spans inside its pane,
     * then render a red badge on the stepper button + a summary banner.
     */
    function showTabErrorBadges() {
        let totalErrors = 0;
        const tabsWithErrors = [];

        tabPaneMap.forEach(({ btnId, pane, label }) => {
            const btn   = document.getElementById(btnId);
            if (!btn) return;

            // Count non-empty error spans in this tab's pane
            const count = $(`${pane} .error-text`).filter(function () {
                return $(this).text().trim() !== '';
            }).length;

            // Remove any existing badge
            $(btn).find('.step-error-badge').remove();

            if (count > 0) {
                totalErrors += count;
                tabsWithErrors.push({ btnId, label, count });

                // Add has-error class to the button
                btn.classList.add('has-error');

                // Inject the red badge
                $(btn).append(
                    `<span class="step-error-badge">${count}</span>`
                );
            } else {
                btn.classList.remove('has-error');
            }
        });

        // Build the summary banner
        if (tabsWithErrors.length > 0) {
            const chips = tabsWithErrors.map(({ btnId, label, count }) => `
                <span class="error-tab-chip" onclick="goTab('${btnId}')">
                    <i class="fa fa-exclamation-circle" style="font-size:.65rem"></i>
                    ${label}
                    <span class="chip-count">${count}</span>
                </span>
            `).join('');

            const banner = `
                <div class="error-banner">
                    <div class="error-banner-icon">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <div class="error-banner-body">
                        <p class="error-banner-title">
                            ${totalErrors} field${totalErrors > 1 ? 's' : ''} need${totalErrors === 1 ? 's' : ''} attention — click a tab below to jump directly to the errors
                        </p>
                        <div class="error-banner-links">${chips}</div>
                    </div>
                </div>
            `;

            $('#errorBanner').html(banner).show();
        } else {
            clearTabErrorBadges();
        }
    }

    /** Remove all error badges, has-error classes, and the banner */
    function clearTabErrorBadges() {
        document.querySelectorAll('.step-btn').forEach((b) => {
            b.classList.remove('has-error');
            $(b).find('.step-error-badge').remove();
        });
        $('#errorBanner').hide().html('');
    }

    // ─────────────────────────────────────────────────────────
    // 4. PROVINCE / CITY / BARANGAY CASCADES
    // ─────────────────────────────────────────────────────────
    function loadProvince() {
        axios.post('/get_province')
            .then(function (res) {
                if (res.data.status == 200) {
                    let opts = '<option value="">— Select Province —</option>';
                    $.each(res.data.data, function (_, row) {
                        opts += `<option value="${row.provCode}">${row.provDesc}</option>`;
                    });
                    $('#txtProvince').html(opts);
                }
            })
            .catch(() => showToast('error', 'Could not load provinces.'));
    }

    $(document).on('change', '#txtProvince', function () {
        const code = $(this).val();
        $('#txtCity').html('<option value="">Loading…</option>');
        $('#txtBrgy').html('<option value="">— Select Barangay —</option>');

        axios.get('/get_city', { params: { id: code } })
            .then(function (res) {
                if (res.data.status == 200) {
                    let opts = '<option value="">— Select City —</option>';
                    $.each(res.data.data, function (_, row) {
                        opts += `<option value="${row.citymunCode}">${row.citymunDesc}</option>`;
                    });
                    $('#txtCity').html(opts);
                }
            })
            .catch(() => showToast('error', 'Could not load cities.'));
    });

    $(document).on('change', '#txtCity', function () {
        const code = $(this).val();
        $('#txtBrgy').html('<option value="">Loading…</option>');

        axios.get('/get_brgy', { params: { id: code } })
            .then(function (res) {
                if (res.data.status == 200) {
                    let opts = '<option value="">— Select Barangay —</option>';
                    $.each(res.data.data, function (_, row) {
                        opts += `<option value="${row.brgyCode}">${row.brgyDesc}</option>`;
                    });
                    $('#txtBrgy').html(opts);
                }
            })
            .catch(() => showToast('error', 'Could not load barangays.'));
    });

    // ─────────────────────────────────────────────────────────
    // 5. EMAIL — format check + availability debounce
    // ─────────────────────────────────────────────────────────
    let emailTimer   = null;
    let emailRequest = null;

    $('#txtEmailAddress').on('keyup', function () {
        const email     = $(this).val().trim();
        const errorSpan = $('.email_error');
        const field     = $(this);

        errorSpan.text('').removeClass('text-danger text-success');
        field.removeClass('is-invalid is-valid');
        clearTimeout(emailTimer);

        if (!email) return;

        if (!isValidEmail(email)) {
            setFieldError(field, errorSpan, 'Enter a valid email (e.g. name@domain.com)');
            return;
        }

        emailTimer = setTimeout(function () {
            if (emailRequest) emailRequest.abort();
            errorSpan.text('Checking…').addClass('text-muted');

            emailRequest = $.ajax({
                url    : '/registerCtrl/checkEmailAvailability',
                method : 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data   : { email },
                success: function (res) {
                    errorSpan.removeClass('text-muted');
                    if (res.exists) {
                        setFieldError(field, errorSpan, 'This email is already registered.');
                    } else {
                        setFieldSuccess(field, errorSpan, '✓ Email is available');
                    }
                },
            });
        }, 500);
    });

    // ─────────────────────────────────────────────────────────
    // 6. FULL NAME — duplicate check debounce
    // ─────────────────────────────────────────────────────────
    let nameTimer = null;

    $('#txtfname, #txtLastName').on('keyup', function () {
        clearTimeout(nameTimer);
        nameTimer = setTimeout(function () {
            const fname = $('#txtfname').val().trim();
            const lname = $('#txtLastName').val().trim();
            if (!fname || !lname) return;

            $.ajax({
                url    : '/check-fullname',
                method : 'GET',
                data   : { firstname: fname, lastname: lname },
                success: function (res) {
                    if (res.exists) {
                        setFieldError(
                            $('#txtfname, #txtLastName'),
                            $('.firstname_error'),
                            'This name already exists in our records.'
                        );
                        $('#btnSaveAll').prop('disabled', true);
                    } else {
                        $('.firstname_error').text('');
                        $('#txtfname, #txtLastName').removeClass('is-invalid');
                        $('#btnSaveAll').prop('disabled', false);
                    }
                },
            });
        }, 500);
    });

    // ─────────────────────────────────────────────────────────
    // 7. SAVE ALL
    // ─────────────────────────────────────────────────────────
    $(document).on('click', '#btnSaveAll', function () {
        const btn    = $(this);
        const formEl = $('#frmEnrolment');

        btn.prop('disabled', true)
           .html('<i class="fa fa-spinner fa-spin me-2"></i>Saving…');

        const formData = new FormData(formEl[0]);
        formData.append('citydesc', $('#txtCity option:selected').text());
        formData.append('brgydesc', $('#txtBrgy option:selected').text());
        formData.append('provdesc', $('#txtProvince option:selected').text());

        axios.post('/enroll/save', formData)
            .then(function (res) {
                clearAllErrors();
                clearTabErrorBadges();

                // ── Validation errors (201)
                if (res.data.status == 201) {
                    $.each(res.data.error, function (field, msgs) {
                        const input = $(`[name="${field}"]`);
                        const span  = $(`.${field}_error`);
                        setFieldError(input, span, msgs[0]);
                    });

                    // Show badges on stepper + error banner
                    showTabErrorBadges();

                    // Jump to first tab with errors
                    jumpToFirstErrorTab();
                }

                // ── Success (200)
                if (res.data.status == 200) {
                    formEl[0].reset();
                    clearAllErrors();
                    clearTabErrorBadges();
                    resetStepper();

                    Swal.fire({
                        icon             : 'success',
                        title            : 'Employee Enrolled!',
                        text             : res.data.msg,
                        timer            : 2500,
                        showConfirmButton : false,
                        confirmButtonColor: '#008080',
                    });
                }

                // ── Warning (202 / 203)
                if (res.data.status == 202 || res.data.status == 203) {
                    Swal.fire({
                        icon             : 'warning',
                        title            : 'Notice',
                        text             : res.data.msg,
                        confirmButtonColor: '#008080',
                    });
                }
            })
            .catch(function () {
                Swal.fire({
                    icon             : 'error',
                    title            : 'Connection Error',
                    text             : 'Could not reach the server. Please try again.',
                    confirmButtonColor: '#008080',
                });
            })
            .finally(function () {
                btn.prop('disabled', false)
                   .html('<i class="fa fa-save me-2"></i>Save All Information');
            });
    });

    // ── Live error clearing — as user fixes fields, clear their error
    //    and immediately refresh the tab badge counts
    $(document).on('input', '.form-control', function () {
        const field = $(this);

        // Only act if this field is currently marked invalid
        if (!field.hasClass('is-invalid')) return;

        const val = field.val().trim();
        if (val === '') return; // still empty — leave the error showing

        // Clear this field's error state
        field.removeClass('is-invalid');

        // Find the closest sibling error span (handles both patterns in the blade)
        const errorSpan = field.siblings('.error-text').first();
        if (errorSpan.length) {
            errorSpan.text('').removeClass('text-danger text-success text-muted');
        }

        // Refresh badge counts immediately (no debounce needed — DOM already updated)
        if ($('.step-error-badge').length > 0) {
            showTabErrorBadges();
        }
    });

    $(document).on('change', '.form-select', function () {
        const field = $(this);

        if (!field.hasClass('is-invalid')) return;

        const val = field.val();
        if (val === null || val === '') return; // truly no selection — leave error

        // Clear this select's error state
        field.removeClass('is-invalid');

        const errorSpan = field.siblings('.error-text').first();
        if (errorSpan.length) {
            errorSpan.text('').removeClass('text-danger text-success text-muted');
        }

        if ($('.step-error-badge').length > 0) {
            showTabErrorBadges();
        }
    });

    // ─────────────────────────────────────────────────────────
    // 8. HELPERS
    // ─────────────────────────────────────────────────────────

    function setFieldError(field, span, message) {
        $(field).addClass('is-invalid').removeClass('is-valid');
        if (span && span.length) span.text(message).removeClass('text-success text-muted').addClass('text-danger');
    }

    function setFieldSuccess(field, span, message) {
        $(field).addClass('is-valid').removeClass('is-invalid');
        if (span && span.length) span.text(message).removeClass('text-danger text-muted').addClass('text-success');
    }

    function clearAllErrors() {
        $('span.error-text').text('').removeClass('text-danger text-success text-muted');
        $('input, select').removeClass('is-invalid is-valid border border-danger');
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function jumpToFirstErrorTab() {
        for (const { btnId, pane } of tabPaneMap) {
            const hasError = $(`${pane} .error-text`).filter(function () {
                return $(this).text().trim() !== '';
            }).length > 0;

            if (hasError) {
                document.getElementById(btnId)?.click();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                break;
            }
        }
    }

    function resetStepper() {
        document.querySelectorAll('.step-btn').forEach((b, i) => {
            b.classList.remove('active', 'visited', 'has-error');
            $(b).find('.step-error-badge').remove();
            const circle = b.querySelector('.step-circle');
            if (circle) {
                circle.innerHTML       = i + 1;
                delete circle.dataset.checked;
            }
        });
        const firstBtn = document.getElementById('home-tab');
        if (firstBtn) firstBtn.click();
    }

    function showToast(type, message) {
        const colors = { success: '#10b981', error: '#ef4444', info: '#008080' };
        const icons  = { success: '✓', error: '✗', info: 'ℹ' };
        const toast  = $(`
            <div style="
                position:fixed;bottom:24px;right:24px;z-index:9999;
                background:#fff;border-left:4px solid ${colors[type]};
                border-radius:8px;padding:12px 18px;
                box-shadow:0 4px 20px rgba(0,0,0,.12);
                font-size:.82rem;color:#334155;
                display:flex;align-items:center;gap:10px;">
                <span style="color:${colors[type]};font-weight:700">${icons[type]}</span>
                ${message}
            </div>
        `);
        $('body').append(toast);
        setTimeout(() => toast.fadeOut(300, () => toast.remove()), 3500);
    }

});

/* ── Global helper called by Back/Next buttons in the blade ── */
function goTab(id) {
    const btn = document.getElementById(id);
    if (btn) { btn.click(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
}
