@extends('layout.app')
@section('content')

<style>
    /* Uniform Sticky Header */
    .table-sticky-header thead th {
        position: sticky !important;
        top: 0;
        background-color: #ffffff;
        z-index: 10;
        border-bottom: 2px solid #f8f9fa;
    }
    .table-hover tbody tr:hover {
        background-color: #fcfcfc;
        transition: background-color 0.2s ease;
    }
    .summary-row {
        background-color: #f8f9fa !important;
        border-bottom: 2px solid #dee2e6;
    }
</style>

<div class="container-fluid px-4 py-3">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">SHIFT MONITORING</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted">Dashboard</li>
                    <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Attendance Logs</li>
                </ol>
            </nav>
        </div>
        
        <div class="d-flex gap-2 align-items-center">
            <div class="input-group input-group-sm shadow-sm">
                <input type="date" id="txtDateFrom" value="{{ date('Y-m-d', strtotime('-10 days')) }}" class="form-control border-0 bg-white">
                <span class="input-group-text bg-white border-0 text-muted small">to</span>
                <input type="date" id="txtDateTo" value="{{ date('Y-m-d') }}" class="form-control border-0 bg-white">
                <button type="button" id="btnLogRef" class="btn btn-primary border-0 shadow-sm" title="Refresh Logs">
                    <i class="fa fa-refresh"></i>
                </button>
            </div>
        </div>

        
    </div>

    <div class="row mb-4 g-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-primary text-white h-100">
            <div class="card-body">
                <h6 class="opacity-75 mb-1">Total Hours</h6>
                <h2 class="fw-bold mb-0">
                    <span id="overallTotalHours">0.00</span> <span class="fs-6 fw-normal">hrs</span>
                </h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="text-muted mb-1">Late Deductions</h6>
                <h2 class="fw-bold text-danger mb-0">
                    <span id="overallLateMins">0</span> <span class="fs-6 fw-normal">mins</span>
                </h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="text-muted mb-1">Period Status</h6>
                <h2 class="fw-bold text-success mb-0" id="overallStatus">Cleared</h2>
            </div>
        </div>
    </div>
</div>

     

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h5 class="fw-bold text-secondary text-uppercase tracking-wide m-0">
                <i class="bi bi-clock-history me-2 text-primary"></i> Attendance Log
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                <table class="table table-hover align-middle table-sticky-header mb-0" id="attendanceTable">
                    <thead class="bg-light">
                        <tr class="text-secondary small fw-bold text-uppercase tracking-wider text-center">
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

    <div class="row mt-4">
    <div class="col-12 text-end">
        <div class="d-inline-flex gap-3 ">
            <button type="button" id="btnTimeOut" class="btn btn-danger rounded-pill px-4 py-2 fw-bold shadow-sm transition-hover">
                <i class="bi bi-box-arrow-right me-1"></i> Time Out
            </button>
            <button type="button" id="btnTimeIn" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm transition-hover">
                <i class="bi bi-clock me-1"></i> Time In
            </button>
        </div>
    </div>
</div>

<style>
    /* Subtle hover effect to make it feel responsive */
    .transition-hover {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .transition-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
    }
    .transition-hover:active {
        transform: translateY(0);
    }
</style>
</div>

<script>
$(document).ready(function () {

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

    $(document).on("click", "#btnUpdatePass", function () { 
      
        const btn = $(this);
        const form = document.getElementById('changePasswordForm');
        const formData = new FormData(form);

        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Updating...');
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
                
                btn.prop('disabled', false).html('<i class="fa-solid fa-save me-2"></i>Change Password');
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
            text.text('Weak password ⚠️').addClass('text-danger').removeClass('text-warning text-success');
        } else if (strength <= 75) {
            bar.addClass('bg-warning').removeClass('bg-danger bg-success');
            text.text('Good password 👍').addClass('text-warning').removeClass('text-danger text-success');
        } else {
            bar.addClass('bg-success').removeClass('bg-danger bg-warning');
            text.text('Strong password 💪').addClass('text-success').removeClass('text-danger text-warning');
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
            $('.conf_msg').text('Passwords do not match.').addClass('text-danger').removeClass('text-success');
            btn.prop('disabled', true); // I-disable kung hindi match
        }
    }

    $('input[name="new_password_confirmation"]').on('keyup', function() {
        checkMatch();
    });

    // 3. Modal Cleanup (Mahalaga para hindi naiiwan ang kulay pag-close)
    $('#changePassModal').on('hidden.bs.modal', function () {
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

    // // 🕒 TIME IN
    // $('#btnTimeIn').click(function (e) {
    //     e.preventDefault();
    //     Swal.fire({
    //         title: 'Confirm Time In?',
    //         text: 'Are you ready to log your attendance for today?',
    //         icon: 'question',
    //         showCancelButton: true,
    //         confirmButtonText: 'Yes, Time In',
    //         confirmButtonColor: '#0d6efd',
    //         cancelButtonColor: '#6c757d',
    //         reverseButtons: true,
    //         customClass: { confirmButton: 'rounded-pill', cancelButton: 'rounded-pill' }
    //     }).then((result) => {
    //         if (result.isConfirmed) {
    //             swalLoader('Processing...', 'Logging your time-in record.');
    //             axios.post("{{ route('attendance.timein') }}")
    //                 .then(res => {
    //                     Swal.close();
    //                     Swal.fire({
    //                         icon: res.data.status === 'success' ? 'success' : 'warning',
    //                         title: res.data.message,
    //                         timer: 2000,
    //                         showConfirmButton: false
    //                     });
    //                     loadAttendance($('#txtDateFrom').val(), $('#txtDateTo').val());
    //                 })
    //                 .catch(err => {
    //                     Swal.close();
    //                     Swal.fire('Error', 'Unable to process time-in request.', 'error');
    //                 });
    //         }
    //     });
    // });

    // // 🚪 TIME OUT
    // $('#btnTimeOut').click(function (e) {
    //     e.preventDefault();
    //     Swal.fire({
    //         title: 'Confirm Time Out?',
    //         text: 'Confirming your time-out will end your shift for the day.',
    //         icon: 'warning',
    //         showCancelButton: true,
    //         confirmButtonText: 'Yes, Time Out',
    //         confirmButtonColor: '#dc3545',
    //         cancelButtonColor: '#6c757d',
    //         reverseButtons: true,
    //         customClass: { confirmButton: 'rounded-pill', cancelButton: 'rounded-pill' }
    //     }).then((result) => {
    //         if (result.isConfirmed) {
    //             swalLoader('Processing...', 'Logging your time-out record.');
    //             axios.post("{{ route('attendance.timeout') }}")
    //                 .then(res => {
    //                     Swal.close();
    //                     Swal.fire({
    //                         icon: res.data.status === 'success' ? 'success' : 'warning',
    //                         title: res.data.message,
    //                         timer: 2000,
    //                         showConfirmButton: false
    //                     });
    //                     loadAttendance($('#txtDateFrom').val(), $('#txtDateTo').val());
    //                 })
    //                 .catch(err => {
    //                     Swal.close();
    //                     Swal.fire('Error', 'Unable to process time-out request.', 'error');
    //                 });
    //         }
    //     });
    // });

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
            customClass: { confirmButton: 'rounded-pill', cancelButton: 'rounded-pill' }
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
                        Swal.fire('Error', `Unable to process time-${action} request.`, 'error');
                    });
            }
        });
    }

    // Cleaner Click Listeners
    $('#btnTimeIn').click(e => {
        e.preventDefault();
        handleAttendancePunch('in', "{{ route('attendance.timein') }}", 'Confirm Time In?', 'Ready to log your attendance?', '#0d6efd');
    });

    $('#btnTimeOut').click(e => {
        e.preventDefault();
        handleAttendancePunch('out', "{{ route('attendance.timeout') }}", 'Confirm Time Out?', 'End your shift for the day?', '#dc3545');
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
        $("#tblAttendance").html('<tr><td colspan="7" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading logs...</td></tr>');
        
        axios.get('/attendance/list', { params: { from, to } })
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

            // Build the table safely at i-compute ang totals
            Object.keys(grouped).forEach(date => {
                
                // 1. Build Individual Punch Rows
                grouped[date].forEach(p => {
                    const tr = document.createElement('tr');
                    
                    tr.appendChild(createEl('td', 'ps-4 fw-bold text-dark', p.attendance_date));
                    tr.appendChild(createEl('td', 'text-muted small', p.day));
                    
                    const tdIn = createEl('td', '');
                    tdIn.appendChild(createEl('span', 'badge bg-light text-primary border-0 fw-bold', p.time_in));
                    tr.appendChild(tdIn);
                    
                    const tdOut = createEl('td', '');
                    tdOut.appendChild(createEl('span', 'badge bg-light text-danger border-0 fw-bold', p.time_out));
                    tr.appendChild(tdOut);
                    
                    tr.appendChild(createEl('td', 'text-muted', p.duration));
                    tr.appendChild(createEl('td', 'text-muted small', p.night_diff));
                    
                    const tdRemarks = createEl('td', 'pe-4');
                    tdRemarks.appendChild(createEl('span', 'small text-secondary fst-italic', p.remarks)); 
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
                    if (dailyStatus.includes('incomplete') || dailyStatus.includes('missing')) {
                        hasIncompleteLogs = true;
                    }

                    // 👉 I-build ang Summary Row UI
                    const tr = document.createElement('tr');
                    tr.className = 'summary-row fw-bold';
                    
                    const tdTitle = createEl('td', 'text-start ps-4 small', 'DAILY SUMMARY');
                    tdTitle.colSpan = 2;
                    tr.appendChild(tdTitle);
                    
                    tr.appendChild(createEl('td', 'small text-primary', `HRS: ${s.total_hours}`));
                    tr.appendChild(createEl('td', 'small text-muted', `ND: ${s.mins_night_diff}m`));
                    
                    const tdLate = createEl('td', 'small', `LATE: ${s.mins_late}m`);
                    tdLate.style.color = s.mins_late > 0 ? '#dc3545' : '#6c757d';
                    tr.appendChild(tdLate);
                    
                    const tdUT = createEl('td', 'small', `UT: ${s.mins_undertime}m`);
                    tdUT.style.color = s.mins_undertime > 0 ? '#fd7e14' : '#6c757d';
                    tr.appendChild(tdUT);
                    
                    const tdStatus = createEl('td', 'pe-4 text-end');
                    tdStatus.appendChild(createEl('span', 'badge bg-white border text-secondary rounded-pill', s.status));
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
                statusEl.className = 'fw-bold text-warning mb-0';
            } else if (grandTotalLates > 0) {
                statusEl.textContent = 'Active (With Lates)';
                statusEl.className = 'fw-bold text-primary mb-0';
            } else {
                statusEl.textContent = 'Perfect Attendance';
                statusEl.className = 'fw-bold text-success mb-0';
            }
        })
        .catch(err => {
            console.error(err);
            $("#tblAttendance").html('<tr><td colspan="7" class="text-center py-5 text-danger">Failed to load logs. Please refresh.</td></tr>');
        });
    }

    // Refresh Events
    $('#btnLogRef').click(() => loadAttendance($('#txtDateFrom').val(), $('#txtDateTo').val()));
    
    // Initial load
    loadAttendance($('#txtDateFrom').val(), $('#txtDateTo').val());
});
</script>
@endsection