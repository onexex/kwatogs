@extends('layout.app')
@section('content')

    <style>
        /* Sticky Header with modern blur effect */
        .table-sticky-header thead th {
            position: sticky !important;
            top: 0;
            background-color: #ffffff;
            z-index: 10;
            border-bottom: 2px solid #f8f9fa;
        }

        /* Custom style for the minus badge */
        .badge-deduction {
            background-color: #fff5f5;
            color: #e53e3e;
            border: 1px solid #feb2b2;
        }
        td{ 
            text-transform: uppercase;
        }
    </style>

    <div class="container-fluid px-4 py-3">

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold text-dark m-0">Attendance Management</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item text-muted">Payroll</li>
                        <li class="breadcrumb-item active fw-semibold" aria-current="page text-primary">Daily Summaries</li>
                    </ol>
                </nav>
            </div>
            <button type="button" class="btn btn-light rounded-pill px-4 shadow-sm fw-bold" onclick="location.reload()">
                <i class="fas fa-sync-alt me-2"></i> Refresh Data
            </button>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <form id="filterForm" class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label small fw-semibold text-muted">Search Employee</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" id="filterSearch" class="form-control bg-light border-0" placeholder="Type to search...">
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label small fw-semibold text-muted">From Date</label>
                        <input type="date" id="filterFrom" class="form-control bg-light border-0" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label small fw-semibold text-muted">To Date</label>
                        <input type="date" id="filterTo" class="form-control bg-light border-0" value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="col-lg-2">
                        <button type="button" id="btnApplyFilter" class="btn btn-primary rounded-pill w-100 fw-bold shadow-sm">
                            <i class="fas fa-search me-2"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                    <table class="table table-hover align-middle table-sticky-header mb-0">
                        <thead class="bg-light">
                            <tr class="text-secondary small fw-bold text-uppercase tracking-wider">
                                <th class="ps-4 py-3">Employee</th>
                                <th class="py-3">Date</th>
                                <th class="py-3 text-center">Gross Hrs</th>
                                <th class="py-3 text-center">Deductions (Min)</th>
                                <th class="py-3 text-center">Net Hrs</th>
                                <th class="pe-4 py-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="tblAttendance" class="border-top-0">
                            
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="mdlDeduction" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-header border-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold text-secondary text-uppercase tracking-wide">
                            <i class="fas fa-user-clock text-danger me-2"></i>
                            <span id="deductionEmployeeName">Log Deduction</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <form id="frmDeduction">
                            @csrf
                            <input type="hidden" id="summary_id" name="attendance_summary_id">

                            <div class="form-group mb-3">
                                <label class="form-label small fw-semibold text-muted">Minutes to Deduct <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-lg bg-light border-0 fs-6"
                                    id="numMinutes" name="deduction_minutes" placeholder="e.g. 60 for 1 hour" required />
                                <span class="text-danger small error-text deduction_minutes_error"></span>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label small fw-semibold text-muted">Reason / Remarks <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control bg-light border-0 fs-6" id="txtReason" name="reason" rows="3"
                                    placeholder="State the reason for this adjustment..."></textarea>
                                <span class="text-danger small error-text reason_error"></span>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted me-2"
                            data-bs-dismiss="modal">Cancel</button>
                        <button id="btnSaveDeduction" type="button"
                            class="btn btn-danger rounded-pill px-5 fw-bold shadow-sm">Confirm Deduction</button>
                    </div>
                </div>
            </div>
        </div>
        </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Open Modal and Set Data
            $(document).on('click', '.btn-deduct', function() {
                const id = $(this).data('id');
                const name = $(this).data('employee');

                $('#summary_id').val(id);
                $('#deductionEmployeeName').text('Deduct from: ' + name);
                $('#frmDeduction')[0].reset();
                $('.error-text').text('');
            });

            // Save Deduction via AJAX
            $('#btnSaveDeduction').on('click', function(e) {
                e.preventDefault();
                const btn = $(this);
                const formData = $('#frmDeduction').serialize();

                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Processing...');

                $.ajax({
                    url: "{{ route('attendance.deductions.store') }}", // Create this route in web.php
                    method: "POST",
                    data: formData,
                    success: function(response) {
                        if (response.status == 200) {
                            location.reload();
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).text('Confirm Deduction');
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, val) {
                                $('.' + key + '_error').text(val[0]);
                            });
                        }
                    }
                });
            });

            // Handle Deletion with Confirmation
            $(document).on('click', '.btn-delete-deduction', function() {
                const deductionId = $(this).data('id');

                Swal.fire({
                    title: 'Remove Deduction?',
                    text: "This will restore the employee's hours. This action cannot be undone.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Keep it'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Use Axios to send DELETE request
                        axios.delete(`/attendance/deductions/${deductionId}`)
                            .then(response => {
                                if (response.data.status == 200) {
                                    Swal.fire('Deleted!', response.data.message, 'success')
                                        .then(() => fetchAttendance());
                                }
                            })
                            .catch(error => {
                                Swal.fire('Error', 'Could not delete the record.', 'error');
                            });
                    }
                });
            });

            function fetchAttendance() {
                const params = {
                    search: $('#filterSearch').val(),
                    from_date: $('#filterFrom').val(),
                    to_date: $('#filterTo').val()
                };

                const tbody = $('#tblAttendance');
                // Show a clean loading state inside your existing table structure
                tbody.html(`
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                            <span class="text-muted fw-bold">Loading records...</span>
                        </td>
                    </tr>
                `);

                axios.get("{{ route('attendance.index') }}", { params: params, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(response => {
                        let html = '';
                        // Handle both array and paginated object responses
                        let records = Array.isArray(response.data) ? response.data : response.data.data;

                        if (!records || records.length === 0) {
                            html = `
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-folder-open d-block mb-2 fs-4"></i>
                                        No attendance records found for this period.
                                    </td>
                                </tr>`;
                        } else {
                            records.forEach(item => {
                                // 1. Logic for Multiple Deduction Badges
                                let deductionsHtml = '';
                                let totalDeductMins = 0;

                                if (item.manual_deductions && item.manual_deductions.length > 0) {
                                    item.manual_deductions.forEach(d => {
                                        totalDeductMins += parseInt(d.deduction_minutes || 0);
                                        deductionsHtml += `
                                            <div class="d-flex align-items-center justify-content-center mb-1">
                                                <span class="badge badge-deduction rounded-pill px-2">
                                                    -${d.deduction_minutes  }m [${d.reason}]
                                                    <i class="fas fa-times ms-1 text-danger btn-delete-deduction"
                                                    style="cursor:pointer;" 
                                                    data-id="${d.id}"
                                                    title="${d.reason || 'No reason provided'}"></i>
                                                </span>
                                            </div>`;
                                    });
                                } else {
                                    deductionsHtml = '<span class="text-muted small">-</span>';
                                }

                                // 2. Formatting
                                let date = new Date(item.attendance_date).toLocaleDateString('en-US', { 
                                    month: 'short', day: '2-digit', year: 'numeric' 
                                });
                                let grossHrs = parseFloat(item.total_hours || 0);
                                let netHrs = (grossHrs - (totalDeductMins / 60)).toFixed(2);

                                // 3. Row Construction
                                html += `
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark">${item.employee ? item.employee.lname + ' ' + item.employee.fname : 'N/A'}</div>
                                            <small class="text-muted">${item.employee_id || ''}</small>
                                        </td>
                                        <td>${date}</td>
                                        <td class="text-center fw-semibold">${grossHrs.toFixed(2)}</td>
                                        <td class="text-center">${deductionsHtml}</td>
                                        <td class="text-center text-primary fw-bold">${netHrs}</td>
                                        <td class="pe-4 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold btn-deduct"
                                                data-id="${item.id}"
                                                data-employee="${item.employee ? item.employee.fname + ' ' + item.employee.fname : 'Employee'}"
                                                data-bs-toggle="modal" data-bs-target="#mdlDeduction">
                                                <i class="fas fa-minus-circle me-1"></i> Deduct
                                            </button>
                                        </td>
                                    </tr>`;
                            });
                        }
                        tbody.html(html);
                    })
                    .catch(error => {
                        console.error(error);
                        tbody.html(`
                            <tr>
                                <td colspan="6" class="text-center py-5 text-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i> 
                                    Failed to load data. Please refresh the page.
                                </td>
                            </tr>`);
                    });
            }

            // Initial call when page loads
            $(document).ready(function() {
                fetchAttendance();
            });

            // Trigger on button click
            $('#btnApplyFilter').on('click', fetchAttendance);

            // Optional: Search as you type (with debounce to save server resources)
            let timeout = null;
            $('#filterSearch').on('keyup', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchAttendance, 500);
            });

     
        });
    </script>
@endsection
