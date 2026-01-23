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
        <div class="d-inline-flex gap-3 bg-white p-2 rounded-pill shadow-sm border border-light">
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

    const swalLoader = (title, text) => {
        Swal.fire({
            title: title,
            text: text,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });
    };

    // 🕒 TIME IN
    $('#btnTimeIn').click(function (e) {
        e.preventDefault();
        Swal.fire({
            title: 'Confirm Time In?',
            text: 'Are you ready to log your attendance for today?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Time In',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            customClass: { confirmButton: 'rounded-pill', cancelButton: 'rounded-pill' }
        }).then((result) => {
            if (result.isConfirmed) {
                swalLoader('Processing...', 'Logging your time-in record.');
                axios.post("{{ route('attendance.timein') }}")
                    .then(res => {
                        Swal.close();
                        Swal.fire({
                            icon: res.data.status === 'success' ? 'success' : 'warning',
                            title: res.data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        loadAttendance($('#txtDateFrom').val(), $('#txtDateTo').val());
                    })
                    .catch(err => {
                        Swal.close();
                        Swal.fire('Error', 'Unable to process time-in request.', 'error');
                    });
            }
        });
    });

    // 🚪 TIME OUT
    $('#btnTimeOut').click(function (e) {
        e.preventDefault();
        Swal.fire({
            title: 'Confirm Time Out?',
            text: 'Confirming your time-out will end your shift for the day.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Time Out',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            customClass: { confirmButton: 'rounded-pill', cancelButton: 'rounded-pill' }
        }).then((result) => {
            if (result.isConfirmed) {
                swalLoader('Processing...', 'Logging your time-out record.');
                axios.post("{{ route('attendance.timeout') }}")
                    .then(res => {
                        Swal.close();
                        Swal.fire({
                            icon: res.data.status === 'success' ? 'success' : 'warning',
                            title: res.data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        loadAttendance($('#txtDateFrom').val(), $('#txtDateTo').val());
                    })
                    .catch(err => {
                        Swal.close();
                        Swal.fire('Error', 'Unable to process time-out request.', 'error');
                    });
            }
        });
    });

    // 🔄 LOAD ATTENDANCE LIST
    function loadAttendance(from, to) {
        $("#tblAttendance").html('<tr><td colspan="7" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading logs...</td></tr>');
        
        axios.get('/attendance/list', { params: { from, to } })
        .then(res => {
            const tbody = document.getElementById('tblAttendance');
            tbody.innerHTML = '';
            const punches = res.data.punches;
            const summary = res.data.summary;
            const grouped = {};

            punches.forEach(p => {
                if (!grouped[p.attendance_date]) grouped[p.attendance_date] = [];
                grouped[p.attendance_date].push(p);
            });

            Object.keys(grouped).forEach(date => {
                grouped[date].forEach(p => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="ps-4 fw-bold text-dark">${p.attendance_date}</td>
                        <td class="text-muted small">${p.day}</td>
                        <td><span class="badge bg-light text-primary border-0 fw-bold">${p.time_in}</span></td>
                        <td><span class="badge bg-light text-danger border-0 fw-bold">${p.time_out}</span></td>
                        <td class="text-muted">${p.duration}</td>
                        <td class="text-muted small">${p.night_diff}</td>
                        <td class="pe-4"><span class="small text-secondary italic">${p.remarks}</span></td>
                    `;
                    tbody.appendChild(tr);
                });

                const s = summary.find(x => x.attendance_date === date);
                if (s) {
                    const tr = document.createElement('tr');
                    tr.classList.add('summary-row', 'fw-bold');
                    let lateStyle = s.mins_late > 0 ? 'color:#dc3545;' : 'color:#6c757d;';
                    let utStyle = s.mins_undertime > 0 ? 'color:#fd7e14;' : 'color:#6c757d;';

                    tr.innerHTML = `
                        <td colspan="2" class="text-start ps-4 small">DAILY SUMMARY</td>
                        <td class="small text-primary">HRS: ${s.total_hours}</td>
                        <td class="small text-muted">ND: ${s.mins_night_diff}m</td>
                        <td class="small" style="${lateStyle}">LATE: ${s.mins_late}m</td>
                        <td class="small" style="${utStyle}">UT: ${s.mins_undertime}m</td>
                        <td class="pe-4 text-end"><span class="badge bg-white border text-secondary rounded-pill">${s.status}</span></td>
                    `;
                    tbody.appendChild(tr);
                }
            });
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