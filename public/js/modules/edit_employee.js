$(document).ready(function () {

    // ─────────────────────────────────────────────────────────
    // 1. CONFIG
    // ─────────────────────────────────────────────────────────
    const stepOrder = [
        'home-tab',
        'educational-tab',
        'employment-tab',
        'compliance-tab',
        'profile-pic-tab',
    ];

    const tabPaneMap = [
        { btnId: 'home-tab',        pane: '#home-tab-pane',        label: 'General Info'  },
        { btnId: 'educational-tab', pane: '#educational-tab-pane', label: 'Educational'   },
        { btnId: 'employment-tab',  pane: '#employment-tab-pane',  label: 'Employment'    },
        { btnId: 'compliance-tab',  pane: '#complaince',           label: 'Compliance'    },
        { btnId: 'profile-pic-tab', pane: '#profile-tab-pane',     label: 'Profile Photo' },
    ];

    // ─────────────────────────────────────────────────────────
    // 2. STEPPER — visited state
    // ─────────────────────────────────────────────────────────
    document.querySelectorAll('.step-btn').forEach((btn) => {
        btn.addEventListener('shown.bs.tab', function () {
            const activeId  = this.id;
            const activeIdx = stepOrder.indexOf(activeId);

            document.querySelectorAll('.step-btn').forEach((b) => {
                const idx = stepOrder.indexOf(b.id);
                b.classList.remove('active');

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
    // 3. ERROR BADGES
    // ─────────────────────────────────────────────────────────
    function showTabErrorBadges() {
        let totalErrors = 0;
        const tabsWithErrors = [];

        tabPaneMap.forEach(({ btnId, pane, label }) => {
            const btn = document.getElementById(btnId);
            if (!btn) return;

            const count = $(`${pane} .error-text`).filter(function () {
                return $(this).text().trim() !== '';
            }).length;

            $(btn).find('.step-error-badge').remove();

            if (count > 0) {
                totalErrors += count;
                tabsWithErrors.push({ btnId, label, count });
                btn.classList.add('has-error');
                $(btn).append(`<span class="step-error-badge">${count}</span>`);
            } else {
                btn.classList.remove('has-error');
            }
        });

        if (tabsWithErrors.length > 0) {
            const chips = tabsWithErrors.map(({ btnId, label, count }) => `
                <span class="error-tab-chip" onclick="goTab('${btnId}')">
                    <i class="fa fa-exclamation-circle" style="font-size:.65rem"></i>
                    ${label}
                    <span class="chip-count">${count}</span>
                </span>
            `).join('');

            $('#errorBanner').html(`
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
            `).show();
        } else {
            clearTabErrorBadges();
        }
    }

    function clearTabErrorBadges() {
        document.querySelectorAll('.step-btn').forEach((b) => {
            b.classList.remove('has-error');
            $(b).find('.step-error-badge').remove();
        });
        $('#errorBanner').hide().html('');
    }

    // ─────────────────────────────────────────────────────────
    // 4. PROVINCE / CITY / BARANGAY — pre-select existing values
    // ─────────────────────────────────────────────────────────
    function loadProvince() {
        const selectedProv = $('#txtProvince').data('selected');

        axios.post('/get_province')
            .then(function (res) {
                if (res.data.status == 200) {
                    let opts = '<option value="">— Select Province —</option>';
                    $.each(res.data.data, function (_, row) {
                        const sel = selectedProv == row.provCode ? 'selected' : '';
                        opts += `<option ${sel} value="${row.provCode}">${row.provDesc}</option>`;
                    });
                    $('#txtProvince').html(opts);

                    // Auto-load city if province is already selected
                    if (selectedProv) loadCity(selectedProv);
                }
            })
            .catch(() => showToast('error', 'Could not load provinces.'));
    }

    function loadCity(provCode) {
        const selectedCity = $('#txtCity').data('selected');

        axios.get('/get_city', { params: { id: provCode } })
            .then(function (res) {
                if (res.data.status == 200) {
                    let opts = '<option value="">— Select City —</option>';
                    $.each(res.data.data, function (_, row) {
                        const sel = selectedCity == row.citymunCode ? 'selected' : '';
                        opts += `<option ${sel} value="${row.citymunCode}">${row.citymunDesc}</option>`;
                    });
                    $('#txtCity').html(opts);

                    // Auto-load barangay if city is already selected
                    if (selectedCity) loadBrgy(selectedCity);
                }
            })
            .catch(() => showToast('error', 'Could not load cities.'));
    }

    function loadBrgy(cityCode) {
        const selectedBrgy = $('#txtBrgy').data('selected');

        axios.get('/get_brgy', { params: { id: cityCode } })
            .then(function (res) {
                if (res.data.status == 200) {
                    let opts = '<option value="">— Select Barangay —</option>';
                    $.each(res.data.data, function (_, row) {
                        const sel = selectedBrgy == row.brgyCode ? 'selected' : '';
                        opts += `<option ${sel} value="${row.brgyCode}">${row.brgyDesc}</option>`;
                    });
                    $('#txtBrgy').html(opts);
                }
            })
            .catch(() => showToast('error', 'Could not load barangays.'));
    }

    // Init on page load
    loadProvince();

    // Manual cascade changes
    $(document).on('change', '#txtProvince', function () {
        $('#txtCity').html('<option value="">Loading…</option>');
        $('#txtBrgy').html('<option value="">— Select Barangay —</option>');
        loadCity($(this).val());
    });

    $(document).on('change', '#txtCity', function () {
        $('#txtBrgy').html('<option value="">Loading…</option>');
        loadBrgy($(this).val());
    });

    // ─────────────────────────────────────────────────────────
    // 5. LIVE ERROR CLEARING — as user fixes fields
    // ─────────────────────────────────────────────────────────
    $(document).on('input', '.form-control', function () {
        const field = $(this);
        if (!field.hasClass('is-invalid')) return;
        if (field.val().trim() === '') return;

        field.removeClass('is-invalid');
        field.siblings('.error-text').first()
            .text('').removeClass('text-danger text-success text-muted');

        if ($('.step-error-badge').length > 0) showTabErrorBadges();
    });

    $(document).on('change', '.form-select', function () {
        const field = $(this);
        if (!field.hasClass('is-invalid')) return;

        const val = field.val();
        if (val === null || val === '') return;

        field.removeClass('is-invalid');
        field.siblings('.error-text').first()
            .text('').removeClass('text-danger text-success text-muted');

        if ($('.step-error-badge').length > 0) showTabErrorBadges();
    });

    // ─────────────────────────────────────────────────────────
    // 6. SAVE / UPDATE
    // ─────────────────────────────────────────────────────────
    $(document).on('click', '#btnSaveAll', function () {
        const btn    = $(this);
        const formEl = $('#frmEnrolment');

        btn.prop('disabled', true)
           .html('<i class="fa fa-spinner fa-spin me-2"></i>Updating…');

        $('#successBanner').hide().html('');

        const formData = new FormData(formEl[0]);
        formData.append('citydesc', $('#txtCity option:selected').text());
        formData.append('brgydesc', $('#txtBrgy option:selected').text());
        formData.append('provdesc', $('#txtProvince option:selected').text());

        axios.post('/employee/update', formData)
            .then(function (res) {
                clearAllErrors();
                clearTabErrorBadges();

                // ── Validation errors (201)
                if (res.data.status == 201) {
                    $.each(res.data.error, function (field, msgs) {
                        $(`[name="${field}"]`).addClass('is-invalid');
                        $(`.${field}_error`).text(msgs[0]).addClass('text-danger');
                    });

                    showTabErrorBadges();
                    jumpToFirstErrorTab();
                }

                // ── Success (200)
                if (res.data.status == 200) {
                    clearAllErrors();
                    clearTabErrorBadges();

                    // Show inline success banner
                    $('#successBanner').html(`
                        <div class="success-banner">
                            <div class="success-banner-icon">
                                <i class="fa fa-check"></i>
                            </div>
                            <div>
                                <p style="font-size:.82rem;font-weight:700;color:#065f46;margin:0 0 2px">Record Updated Successfully</p>
                                <p style="font-size:.75rem;color:#047857;margin:0">${res.data.msg ?? 'All changes have been saved.'}</p>
                            </div>
                        </div>
                    `).show();

                    window.scrollTo({ top: 0, behavior: 'smooth' });

                    // Reload after short delay so user sees the banner
                    setTimeout(() => window.location.reload(), 1800);
                }

                // ── Other notices (202 / 203)
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
                   .html('<i class="fa fa-save me-2"></i>Update Information');
            });
    });

    // ─────────────────────────────────────────────────────────
    // 7. HELPERS
    // ─────────────────────────────────────────────────────────
    function clearAllErrors() {
        $('span.error-text').text('').removeClass('text-danger text-success text-muted');
        $('input, select').removeClass('is-invalid is-valid border border-danger');
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

/* ── Global tab helper called by Back / Next buttons ── */
function goTab(id) {
    const btn = document.getElementById(id);
    if (btn) { btn.click(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
}
