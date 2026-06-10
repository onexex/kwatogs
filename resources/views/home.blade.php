@extends('layout.app')
@section('content')
    <style>
        /* ── Design tokens (shared with Edit Employee) ───────────── */
        :root {
            --teal:         #008080;
            --teal-dark:    #006666;
            --teal-mid:     #4db6ac;
            --teal-light:   #e0f2f1;
            --slate:        #334155;
            --slate-light:  #64748b;
            --muted:        #94a3b8;
            --bg:           #f1f5f9;
            --surface:      #ffffff;
            --border:       #e2e8f0;
            --danger:       #ef4444;
            --success:      #10b981;
            --warning:      #f59e0b;
            --radius-card:  14px;
            --shadow-card:  0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
        }

        .home-shell {
            background: var(--bg);
            min-height: 100vh;
            margin: -1rem -1.5rem;
            padding: 24px 28px 60px;
        }

        /* ── Top header bar ──────────────────────────────────────── */
        .home-topbar {
            background: var(--surface);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        .home-topbar .page-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--slate);
            margin: 0;
            letter-spacing: -.2px;
            text-transform: uppercase;
        }
        .home-topbar .breadcrumb {
            font-size: 0.75rem;
            margin: 2px 0 0;
            padding: 0;
            background: none;
        }
        .home-topbar .breadcrumb-item.active {
            color: var(--teal);
            font-weight: 600;
        }

        .date-range-group {
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }
        .date-range-group .form-control {
            border: none;
            background: #fafbfc;
            font-size: 0.85rem;
            color: var(--slate);
        }
        .date-range-group .form-control:focus {
            box-shadow: none;
            background: #fff;
        }
        .date-range-group .input-group-text {
            background: #fafbfc;
            border: none;
            color: var(--muted);
            font-size: 0.75rem;
        }
        .btn-teal {
            background: var(--teal);
            border-color: var(--teal);
            color: #fff;
        }
        .btn-teal:hover {
            background: var(--teal-dark);
            border-color: var(--teal-dark);
            color: #fff;
        }

        /* ── Summary cards ────────────────────────────────────────── */
        .summary-card {
            background: var(--surface);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border);
            padding: 20px 22px;
            height: 100%;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,.06);
        }
        .summary-card.accent {
            background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dark) 100%);
            border: none;
        }
        .summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--teal-light);
            color: var(--teal);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .summary-card.accent .summary-icon {
            background: rgba(255,255,255,.18);
            color: #fff;
        }
        .summary-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--slate-light);
            margin: 0 0 4px;
        }
        .summary-card.accent .summary-label {
            color: rgba(255,255,255,.8);
        }
        .summary-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--slate);
            margin: 0;
            line-height: 1.1;
        }
        .summary-card.accent .summary-value {
            color: #fff;
        }
        .summary-unit {
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* ── Section card (matches edit_employee .sc) ────────────── */
        .sc {
            background: var(--surface);
            border-radius: var(--radius-card);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-card);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .sc-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 22px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(to right, #fafcff, #f8fbfa);
        }
        .sc-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: var(--teal-light);
            color: var(--teal);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            flex-shrink: 0;
        }
        .sc-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--slate);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin: 0;
        }
        .sc-body { padding: 0; }

        /* ── Table styling ────────────────────────────────────────── */
        .table-sticky-header thead th {
            position: sticky !important;
            top: 0;
            background-color: #fafbfc;
            z-index: 10;
            border-bottom: 2px solid var(--border);
            color: var(--slate-light);
            font-size: 0.7rem;
            letter-spacing: .5px;
        }

        .table-hover tbody tr:hover {
            background-color: var(--teal-light);
            transition: background-color 0.2s ease;
        }

        .summary-row {
            background-color: #f8fafc !important;
            border-bottom: 2px solid var(--border);
        }

        /* ── Action buttons ───────────────────────────────────────── */
        .transition-hover {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .transition-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
        }

        .transition-hover:active {
            transform: translateY(0);
        }

        .btn-punch-out {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
        }
        .btn-punch-in {
            background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dark) 100%);
            border: none;
        }
    </style>

    <div class="home-shell">

        {{-- ── Top header ── --}}
        <div class="home-topbar">
            <div>
                <h4 class="page-title">Shift Monitoring</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item text-muted">Dashboard</li>
                        <li class="breadcrumb-item active fw-semibold" aria-current="page">Attendance Logs</li>
                    </ol>
                </nav>
            </div>

            <div class="date-range-group d-flex align-items-stretch shadow-sm" style="max-width: 380px;">
                <input type="date" id="txtDateFrom" value="{{ date('Y-m-d', strtotime('-10 days')) }}" class="form-control">
                <span class="input-group-text">to</span>
                <input type="date" id="txtDateTo" value="{{ date('Y-m-d') }}" class="form-control">
                <button type="button" id="btnLogRef" class="btn btn-teal" title="Refresh Logs">
                    <i class="fa fa-refresh"></i>
                </button>
            </div>
        </div>

        {{-- ── Summary cards ── --}}
        <div class="row mb-4 g-3">
            <div class="col-md-4">
                <div class="summary-card accent">
                    <div class="summary-icon"><i class="fa fa-clock"></i></div>
                    <div>
                        <p class="summary-label">Total Hours</p>
                        <h2 class="summary-value">
                            <span id="overallTotalHours">0.00</span> <span class="summary-unit">hrs</span>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="summary-icon" style="background:#fee2e2;color:var(--danger);"><i class="fa fa-hourglass-half"></i></div>
                    <div>
                        <p class="summary-label">Late Deductions</p>
                        <h2 class="summary-value text-danger">
                            <span id="overallLateMins">0</span> <span class="summary-unit">mins</span>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="summary-icon" style="background:#dcfce7;color:var(--success);"><i class="fa fa-circle-check"></i></div>
                    <div>
                        <p class="summary-label">Period Status</p>
                        <h2 class="summary-value text-success" id="overallStatus" style="font-size:1.15rem;">Cleared</h2>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Attendance log ── --}}
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon"><i class="bi bi-clock-history"></i></div>
                <h5 class="sc-title">Attendance Log</h5>
            </div>
            <div class="sc-body">
                <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                    <table class="table table-hover align-middle table-sticky-header mb-0" id="attendanceTable">
                        <thead>
                            <tr class="text-secondary fw-bold text-uppercase text-center">
                                <th class="ps-4">Date</th>
                                <th>Day</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Duration</th>
                                <th>Night Diff</th>
                                <th class="pe-4">Remarks</th>
                            </tr>
                        </thead>
                        <tbody id="tblAttendance" class="text-center border-top-0">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Action buttons ── --}}
        <div class="row mt-4">
            <div class="col-12 text-end">
                <div class="d-inline-flex gap-3">
                    <button type="button" id="btnTimeOut"
                        class="btn btn-punch-out text-white rounded-pill px-4 py-2 fw-bold shadow-sm transition-hover">
                        <i class="bi bi-box-arrow-right me-1"></i> Time Out
                    </button>
                    <button type="button" id="btnTimeIn"
                        class="btn btn-punch-in text-white rounded-pill px-4 py-2 fw-bold shadow-sm transition-hover">
                        <i class="bi bi-clock me-1"></i> Time In
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {

            // 412026 - Toggle Password Visibility
            $('.toggle-password').on('click', function() {
                const input = $(this).closest('.input-group').find('input');
                const icon = $(this).find('i');

                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye text-primary');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye text-primary').addClass('fa-eye-slash');
                }
            });

            $(document).on("click", "#btnUpdatePass", function() {

                const btn = $(this);
                const form = document.getElementById('changePasswordForm');
                const formData = new FormData(form);

                btn.prop('disabled', true).html(
                    '<i class="fa-solid fa-spinner fa-spin me-2"></i>Updating...');
                $('.error-text').text('');
                $('.form-control').removeClass('is-invalid');

                axios.post('/update-password', formData)
                    .then(response => {
                        if (response.data.status == 200) {
                            Swal.fire('Success!', response.data.message, 'success');
                            $('#changePassModal').modal('hide');
                            form.reset();
                        }
                    })
                    .catch(error => {

                        if (error.response && error.response.status === 422) {
                            const errors = error.response.data.errors;
                            Object.keys(errors).forEach(key => {

                                $(`[name="${key}"]`).addClass('is-invalid');
                                $(`.${key}_error`).text(errors[key][0]);
                            });
                        } else {
                            Swal.fire('Error', 'Something went wrong!', 'error');
                            console.error(error);
                        }
                    })
                    .finally(() => {

                        btn.prop('disabled', false).html(
                            '<i class="fa-solid fa-save me-2"></i>Change Password');
                    });
            });

            $('#new_password').on('keyup', function() {
                let val = $(this).val();
                let strength = 0;
                if (val.length > 7) strength += 25;
                if (val.match(/[a-z]/) && val.match(/[A-Z]/)) strength += 25;
                if (val.match(/\d/)) strength += 25;
                if (val.match(/[^a-zA-Z\d]/)) strength += 25;

                let bar = $('#strengthBar');
                let text = $('#strengthText');
                bar.css('width', strength + '%');

                if (strength <= 25) {
                    bar.addClass('bg-danger').removeClass('bg-warning bg-success');
                    text.text('Weak password ⚠️').addClass('text-danger').removeClass(
                        'text-warning text-success');
                } else if (strength <= 75) {
                    bar.addClass('bg-warning').removeClass('bg-danger bg-success');
                    text.text('Good password 👍').addClass('text-warning').removeClass(
                        'text-danger text-success');
                } else {
                    bar.addClass('bg-success').removeClass('bg-danger bg-warning');
                    text.text('Strong password 💪').addClass('text-success').removeClass(
                        'text-danger text-warning');
                }

                checkMatch(); // Check matching on every keyup of new password
            });

            // 2. Real-time Password Matching Logic
            function checkMatch() {
                let original = $('#new_password').val();
                let confirmation = $('input[name="new_password_confirmation"]').val();
                let btn = $('#btnUpdatePass');

                if (confirmation === '') {
                    $('.conf_msg').text('');
                    btn.prop('disabled', true);
                    return;
                }

                if (original === confirmation) {
                    $('input[name="new_password_confirmation"]').addClass('is-valid').removeClass('is-invalid');
                    $('.conf_msg').text('Passwords match!').addClass('text-success').removeClass('text-danger');
                    btn.prop('disabled', false); // I-enable ang button kung match
                } else {
                    $('input[name="new_password_confirmation"]').addClass('is-invalid').removeClass('is-valid');
                    $('.conf_msg').text('Passwords do not match.').addClass('text-danger').removeClass(
                        'text-success');
                    btn.prop('disabled', true); // I-disable kung hindi match
                }
            }

            $('input[name="new_password_confirmation"]').on('keyup', function() {
                checkMatch();
            });

            // 3. Modal Cleanup (Mahalaga para hindi naiiwan ang kulay pag-close)
            $('#changePassModal').on('hidden.bs.modal', function() {
                $('#changePasswordForm')[0].reset();
                $('.form-control').removeClass('is-invalid is-valid');
                $('.error-text, .conf_msg, #strengthText').text('');
                $('#strengthBar').css('width', '0%').removeClass('bg-danger bg-warning bg-success');
                $('#btnUpdatePass').prop('disabled', true);
            });

            const swalLoader = (title, text) => {
                Swal.fire({
                    title: title,
                    text: text,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading()
                });
            };

            // Reusable Punch Function
            function handleAttendancePunch(action, url, title, text, confirmBtnColor) {
                Swal.fire({
                    title: title,
                    text: text,
                    icon: action === 'in' ? 'question' : 'warning',
                    showCancelButton: true,
                    confirmButtonText: `Yes, Time ${action === 'in' ? 'In' : 'Out'}`,
                    confirmButtonColor: confirmBtnColor,
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    customClass: {
                        confirmButton: 'rounded-pill',
                        cancelButton: 'rounded-pill'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        swalLoader('Processing...', `Logging your time-${action} record.`);
                        axios.post(url)
                            .then(res => {
                                Swal.fire({
                                    icon: res.data.status === 'success' ? 'success' : 'warning',
                                    title: res.data.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                loadAttendance($('#txtDateFrom').val(), $('#txtDateTo').val());
                            })
                            .catch(err => {
                                Swal.fire('Error', `Unable to process time-${action} request.`,
                                'error');
                            });
                    }
                });
            }

            // Cleaner Click Listeners
            $('#btnTimeIn').click(e => {
                e.preventDefault();
                handleAttendancePunch('in', "{{ route('attendance.timein') }}", 'Confirm Time In?',
                    'Ready to log your attendance?', '#008080');
            });

            $('#btnTimeOut').click(e => {
                e.preventDefault();
                handleAttendancePunch('out', "{{ route('attendance.timeout') }}", 'Confirm Time Out?',
                    'End your shift for the day?', '#ef4444');
            });

            // 🛠️ HELPER: Safe DOM Element Builder (Huwag buburahin)
            function createEl(tag, className, text = null) {
                const el = document.createElement(tag);
                if (className) el.className = className;
                if (text !== null) el.textContent = text;
                return el;
            }

            // 🔄 LOAD ATTENDANCE LIST & UPDATE CARDS
            function loadAttendance(from, to) {
                $("#tblAttendance").html(
                    '<tr><td colspan="7" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading logs...</td></tr>'
                    );

                axios.get('/attendance/list', {
                        params: {
                            from,
                            to
                        }
                    })
                    .then(res => {
                        const tbody = document.getElementById('tblAttendance');
                        tbody.innerHTML = ''; // Clear loading spinner

                        const punches = res.data.punches;
                        const summary = res.data.summary;
                        const grouped = {};

                        // 🌟 VARIABLES PARA SA SUMMARY CARDS
                        let grandTotalHours = 0;
                        let grandTotalLates = 0;
                        let hasIncompleteLogs = false;

                        // Group punches by date
                        punches.forEach(p => {
                            if (!grouped[p.attendance_date]) grouped[p.attendance_date] = [];
                            grouped[p.attendance_date].push(p);
                        });

                        if (Object.keys(grouped).length === 0) {
                            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No attendance records found for this period.</td></tr>';
                        }

                        // Build the table safely at i-compute ang totals
                        Object.keys(grouped).forEach(date => {

                            // 1. Build Individual Punch Rows
                            grouped[date].forEach(p => {
                                const tr = document.createElement('tr');

                                tr.appendChild(createEl('td', 'ps-4 fw-bold text-dark', p
                                    .attendance_date));
                                tr.appendChild(createEl('td', 'text-muted small', p.day));

                                const tdIn = createEl('td', '');
                                tdIn.appendChild(createEl('span',
                                    'badge bg-light text-primary border-0 fw-bold', p
                                    .time_in));
                                tr.appendChild(tdIn);

                                const tdOut = createEl('td', '');
                                tdOut.appendChild(createEl('span',
                                    'badge bg-light text-danger border-0 fw-bold', p
                                    .time_out));
                                tr.appendChild(tdOut);

                                tr.appendChild(createEl('td', 'text-muted', p.duration));
                                tr.appendChild(createEl('td', 'text-muted small', p
                                .night_diff));

                                const tdRemarks = createEl('td', 'pe-4');
                                tdRemarks.appendChild(createEl('span',
                                    'small text-secondary fst-italic', p.remarks));
                                tr.appendChild(tdRemarks);

                                tbody.appendChild(tr);
                            });

                            // 2. Build the Summary Row & ACCUMULATE TOTALS
                            const s = summary.find(x => x.attendance_date === date);
                            if (s) {
                                // 👉 I-plus ang oras at lates para sa Cards
                                grandTotalHours += parseFloat(s.total_hours || 0);
                                grandTotalLates += parseInt(s.mins_late || 0);

                                // 👉 Check kung may "Incomplete" o "Missing" status
                                const dailyStatus = (s.status || '').toLowerCase();
                                if (dailyStatus.includes('incomplete') || dailyStatus.includes(
                                        'missing')) {
                                    hasIncompleteLogs = true;
                                }

                                // 👉 I-build ang Summary Row UI
                                const tr = document.createElement('tr');
                                tr.className = 'summary-row fw-bold';

                                const tdTitle = createEl('td', 'text-start ps-4 small',
                                'DAILY SUMMARY');
                                tdTitle.colSpan = 2;
                                tr.appendChild(tdTitle);

                                tr.appendChild(createEl('td', 'small text-primary',
                                    `HRS: ${s.total_hours}`));
                                tr.appendChild(createEl('td', 'small text-muted',
                                    `ND: ${s.mins_night_diff}m`));

                                const tdLate = createEl('td', 'small', `LATE: ${s.mins_late}m`);
                                tdLate.style.color = s.mins_late > 0 ? '#dc3545' : '#6c757d';
                                tr.appendChild(tdLate);

                                const tdUT = createEl('td', 'small', `UT: ${s.mins_undertime}m`);
                                tdUT.style.color = s.mins_undertime > 0 ? '#fd7e14' : '#6c757d';
                                tr.appendChild(tdUT);

                                const tdStatus = createEl('td', 'pe-4 text-end');
                                tdStatus.appendChild(createEl('span',
                                    'badge bg-white border text-secondary rounded-pill', s
                                    .status));
                                tr.appendChild(tdStatus);

                                tbody.appendChild(tr);
                            }
                        });

                        // 🌟 I-UPDATE ANG UI NG TATLONG CARDS SA TAAS 🌟
                        document.getElementById('overallTotalHours').textContent = grandTotalHours.toFixed(2);
                        document.getElementById('overallLateMins').textContent = grandTotalLates;

                        const statusEl = document.getElementById('overallStatus');
                        if (hasIncompleteLogs) {
                            statusEl.textContent = 'Needs Action';
                            statusEl.className = 'summary-value text-warning';
                            statusEl.style.fontSize = '1.15rem';
                        } else if (grandTotalLates > 0) {
                            statusEl.textContent = 'Active (With Lates)';
                            statusEl.className = 'summary-value';
                            statusEl.style.color = 'var(--teal)';
                            statusEl.style.fontSize = '1.15rem';
                        } else {
                            statusEl.textContent = 'Perfect Attendance';
                            statusEl.className = 'summary-value text-success';
                            statusEl.style.fontSize = '1.15rem';
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        $("#tblAttendance").html(
                            '<tr><td colspan="7" class="text-center py-5 text-danger">Failed to load logs. Please refresh.</td></tr>'
                            );
                    });
            }

            // Refresh Events
            $('#btnLogRef').click(() => loadAttendance($('#txtDateFrom').val(), $('#txtDateTo').val()));

            // Initial load
            loadAttendance($('#txtDateFrom').val(), $('#txtDateTo').val());
        });
    </script>
@endsection
