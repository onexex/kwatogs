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

        /* ── Floating schedule-change assistant (chat bubble) ── */
        #saFab { position:fixed; right:24px; bottom:24px; z-index:1050; background:var(--teal,#008080); color:#fff;
            border-radius:999px; padding:12px 18px; box-shadow:0 10px 26px rgba(0,128,128,.4); cursor:pointer;
            display:flex; align-items:center; gap:8px; font-weight:700; font-size:.85rem; transition:transform .15s, box-shadow .15s; }
        #saFab:hover { transform:translateY(-2px); box-shadow:0 14px 32px rgba(0,128,128,.5); }
        #saFab .saFab-dot { width:8px; height:8px; border-radius:50%; background:#86efac; box-shadow:0 0 0 3px rgba(134,239,172,.35); }
        .sa-pop { position:fixed; right:24px; bottom:84px; z-index:1051; width:370px; max-width:calc(100vw - 32px);
            background:#fff; border:1px solid var(--border,#e2e8f0); border-radius:16px; box-shadow:0 20px 55px rgba(0,0,0,.2);
            overflow:hidden; display:none; }
        .sa-pop.open { display:block; animation:saPop .18s ease; }
        @keyframes saPop { from { opacity:0; transform:translateY(10px) scale(.98); } to { opacity:1; transform:none; } }
        .sa-pop-head { background:linear-gradient(135deg,var(--teal,#008080),var(--teal-dark,#006666)); color:#fff;
            padding:12px 14px; display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .sa-pop-avatar { width:34px; height:34px; border-radius:50%; background:rgba(255,255,255,.22); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .sa-pop-title { font-weight:800; font-size:.9rem; line-height:1.1; }
        .sa-pop-sub { font-size:.66rem; opacity:.9; }
        .sa-pop-close { background:none; border:none; color:#fff; font-size:1.4rem; line-height:1; cursor:pointer; opacity:.85; }
        .sa-pop-close:hover { opacity:1; }
        .sa-pop-body { padding:16px; max-height:62vh; overflow:auto; }
        .sa-toggle { display:flex; align-items:center; gap:6px; cursor:pointer; }
        .sa-switch { position:relative; display:inline-block; width:36px; height:18px; }
        .sa-switch input { opacity:0; width:0; height:0; }
        .sa-slider { position:absolute; inset:0; background:rgba(255,255,255,.35); border-radius:20px; transition:.3s; cursor:pointer; }
        .sa-slider:before { content:""; position:absolute; height:12px; width:12px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.3s; }
        .sa-switch input:checked + .sa-slider { background:#fff; }
        .sa-switch input:checked + .sa-slider:before { transform:translateX(18px); background:var(--teal,#008080); }
        .field-label { font-size:.66rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; display:block; }
        .sa-bubble { background:var(--teal-light,#e0f2f1); color:#04685f; border-radius:12px 12px 12px 4px; padding:10px 12px;
            font-size:.84rem; margin-bottom:14px; display:flex; align-items:flex-start; gap:8px; }
        .sa-bubble i { color:var(--teal,#008080); margin-top:2px; }
        .sa-dots { display:flex; gap:6px; }
        .sa-dots span { width:8px; height:8px; border-radius:50%; background:#cbd5e1; transition:.2s; }
        .sa-dots span.on { background:var(--teal,#008080); }
        .sa-review { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 14px; font-size:.84rem; line-height:1.7; }
        .sa-list-item { border:1px solid #e2e8f0; border-radius:8px; padding:6px 10px; margin-bottom:6px; display:flex; justify-content:space-between; align-items:center; gap:8px; }
        .sa-badge { font-size:.58rem; font-weight:700; padding:2px 8px; border-radius:999px; white-space:nowrap; }
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

        @can('createschedulechange')
        {{-- ── Floating schedule-change assistant ── --}}
        <div id="saFab" title="Kuya Kwatogs — adjust today's schedule">
            <span class="saFab-dot"></span><i class="fa fa-wand-magic-sparkles"></i> Kuya Kwatogs
        </div>
        <div id="schedAssistCard" class="sa-pop">
            <div class="sa-pop-head">
                <div class="d-flex align-items-center gap-2" style="min-width:0;">
                    <div class="sa-pop-avatar"><i class="fa fa-robot"></i></div>
                    <div style="min-width:0;">
                        <div class="sa-pop-title">Kuya Kwatogs</div>
                        <div class="sa-pop-sub text-truncate" id="saCurrent">Checking your schedule…</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="sa-toggle" title="Guided / quick form">
                        <span class="sa-switch"><input type="checkbox" id="saToggle" checked><span class="sa-slider"></span></span>
                    </label>
                    <button id="saClose" class="sa-pop-close" type="button">&times;</button>
                </div>
            </div>
            <div class="sa-pop-body">
                {{-- Guided assistant --}}
                <div id="saAssistant">
                    <div class="sa-bubble"><i class="fa fa-robot"></i><span id="saBubble">Hi, I'm Kuya Kwatogs! How can I help today? First, which day do you need to adjust?</span></div>
                    <div class="sa-step" data-step="1">
                        <label class="field-label">Which day?</label>
                        <input type="date" id="saDate" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="sa-step d-none" data-step="2">
                        <div class="row g-2">
                            <div class="col-6"><label class="field-label">New Time In</label><input type="time" id="saIn" class="form-control form-control-sm"></div>
                            <div class="col-6"><label class="field-label">New Time Out</label><input type="time" id="saOut" class="form-control form-control-sm"></div>
                        </div>
                    </div>
                    <div class="sa-step d-none" data-step="3">
                        <label class="field-label">Reason</label>
                        <input type="text" id="saReason" class="form-control form-control-sm" placeholder="e.g. Opening the store early" maxlength="255">
                    </div>
                    <div class="sa-step d-none" data-step="4">
                        <div class="sa-review" id="saReview"></div>
                    </div>
                    <div class="sa-dots mt-3" id="saDots"></div>
                    <div class="d-flex mt-2">
                        <button class="btn btn-light btn-sm" id="saBack" type="button" style="display:none;">Back</button>
                        <div class="ms-auto d-flex gap-2">
                            <button class="btn btn-teal btn-sm" id="saNext" type="button">Next</button>
                            <button class="btn btn-success btn-sm" id="saSubmit" type="button" style="display:none;"><i class="fa fa-paper-plane me-1"></i> Submit</button>
                        </div>
                    </div>
                </div>
                {{-- Quick form --}}
                <div id="saQuick" class="d-none">
                    <div class="row g-2">
                        <div class="col-6"><label class="field-label">Day</label><input type="date" id="qDate" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}"></div>
                        <div class="col-6"><label class="field-label">Reason</label><input type="text" id="qReason" class="form-control form-control-sm" maxlength="255"></div>
                        <div class="col-6"><label class="field-label">Time In</label><input type="time" id="qIn" class="form-control form-control-sm"></div>
                        <div class="col-6"><label class="field-label">Time Out</label><input type="time" id="qOut" class="form-control form-control-sm"></div>
                        <div class="col-12"><button class="btn btn-teal btn-sm w-100" id="qSubmit" type="button">Submit request</button></div>
                    </div>
                </div>
                <hr class="my-3" style="opacity:.4;">
                <div class="field-label">My recent requests</div>
                <div id="saList" class="small text-muted">—</div>
            </div>
        </div>
        @endcan

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
<script>
$(function () {
    const $card = $('#schedAssistCard');
    if (!$card.length) return;
    $('#saFab').on('click', () => $card.toggleClass('open'));
    $('#saClose').on('click', () => $card.removeClass('open'));
    let step = 1; const maxStep = 4;
    const sw = (cond, msg, title) => { if (!cond) { (window.Swal ? Swal.fire(title || 'Notice', msg, 'warning') : alert(msg)); return false; } return true; };

    $('#saToggle').on('change', function () {
        if (this.checked) { $('#saAssistant').removeClass('d-none'); $('#saQuick').addClass('d-none'); }
        else { $('#saAssistant').addClass('d-none'); $('#saQuick').removeClass('d-none'); }
    });

    const bubbles = {
        1: "Hi, I'm Kuya Kwatogs! How can I help today? First, which day do you need to adjust?",
        2: "What time should you start and end that day?",
        3: "Tell me why — your approver will see this.",
        4: "Here's your request. Looks good? Hit Submit."
    };

    function renderDots() { let h = ''; for (let i = 1; i <= maxStep; i++) h += `<span class="${i <= step ? 'on' : ''}"></span>`; $('#saDots').html(h); }

    function showStep(n) {
        step = n;
        $('.sa-step').addClass('d-none');
        $(`.sa-step[data-step="${n}"]`).removeClass('d-none');
        $('#saBack').toggle(n > 1);
        $('#saNext').toggle(n < maxStep);
        $('#saSubmit').toggle(n === maxStep);
        $('#saBubble').text(bubbles[n]);
        renderDots();
        if (n === 2) fetchCurrent($('#saDate').val());
        if (n === 4) buildReview();
    }

    function fetchCurrent(date) {
        if (!date) return;
        axios.get('/schedulerequest/current-schedule', { params: { date } }).then(r => {
            const d = r.data;
            if (d.has_schedule) {
                $('#saCurrent').html('Your schedule for ' + date + ': <b>' + (d.sched_in || '').substring(0, 5) + ' – ' + (d.sched_out || '').substring(0, 5) + '</b>');
                if (!$('#saIn').val()) $('#saIn').val((d.sched_in || '').substring(0, 5));
                if (!$('#saOut').val()) $('#saOut').val((d.sched_out || '').substring(0, 5));
            } else { $('#saCurrent').html('No schedule set for ' + date + ' yet.'); }
        }).catch(() => {});
    }

    function buildReview() {
        $('#saReview').html('<b>Date:</b> ' + $('#saDate').val() +
            '<br><b>New time:</b> ' + $('#saIn').val() + ' – ' + $('#saOut').val() +
            '<br><b>Reason:</b> ' + ($('#saReason').val() || '—'));
    }

    $('#saNext').on('click', function () {
        if (step === 1 && !sw($('#saDate').val(), 'Choose which day to adjust.', 'Pick a day')) return;
        if (step === 2 && !sw($('#saIn').val() && $('#saOut').val(), 'Enter both time in and out.', 'Times needed')) return;
        showStep(Math.min(step + 1, maxStep));
    });
    $('#saBack').on('click', () => showStep(Math.max(step - 1, 1)));
    $('#saDate').on('change', function () { if (step >= 2) fetchCurrent($(this).val()); });

    function submit(payload, done) {
        if (window.Swal) Swal.fire({ title: 'Submitting…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        axios.post('/schedulerequest/store', payload).then(r => {
            if (window.Swal) Swal.fire({ icon: 'success', title: 'Sent', text: r.data.message, timer: 2200, showConfirmButton: false });
            if (done) done();
            fetchMine();
            fetchCurrent(payload.request_date); // refresh the header to the new schedule
        }).catch(e => { const m = e.response?.data?.message || 'Could not submit.'; window.Swal ? Swal.fire('Error', m, 'error') : alert(m); });
    }

    $('#saSubmit').on('click', () => submit(
        { request_date: $('#saDate').val(), new_sched_in: $('#saIn').val(), new_sched_out: $('#saOut').val(), reason: $('#saReason').val() },
        () => { $('#saReason').val(''); showStep(1); }
    ));
    $('#qSubmit').on('click', () => {
        if (!sw($('#qDate').val() && $('#qIn').val() && $('#qOut').val(), 'Fill day, time in and out.', 'Missing')) return;
        submit({ request_date: $('#qDate').val(), new_sched_in: $('#qIn').val(), new_sched_out: $('#qOut').val(), reason: $('#qReason').val() });
    });

    function fetchMine() {
        axios.get('/schedulerequest/mine').then(r => {
            const rows = r.data || [];
            if (!rows.length) { $('#saList').html('No requests yet.'); return; }
            const style = s => ({ APPROVED: 'background:#dcfce7;color:#166534;', DISAPPROVED: 'background:#fee2e2;color:#991b1b;', FORAPPROVAL: 'background:#fef3c7;color:#92400e;' }[s] || 'background:#e2e8f0;color:#334155;');
            $('#saList').html(rows.map(r => `<div class="sa-list-item"><span>${r.request_date} · <b>${r.new_sched_in}–${r.new_sched_out}</b></span><span class="sa-badge" style="${style(r.status)}">${r.status}</span></div>`).join(''));
        }).catch(() => {});
    }

    fetchCurrent($('#saDate').val());
    showStep(1);
    fetchMine();
});
</script>
@endsection