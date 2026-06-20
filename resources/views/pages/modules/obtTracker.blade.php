@extends('layout.app', ['title' => 'OB Tracker'])
@section('content')

<style>
    :root {
        --teal:#008080; --teal-dark:#006666; --teal-mid:#4db6ac; --teal-light:#e0f2f1;
        --slate:#334155; --slate-light:#64748b; --muted:#94a3b8; --bg:#f1f5f9;
        --surface:#fff; --border:#e2e8f0; --danger:#ef4444; --success:#10b981;
        --radius-card:14px; --radius-input:8px; --shadow-card:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);
    }
    .ai-shell { background:var(--bg); min-height:100vh; padding:24px 28px 60px; margin:-1.5rem -1.5rem 0; }
    .ai-topbar { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); padding:16px 22px; margin-bottom:20px; display:flex;
        align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
    .ai-topbar .page-title { font-size:1.1rem; font-weight:700; color:var(--slate); margin:0; }
    .ai-topbar .page-sub { font-size:.78rem; color:var(--muted); margin:2px 0 0; }
    .sc { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card);
        box-shadow:var(--shadow-card); margin-bottom:20px; overflow:hidden; }
    .sc-head { display:flex; align-items:center; gap:10px; padding:14px 22px; border-bottom:1px solid var(--border);
        background:linear-gradient(to right,#fafcff,#f8fbfa); }
    .sc-icon { width:30px; height:30px; border-radius:8px; background:var(--teal-light); color:var(--teal);
        display:flex; align-items:center; justify-content:center; font-size:.78rem; }
    .sc-title { font-size:.78rem; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:.5px; margin:0; }
    .sc-body { padding:22px; }
    .btn-teal { background:var(--teal); color:#fff; border:none; border-radius:var(--radius-input); padding:10px 20px;
        font-size:.82rem; font-weight:700; cursor:pointer; box-shadow:0 4px 14px rgba(0,128,128,.25); transition:all .2s;
        display:inline-flex; align-items:center; gap:8px; }
    .btn-teal:hover { background:var(--teal-dark); transform:translateY(-1px); color:#fff; }
    .btn-ghost { background:var(--surface); color:var(--slate-light); border:1.5px solid var(--border);
        border-radius:var(--radius-input); padding:10px 20px; font-size:.82rem; font-weight:700; cursor:pointer;
        transition:all .2s; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
    .btn-ghost:hover { background:var(--teal-light); border-color:var(--teal-mid); color:var(--teal); }
    .btn-teal-outline { background:var(--surface); color:var(--teal); border:1.5px solid var(--teal-mid);
        border-radius:var(--radius-input); padding:10px 20px; font-size:.82rem; font-weight:700; cursor:pointer;
        transition:all .2s; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
    .btn-teal-outline:hover { background:var(--teal-light); border-color:var(--teal); color:var(--teal-dark); }

    /* Table styles */
    .table-teal { width:100%; border-collapse:collapse; }
    .table-teal thead th {
        background:var(--teal-light); color:var(--teal-dark); font-size:.72rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.5px; padding:12px 14px; border-bottom:2px solid var(--teal-mid);
        position:sticky; top:0; z-index:5;
    }
    .table-teal tbody td {
        padding:10px 14px; font-size:.82rem; color:var(--slate); border-bottom:1px solid var(--border);
    }
    .table-teal tbody tr:hover { background:#f8fdfb; }
    .table-teal tbody tr:last-child td { border-bottom:none; }

    /* Status badges */
    .badge-status {
        display:inline-block; padding:4px 12px; border-radius:20px; font-size:.7rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.3px;
    }
    .badge-pending { background:#fff3cd; color:#856404; border:1px solid #ffc107; }
    .badge-approved { background:#d1fae5; color:#065f46; border:1px solid #10b981; }
    .badge-disapproved { background:#fee2e2; color:#991b1b; border:1px solid #ef4444; }

    /* Filter inputs */
    .filter-input {
        border:1.5px solid var(--border); border-radius:var(--radius-input); padding:8px 14px;
        font-size:.82rem; color:var(--slate); background:var(--surface); transition:border-color .2s;
    }
    .filter-input:focus { border-color:var(--teal-mid); outline:none; box-shadow:0 0 0 3px rgba(0,128,128,.1); }

    /* Modal refinements */
    .modal-content { border:none; border-radius:var(--radius-card); box-shadow:var(--shadow-card); }
    .modal-header-teal { background:linear-gradient(135deg, var(--teal), var(--teal-dark)); border-bottom:none;
        border-radius:var(--radius-card) var(--radius-card) 0 0; padding:16px 22px; }
    .modal-header-teal .modal-title { color:#fff; font-size:.95rem; font-weight:700; letter-spacing:.3px; }
    .modal-header-teal .btn-close { filter:brightness(0) invert(1); opacity:.8; }
    .form-floating-teal .form-control {
        border:1.5px solid var(--border); border-radius:var(--radius-input);
        background:var(--surface); font-size:.85rem; padding:14px 12px 8px;
    }
    .form-floating-teal .form-control:focus {
        border-color:var(--teal-mid); box-shadow:0 0 0 3px rgba(0,128,128,.08);
    }
    .section-label {
        font-size:.72rem; font-weight:700; color:var(--teal); text-transform:uppercase;
        letter-spacing:1px; margin-bottom:12px; padding-bottom:6px; border-bottom:2px solid var(--teal-light);
    }
    .responsive-table-wrap { max-height:55vh; overflow-y:auto; }
</style>

<div class="ai-shell">
    <!-- Top Bar -->
    <div class="ai-topbar">
        <div>
            <p class="page-title">Official Business Trip Tracker</p>
            <p class="page-sub">File and track official business trips with itinerary, time, and cash advance details</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn-ghost" id="btnRefreshTbl">
                <i class="fa fa-sync-alt"></i> Refresh
            </button>
            <button class="btn-teal" id="btnCreateOBTModal" data-bs-toggle="modal" data-bs-target="#mdlOBT">
                <i class="fa fa-plus"></i> New Business Trip
            </button>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-filter"></i></div>
            <h5 class="sc-title">Filter Records</h5>
        </div>
        <div class="sc-body">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div>
                    <label class="form-label small fw-semibold text-muted mb-1" style="font-size:.72rem;">From Date</label>
                    <input type="date" id="txtDateFromTop" class="filter-input">
                </div>
                <div>
                    <label class="form-label small fw-semibold text-muted mb-1" style="font-size:.72rem;">To Date</label>
                    <input type="date" id="txtDateToTop" class="filter-input">
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="sc">
        <div class="sc-head">
            <div class="sc-icon"><i class="fa fa-briefcase"></i></div>
            <h5 class="sc-title">OB History</h5>
        </div>
        <div class="sc-body p-0">
            <div class="responsive-table-wrap">
                <table class="table-teal mb-0">
                    <thead>
                        <tr>
                            <th>Filing Date</th>
                            <th>Date From</th>
                            <th>Date To</th>
                            <th>Time From</th>
                            <th>Time To</th>
                            <th>Itinerary From</th>
                            <th>Itinerary To</th>
                            <th>Purpose</th>
                            <th>Cash Advance</th>
                            <th>CA Purpose</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="tblOBTTracker"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="mdlOBT" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
         aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-header-teal">
                    <h5 class="modal-title" id="staticBackdropLabel">
                        <i class="fa fa-plane-departure me-2"></i>
                        <span id="lblTitleOBT">Official Business Trip Form</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <form id="frmOBT">
                        <div class="row g-3">
                            <!-- Left Column - Personnel Info -->
                            <div class="col-lg-6">
                                <div class="section-label">Personnel Information</div>
                                <div class="row g-2">
                                    <div class="col-12 mb-2">
                                        <div class="form-floating form-floating-teal">
                                            <input class="form-control" id="txtPersonnel" name="personnel" type="text" placeholder="-" readonly/>
                                            <label for="txtPersonnel">Personnel Name <span class="text-danger">*</span></label>
                                            <span class="text-danger small error-text personnel_error"></span>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-2">
                                        <div class="form-floating form-floating-teal">
                                            <input class="form-control" id="txtCompany" name="company" type="text" placeholder="-" readonly/>
                                            <label for="txtCompany">Company Name <span class="text-danger">*</span></label>
                                            <span class="text-danger small error-text company_error"></span>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-2">
                                        <div class="form-floating form-floating-teal">
                                            <input class="form-control" id="txtDept" name="department" type="text" placeholder="-" readonly/>
                                            <label for="txtDept">Department <span class="text-danger">*</span></label>
                                            <span class="text-danger small error-text department_error"></span>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-2">
                                        <div class="form-floating form-floating-teal">
                                            <input class="form-control" id="txtDesignation" name="designation" type="text" placeholder="-" readonly/>
                                            <label for="txtDesignation">Designation <span class="text-danger">*</span></label>
                                            <span class="text-danger small error-text designation_error"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column - Date Info -->
                            <div class="col-lg-6">
                                <div class="section-label">Schedule Details</div>
                                <div class="row g-2">
                                    <div class="col-12 mb-2">
                                        <div class="form-floating form-floating-teal">
                                            <input class="form-control" value="{{date('F j, Y')}}" id="txtFilingDate" name="dateFil" type="text" placeholder="-" readonly/>
                                            <label for="txtFilingDate">Filing Date <span class="text-danger">*</span></label>
                                            <span class="text-danger small error-text dateFil_error"></span>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-2">
                                        <div class="form-floating form-floating-teal">
                                            <input class="form-control" id="txtDateFromOB" name="dateFrom" type="date" placeholder="-"/>
                                            <label for="txtDateFromOB">OB Date From <span class="text-danger">*</span></label>
                                            <span class="text-danger small error-text dateFrom_error"></span>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-2">
                                        <div class="form-floating form-floating-teal">
                                            <input class="form-control" id="txtDateToOB" name="dateTo" type="date" placeholder="-"/>
                                            <label for="txtDateToOB">OB Date To <span class="text-danger">*</span></label>
                                            <span class="text-danger small error-text dateTo_error"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-3" style="border-color:var(--border);">

                        <div class="row g-3">
                            <!-- Itinerary -->
                            <div class="col-lg-4">
                                <div class="section-label">Itinerary</div>
                                <div class="row g-2">
                                    <div class="col-12 mb-2">
                                        <div class="form-floating form-floating-teal">
                                            <input class="form-control" id="txtItineraryFrom" name="itineraryF" type="text" placeholder="-"/>
                                            <label for="txtItineraryFrom">From <span class="text-danger">*</span></label>
                                            <span class="text-danger small error-text itineraryF_error"></span>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-2">
                                        <div class="form-floating form-floating-teal">
                                            <input class="form-control" id="txtItineraryTo" name="itineraryT" type="text" placeholder="-"/>
                                            <label for="txtItineraryTo">To <span class="text-danger">*</span></label>
                                            <span class="text-danger small error-text itineraryT_error"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Inclusive Time -->
                            <div class="col-lg-4">
                                <div class="section-label">Inclusive Time</div>
                                <div class="row g-2">
                                    <div class="col-6 mb-2">
                                        <div class="form-floating form-floating-teal">
                                            <input class="form-control" id="txtTimeDeparture" name="departure" type="time" placeholder="-"/>
                                            <label for="txtTimeDeparture">Departure <span class="text-danger">*</span></label>
                                            <span class="text-danger small error-text departure_error"></span>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <div class="form-floating form-floating-teal">
                                            <input class="form-control" id="txtTimeReturn" name="return" type="time" placeholder="-"/>
                                            <label for="txtTimeReturn">Return <span class="text-danger">*</span></label>
                                            <span class="text-danger small error-text return_error"></span>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating form-floating-teal">
                                            <input class="form-control" id="txtPurposeITime" name="purposeT" type="text" placeholder="-"/>
                                            <label for="txtPurposeITime">Purpose <span class="text-danger">*</span></label>
                                            <span class="text-danger small error-text purposeT_error"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cash Advance -->
                            <div class="col-lg-4">
                                <div class="section-label">Cash Advance</div>
                                <div class="row g-2">
                                    <div class="col-12 mb-2">
                                        <div class="form-floating form-floating-teal">
                                            <input class="form-control" id="txtCAAmount" name="amount" type="number" step="0.01" placeholder="-"/>
                                            <label for="txtCAAmount">Amount</label>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-2">
                                        <div class="form-floating form-floating-teal">
                                            <input class="form-control" id="txtCAPurpose" name="purposeCA" type="text" placeholder="-"/>
                                            <label for="txtCAPurpose">Purpose</label>
                                            <span class="text-danger small error-text purposeCA_error"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0 pb-3 px-4">
                    <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button id="btnSaveOBT" type="button" class="btn-teal">
                        <i class="fa fa-paper-plane me-1"></i> Submit
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var obID = 0;
    getall();

    // Open modal - fetch employee details
    $(document).on('click', '#btnCreateOBTModal', function(e) {
        axios.get('/obtTracker/get_details')
            .then(function(response) {
                $(response.data.data).each(function(index, row) {
                    $('#txtPersonnel').val(row.lname + " " + row.fname);
                    $('#txtCompany').val(row.comp_name);
                    $('#txtDept').val(row.dep_name);
                    $('#txtDesignation').val(row.pos_desc);
                });
            })
            .catch(function(error) {
                Swal.fire('Error', 'Unable to fetch employee details.', 'error');
            });
    });

    // Save OBT
    $(document).on('click', '#btnSaveOBT', function(e) {
        var datas = $('#frmOBT');
        var formData = new FormData($(datas)[0]);

        axios.post('/obtTracker/create_obt', formData)
            .then(function(response) {
                if (response.data.status == 201) {
                    $.each(response.data.error, function(prefix, val) {
                        $('input[name=' + prefix + ']').addClass("border border-danger");
                        $('span.' + prefix + '_error').text(val[0]);
                    });
                    Swal.fire({
                        position: 'center',
                        icon: 'warning',
                        title: response.data.msg,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
                if (response.data.status == 200) {
                    $('span.error-text').text("");
                    $('input.border').removeClass('border border-danger');
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: response.data.msg,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    getall();
                }
                if (response.data.status == 202) {
                    $('span.error-text').text("");
                    $('input.border').removeClass('border border-danger');
                    Swal.fire({
                        position: 'center',
                        icon: 'warning',
                        title: response.data.msg,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            })
            .catch(function(error) {
                Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
            });
    });

    // Refresh button
    $('#btnRefreshTbl').on('click', function() {
        getall();
    });

    // Get all OB records
    function getall() {
        var htmlData = '';
        axios.get('/obtTracker/getall')
            .then(function(response) {
                $(response.data.data).each(function(index, row) {
                    var statusClass = '';
                    var statusText = (row.obStatus || '').toUpperCase();
                    if (statusText.includes('APPROVED') || statusText === 'APPROVED') {
                        statusClass = 'badge-approved';
                    } else if (statusText.includes('DISAPPROVED') || statusText.includes('DENIED')) {
                        statusClass = 'badge-disapproved';
                    } else {
                        statusClass = 'badge-pending';
                    }

                    htmlData += '<tr>' +
                        '<td>' + row.obFD + '</td>' +
                        '<td>' + row.obDateFrom + '</td>' +
                        '<td>' + row.obDateTo + '</td>' +
                        '<td>' + row.obTFrom + '</td>' +
                        '<td>' + row.obTTo + '</td>' +
                        '<td>' + row.obIFrom + '</td>' +
                        '<td>' + row.obITo + '</td>' +
                        '<td>' + row.obPurpose + '</td>' +
                        '<td>' + (row.obCAAmt ? row.obCAAmt : '—') + '</td>' +
                        '<td>' + (row.obCAPurpose ? row.obCAPurpose : '—') + '</td>' +
                        '<td><span class="badge-status ' + statusClass + '">' + statusText + '</span></td>' +
                        '</tr>';
                });

                if (!response.data.data || response.data.data.length === 0) {
                    htmlData = '<tr><td colspan="11" class="text-center py-5 text-muted">' +
                        '<i class="fa fa-folder-open d-block mb-2 fs-4"></i>' +
                        'No business trip records found.' +
                        '</td></tr>';
                }

                $("#tblOBTTracker").empty().append(htmlData);
            })
            .catch(function(error) {
                $("#tblOBTTracker").empty().append(
                    '<tr><td colspan="11" class="text-center py-5 text-danger">' +
                    '<i class="fa fa-exclamation-triangle me-2"></i> Failed to load records. Please refresh.</td></tr>'
                );
            });
    }
});
</script>
@endsection