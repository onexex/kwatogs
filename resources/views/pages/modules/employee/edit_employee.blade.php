@extends('layout.app')
@section('content')

    <style>
        input {
            text-transform: uppercase;
            }
        select {
            text-transform: uppercase;
            }
            textarea {
            text-transform: uppercase;
            }
        /* Force inactive tabs to zero height and hide overflow */
.tab-content > .tab-pane:not(.active) {
    display: none !important;
    height: 0;
    overflow: hidden;
}

/* Ensure the active tab resets to auto height */
.tab-content > .active {
    display: block !important;
    height: auto !important;
}
        
        
        
        :root {
            --primary-color: #696cff; /* Modern Blue/Indigo */
            --text-muted: #a1acb8;
            --bg-light: #f5f5f9;
        }

        .nav-pills {
            border-bottom: 1px solid #e1e4e8;
            gap: 10px;
        }

        .nav-pills .nav-item .nav-link {
            background: transparent;
            border: none;
            border-radius: 0;
            color: var(--text-muted);
            font-weight: 500;
            padding: 12px 20px;
            position: relative;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* The Hover Effect */
        .nav-pills .nav-link:hover {
            background-color: rgba(105, 108, 255, 0.05);
            color: var(--primary-color) !important;
            box-shadow: none !important;
        }

        /* The Active State (Modern Underline) */
        .nav-pills .nav-link.active {
            background: transparent !important;
            color: var(--primary-color) !important;
            box-shadow: none !important;
        }

        .nav-pills .nav-link.active::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary-color);
            border-radius: 10px 10px 0 0;
        }

        .nav-pills .tf-icons {
            font-size: 1.1rem;
        }

        /* Form Container Styling */
        #frmEnrolment {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-top: -1px; /* Merges with the tab border */
        }
        /* Container & Section Styling */
        .gi-section-header {
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #566a7f;
            padding: 1.5rem 1.5rem 0.5rem 1.5rem;
            text-transform: uppercase;
        }

        .card.modern-card {
            border: none;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.04) !important;
        }

        /* Label Styling - Clean & Subtle */
        .form-label-custom {
            font-size: 0.75rem;
            font-weight: 600;
            color: #8e94a9;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        /* Required Star */
        .text-danger {
            font-size: 1rem;
            margin-left: 2px;
        }

        /* Input Styling */
        .form-control, .form-select {
            border: 1px solid #d9dee3;
            border-radius: 6px;
            padding: 0.55rem 0.85rem;
            font-size: 0.93rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #696cff;
            box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.1);
        }

        /* Error Text Styling */
        .error-text {
            font-size: 0.7rem;
            margin-top: 3px;
            display: block;
            font-weight: 500;
        }
    </style>
    <div class="container-fluid">
        <div class="row mb-3">
           <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold text-dark m-0 text-capitalize">Edit Information of : {{ $user->fname }} {{ $user->lname }}</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item text-muted">Operation</li>
                            <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Employee Updating Information</li>
                        </ol>
                    </nav>
                </div>
                
            </div>
                

       
            <div class="col-12">
                <ul class="nav nav-pills mb-3" id="enrollmentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane" type="button" role="tab">
                            <i class="tf-icons fa fa-user me-1"></i> General Info
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="educational-tab" data-bs-toggle="tab" data-bs-target="#educational-tab-pane" type="button" role="tab">
                            <i class="tf-icons fa fa-book-open me-1"></i> Educational
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="employment-tab" data-bs-toggle="tab" data-bs-target="#employment-tab-pane" type="button" role="tab">
                            <i class="tf-icons fa fa-briefcase me-1"></i> Employment
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="compliance-tab" data-bs-toggle="tab" data-bs-target="#complaince" type="button" role="tab">
                            <i class="tf-icons fa fa-user-shield me-1"></i> Compliance
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="profile-pic-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab">
                            <i class="tf-icons fa fa-image me-1"></i> Profile Picture
                        </button>
                    </li>
                </ul>
            </div>
        

      
            <div class="col-12">
               <form id="frmEnrolment" class="py-4 px-0">
                    <div class="tab-content" id="myTabContent">

                        <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" tabindex="0">
                            <div class="container-fluid px-0">
                                
                                <div class="card border-0 shadow-sm rounded-4 mb-3">
                                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                                        <h5 class="fw-bold text-uppercase tracking-wider small text-secondary mb-0">General Information</h5>
                                    </div>
                                    
                                    <div class="card-body p-4">
                                        <div class="row g-4 px-2">
                                            <div class="col-lg-3 col-md-6">
                                                <input type="hidden" class="form-control form-control-lg bg-light border-0 fs-6" value="{{ $user->empID }}" id="userId" name="empID"  >
                                                <label for="txtfname" class="form-label small fw-semibold text-muted">First Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-lg bg-light border-0 fs-6" value="{{ $user->fname }}" id="txtfname" name="firstname"  >
                                                <span class="text-danger error-text firstname_error"></span>
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <label for="txtMiddleName" class="form-label small fw-semibold text-muted">Middle Name <span class="text-danger"></span></label>
                                                <input type="text" class="form-control form-control-lg bg-light border-0 fs-6" value="{{ $user->mname }}" id="txtMiddleName" name="middlename">
                                                <span class="text-danger error-text middlename_error"></span>
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <label for="txtLastName" class="form-label small fw-semibold text-muted">Last Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-lg bg-light border-0 fs-6" value="{{ $user->lname }}" id="txtLastName" name="lastname">
                                                <span class="text-danger error-text lastname_error"></span>
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <label for="txtSuffix" class="form-label small fw-semibold text-muted">Suffix</label>
                                                <input type="text" class="form-control form-control-lg bg-light border-0 fs-6" value="{{ $user->suffix }}" id="txtSuffix" name="suffix" placeholder="e.g. Jr.">
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <label for="selGender" class="form-label small fw-semibold text-muted">Gender <span class="text-danger">*</span></label>
                                                <select class="form-select form-control-lg bg-light border-0 fs-6" name="gender" id="selGender">
                                                    <option {{ $user->employeeInformation->gender == 'Female' ? 'selected' : '' }} value="Female">Female</option>
                                                    <option {{ $user->employeeInformation->gender == 'Male' ? 'selected' : '' }}  value="Male">Male</option>
                                                </select>
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <label for="txtCitizenship" class="form-label small fw-semibold text-muted">Citizenship <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-lg bg-light border-0 fs-6" value="{{ $user->employeeInformation->citizenship }}" id="txtCitizenship" name="citizenship">
                                                <span class="text-danger error-text citizenship_error"></span>
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <label for="txtDOB" class="form-label small fw-semibold text-muted">Date of Birth <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control form-control-lg bg-light border-0 fs-6" id="txtDOB" value="{{ substr($user->employeeInformation->empBdate, 0, 10) }}" name="birthdate">
                                                <span class="text-danger small error-text birthdate_error"></span>
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <label for="selCivilStatus" class="form-label small fw-semibold text-muted">Civil Status <span class="text-danger">*</span></label>
                                                <select class="form-select form-control-lg bg-light border-0 fs-6" name="status" id="selCivilStatus">
                                                    <option {{ $user->employeeInformation->empCStatus == '0' ? 'selected' : '' }} value="0">Single</option>
                                                    <option {{ $user->employeeInformation->empCStatus == '1' ? 'selected' : '' }} value="1">Married</option>
                                                    <option {{ $user->employeeInformation->empCStatus == '2' ? 'selected' : '' }} value="2">Divorced</option>
                                                </select>
                                                <span class="text-danger small error-text status_error"></span>
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <label for="txtHomePhone" class="form-label small fw-semibold text-muted">Home Phone</label>
                                                <input type="number" class="form-control form-control-lg bg-light border-0 fs-6" value="{{ $user->employeeInformation->empPContact }}"  id="txtHomePhone" name="homephone">
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <label for="txtMobileNumber" class="form-label small fw-semibold text-muted">Mobile Number <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control form-control-lg bg-light border-0 fs-6"  value="{{ $user->employeeInformation->empHContact }}" id="txtMobileNumber" name="mobile">
                                                <span class="text-danger small error-text mobile_error"></span>
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <label for="txtEmailAddress" class="form-label small fw-semibold text-muted">Email Address <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control form-control-lg bg-light text-lowercase border-0 fs-6" value="{{ $user->employeeInformation->empEmail }}" id="txtEmailAddress" name="email">
                                                <span class="text-danger small error-text email_error"></span>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm rounded-4 mb-4">
                                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                                        <h5 class="fw-bold text-uppercase tracking-wider small text-secondary mb-0">Complete Mailing Address</h5>
                                    </div>
                                    
                                    <div class="card-body p-4">
                                        <div class="row g-4 px-2">
                                            <div class="col-lg-4">
                                                <label for="txtProvince" class="form-label small fw-semibold text-muted">Province <span class="text-danger">*</span></label>
                                                <select class="form-select form-control-lg bg-light border-0 fs-6" id="txtProvince" 
                                                    data-selected="{{ $user->employeeInformation->empProv }}"
                                                    name="province"></select>
                                                <span class="text-danger small error-text province_error"></span>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="txtCity" class="form-label small fw-semibold text-muted">City <span class="text-danger">*</span></label>
                                                <select class="form-select form-control-lg bg-light border-0 fs-6" data-selected="{{ $user->employeeInformation->empAddCity }}" id="txtCity" name="city"></select>
                                                <span class="text-danger small error-text city_error"></span>
                                            </div>
                                            <div class="col-lg-4">
                                                <label for="txtBrgy" class="form-label small fw-semibold text-muted">Barangay <span class="text-danger">*</span></label>
                                                <select class="form-select form-control-lg bg-light border-0 fs-6" data-selected="{{ $user->employeeInformation->empAddBrgy }}" id="txtBrgy" name="barangay"></select>
                                                <span class="text-danger small error-text barangay_error"></span>
                                            </div>
                                            <div class="col-lg-6">
                                                <label for="txtStreet" class="form-label small fw-semibold text-muted">Street No / Subdivision <span class="text-danger"></span></label>
                                                <input type="text" class="form-control form-control-lg bg-light border-0 fs-6" id="txtStreet" value="{{ $user->employeeInformation->empAddStreet }}" name="street">
                                            </div>
                                            <div class="col-lg-3">
                                                <label for="txtZipCode" class="form-label small fw-semibold text-muted">Zip Code <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-lg bg-light border-0 fs-6" value="{{ $user->employeeInformation->empZipcode }}" id="txtZipCode" name="zipcode">
                                            <span class="text-danger small error-text zipcode_error"></span>
                                            </div>
                                            <div class="col-lg-3">
                                                <label for="txtCountry" class="form-label small fw-semibold text-muted">Country <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-lg bg-light border-0 fs-6 text-muted" id="txtCountry" name="country" value="Philippines" readonly>
                                                <span class="text-danger small error-text country_error"></span>
                                            </div>
                                            
                                               
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>

                        <div class="tab-pane fade" id="educational-tab-pane" role="tabpanel" aria-labelledby="educational-tab" tabindex="0">
                            <div class="container-fluid px-0">
                                <div class="card border-0 shadow-sm rounded-4 ">
                                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                                        <h5 class="fw-bold text-uppercase tracking-wider small text-secondary mb-0">Educational Background</h5>
                                    </div>
                                    
                                    <div class="card-body p-4">
                                        
                                        @foreach ($user->education as $education)
                                            @php
                                                $name = 'primary';
                                                if ($education->schoolLevel == 'Secondary') {
                                                    $name = 'secondary';
                                                } else if ($education->schoolLevel == 'Tertiary') {
                                                    $name = 'tertiary';
                                                }
                                            @endphp
                                            <div class="row g-4 mb-5 px-2">
                                                <div class="col-12">
                                                    <div class="d-flex align-items-center">
                                                        <h6 class="fw-bold text-primary mb-0 small uppercase tracking-wide">{{ $education->schoolLevel }} Education</h6>
                                                        <div class="flex-grow-1 ms-3 border-bottom opacity-25"></div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <label for="txtPrimarySchool" class="form-label small fw-semibold text-muted">Name of School</label>
                                                    <input type="text" class="form-control form-control-lg bg-light border-0 fs-6" id="txtPrimarySchool" value="{{ $education->schoolName }}" name="{{ $name }}_school" placeholder="Enter school name">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="txtPrimaryStarted" class="form-label small fw-semibold text-muted">Year Started</label>
                                                    <input type="text" class="form-control form-control-lg bg-light border-0 fs-6" id="txtPrimaryStarted" value="{{ $education->schoolYearStarted }}" name="{{ $name }}_year_started" placeholder="YYYY">
                                                </div>
                                                
                                                <div class="col-md-3">
                                                    <label for="txtPrimaryGraduated" class="form-label small fw-semibold text-muted">Year Graduated</label>
                                                    <input type="text" class="form-control form-control-lg bg-light border-0 fs-6" id="txtPrimaryGraduated" value="{{ $education->schoolYearEnded }}" name="{{ $name }}_year_graduated" placeholder="YYYY">
                                                </div>
                                                
                                                <div class="col-12 mt-3">
                                                    <label for="txtPrimaryAddress" class="form-label small fw-semibold text-muted">School Address</label>
                                                    <input type="text" class="form-control form-control-lg bg-light border-0 fs-6" id="txtPrimaryAddress" value="{{ $education->schoolAddress }}" name="{{ $name }}_school_address" placeholder="Street, City, Province">
                                                    <span class="text-danger small error-text {{ $name }}_address_error"></span>
                                                </div>
                                            </div>
                                        @endforeach

                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="employment-tab-pane" role="tabpanel" aria-labelledby="employment-tab" tabindex="0">
                            <div class="container-fluid px-0">
                                <div class="card border-0 shadow-sm rounded-4 mb-4">
                                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                                        <h5 class="fw-bold text-uppercase tracking-wider small text-secondary mb-0">Employment Information</h5>
                                    </div>

                                    <div class="card-body p-4">
                                        <div class="row g-4 px-2">
                                            <div class="col-lg-4">

                                                <div class="form-group mb-3">
                                                    <label for="selCompany" class="form-label small fw-semibold text-muted">Company <span class="text-danger">*</span></label>
                                                    <select class="form-select form-control-lg bg-light border-0 fs-6" name="company" id="selCompany">
                                                        <option value="">Select Company</option>
                                                        @if (count($companyData) > 0)
                                                            @foreach ($companyData as $companyDatas)
                                                                <option {{ $user->empDetail->empCompID == $companyDatas->comp_id ? 'selected' : '' }} value='{{ $companyDatas->comp_id }}'>{{ $companyDatas->comp_name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <span class="text-danger small error-text company_error"></span>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label for="selDepartment" class="form-label small fw-semibold text-muted">Department <span class="text-danger">*</span></label>
                                                    <select class="form-select form-control-lg bg-light border-0 fs-6" name="department" id="selDepartment">
                                                        <option value="">Select Department</option>
                                                        
                                                        @if (count($departmentData) > 0)
                                                            @foreach ($departmentData as $departmentDatas)
                                                                <option {{ $user->empDetail->empDepID == $departmentDatas->id ? 'selected' : '' }} value='{{ $departmentDatas->id }}'>{{ $departmentDatas->dep_name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <span class="text-danger small error-text department_error"></span>
                                                </div>
                                                
                                                <div class="form-group mb-3">
                                                    <label for="selPosition" class="form-label small fw-semibold text-muted">Position <span class="text-danger">*</span></label>
                                                    <select class="form-select form-control-lg bg-light border-0 fs-6" name="position" id="selPosition">
                                                        <option value="">Select Position</option>
                                                        @if (count($positionData) > 0)
                                                            @foreach ($positionData as $positionDatas)
                                                                <option {{ $user->empDetail->empPos == $positionDatas->id ? 'selected' : '' }} value='{{ $positionDatas->id }}'>{{ $positionDatas->pos_desc }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <span class="text-danger small error-text position_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-4">
                                                <div class="form-group mb-3">
                                                    <label for="selClassification" class="form-label small fw-semibold text-muted">Classification <span class="text-danger">*</span></label>
                                                    <select class="form-select form-control-lg bg-light border-0 fs-6" name="classification" id="selClassification">
                                                        <option value="">Select Classification</option>
                                                        @if (count($employeeClassification) > 0)
                                                            @foreach ($employeeClassification as $employeeClassifications)
                                                                <option {{ $user->empDetail->empClassification == $employeeClassifications->class_code ? 'selected' : '' }} value='{{ $employeeClassifications->class_code }}'>{{ $employeeClassifications->class_desc }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <span class="text-danger small error-text classification_error"></span>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label for="selImmediate" class="form-label small fw-semibold text-muted">Immediate Superior <span class="text-danger">*</span></label>
                                                    <select class="form-select form-control-lg bg-light border-0 fs-6" name="immediate" id="selImmediate">
                                                        <option value="">Select Immediate Superior</option>
                                                        @if (count($immediateData) > 0)
                                                            @foreach ($immediateData as $immediateDatas)
                                                                <option {{ $user->empDetail->empISID == $immediateDatas->empID ? 'selected' : '' }} value='{{ $immediateDatas->empID }}'>{{ $immediateDatas->fname . ' ' . $immediateDatas->lname }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <span class="text-danger small error-text classification_error"></span>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label for="selStatus" class="form-label small fw-semibold text-muted">Status <span class="text-danger">*</span></label>
                                                    <select class="form-select form-control-lg bg-light border-0 fs-6" name="status" id="selStatus">
                                                        <option value="">Select Status</option>
                                                        <option {{ $user->empDetail->empStatus == 1 ? 'selected' : '' }} value="1">Employed</option>
                                                        <option {{ $user->empDetail->empStatus == 0 ? 'selected' : '' }} value="0">Resigned</option>
                                                    </select>
                                                    <span class="text-danger small error-text status_error"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="opacity-50 my-4 mx-2">

                                        <div class="row g-4 px-2">
                                            <div class="col-lg-4">
                                                <div class="form-group mb-3">
                                                    <label for="txtDateHired" class="form-label small fw-semibold text-muted">Date Hired <span class="text-danger">*</span></label>
                                                    <input value="{{ substr($user->empDetail->empDateHired, 0, 10) }}" class="form-control form-control-lg bg-light border-0 fs-6" id="txtDateHired" name="date_hired" type="date" />
                                                    <span class="text-danger small error-text date_hired_error"></span>
                                                </div>
                                                <div class="form-group mb-3">
                                                    <label for="txtDateRegular" class="form-label small fw-semibold text-muted">Date Regular <span class="text-danger"></span></label>
                                                    <input value="{{ substr($user->empDetail->empDateRegular, 0, 10) }}" class="form-control form-control-lg bg-light border-0 fs-6" id="txtDateRegular" name="date_regularization" type="date" />
                                                    <span class="text-danger small error-text date_regularization_error"></span>
                                                </div>
                                                <div class="form-group mb-3">
                                                    <label for="txtDateResigned" class="form-label small fw-semibold text-muted">Date Resigned</label>
                                                    <input value="{{ substr($user->empDetail->empDateResigned, 0, 10) }}" class="form-control form-control-lg bg-light border-0 fs-6" id="txtDateResigned" name="date_resingned" type="date" />
                                                </div>
                                            </div>

                                            <div class="col-lg-4">
                                                <div class="form-group mb-3">
                                                    <label for="txtBasic" class="form-label small fw-semibold text-muted">Basic Salary <span class="text-danger">*</span></label>
                                                    <input  value="{{ $user->empDetail->empBasic }}" type="number" class="form-control form-control-lg bg-light border-0 fs-6" name="basic" id="txtBasic"   />
                                                    <span class="text-danger small error-text basic_error"></span>
                                                </div>
                                                <div class="form-group mb-3">
                                                    <label for="txtAllowance" class="form-label small fw-semibold text-muted">Allowance <span class="text-danger">*</span></label>
                                                    <input value="{{ $user->empDetail->empAllowance }}" class="form-control form-control-lg bg-light border-0 fs-6" id="txtAllowance" name="allowance" type="number"   />
                                                    <span class="text-danger small error-text allowance_error"></span>
                                                </div>
                                                <div class="form-group mb-3">
                                                    <label for="txtHourlyRate" class="form-label small fw-semibold text-muted">Hourly Rate <span class="text-danger"></span></label>
                                                    <input value="{{ $user->empDetail->empHrate }}" class="form-control form-control-lg bg-light border-0 fs-6" id="txtHourlyRate" name="hourly_rate" type="number" value="0"  />
                                                    <span class="text-danger small error-text hourly_rate_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-4">
                                                <div class="form-group mb-3">
                                                    <label for="selPreviousPosition" class="form-label small fw-semibold text-muted">Previous Position</label>
                                                    <input type="text" value="{{ $user->empDetail->empPrevPos }}" class="form-control form-control-lg bg-light border-0 fs-6" name="previous_position" id="selPreviousPosition" />
                                                </div>
                                                <div class="form-group mb-3">
                                                    <label for="txtPreviousDepartment" class="form-label small fw-semibold text-muted">Previous Department</label>
                                                    <input value="{{ $user->empDetail->empPrevDep }}" class="form-control form-control-lg bg-light border-0 fs-6" id="txtPreviousDepartment" name="previous_department" type="text" />
                                                </div>
                                                <div class="form-group mb-3">
                                                    <label for="txtPreviousDesignation" class="form-label small fw-semibold text-muted">Previous Designation</label>
                                                    <input value="{{ $user->empDetail->empPrevDesignation }}" class="form-control form-control-lg bg-light border-0 fs-6" id="txtPreviousDesignation" name="previous_designation" type="text" />
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="complaince" role="tabpanel" aria-labelledby="complaince-tab" tabindex="0">
                            <div class="container-fluid px-0">
                                <div class="card border-0 shadow-sm rounded-4 mb-4">
                                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                                        <h5 class="fw-bold text-uppercase tracking-wider small text-secondary mb-0">Compliance Information</h5>
                                    </div>
                                    
                                    <div class="card-body p-4">
                                        <div class="row g-4"> 

                                            <div class="col-lg-4">
                                                <div class="form-group mb-3">
                                                    <label for="txtPhilhealth" class="form-label small fw-semibold text-muted">PhilHealth <span class="text-danger"></span></label>
                                                    <input type="text" class="form-control form-control-lg bg-light border-0 fs-6" value="{{ $user->empDetail->empPhilhealth }}" name="philhealth" id="txtPhilhealth" placeholder="00-000000000-0">
                                                    <span class="text-danger small error-text philhealth_error"></span>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label for="txtSSS" class="form-label small fw-semibold text-muted">SSS No. <span class="text-danger"></span></label>
                                                    <input class="form-control form-control-lg bg-light border-0 fs-6" id="txtSSS" value="{{ $user->empDetail->empSSS }}" name="sss" type="text" placeholder="00-0000000-0">
                                                    <span class="text-danger small error-text sss_error"></span>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label for="txtPagibig" class="form-label small fw-semibold text-muted">Pag-IBIG No. <span class="text-danger"></span></label>
                                                    <input class="form-control form-control-lg bg-light border-0 fs-6" id="txtPagibig" value="{{ $user->empDetail->empPagibig }}" name="pagibig" type="text" placeholder="0000-0000-0000">
                                                    <span class="text-danger small error-text pagibig_error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-4">
                                                <div class="form-group mb-3">
                                                    <label for="txtTIN" class="form-label small fw-semibold text-muted">TIN No. <span class="text-danger"></span></label>
                                                    <input class="form-control form-control-lg bg-light border-0 fs-6" id="txtTIN" name="tin" value="{{ $user->empDetail->empTIN }}" type="text" placeholder="000-000-000-000">
                                                    <span class="text-danger small error-text tin_error"></span>
                                                </div>

                                                <div class="form-group mb-3">
                                                    <label for="txtUMIDNo" class="form-label small fw-semibold text-muted">UMID <span class="text-danger"></span></label>
                                                    <input class="form-control form-control-lg bg-light border-0 fs-6" id="txtUMIDNo" value="{{ $user->empDetail->empUMID }}" name="umid" type="text" placeholder="0000-0000000-0">
                                                    <span class="text-danger small error-text umid_error"></span>
                                                </div>
                                            </div>

                                             
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                      <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                            <div class="container-fluid px-0">
                                <div class="card border-0 shadow-sm rounded-4 mb-4">
                                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                                        <h5 class="fw-bold text-uppercase tracking-wider small text-secondary mb-0">Profile Picture</h5>
                                    </div>
                                    
                                    <div class="card-body p-4">
                                        <div class="row g-4 px-2">
                                            <div class="col-lg-6">
                                                <div class="form-group mb-4">
                                                    <label for="formFileLg" class="form-label small fw-semibold text-muted">
                                                        Upload Photo <span class="text-danger"></span>
                                                    </label>
                                                    <input class="form-control form-control-lg bg-light border-0 fs-6" id="formFileLg" name="path" type="file" accept="image/*" onchange="previewImage(this)" />
                                                    <div class="form-text text-muted small mt-2">Accepted formats: JPG, PNG. Max size: 2MB.</div>
                                                    <span class="text-danger small error-text path_error"></span>
                                                </div>

                                                <div class="form-group pt-2">
                                                    <button id="btnSaveAll" type="button" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm">
                                                        SAVE ALL INFORMATION
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="col-lg-6 d-flex align-items-center justify-content-center border-start border-light">
                                                <div class="text-center">
                                                    <div class="rounded-circle overflow-hidden d-flex align-items-center justify-content-center mb-3 mx-auto shadow-sm border border-5 border-white" style="width: 150px; height: 150px; background-color: #f8f9fa;">
                                                        <i id="previewIcon" class="fas fa-user text-secondary opacity-25" style="font-size: 4rem;"></i>
                                                        <img {{ $user->empDetail->empPicPath }} id="imagePreview" src="#" alt="Preview" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                                                    </div>
                                                    <p class="small text-muted mb-0">Photo Preview</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                    

              

            </div>
            
        </div>
    </div>
    <script>
        
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const icon = document.getElementById('previewIcon');
            const file = input.files[0];

            if (file) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    // Set the image source to the result of the file reader
                    preview.src = e.target.result;
                    // Show the image and hide the default icon
                    preview.style.display = 'block';
                    icon.style.display = 'none';
                }

                reader.readAsDataURL(file);
            } else {
                // If user clears the input, reset to default icon
                preview.src = "#";
                preview.style.display = 'none';
                icon.style.display = 'block';
            }
        }
        $(document).ready(function() {

            $(document).on('change', '#txtProvince ', function(e) {
                var provCode = $(this).val();
                var txtProvince = $('#txtCity').data('selected');
                loadCity(provCode)
            });

            $(document).on('change', '#txtCity ', function(e) {
                var code = $(this).val();
                loadBrgy(code)
            });

            function loadCity(prov) {
                var txtCity = $('#txtCity').data('selected');
                axios.get('/get_city',{
                    params: {
                        id: prov
                    }
                })    .then(function (response) {
                    if (response.data.status == 200) {
                        var bodyData = '';
                        //    bodyData += ("<option value=0>-</option>");
                        $.each(response.data.data, function(index, row) {
                            let selected = txtCity == row.citymunCode ? 'selected' : '';
                            bodyData += ("<option " + selected + " value=" + row.citymunCode + ">" + row.citymunDesc + "</option>");
                        })
                        $("#txtCity").empty();
                        $("#txtCity").append(bodyData);
                        
                        if (txtCity) {
                            loadBrgy(txtCity)
                        }
                    }
                })
                .catch(function (error) {
                    dialog.alert({
                        message: error
                    });  
                })
                .then(function () {}); 
            }
            function loadBrgy(city) {
                var txtBrgy = $('#txtBrgy').data('selected');
                axios.get('/get_brgy',{
                    params: {
                        id: city
                    }
                })    .then(function (response) {
                    if (response.data.status == 200) {
                    var bodyData = '';
                    //    bodyData += ("<option value=0>-</option>");
                    $.each(response.data.data, function(index, row) {
                        let selected = txtBrgy == row.brgyCode ? 'selected' : '';
                        bodyData += ("<option " + selected + " value=" + row.brgyCode + ">" + row.brgyDesc + "</option>");
                    })
                    $("#txtBrgy").empty();
                    $("#txtBrgy").append(bodyData);
                    }
                })
                .catch(function (error) {
                    dialog.alert({
                        message: error
                    });  
                })
                .then(function () {}); 
            }
            loadprovince()
            function loadprovince(e) {
                var txtProvince = $('#txtProvince').data('selected');
                axios.post('/get_province',)  
                .then(function (response) {
                    //error response
                    if (response.data.status == 200) {
                        var bodyData = '';
                        bodyData += ("<option value=0>-</option>");
                        $.each(response.data.data, function(index, row) {
                            let selected = txtProvince == row.provCode ? 'selected' : '';

                            bodyData += (`<option ${selected} value=` + row.provCode + `>` + row.provDesc + `</option>`);
                        })

                        if (txtProvince) {
                            loadCity(txtProvince)
                        }
                        $("#txtProvince").empty();
                        $("#txtProvince").append(bodyData);
                    }
                })
                .catch(function (error) {
                    dialog.alert({
                        message: error
                    });  
                })
                .then(function () {});  
            }

            $(document).on('click', '#btnSaveAll', function(e) {
                var datas = $('#frmEnrolment');
                var city=$("#txtCity option:selected" ).text();
                var brgy=$("#txtBrgy option:selected" ).text();
                var prov=$("#txtProvince option:selected" ).text();
                var formData = new FormData($(datas)[0]);
                formData.append('citydesc', city);
                formData.append('brgydesc', brgy);
                formData.append('provdesc', prov);

                axios.post('/employee/update',formData)  
                    .then(function (response) {

                        if (response.data.status == 201) {
                            $.each(response.data.error, function(prefix, val) {
                                $('input[name='+ prefix +']').addClass(" border border-danger") ;
                                $('span.' + prefix + '_error').text(val[0]);
                            });
                            dialog.alert({
                                message: 'Please complete all required fields highlighted in red.'
                            });
                        }

                        if(response.data.status == 200){
                            $('span.error-text').text("");
                            $('input.border').removeClass('border border-danger');
                            $('#frmEnrolment')[0].reset();
                            dialog.alert({
                                message: response.data.msg
                            });

                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        }
                        //success respose
                        if(response.data.status == 202){
                            $('span.error-text').text("");
                            $('input.border').removeClass('border border-danger');
                            dialog.alert({
                                message: response.data.msg
                            });
                        }

                        if(response.data.status == 203){
                        
                            dialog.alert({
                                message: response.data.msg
                            });
                        }
                    })
                    .catch(function (error) {
                        dialog.alert({
                            message: error
                        });  
                    })
                    .then(function () {});   
            
            });
        })

    </script>
 {{-- <script src="{{ asset('js/modules/enrollment.js') }}" defer></script> --}}
@endsection
