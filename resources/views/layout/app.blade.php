<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - {{ $title ?? 'Dashboard' }}</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <link href="{{ asset('css/app.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('css/jquery.dialog.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery.dialog.js') }}" defer></script>
    
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Professional Sidebar Design */
        #accordionSidebar {
            background: linear-gradient(180deg, #008080 0%, #005a5a 100%) !important;
            min-height: 100vh;
        }

        .sidebar-brand {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem 0 !important;
        }

        .sidebar-heading {
            font-size: 0.65rem !important;
            font-weight: 800 !important;
            letter-spacing: 1.5px;
            color: rgba(255, 255, 255, 0.4) !important;
            padding: 0 1.5rem;
            margin-top: 1.5rem;
            text-transform: uppercase;
        }

        .nav-item .nav-link {
            font-weight: 500;
            padding: 0.8rem 1.5rem !important;
            transition: all 0.2s;
        }

        .nav-item .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff !important;
        }

        .nav-link i { font-size: 0.9rem; width: 20px; }

        /* Collapse Inner Styling */
        .collapse-inner {
            background: #ffffff !important;
            border-radius: 0.75rem !important;
            margin: 0.5rem 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
            border: none !important;
        }

        .collapse-item {
            font-size: 0.75rem !important;
            padding: 0.6rem 1rem !important;
            border-radius: 0.5rem !important;
            margin: 2px 0;
            display: flex !important;
            align-items: center;
            color: #4a4a4a !important;
            transition: all 0.2s;
        }

        .collapse-item:hover {
            background-color: #f1f8f8 !important;
            color: #008080 !important;
            font-weight: 600;
            padding-left: 1.25rem !important;
        }

        .collapse-item i { width: 22px; color: #008080; opacity: 0.7; }

        /* Topbar & Alerts */
        .topbar { box-shadow: 0 1px 10px rgba(0,0,0,0.05) !important; }
        .alert { border-radius: 12px; border: none; }

        .btn-blue {
            background-color: #008080;
            color: #fff;
        }

        .btn-blue:hover {
            background-color: #ffffff;
            color: #008080 !important;
            border: 1px solid #008080 !important;
        }
    </style>
</head>

<body id="page-top">

    @if (session('success') || session('error'))
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
            <div class="alert alert-{{ session('success') ? 'success' : 'danger' }} alert-dismissible fade show shadow-lg" role="alert">
                <i class="fas fa-{{ session('success') ? 'check-circle' : 'exclamation-circle' }} me-2"></i>
                {{ session('success') ?? session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    <div id="wrapper">
        <ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ url('/') }}">
                <div class="sidebar-brand-icon">
                    <img style="width: 50px;" src="{{URL::asset('/img/kwatogslogo.png')}}" alt="Logo">
                </div>
            </a>

            <hr class="sidebar-divider my-0 opacity-25">

            @can('home')
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/') }}">
                        <i class="fas fa-fw fa-house"></i>
                        <span>Home</span>
                    </a>
                </li>
            @endcan

            @can('registration')
                <li class="nav-item">
                    <a class="nav-link" href="/pages/modules/registration">
                        <i class="fas fa-fw fa-user-plus"></i>
                        <span>Registration</span>
                    </a>
                </li>
            @endcan

            @php
                $modulePages = [
                    'e201'             => ['name' => 'E-201', 'url' => '/pages/modules/E201', 'icon' => 'fa-id-badge'],
                    'earlyout'         => ['name' => 'Earlyout', 'url' => '/pages/modules/earlyout', 'icon' => 'fa-door-open'],
                    'enrollemployee'   => ['name' => 'Enroll Employee', 'url' => '/pages/modules/registration', 'icon' => 'fa-user-gear'],
                    'loanmanagement'   => ['name' => 'Loans & Charges', 'url' => '/pages/modules/loanManagement', 'icon' => 'fa-hand-holding-dollar'],
                    'leaveapplication' => ['name' => 'Leave App', 'url' => '/pages/modules/leaveApplication', 'icon' => 'fa-calendar-day'],
                    'obttracker'       => ['name' => 'OB Tracker', 'url' => '/pages/modules/obtTracker', 'icon' => 'fa-map-location-dot'],
                    'overtime'         => ['name' => 'Overtime', 'url' => '/pages/modules/overtime', 'icon' => 'fa-user-clock'],
                    'payroll'          => ['name' => 'Payroll System', 'url' => '/pages/modules/payroll', 'icon' => 'fa-file-invoice-dollar'],
                    'debitadvise'      => ['name' => 'Debit Advise', 'url' => '/pages/modules/debitAdvise', 'icon' => 'fa-receipt'],
                    'sendobt'          => ['name' => 'Send to OBT', 'url' => '/pages/modules/sendOBT', 'icon' => 'fa-paper-plane'],
                ];
                
                // 1. Sort the main array first
$modulePages = collect($modulePages)->sort()->toArray();

// 2. Use the sorted array for your check
$hasPagesAccess = collect($modulePages)->keys()->some(fn($key) => auth()->user()?->can($key));

// 3. Pass $modulePages to your view—it will now be alphabetical!
            @endphp

            @if ($hasPagesAccess)
                <div class="sidebar-heading">Operations</div>
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseModules">
                        <i class="fas fa-fw fa-cubes"></i>
                        <span>Workforce</span>
                    </a>
                    <div id="collapseModules" class="collapse" data-bs-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner">
                            @foreach ($modulePages as $key => $page)
                                @can($key)
                                    <a class="collapse-item" href="{{ $page['url'] }}">
                                        <i class="fa-solid {{ $page['icon'] }} me-1"></i> {{ $page['name'] }}
                                    </a>
                                @endcan
                            @endforeach
                        </div>
                    </div>
                </li>
            @endif

            @php
                $managementModules = [
                    'accessrights'        => ['name' => 'Employee Role', 'url' => '/pages/management/accessrights', 'icon' => 'fa-users-gear'],
                    'agencies'            => ['name' => 'Agencies', 'url' => '/pages/management/agencies', 'icon' => 'fa-building-shield'],
                    'archive'             => ['name' => 'Archive', 'url' => '/pages/management/archive', 'icon' => 'fa-box-archive'],
                    'classification'      => ['name' => 'Classification', 'url' => '/pages/management/classification', 'icon' => 'fa-tags'],
                    'companies'           => ['name' => 'Companies', 'url' => '/pages/management/companies', 'icon' => 'fa-building'],
                    'departments'         => ['name' => 'Departments', 'url' => '/pages/management/departments', 'icon' => 'fa-sitemap'],
                    'employeestatus'      => ['name' => 'Emp Status', 'url' => '/pages/management/employeestatus', 'icon' => 'fa-user-tag'],
                    'hmo'                 => ['name' => 'HMOs', 'url' => '/pages/management/hmo', 'icon' => 'fa-heart-pulse'],
                    'holidaylogger'       => ['name' => 'Holidays', 'url' => '/pages/management/holidaylogger', 'icon' => 'fa-calendar-star'],
                    'joblevels'           => ['name' => 'Job Levels', 'url' => '/pages/management/joblevels', 'icon' => 'fa-layer-group'],
                    'leavevalidations'    => ['name' => 'Leave Valid.', 'url' => '/pages/management/leavevalidations', 'icon' => 'fa-calendar-check'],
                    'lilovalidations'     => ['name' => 'Lilo Valid.', 'url' => '/pages/management/lilovalidations', 'icon' => 'fa-clock-rotate-left'],
                    'obvalidations'       => ['name' => 'OB Valid.', 'url' => '/pages/management/obvalidations', 'icon' => 'fa-map-check'],
                    'otfiling'            => ['name' => 'OT Maintenance', 'url' => '/pages/management/otfiling', 'icon' => 'fa-wrench'],
                    'pagibigcontribution' => ['name' => 'Pagibig Contri.', 'url' => '/pages/management/pagibigcontribution', 'icon' => 'fa-piggy-bank'],
                    'parentalsetting'     => ['name' => 'Parental Set.', 'url' => '/pages/management/parentalsetting', 'icon' => 'fa-users-between-lines'],
                    'philhealth'          => ['name' => 'Philhealth', 'url' => '/pages/management/philhealth', 'icon' => 'fa-kit-medical'],
                    'positions'           => ['name' => 'Positions', 'url' => '/pages/management/positions', 'icon' => 'fa-briefcase'],
                    'relationship'        => ['name' => 'Relationship', 'url' => '/pages/management/relationship', 'icon' => 'fa-people-arrows'],
                    'employeeschedules'   => ['name' => 'Scheduler', 'url' => '/employee-schedules', 'icon' => 'fa-calendar-days'],
                    'scheduletime'        => ['name' => 'Schedule Time', 'url' => '/pages/management/time', 'icon' => 'fa-clock'],
                    'ssscontribution'     => ['name' => 'SSS Contri.', 'url' => '/pages/management/ssscontribution', 'icon' => 'fa-hand-holding-medical'],
                    'leavetypes'          => ['name' => 'Leave Types', 'url' => '/pages/management/leavetypes', 'icon' => 'fa-list-check'],
                    'userroles'           => ['name' => 'User Roles', 'url' => '/user-roles', 'icon' => 'fa-shield-halved'],
                    'admine201'           => ['name' => 'Admin E-201', 'url' => '/pages/management/e201', 'icon' => 'fa-id-card-alt'],
                    'leavecreditallocation'          => ['name' => 'Leave Credit Allocation', 'url' => '/pages/management/leavecreditallocations', 'icon' => 'fa-list-check'],
                ];
                // 1. Sort the modules by their values (labels) A-Z
                $managementModules = collect($managementModules)->sort()->toArray();

                // 2. Perform your access check
                $hasManagementAccess = collect($managementModules)->keys()->some(fn($key) => auth()->user()?->can($key));
                // $hasManagementAccess = collect($managementModules)->keys()->some(fn($key) => auth()->user()?->can($key));
            @endphp

            @if ($hasManagementAccess)
                <div class="sidebar-heading">Management</div>
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSettings">
                        <i class="fas fa-fw fa-gears"></i>
                        <span>Settings</span>
                    </a>
                    <div id="collapseSettings" class="collapse" data-bs-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner">
                            @foreach ($managementModules as $key => $module)
                                @can($key)
                                    <a class="collapse-item" href="{{ $module['url'] }}">
                                        <i class="fa-solid {{ $module['icon'] }} me-1"></i> {{ $module['name'] }}
                                    </a>
                                @endcan
                            @endforeach
                        </div>
                    </div>
                </li>
            @endif

            @php
                $moduleReports = ['attendance' => ['name' => 'Attendance Viewer', 'url' => '/pages/reports/attendance', 'icon' => 'fa-chart-column']];
                $hasReportAccess = collect($moduleReports)->keys()->some(fn($key) => auth()->user()?->can($key));
            @endphp

            @if ($hasReportAccess)
                <div class="sidebar-heading">Analysis</div>
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseReports">
                        <i class="fas fa-fw fa-file-contract"></i>
                        <span>Reports</span>
                    </a>
                    <div id="collapseReports" class="collapse" data-bs-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner">
                            @foreach ($moduleReports as $key => $page)
                                @can($key)
                                    <a class="collapse-item" href="{{ $page['url'] }}">
                                        <i class="fa-solid {{ $page['icon'] }} me-1"></i> {{ $page['name'] }}
                                    </a>
                                @endcan
                            @endforeach
                        </div>
                    </div>
                </li>
            @endif

            <hr class="sidebar-divider d-none d-md-block opacity-25">
            <div class="text-center d-none d-md-inline pt-3">
                <button class="rounded-circle border-0" id="sidebarToggle" style="background: rgba(255,255,255,0.2)"></button>
            </div>
        </ul>

        <div id="content-wrapper" class="d-flex flex-column bg-light">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top border-bottom">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle me-3">
                        <i class="fa fa-bars text-primary"></i>
                    </button>

                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-bs-toggle="dropdown">
                                <div class="d-flex flex-column text-end me-3 d-none d-lg-flex">
                                    <span class="text-dark small fw-bold">{{ session()->get('loggedEmployee') }}</span>
                                    <span class="text-muted" style="font-size: 0.6rem;">System Administrator</span>
                                </div>
                                <img class="img-profile rounded-circle border shadow-sm" src="{{ URL::asset('/img/undraw_profile.svg') }}" width="35">
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                <div class="dropdown-header">Account Settings</div>
                                <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-danger"></i> Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>

            <footer class="sticky-footer bg-white border-top py-3 mt-4">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto text-muted small">
                        <span>Copyright &copy; <b>{{ config('app.name') }}</b> 2026</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <a class="scroll-to-top rounded-circle shadow" href="#page-top" style="background: #008080;"><i class="fas fa-angle-up"></i></a>

    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold">Ready to Leave?</h5>
                    <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 text-muted">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <a class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" href="/logoutSystem">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/jquery.easing.min.js') }}"></script>
    <script src="{{ asset('js/system.js') }}" defer></script>
</body>

</html>