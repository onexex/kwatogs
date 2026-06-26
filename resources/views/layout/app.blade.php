<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        // Fall back to a humanized route name when a page doesn't pass an explicit $title,
        // so the tab isn't labeled "Dashboard" everywhere. Strips trailing action segments
        // (index/show/edit/...) and turns "mail-integration.index" -> "Mail Integration".
        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
        $derivedTitle = $routeName
            ? \Illuminate\Support\Str::of($routeName)
                ->replaceMatches('/\.(index|show|edit|create|store|update|destroy|view|list)$/', '')
                ->replace('.', ' ')
                ->headline()
            : null;
        $pageTitle = $title ?? ($derivedTitle ?: 'Dashboard');
    @endphp
    <title>{{ config('app.name') }} - {{ $pageTitle }}</title>

    {{-- Brand favicon (public/favicon.ico was empty; use the square 960x960 logo) --}}
    <link rel="icon" type="image/png" href="{{ asset('img/kwatogslogo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('img/kwatogslogo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/kwatogslogo.png') }}">

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

        /* Allow notification badges (e.g. pending leave requests) to align right */
        .nav-item .nav-link,
        .collapse-item {
            display: flex !important;
            align-items: center;
        }
        .nav-item .nav-link .badge,
        .collapse-item .badge {
            font-size: 0.65rem;
            padding: 0.3em 0.55em;
        }

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

        /* Active state highlighting so the user knows where they are */
        .nav-item .nav-link.active-page,
        .nav-item .nav-link.active-parent {
            background: rgba(255, 255, 255, 0.15);
            color: #fff !important;
            font-weight: 700;
            position: relative;
        }

        .nav-item .nav-link.active-page::before,
        .nav-item .nav-link.active-parent::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #ffc107;
        }

        .collapse-item.active {
            background-color: #e0f2f1 !important;
            color: #008080 !important;
            font-weight: 700;
        }

        .collapse-item.active i { opacity: 1; }

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
    
    <script>
        window.userPermissions = {!! json_encode(auth()->user()?->getAllPermissions()->pluck('name')) !!};

        
    </script>
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
                    <a class="nav-link {{ request()->is('/') ? 'active-page' : '' }}" href="{{ url('/') }}">
                        <i class="fas fa-fw fa-house"></i>
                        <span>Home</span>
                    </a>
                </li>
            @endcan

            @can('kuboaccess')
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('kubo*') ? 'active-page' : '' }}" href="{{ route('kubo.feed') }}">
                        <i class="fas fa-fw fa-users"></i>
                        <span>KwHub</span>
                    </a>
                </li>
            @endcan

            @can('registration')
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('pages/modules/registration*') ? 'active-page' : '' }}" href="/pages/modules/registration">
                        <i class="fas fa-fw fa-user-plus"></i>
                        <span>Registration</span>
                    </a>
                </li>
            @endcan

            @php
                $modulePages = [
                    'hrdashboard'      => ['name' => 'HR Dashboard', 'url' => '/pages/management/hr-dashboard', 'icon' => 'fa-gauge-high'],
                    'e201'             => ['name' => 'E-201', 'url' => '/pages/modules/E201', 'icon' => 'fa-id-badge'],
                    'earlyout'         => ['name' => 'Earlyout', 'url' => '/pages/modules/earlyout', 'icon' => 'fa-door-open'],
                    'enrollemployee'   => ['name' => 'Enroll Employee', 'url' => '/pages/modules/registration', 'icon' => 'fa-user-gear'],
                    'loanmanagement'   => ['name' => 'Loans & Charges', 'url' => '/pages/modules/loanManagement', 'icon' => 'fa-hand-holding-dollar'],
                    'payadjustments'   => ['name' => 'Pay Adjustments', 'url' => '/pages/modules/payadjustments', 'icon' => 'fa-sliders'],
                    'attendanceimport' => ['name' => 'Attendance Import', 'url' => '/attendance-import', 'icon' => 'fa-file-import'],
                    'overtimeimport'   => ['name' => 'Overtime Import', 'url' => '/overtime-import', 'icon' => 'fa-clock'],
                    'leaveimport'      => ['name' => 'Leave Import', 'url' => '/leave-import', 'icon' => 'fa-calendar-check'],
                    'scheduleimport'   => ['name' => 'Schedule Import', 'url' => '/schedule-import', 'icon' => 'fa-calendar-plus'],
                    'approveschedulechange' => ['name' => 'Pending Schedule Requests', 'url' => '/pages/modules/schedulerequests', 'icon' => 'fa-calendar-check'],
                    'leaveapplication' => ['name' => 'Leave Application', 'url' => '/pages/modules/leaveApplication', 'icon' => 'fa-calendar-day'],
                    'pendingleaverequests' => ['name' => 'Pending Leave Requests', 'url' => '/pages/modules/leaverequests', 'icon' => 'fa-calendar-day'],
                    'obttracker'       => ['name' => 'OB Tracker', 'url' => '/pages/modules/obtTracker', 'icon' => 'fa-map-location-dot'],
                    'overtime'         => ['name' => 'Overtime', 'url' => '/pages/modules/overtime', 'icon' => 'fa-user-clock'],
                    'pendingovertimerequests' => ['name' => 'Pending Overtime Requests', 'url' => '/pages/modules/overtimerequests', 'icon' => 'fa-calendar-day'],
                    'payroll'          => ['name' => 'Payroll System', 'url' => '/pages/modules/payroll', 'icon' => 'fa-file-invoice-dollar'],
                    'payrolllogs'      => ['name' => 'Payroll Logs', 'url' => '/payroll-logs', 'icon' => 'fa-clipboard-list'],
                    'debitadvise'      => ['name' => 'Debit Advise', 'url' => '/pages/modules/debitAdvise', 'icon' => 'fa-receipt'],
                    'sendobt'          => ['name' => 'Send to OBT', 'url' => '/pages/modules/sendOBT', 'icon' => 'fa-paper-plane'],
                    'manual_entry'          => ['name' => 'Adjustment Time', 'url' => '/pages/modules/adjustmentTime', 'icon' => 'fa-paper-plane'],
                ];
                
                // 1. Sort the main array alphabetically (A→Z) by display name
                $modulePages = collect($modulePages)->sortBy(fn($p) => strtolower($p['name']))->toArray();

                // 2. Use the sorted array for your check
                $hasPagesAccess = collect($modulePages)->keys()->some(fn($key) => auth()->user()?->can($key));

                // 3. Pass $modulePages to your view—it will now be alphabetical!

                // 4. Determine if the current URL belongs to this group, so we can keep
                //    it expanded and highlight the active item after navigation.
                $modulePagesActiveKey = collect($modulePages)->keys()->first(function ($key) use ($modulePages) {
                    return request()->is(ltrim($modulePages[$key]['url'], '/') . '*');
                });
                $modulePagesGroupActive = !is_null($modulePagesActiveKey);

                // 5. Count pending leave requests waiting for the current user's approval
                //    NOTE: scoped by emp_details.empCompID (company-wide), matching
                //    LeaveRequestContoller::getAll() exactly — it previously filtered by
                //    empISID (direct reports only), which under-counted (often to 0) for
                //    HR/CFO-level approvers who aren't anyone's direct supervisor but can
                //    still see/approve every pending leave in the company on that list page.
                $pendingLeaveCount = 0;
                if (auth()->user()?->can('pendingleaverequests')) {
                    $pendingLeaveUser = auth()->user();
                    $pendingLeaveCount = \App\Models\Leave::join('emp_details', 'emp_details.empID', '=', 'leaves.employee_id')
                        ->where('emp_details.empCompID', optional($pendingLeaveUser->empDetail)->empCompID)
                        ->where(function ($q) use ($pendingLeaveUser) {
                            if ($pendingLeaveUser->can('approveleave')) {
                                $q->orWhere('leaves.status', \App\Enums\LeaveStatusEnum::FORAPPROVAL->name);
                            }

                            if ($pendingLeaveUser->can('approvecfoleave')) {
                                $q->orWhere('leaves.status', \App\Enums\LeaveStatusEnum::APPROVED->name);
                            }
                        })
                        ->count();
                }
            @endphp

            @if ($hasPagesAccess)
                <div class="sidebar-heading">Operations</div>
                <li class="nav-item">
                    <a class="nav-link {{ $modulePagesGroupActive ? 'active-parent' : 'collapsed' }}" href="#" data-bs-toggle="collapse" data-bs-target="#collapseModules" aria-expanded="{{ $modulePagesGroupActive ? 'true' : 'false' }}">
                        <i class="fas fa-fw fa-cubes"></i>
                        <span>Workforce</span>
                        @if ($pendingLeaveCount > 0)
                            <span class="badge rounded-pill bg-danger ms-auto">{{ $pendingLeaveCount }}</span>
                        @endif
                    </a>
                    <div id="collapseModules" class="collapse {{ $modulePagesGroupActive ? 'show' : '' }}" data-bs-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner">
                            @foreach ($modulePages as $key => $page)
                                @can($key)
                                    <a class="collapse-item {{ $key === $modulePagesActiveKey ? 'active' : '' }}" href="{{ $page['url'] }}">
                                        <i class="fa-solid {{ $page['icon'] }} me-1"></i> {{ $page['name'] }}
                                        @if ($key === 'pendingleaverequests' && $pendingLeaveCount > 0)
                                            <span class="badge rounded-pill bg-danger ms-auto">{{ $pendingLeaveCount }}</span>
                                        @endif
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
                    'auditlog'            => ['name' => 'Audit Trail', 'url' => '/pages/management/audit-trail', 'icon' => 'fa-clipboard-list'],
                    'classification'      => ['name' => 'Classification', 'url' => '/pages/management/classification', 'icon' => 'fa-tags'],
                    'companies'           => ['name' => 'Companies', 'url' => '/pages/management/companies', 'icon' => 'fa-building'],
                    'databasebackup'      => ['name' => 'Database Backup', 'url' => '/pages/management/databasebackup', 'icon' => 'fa-database', 'permissions' => ['databasebackup', 'databasebackupcreate', 'databasebackuprestore', 'databasebackupdelete']],
                    'departments'         => ['name' => 'Departments', 'url' => '/pages/management/departments', 'icon' => 'fa-sitemap'],
                    'employeestatus'      => ['name' => 'Emp Status', 'url' => '/pages/management/employeestatus', 'icon' => 'fa-user-tag'],
                    'govdues'             => ['name' => 'Government Dues', 'url' => '/pages/management/govdues', 'icon' => 'fa-landmark'],
                    'holidaylogger'       => ['name' => 'Holidays', 'url' => '/pages/management/holidaylogger', 'icon' => 'fa-calendar'],
                    'leavevalidations'    => ['name' => 'Leave Valid.', 'url' => '/pages/management/leavevalidations', 'icon' => 'fa-calendar-check'],
                    'lilovalidations'     => ['name' => 'Lilo Valid.', 'url' => '/pages/management/lilovalidations', 'icon' => 'fa-clock-rotate-left'],
                    'mailintegration'     => ['name' => 'Mail Integration', 'url' => '/pages/management/mailintegration', 'icon' => 'fa-paper-plane'],
                    'maintenancemode'     => ['name' => 'Maintenance Mode', 'url' => '/pages/management/maintenancemode', 'icon' => 'fa-screwdriver-wrench'],
                    'obvalidations'       => ['name' => 'OB Valid.', 'url' => '/pages/management/obvalidations', 'icon' => 'fa-map-check'],
                    'otfiling'            => ['name' => 'OT Maintenance', 'url' => '/pages/management/otfiling', 'icon' => 'fa-wrench'],
                    'pagibigcontribution' => ['name' => 'Pagibig Contri.', 'url' => '/pages/management/pagibigcontribution', 'icon' => 'fa-piggy-bank'],
                    'philhealth'          => ['name' => 'Philhealth', 'url' => '/pages/management/philhealth', 'icon' => 'fa-kit-medical'],
                    'positions'           => ['name' => 'Positions', 'url' => '/pages/management/positions', 'icon' => 'fa-briefcase'],
                    'relationship'        => ['name' => 'Relationship', 'url' => '/pages/management/relationship', 'icon' => 'fa-people-arrows'],
                    'employeeschedules'   => ['name' => 'Scheduler', 'url' => '/employee-schedules', 'icon' => 'fa-calendar-days'],
                    'ssscontribution'     => ['name' => 'SSS Contri.', 'url' => '/pages/management/ssscontribution', 'icon' => 'fa-hand-holding-medical'],
                    'leavetypes'          => ['name' => 'Leave Types', 'url' => '/pages/management/leavetypes', 'icon' => 'fa-list-check'],
                    'userroles'           => ['name' => 'User Roles', 'url' => '/user-roles', 'icon' => 'fa-shield-halved'],
                    'admine201'           => ['name' => 'Admin E-201', 'url' => '/pages/management/e201', 'icon' => 'fa-id-card-alt'],
                    'leavecreditallocation'          => ['name' => 'Leave Credit Allocation', 'url' => '/pages/management/leavecreditallocations', 'icon' => 'fa-list-check'],
                    'allowedips'          => ['name' => 'IP Restriction', 'url' => '/pages/management/allowed-ips', 'icon' => 'fa-network-wired', 'permissions' => ['allowedips', 'allowedipslogs']],
                ];
                // 1. Sort the modules alphabetically (A→Z) by display name
                $managementModules = collect($managementModules)->sortBy(fn($m) => strtolower($m['name']))->toArray();

                // 2. Perform your access check
                $hasManagementAccess = collect($managementModules)->some(function ($module, $key) {
                    $permissions = $module['permissions'] ?? [$key];

                    return collect($permissions)->some(fn($permission) => auth()->user()?->can($permission));
                });

                // 3. Determine the active item so the menu stays open and highlighted
                //    after navigating to a Management page.
                $managementActiveKey = collect($managementModules)->keys()->first(function ($key) use ($managementModules) {
                    return request()->is(ltrim($managementModules[$key]['url'], '/') . '*');
                });
                $managementGroupActive = !is_null($managementActiveKey);
            @endphp

            @if ($hasManagementAccess)
                <div class="sidebar-heading">Management</div>
                <li class="nav-item">
                    <a class="nav-link {{ $managementGroupActive ? 'active-parent' : 'collapsed' }}" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSettings" aria-expanded="{{ $managementGroupActive ? 'true' : 'false' }}">
                        <i class="fas fa-fw fa-gears"></i>
                        <span>Settings</span>
                    </a>
                    <div id="collapseSettings" class="collapse {{ $managementGroupActive ? 'show' : '' }}" data-bs-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner">
                            @foreach ($managementModules as $key => $module)
                                @php
                                    $modulePermissions = $module['permissions'] ?? [$key];
                                    $hasModuleAccess = collect($modulePermissions)->some(fn($permission) => auth()->user()?->can($permission));
                                @endphp
                                @if ($hasModuleAccess)
                                    <a class="collapse-item {{ $key === $managementActiveKey ? 'active' : '' }}" href="{{ $module['url'] }}">
                                        <i class="fa-solid {{ $module['icon'] }} me-1"></i> {{ $module['name'] }}
                                    </a>
                                @endif
                            @endforeach
                            {{-- Documentation: always available to anyone who can see Settings --}}
                            <a class="collapse-item {{ request()->is('pages/management/documentation*') ? 'active' : '' }}" href="/pages/management/documentation">
                                <i class="fa-solid fa-book me-1"></i> Documentation
                            </a>
                        </div>
                    </div>
                </li>
            @endif

            @php
                $moduleReports = [
                    'attendance' => ['name' => 'Attendance Viewer', 'url' => '/pages/reports/attendance', 'icon' => 'fa-chart-column'],
                    'employeeinformation' => ['name' => 'Employee Information', 'url' => '/reports/employee-information', 'icon' => 'fa-chart-column'],
                    'overtimereport' => ['name' => 'Overtime Report', 'url' => '/reports/overtime', 'icon' => 'fa-user-clock'],
                    'leavereport' => ['name' => 'Leave Report', 'url' => '/reports/leave', 'icon' => 'fa-calendar-day'],
                    'thirteenthmonth' => ['name' => '13th Month Pay', 'url' => '/reports/thirteenth-month', 'icon' => 'fa-gift']
                ];
                // Sort the reports alphabetically (A→Z) by display name
                $moduleReports = collect($moduleReports)->sortBy(fn($r) => strtolower($r['name']))->toArray();

                $hasReportAccess = collect($moduleReports)->keys()->some(fn($key) => auth()->user()?->can($key));

                // Determine the active item so the menu stays open and highlighted
                // after navigating to a Reports page.
                $reportsActiveKey = collect($moduleReports)->keys()->first(function ($key) use ($moduleReports) {
                    return request()->is(ltrim($moduleReports[$key]['url'], '/') . '*');
                });
                $reportsGroupActive = !is_null($reportsActiveKey);
            @endphp

            @if ($hasReportAccess)
                <div class="sidebar-heading">Analysis</div>
                <li class="nav-item">
                    <a class="nav-link {{ $reportsGroupActive ? 'active-parent' : 'collapsed' }}" href="#" data-bs-toggle="collapse" data-bs-target="#collapseReports" aria-expanded="{{ $reportsGroupActive ? 'true' : 'false' }}">
                        <i class="fas fa-fw fa-file-contract"></i>
                        <span>Reports</span>
                    </a>
                    <div id="collapseReports" class="collapse {{ $reportsGroupActive ? 'show' : '' }}" data-bs-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner">
                            @foreach ($moduleReports as $key => $page)
                                @can($key)
                                    <a class="collapse-item {{ $key === $reportsActiveKey ? 'active' : '' }}" href="{{ $page['url'] }}">
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
                                    {{-- Real job designation from the employee record (positions.pos_desc), not a fixed label --}}
                                    <span class="text-muted" style="font-size: 0.6rem;">{{ optional(optional(auth()->user()?->empDetail)->position)->pos_desc ?: 'Employee' }}</span>
                                </div>
                                <img class="img-profile rounded-circle border shadow-sm" src="{{ URL::asset('/img/undraw_profile.svg') }}" width="35">
                            </a>
                            
                            <div class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                <div class="dropdown-header">Account Settings</div>
                                
                                <a class="dropdown-item py-2" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#changePassModal">
                                    <i class="fas fa-user-cog fa-sm fa-fw me-2 text-primary"></i> Password Settings
                                </a>
                                
                                <div class="dropdown-divider"></div> {{-- Divider line para malinis tignan --}}
                                
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

    {{-- // Change Password Modal 412026 --}}
    <div class="modal fade" id="changePassModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                <div class="modal-header bg-light border-0 py-3" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fa-solid fa-lock me-2 text-teal"></i>Update Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="changePasswordForm">
                    @csrf
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-4">Siguraduhing ang iyong bagong password ay mahirap hulaan para sa seguridad ng iyong account.</p>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Current Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fa-solid fa-key text-muted"></i></span>
                                <input type="password" name="current_password" class="form-control bg-light border-0" placeholder="••••••••" required>
                                <button class="btn btn-light border-0 toggle-password" type="button"><i class="fa-solid fa-eye-slash"></i></button>
                            </div>
                            <span class="text-danger error-text current_password_error small"></span>
                        </div>

                        <hr class="my-4 opacity-50">

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fa-solid fa-shield-halved text-muted"></i></span>
                                <input type="password" name="new_password" id="new_password" class="form-control bg-light border-0" placeholder="••••••••" required>
                                <button class="btn btn-light border-0 toggle-password" type="button"><i class="fa-solid fa-eye-slash"></i></button>
                            </div>
                            <div class="progress mt-2" style="height: 5px;">
                                <div id="strengthBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small id="strengthText" class="text-muted extra-small"></small>
                            <span class="text-danger error-text new_password_error small"></span>
                        </div>

                        <div class="mb-0">
                            <label class="form-label small fw-bold text-muted text-uppercase">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fa-solid fa-check-double text-muted"></i></span>
                                <input type="password" name="new_password_confirmation" class="form-control bg-light border-0" placeholder="••••••••" required>
                            </div>
                              <small class="conf_msg extra-small mt-1 d-block"></small> 
                        </div>

                        
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted" data-bs-dismiss="modal">Cancel</button>
                       <button type="button" id="btnUpdatePass" class="btn btn-primary">
                        <i class="fa-solid fa-save me-2"></i>Change Password
                    </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/jquery.easing.min.js') }}"></script>
    <script src="{{ asset('js/system.js') }}" defer></script>

    @stack('scripts')
</body>

</html>