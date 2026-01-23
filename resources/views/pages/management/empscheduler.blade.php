@extends('layout.app')

@section('content')
<style>
    /* Uniform Table Design */
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
</style>

<div class="container-fluid px-4 py-3">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">Settings</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item text-muted">Settings</li>
                    <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Scheduling Module</li>
                </ol>
            </nav>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" id="btnCreateModal" data-bs-toggle="modal" data-bs-target="#mdlEmpScheduler">
            <i class="fas fa-plus me-2"></i> Add Schedule
        </button>
    </div>

    <div class="row g-3 mb-4 align-items-center">
        <div class="col-lg-8">
            <div class="input-group bg-white rounded-pill shadow-sm overflow-hidden px-3 border">
                <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="txtSearchEmp" class="form-control border-0 shadow-none" placeholder="Search by Employee Name...">
            </div>
        </div>
        <div class="col-lg-4">
            <select id="selPerPage" class="form-select rounded-pill shadow-sm border px-4">
                <option value="10">10 entries per page</option>
                <option value="25">25 entries</option>
                <option value="50">50 entries</option>
                <option value="100">100 entries</option>
            </select>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-sticky-header">
                    <thead class="bg-light">
                        <tr class="text-secondary small fw-bold text-uppercase tracking-wider">
                            <th class="ps-4 py-3">Employee Name</th>
                            <th class="py-3">From (Date & Time)</th>
                            <th class="py-3">To (Date & Time)</th>
                            <th class="pe-4 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tblEmpScheduler" class="border-top-0">
                        </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="paginationContainer" class="mt-4"></div>
</div>

<div class="modal fade" id="mdlEmpScheduler" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header border-0 pt-4 px-4 bg-white">
                <h5 class="modal-title fw-bold text-secondary text-uppercase tracking-wide">
                    <i class="bi bi-calendar-check me-2 text-primary"></i> Employee Schedule
                </h5>
                <button type="button" class="btn-close closereset_update" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <form id="frmEmpScheduler" autocomplete="off">
                    <input type="hidden" id="schedule_id">

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">Select Employee <span class="text-danger">*</span></label>
                        <select class="form-select form-control-lg bg-light border-0 fs-6 text-uppercase" id="selEmployee" name="employee_id">
                            <option selected disabled value="">Choose...</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->empID }}">{{ $emp->lname }}, {{ $emp->fname }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger small error-text employee_id_error"></span>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Start Date</label>
                            <input type="date" class="form-control form-control-lg bg-light border-0 fs-6" name="sched_start_date" id="sched_start_date">
                            <span class="text-danger small error-text sched_start_date_error"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">End Date</label>
                            <input type="date" class="form-control form-control-lg bg-light border-0 fs-6" name="sched_end_date" id="sched_end_date">
                            <span class="text-danger small error-text sched_end_date_error"></span>
                        </div>
                    </div>

                    <div class="mb-4 bg-light p-3 rounded-3">
                        <label class="form-label small fw-bold text-muted d-block mb-2">Repeat on these Days:</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input day-check d-none" type="checkbox" value="{{ $day }}" id="chk{{ $day }}">
                                    <label class="badge rounded-pill border py-2 px-3 fw-medium day-label" for="chk{{ $day }}" style="cursor: pointer; color: #666;">
                                        {{ $day }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted">Time In</label>
                            <input type="time" class="form-control bg-light border-0" name="sched_in" id="sched_in">
                            <span class="text-danger small error-text sched_in_error"></span>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted">Time Out</label>
                            <input type="time" class="form-control bg-light border-0" name="sched_out" id="sched_out">
                            <span class="text-danger small error-text sched_out_error"></span>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted">Break Start</label>
                            <input type="time" class="form-control bg-light border-0" name="break_start" id="break_start">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted">Break End</label>
                            <input type="time" class="form-control bg-light border-0" name="break_end" id="break_end">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-muted">Shift Type</label>
                        <input type="text" class="form-control bg-light border-0" name="shift_type" id="shift_type" placeholder="e.g. Regular Morning">
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="btnSaveScheduler" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Save Schedule</button>
            </div>
        </div>
    </div>
</div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function(){

    // 🎨 UI Helper for Checkboxes (Pill buttons)
    $('.day-check').on('change', function() {
        if($(this).is(':checked')) {
            $(this).next('.day-label').addClass('bg-primary text-white border-primary').removeClass('bg-transparent text-muted');
        } else {
            $(this).next('.day-label').removeClass('bg-primary text-white border-primary').addClass('bg-transparent text-muted');
        }
    });

    // 🧩 LOAD SCHEDULES
    const loadSchedules = (search = '', page = 1, perPage = 10) => {
        $("#tblEmpScheduler").html('<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary opacity-50" role="status"></div></td></tr>');

        axios.get("{{ route('employee-schedules.get') }}", {
            params: { search, page, per_page: perPage }
        })
        .then(res => {
            const data = res.data.data;
            let html = '';

            if(data.length > 0) {
                data.forEach(s => {
                    html += `
                        <tr>
                            <td class="ps-4 fw-bold text-dark text-uppercase small">${s.employee_name}</td>
                            <td class="text-muted">${s.sched_start_date} <span class="badge bg-light text-dark border-0 ms-1 fw-bold">${s.sched_in}</span></td>
                            <td class="text-muted">${s.sched_end_date} <span class="badge bg-light text-dark border-0 ms-1 fw-bold">${s.sched_out}</span></td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-light btn-sm rounded-circle shadow-sm p-2 btnEdit" data-id="${s.id}" title="Edit">
                                        <i class="fa-solid fa-pencil text-primary"></i>
                                    </button>
                                    <button class="btn btn-light btn-sm rounded-circle shadow-sm p-2 btnDelete" data-id="${s.id}" title="Delete">
                                        <i class="fa-solid fa-trash text-danger"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>`;
                });
            } else {
                html = '<tr><td colspan="4" class="text-center py-5 text-muted small">No scheduling records found.</td></tr>';
            }

            $('#tblEmpScheduler').html(html);

            // Pagination builder
            let pagination = '';
            if (res.data.last_page > 1) {
                pagination += `<nav><ul class="pagination pagination-sm justify-content-end gap-1">`;
                for (let i = 1; i <= res.data.last_page; i++) {
                    pagination += `
                        <li class="page-item ${i === res.data.current_page ? 'active' : ''}">
                            <a href="#" class="page-link rounded border-0 ${i === res.data.current_page ? 'bg-primary shadow-sm' : 'text-muted'}" data-page="${i}">${i}</a>
                        </li>`;
                }
                pagination += `</ul></nav>`;
            }
            $('#paginationContainer').html(pagination);
        })
        .catch(err => {
            Swal.fire({ icon: 'error', title: 'Fetch Error', text: 'Unable to load schedules.' });
        });
    };

    // 🔹 Initial Load
    loadSchedules();

    // Reset Modal on Open
    $('#btnCreateModal').on('click', function() {
        $('#schedule_id').val('');
        $('#frmEmpScheduler')[0].reset();
        $('.day-label').removeClass('bg-primary text-white border-primary').addClass('bg-transparent text-muted');
        $('.error-text').text('');
        $('input, select').removeClass('border-danger');
    });

    // 🔍 Search & Filters
    $('#txtSearchEmp').on('keyup', function(){ loadSchedules($(this).val(), 1, $('#selPerPage').val()); });
    $('#selPerPage').on('change', function(){ loadSchedules($('#txtSearchEmp').val(), 1, $(this).val()); });
    $(document).on('click', '.page-link', function(e){
        e.preventDefault();
        loadSchedules($('#txtSearchEmp').val(), $(this).data('page'), $('#selPerPage').val());
    });

    // 💾 Save Logic
    $('#btnSaveScheduler').on('click', function() {
        let schedule_id = $('#schedule_id').val();
        let url = schedule_id ? `{{ url('employee-schedules/update') }}/${schedule_id}` : "{{ route('employee-schedules.store') }}";
        let method = schedule_id ? 'put' : 'post';

        let selectedDays = [];
        $('.day-check:checked').each(function() { selectedDays.push($(this).val()); });

        let formData = {
            employee_id: $('#selEmployee').val(),
            sched_start_date: $('#sched_start_date').val(),
            sched_in: $('#sched_in').val(),
            sched_end_date: $('#sched_end_date').val(),
            sched_out: $('#sched_out').val(),
            shift_type: $('#shift_type').val(),
            break_start: $('#break_start').val(),
            break_end: $('#break_end').val(),
            days: selectedDays,
        };

        function submitSchedule(data, isConfirmed = false) {
            if (isConfirmed) data.confirm_long_shift = true;

            Swal.fire({
                title: 'Processing...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            axios({ method, url, data })
                .then(res => {
                    if (res.data.warning) {
                        Swal.fire({
                            title: 'Confirm Schedule?',
                            text: res.data.message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, proceed'
                        }).then(result => { if (result.isConfirmed) submitSchedule(data, true); });
                        return;
                    }

                    Swal.fire({ icon: 'success', title: 'Success', text: res.data.message, timer: 1500, showConfirmButton: false });
                    $('#mdlEmpScheduler').modal('hide');
                    loadSchedules();
                })
                .catch(err => {
                    Swal.close();
                    if (err.response && err.response.status === 422) {
                        let errors = err.response.data.errors;
                        $('.error-text').text('');
                        Object.keys(errors).forEach(key => {
                            $(`.${key}_error`).text(errors[key][0]);
                            $(`#${key}`).addClass('border-danger');
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'An unexpected error occurred.' });
                    }
                });
        }
        submitSchedule(formData);
    });

    // 📝 Edit
    $(document).on('click', '.btnEdit', function(){
        let id = $(this).data('id');
        const trimSeconds = (t) => t ? t.substring(0, 5) : '';
        
        axios.get(`{{ url('employee-schedules/edit') }}/${id}`).then(res => {
            let s = res.data;
            $('#schedule_id').val(s.id);
            $('#selEmployee').val(s.employee_id);
            $('#sched_start_date').val(s.sched_start_date);
            $('#sched_end_date').val(s.sched_end_date);
            $('#shift_type').val(s.shift_type);
            $('#sched_in').val(trimSeconds(s.sched_in));
            $('#sched_out').val(trimSeconds(s.sched_out));
            $('#break_start').val(trimSeconds(s.break_start));
            $('#break_end').val(trimSeconds(s.break_end));
            
            // Note: If you store days in DB, you'd trigger them here
            $('#mdlEmpScheduler').modal('show');
        });
    });

    // 🗑️ Delete
    $(document).on('click', '.btnDelete', function(){
        let id = $(this).data('id');
        Swal.fire({
            title: 'Delete Schedule?',
            text: "This record will be permanently removed.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            confirmButtonColor: '#d33'
        }).then((result) => {
            if(result.isConfirmed){
                axios.delete(`{{ url('employee-schedules/delete') }}/${id}`).then(res => {
                    Swal.fire('Deleted!', res.data.message, 'success');
                    loadSchedules();
                });
            }
        });
    });
});
</script>
@endsection