$(document).ready(function(){

  let currentPage = 1;
let sortField = 'sched_start_date';
let sortDirection = 'asc';
let perPage = 10;

function fetchSchedules(search = '') {
    axios.get('/schedules', {
        params: {
            search,
            page: currentPage,
            sortField,
            sortDirection,
            perPage
        }
    })
    .then(res => {
        let data = res.data.data;
        let html = '';
        data.forEach(s => {
            html += `<tr data-id="${s.id}">
                <td>${s.employee_name}</td>
                <td>${s.sched_start_date}</td>
                <td>${s.sched_in}</td>
                <td>${s.sched_end_date}</td>
                <td>${s.sched_out}</td>
                <td>${s.shift_type || ''}</td>
                <td>
                    <button class="btn btn-sm btn-primary editBtn">Edit</button>
                    <button class="btn btn-sm btn-danger deleteBtn">Delete</button>
                </td>
            </tr>`;
        });
        $('#tblEmpScheduler tbody').html(html);

        // Pagination buttons
        let pagination = '';
        for(let i=1;i<=res.data.last_page;i++){
            pagination += `<button class="btn btn-sm btn-outline-secondary pageBtn ${i==currentPage?'active':''}" data-page="${i}">${i}</button>`;
        }
        $('#pagination').html(pagination);
    });
}

// Pagination click
$(document).on('click', '.pageBtn', function(){
    currentPage = $(this).data('page');
    fetchSchedules($('#txtSearchEmp').val());
});

// Sorting click
$(document).on('click', 'th.sortable', function(){
    let field = $(this).data('field');
    if(sortField === field){
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        sortField = field;
        sortDirection = 'asc';
    }
    fetchSchedules($('#txtSearchEmp').val());
});

 

    // Initial fetch
    fetchSchedules();

    // Search input event
    $('#txtSearchEmp').on('input', function(){
        let val = $(this).val();
        fetchSchedules(val);
    });

    // Show modal for add
    $('#btnAddSchedule').click(function(){
        $('#frmEmpScheduler')[0].reset();
        $('#scheduleId').val('');
        $('#mdlEmpScheduler').modal('show');
    });

    // Save or update
    $('#btnSaveSchedule').click(function(){
        let id = $('#scheduleId').val();
        let url = id ? `/schedules/${id}/update` : '/schedules/store';

        axios.post(url, $('#frmEmpScheduler').serialize())
            .then(res => {
                Swal.fire('Success', res.data.message, 'success');
                $('#mdlEmpScheduler').modal('hide');
                fetchSchedules($('#txtSearchEmp').val());
            })
            .catch(err => {
                if(err.response && err.response.data.errors){
                    let messages = Object.values(err.response.data.errors)
                        .map(a=>a.join(', ')).join('\n');
                    Swal.fire('Error', messages, 'error');
                }
            });
    });

    // Edit
    $(document).on('click', '.editBtn', function(){
        let id = $(this).closest('tr').data('id');
        axios.get(`/schedules/${id}/edit`).then(res=>{
            let d = res.data;
            $('#scheduleId').val(d.id);
            $('#selEmployee').val(d.employee_id);
            $('#sched_start_date').val(d.sched_start_date);
            $('#sched_in').val(d.sched_in);
            $('#sched_end_date').val(d.sched_end_date);
            $('#sched_out').val(d.sched_out);
            $('#shift_type').val(d.shift_type);
            $('#mdlEmpScheduler').modal('show');
        });
    });

    // Delete
    $(document).on('click', '.deleteBtn', function(){
        let id = $(this).closest('tr').data('id');
        Swal.fire({
            title: 'Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete!'
        }).then(result=>{
            if(result.isConfirmed){
                axios.delete(`/schedules/${id}/delete`).then(res=>{
                    Swal.fire('Deleted', res.data.message, 'success');
                    fetchSchedules($('#txtSearchEmp').val());
                });
            }
        });
    });

});
