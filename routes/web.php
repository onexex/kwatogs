<?php


use App\Http\Controllers\agenciesCtrl;
use App\Http\Controllers\archiveCtrl;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\classiticationCtrl;
use App\Http\Controllers\companyCtrl;
use App\Http\Controllers\PayrollPeriodController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\MailIntegrationController;
use App\Http\Controllers\MaintenanceModeController;
use App\Http\Controllers\PayslipEmailController;
use App\Http\Controllers\departmentCtrl;
use App\Http\Controllers\earlyoutCtrl;
use App\Http\Controllers\EmployeeRecordController;
use App\Http\Controllers\EmployeeScheduleController;
use App\Http\Controllers\empSchedulerCtrl;
use App\Http\Controllers\empStatCtrl;
use App\Http\Controllers\eovalidationCtrl;
use App\Http\Controllers\hmoCtrl;
use App\Http\Controllers\holidayLoggerCtrl;
use App\Http\Controllers\homeDarCtrl;
use App\Http\Controllers\jobleveCtrl;
use App\Http\Controllers\Leave\LeaveController;
use App\Http\Controllers\Leave\LeaveRequestContoller;
use App\Http\Controllers\LeaveCreditAllocationController;
use App\Http\Controllers\leavetypeCtrl;
use App\Http\Controllers\leavevalidationCtrl;
use App\Http\Controllers\liloValidationsCtrl;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\CoeController;
use App\Http\Controllers\CoeSignatoryController;
use App\Http\Controllers\PayAdjustmentController;
use App\Http\Controllers\loginCtrl;
use App\Http\Controllers\obValidationsCtrl;
use App\Http\Controllers\otfillingCtrl;
use App\Http\Controllers\Overtime\OvertimeRequestController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\pageCtrl;
use App\Http\Controllers\pagibigCtrl;
use App\Http\Controllers\parentalSettingsCtrl;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollApprovalController;
use App\Http\Controllers\PayrollExportController;
use App\Http\Controllers\AttendanceImportController;
use App\Http\Controllers\ImportHistoryController;
use App\Http\Controllers\ScheduleImportController;
use App\Http\Controllers\OvertimeImportController;
use App\Http\Controllers\LeaveImportController;
use App\Http\Controllers\ScheduleRequestController;
use App\Http\Controllers\HrDashboardController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\PayrollLogController;
use App\Http\Controllers\philhealthCtrl;
use App\Http\Controllers\positionCtrl;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\registerCtrl;
use App\Http\Controllers\relationshipCtrl;
use App\Http\Controllers\reportAttendanceCtrl;
use App\Http\Controllers\Reports\EmployeeInformationReportController;
use App\Http\Controllers\Reports\OvertimeReportController;
use App\Http\Controllers\Reports\LeaveReportController;
use App\Http\Controllers\Reports\ThirteenthMonthController;
use App\Http\Controllers\roleCtrl;
use App\Http\Controllers\Roles\EmployeeRoleController;
use App\Http\Controllers\Roles\RolesController;
use App\Http\Controllers\silCtrl;
use App\Http\Controllers\sssCtrl;
use App\Http\Controllers\workTimeCtrl;
use App\Http\Controllers\KuBo\KuBoController;
use App\Http\Controllers\KuBo\FeedController;
use App\Http\Controllers\KuBo\PostController;
use App\Http\Controllers\KuBo\ReactionController;
use App\Http\Controllers\KuBo\CommentController;
use App\Http\Controllers\AllowedIpController;
use App\Http\Controllers\KuBo\RepostController;
use App\Http\Controllers\KuBo\NotificationController;
use App\Http\Controllers\KuBo\ExploreController;
use App\Http\Controllers\KuBo\ProfileController as KuBoProfileController;
use App\Http\Controllers\KuBo\HashtagController;
use App\Http\Controllers\KuBo\PresenceController;
use App\Http\Controllers\KuBo\ChatController;
use App\Http\Controllers\KuBo\ImageUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/login', function () {return view('login.login');})->middleware('throttle:10,1');
Route::post('/loginSystem',[loginCtrl::class, 'loginSystem'])->middleware('throttle:10,1');
Route::get('/logoutSystem',[loginCtrl::class, 'logoutSystem']);
// Route::get('/', function () {return view('home');});


Route::group(['middleware' => ['AuthCheck', 'force.password', 'check.employee.ip', 'check.maintenance', 'block.separated']], function () {

    ##

    Route::post('/earlyout/submit',[earlyoutCtrl::class, 'submit']);
    ##

    Route::get('/login/testmoto',[pageCtrl::class, 'test']);


    Route::get('/', [\App\Http\Controllers\LandingController::class, 'index']);
    Route::get('/users/manage',[pageCtrl::class, 'indexUsers']);
    Route::get('/pages/test',[pageCtrl::class, 'alas']);
    //pages
    Route::get('/pages/management/time',[pageCtrl::class, 'officetime']);
    Route::get('/pages/management/companies',[pageCtrl::class, 'companies']);
    Route::get('/pages/management/classification',[pageCtrl::class, 'classification']);
    Route::get('/pages/management/e201',[pageCtrl::class, 'e201']);
    Route::get('/pages/management/whatsnew',[pageCtrl::class, 'whatsnew'])->name('whatsnew');
    Route::get('/pages/modules/registration',[pageCtrl::class, 'registration']);

    //work time function
    Route::post('/wt/create_update',[workTimeCtrl::class, 'create_update']);
    Route::get('/wt/get',[workTimeCtrl::class, 'wt_get']);
    Route::get('/wt/wt_edit',[workTimeCtrl::class, 'wt_edit']);

    //company
    Route::post('/company/create_update',[companyCtrl::class, 'create_update']);
    Route::get('/company/get_all',[companyCtrl::class, 'get_all']);
    Route::get('/company/get_edit',[companyCtrl::class, 'get_edit']);
    Route::get('/company/delete',[companyCtrl::class, 'delete']);
    Route::get('/payroll-periods/{company}', [PayrollPeriodController::class, 'byCompany'])->name('payroll-periods.by-company')->middleware('can:companies');
    Route::post('/payroll-periods/{company}', [PayrollPeriodController::class, 'save'])->name('payroll-periods.save')->middleware('can:companies');

    //classification
    Route::post('/classification/create_update',[classiticationCtrl::class, 'create_update']);
    Route::get('/classification/get_all',[classiticationCtrl::class, 'get_all']);
    Route::get('/classification/delete',[classiticationCtrl::class, 'delete']);
    Route::get('/classification/edit',[classiticationCtrl::class, 'edit']);

    //government dues (per-employee SSS / PhilHealth / Pag-IBIG enrolment toggles)
    Route::get('/pages/management/govdues',[App\Http\Controllers\GovDuesCtrl::class, 'index'])->middleware('can:govdues');
    Route::get('/govdues/get_all',[App\Http\Controllers\GovDuesCtrl::class, 'getAll'])->middleware('can:govdues');
    Route::post('/govdues/toggle',[App\Http\Controllers\GovDuesCtrl::class, 'toggle'])->middleware('can:govdues');
    Route::post('/govdues/toggle-all',[App\Http\Controllers\GovDuesCtrl::class, 'toggleAll'])->middleware('can:govdues');

    //payroll
    Route::get('/pages/modules/payroll',[pageCtrl::class, 'payroll']);

    //functions
    Route::post('/function/generateEmpid',[registerCtrl::class, 'generateEmpID']);
    Route::post('/enroll/save',[registerCtrl::class, 'create']);
    Route::post('/employee/update',[registerCtrl::class, 'update']);
    Route::get('admin/e201/fetch/{empID}', [EmployeeRecordController::class, 'getEmployeeDetails']);
    Route::get('admin/e201/edit/{user}', [EmployeeRecordController::class, 'editEmployee']);
    Route::post('admin/e201/reset-password/{user}', [EmployeeRecordController::class, 'resetPassword']);
    Route::post('admin/e201/update-status/{user}', [EmployeeRecordController::class, 'updateStatus'])
        ->middleware('can:manageemployeestatus');


    // JMC
    //JM 22/09/2022
    Route::get('/pages/management/agencies',[pageCtrl::class, 'agencies']);
    Route::get('/pages/management/positions',[pageCtrl::class, 'positions']);
    Route::get('/pages/management/joblevels',[pageCtrl::class, 'joblevels']);
    Route::get('/pages/management/hmo',[pageCtrl::class, 'hmo']);
    Route::get('/pages/management/employeestatus',[pageCtrl::class, 'employeestatus']);
    Route::get('/pages/management/leavetypes',[pageCtrl::class, 'leavetypes']);
    Route::get('/pages/management/userroles',[pageCtrl::class, 'userroles']);
    Route::get('/pages/management/otfiling',[pageCtrl::class, 'otfiling']);
    Route::get('/pages/management/eo',[pageCtrl::class, 'eo']);
    Route::get('/pages/management/philhealth',[pageCtrl::class, 'philhealth'])->middleware('can:philhealth');
    Route::get('/pages/management/sil',[pageCtrl::class, 'sil']);
    Route::get('/pages/management/parentalsetting',[pageCtrl::class, 'parental']);
    Route::get('/pages/management/shifts',[pageCtrl::class, 'shifts']);
    Route::get('/pages/management/archive',[pageCtrl::class, 'archive']);
    Route::get('/pages/management/E201',[pageCtrl::class, 'E201Mgt']);

    // 28/09/2022 Reports
    Route::get('/pages/report/alas',[pageCtrl::class, 'alasView']);
    Route::get('/pages/reports/attendance',[pageCtrl::class, 'attendanceView']);
    Route::get('/pages/report/dar',[pageCtrl::class, 'darView']);
    Route::get('/pages/report/eo',[pageCtrl::class, 'eoView']);
    Route::get('/pages/report/ob',[pageCtrl::class, 'obView']);
    Route::get('/pages/report/ot',[pageCtrl::class, 'otView']);
    Route::get('/pages/report/leave_credit',[pageCtrl::class, 'leaveView']);

    // Modules
    Route::get('/pages/modules/E201',[pageCtrl::class, 'e201File']);
    Route::get('/pages/modules/memorandum',[pageCtrl::class, 'memorandum']);

    // SHAIRA
    //MANAGEMENT
    Route::get('/pages/management/documentation',[pageCtrl::class, 'documentation'])->name('documentation');
    Route::get('/pages/management/accessrights',[EmployeeRoleController::class, 'index']);
    Route::get('/pages/management/databasebackup',[DatabaseBackupController::class, 'index'])->name('database-backup.index');
    Route::post('/pages/management/databasebackup',[DatabaseBackupController::class, 'store'])->name('database-backup.store');
    Route::post('/pages/management/databasebackup/import',[DatabaseBackupController::class, 'import'])->name('database-backup.import');
    Route::get('/pages/management/databasebackup/{filename}/download',[DatabaseBackupController::class, 'download'])->name('database-backup.download');
    Route::post('/pages/management/databasebackup/{filename}/restore',[DatabaseBackupController::class, 'restore'])->name('database-backup.restore');
    Route::delete('/pages/management/databasebackup/{filename}',[DatabaseBackupController::class, 'destroy'])->name('database-backup.destroy');

    // Mail Integration (modular email provider config for payslip automation, etc.)
    Route::get('/pages/management/mailintegration',[MailIntegrationController::class, 'index'])->name('mail-integration.index')->middleware('can:mailintegration');
    Route::post('/pages/management/mailintegration',[MailIntegrationController::class, 'store'])->name('mail-integration.store')->middleware('can:mailintegration');
    Route::put('/pages/management/mailintegration/{mailIntegration}',[MailIntegrationController::class, 'update'])->name('mail-integration.update')->middleware('can:mailintegration');
    Route::post('/pages/management/mailintegration/{mailIntegration}/test',[MailIntegrationController::class, 'test'])->name('mail-integration.test')->middleware('can:mailintegration');
    Route::post('/pages/management/mailintegration/{mailIntegration}/activate',[MailIntegrationController::class, 'activate'])->name('mail-integration.activate')->middleware('can:mailintegration');
    Route::delete('/pages/management/mailintegration/{mailIntegration}',[MailIntegrationController::class, 'destroy'])->name('mail-integration.destroy')->middleware('can:mailintegration');

    Route::get('/pages/management/maintenancemode',[MaintenanceModeController::class, 'index'])->name('maintenance-mode.index')->middleware('can:maintenancemode');
    Route::post('/pages/management/maintenancemode',[MaintenanceModeController::class, 'update'])->name('maintenance-mode.update')->middleware('can:maintenancemode');

    Route::get('/pages/management/departments',[pageCtrl::class, 'departments']);
    Route::get('/pages/management/relationship',[pageCtrl::class, 'relationship']);
    Route::get('/pages/management/leavevalidations',[pageCtrl::class, 'leavevalidations']);
    Route::get('/pages/management/holidaylogger',[pageCtrl::class, 'holidaylogger']);
    Route::get('/pages/management/obvalidations',[pageCtrl::class, 'obvalidations']);
    Route::get('/pages/management/ssscontribution',[pageCtrl::class, 'ssscontribution'])->middleware('can:ssscontribution');
    Route::get('/pages/management/pagibigcontribution',[pageCtrl::class, 'pagibigcontribution'])->middleware('can:pagibigcontribution');
    Route::get('/pages/management/empscheduler',[pageCtrl::class, 'empscheduler']);
    // leave
    Route::get('/pages/management/leavecreditallocations',[LeaveCreditAllocationController::class, 'index']);
    Route::post('/pages/leavecreditallocations/store',[LeaveCreditAllocationController::class, 'store'])->name('leavecreditallocation.store');
    Route::post('/pages/leavecreditallocations/update', [LeaveCreditAllocationController::class, 'update']);
    Route::delete('/pages/leavecreditallocations/delete/{leaveCreditAllocation}', [LeaveCreditAllocationController::class, 'destroy']);


    // employee role
    Route::post('/emprole/create_update',[EmployeeRoleController::class, 'create_update'])->name('employee.roles.assign');
    Route::delete('/users/{user}/roles/{role}', [EmployeeRoleController::class, 'removeRole'])->name('users.roles.remove');

    //MODULE
    Route::get('/pages/modules/obtTracker',[pageCtrl::class, 'obtTracker']);
    Route::get('/pages/modules/sendOBT',[pageCtrl::class, 'sendOBT']);
    Route::get('/pages/modules/overtime',[OvertimeController::class, 'index']);
    Route::get('/pages/modules/earlyout',[pageCtrl::class, 'earlyout']);
    Route::get('/pages/modules/debitAdvise',[pageCtrl::class, 'debitAdvise']);
    Route::get('/pages/modules/checkRegister',[pageCtrl::class, 'checkRegister']);

    // leave application
    Route::get('/pages/modules/leaveApplication',[pageCtrl::class, 'leaveApplication']);
    Route::get('/pages/modules/leave-check-credit',[LeaveController::class, 'checkLeaveCredit'])->name('leave.credit.check');
    Route::post('/pages/modules/leave',[LeaveController::class, 'store'])->name('leave.store');
    Route::get('/pages/modules/leave/getall',[LeaveController::class, 'getAllLeaves'])->name('leave.getall');
    Route::delete('/pages/modules/leave/delete/{leave}', [LeaveController::class, 'destroy'])->name('leave.delete');

    // leavel approval
    Route::get('/pages/modules/leaverequests', [LeaveRequestContoller::class, 'index'])->name('leave-requests.index')->middleware('can:pendingleaverequests');
    Route::get('/leaverequests/getAll', [LeaveRequestContoller::class, 'getAll'])->name('leave-requests.get')->middleware('can:pendingleaverequests');
    Route::post('/leaverequests/updateStatus', [LeaveRequestContoller::class, 'updateStatus'])->name('leave-requests.update');

    // overtime approval
    Route::get('/pages/modules/overtimerequests', [OvertimeRequestController::class, 'index'])->name('overtime-requests.index')->middleware('can:pendingovertimerequests');
    Route::get('/overtimerequests/getAll', [OvertimeRequestController::class, 'getAll'])->name('overtime-requests.get')->middleware('can:pendingovertimerequests');
    Route::post('/overtimerequests/updateStatus', [OvertimeRequestController::class, 'updateStatus'])->name('overtime-requests.update');

    //joblevel
    Route::post('/joblevel/create_update',[jobleveCtrl::class, 'create_update']);
    Route::get('/joblevel/get_joblevel',[jobleveCtrl::class, 'get_all']);
    Route::get('/joblevel/edit',[jobleveCtrl::class, 'edit']);

    //postion
    Route::post('/position/create_update',[positionCtrl::class, 'create_update']);
    Route::get('/position/get_position',[positionCtrl::class, 'get_all']);
    Route::get('/position/edit',[positionCtrl::class, 'edit']);

    //department
    Route::post('/department/create_update',[departmentCtrl::class, 'create_update']);
    Route::get('/department/getall',[departmentCtrl::class, 'getall']);
    Route::get('/department/edit',[departmentCtrl::class, 'edit']);
    Route::get('/department/documents',[departmentCtrl::class, 'documents']);
    Route::post('/department/document/upload',[departmentCtrl::class, 'uploadDocument']);
    Route::get('/department/document/download',[departmentCtrl::class, 'downloadDocument']);
    Route::get('/department/document/delete',[departmentCtrl::class, 'deleteDocument']);

    //relationship
    Route::post('/relationship/create_update',[relationshipCtrl::class, 'create_update']);
    Route::get('/relationship/getall',[relationshipCtrl::class, 'getall']);
    Route::get('/relationship/edit',[relationshipCtrl::class, 'edit']);

    //leavetype
    Route::post('/leavetype/create_update',[leavetypeCtrl::class, 'create_update']);
    Route::get('/leavetype/getall',[leavetypeCtrl::class, 'getall']);
    Route::get('/leavetype/edit',[leavetypeCtrl::class, 'edit']);

    //userrole
    Route::resource('/user-roles', RolesController::class);
    Route::post('/roles/{role}/permissions', [RolesController::class, 'addPermission']);
    Route::delete('/roles/{role}/permissions', [RolesController::class, 'removePermission']);
    Route::post('/roles/create_update',[roleCtrl::class, 'create_update']);
    Route::get('/roles/getall',[roleCtrl::class, 'getall']);
    Route::get('/roles/edit',[roleCtrl::class, 'edit']);
    Route::post('/roles/search',[roleCtrl::class, 'search']);

     //leave validation
     Route::post('/leaveval/create_update',[leavevalidationCtrl::class, 'create_update']);
     Route::get('/leaveval/getall',[leavevalidationCtrl::class, 'getall']);
     Route::get('/leaveval/edit',[leavevalidationCtrl::class, 'edit']);

    //ot filling
    Route::post('/otfilling/create_update',[otfillingCtrl::class, 'create_update']);
    Route::get('/otfilling/getall',[otfillingCtrl::class, 'getall']);
    Route::get('/otfilling/edit',[otfillingCtrl::class, 'edit']);


    //SHAI
    //Agencies Shai
    Route::post('/agency/create_update',[agenciesCtrl::class, 'create_update']);
    Route::get('/agency/getall',[agenciesCtrl::class, 'getall']);
    Route::get('/agency/edit',[agenciesCtrl::class, 'edit']);

    //Lilo Validation Shai
    Route::get('/pages/management/lilovalidations',[liloValidationsCtrl::class, 'index']);
    Route::post('/lilo/create_update',[liloValidationsCtrl::class, 'create_update']);
    Route::get('/lilo/getall',[liloValidationsCtrl::class, 'getall']);
    Route::get('/lilo/edit',[liloValidationsCtrl::class, 'edit']);

    //OB Validation Shai
    Route::post('/ob/create_update',[obValidationsCtrl::class, 'create_update']);
    Route::get('/ob/getall',[obValidationsCtrl::class, 'getall']);
    Route::get('/ob/edit',[obValidationsCtrl::class, 'edit']);

    // JMC 10/12/22
    Route::post('/classification/create_updateHMO',[hmoCtrl::class, 'create_update']);
    Route::get('/getHMO',[hmoCtrl::class, 'getHMO']);
    Route::get('/getData',[hmoCtrl::class, 'getData']);

    Route::post('/classification/create_updateEmpStat',[empStatCtrl::class, 'create_update']);
    Route::get('/getEmployeeStatus',[empStatCtrl::class, 'getEmployeeStatus']);
    Route::get('/getEmployeeStatusData',[empStatCtrl::class, 'getData']);

    Route::post('/classification/createHolidayLogger',[holidayLoggerCtrl::class, 'create_update']);
    Route::get('/getHL',[holidayLoggerCtrl::class, 'getall']);
    Route::get('/getHLData',[holidayLoggerCtrl::class, 'edit']);
    Route::delete('/deleteHL',[holidayLoggerCtrl::class, 'delete']);

    Route::post('/settings/eo_validation',[eovalidationCtrl::class, 'create_update']);
    Route::get('/getEOValidation',[eovalidationCtrl::class, 'getall']);
    Route::get('/updateEO',[eovalidationCtrl::class, 'edit']);

    Route::post('/settings/SSS',[sssCtrl::class, 'create_update'])->middleware('can:ssscontribution');
    Route::get('/getSSS',[sssCtrl::class, 'getall'])->middleware('can:ssscontribution');
    Route::get('/updateSSS',[sssCtrl::class, 'edit'])->middleware('can:ssscontribution');
    Route::post('/deleteSSS',[sssCtrl::class, 'delete'])->middleware('can:ssscontribution');

    Route::post('/settings/Pagibig',[pagibigCtrl::class, 'create_update'])->middleware('can:pagibigcontribution');
    Route::get('/getPagibig',[pagibigCtrl::class, 'getall'])->middleware('can:pagibigcontribution');
    Route::get('/updatePagibig',[pagibigCtrl::class, 'edit'])->middleware('can:pagibigcontribution');
    Route::post('/deletePagibig',[pagibigCtrl::class, 'delete'])->middleware('can:pagibigcontribution');

    Route::post('/settings/Philhealth',[philhealthCtrl::class, 'create_update'])->middleware('can:philhealth');
    Route::get('/getPhilhealth',[philhealthCtrl::class, 'getall'])->middleware('can:philhealth');
    Route::get('/updatePhilhealth',[philhealthCtrl::class, 'edit'])->middleware('can:philhealth');
    Route::post('/deletePhilhealth',[philhealthCtrl::class, 'delete'])->middleware('can:philhealth');

    //enrolment
    // Route::resource('enrolment', registerCtrl::class);

    Route::post('/sil/create_update',[silCtrl::class, 'create_update']);
    Route::get('/sil/getusers', [silCtrl::class, 'getusers']);
    Route::get('/sil/getall',[silCtrl::class, 'getall']);
    Route::get('/sil/edit',[silCtrl::class, 'edit']);

    //SHAI
    //PARENTAL SETTINGS
    Route::post('/parental/create_update',[parentalSettingsCtrl::class, 'create_update']);
    Route::get('/parental/getall',[parentalSettingsCtrl::class, 'getall']);
    Route::get('/parental/edit',[parentalSettingsCtrl::class, 'edit']);
    Route::get('/parental/delete_record',[parentalSettingsCtrl::class, 'delete_record']);

    //initialize address
    Route::post('/get_province',[registerCtrl::class, 'get_province']);
    Route::get('/get_city',[registerCtrl::class, 'get_city']);
    Route::get('/get_brgy',[registerCtrl::class, 'get_brgy']);

    //EMPLOYEE SCHEDULER SETTINGS
    Route::post('/scheduler/create_update',[empSchedulerCtrl::class, 'create_update']);
    Route::get('/scheduler/getall',[empSchedulerCtrl::class, 'getall']);
    //UPDATE TIME
    Route::get('/scheduler/getall_time',[empSchedulerCtrl::class, 'getall_time']);
    Route::post('/scheduler/update_time',[empSchedulerCtrl::class, 'update_time']);
    Route::get('/scheduler/edit_time',[empSchedulerCtrl::class, 'edit_time']);
    Route::get('/scheduler/getall_time',[empSchedulerCtrl::class, 'getall_time']);
    //UPDATE DATE
    Route::get('/scheduler/edit_date',[empSchedulerCtrl::class, 'edit_date']);
    Route::post('/scheduler/update_date',[empSchedulerCtrl::class, 'update_date']);

    Route::post('/scheduler/search',[empSchedulerCtrl::class, 'search']);

    //HOME DAR
    Route::post('/home/create_dar',[homeDarCtrl::class, 'create_dar']);
    Route::get('/home/getall_dar',[homeDarCtrl::class, 'getall_dar']);
    Route::get('/home/filter_dar',[homeDarCtrl::class, 'filter_dar']);
    Route::get('/home/logs_dar',[homeDarCtrl::class, 'logs_dar']);

    // HOME ATTENDANCE LOG — legacy CENAR punch retired (wrote non-fillable columns and
    // corrupted home_attendance). The live punch is AttendanceController@timeIn/timeOut
    // (routes 'attendance.timein' / 'attendance.timeout') backed by homeAttendance::logTimeIn/logTimeOut.

    //ARCHIVE MANAGEMENT
    Route::post('/archive/create_update',[archiveCtrl::class, 'create_update']);
    Route::get('/archive/getall',[archiveCtrl::class, 'getall']);
    Route::get('/archive/edit',[archiveCtrl::class, 'edit']);
    Route::post('/archive/search',[archiveCtrl::class, 'search']);

    ///REPORTS
    // Route::post('/ViewReportAttend',[reportAttendanceCtrl::class, 'viewreportattend']);
    Route::get('/task/search',[reportAttendanceCtrl::class,'searchTask']);
    Route::get('/reports/employee-information',[EmployeeInformationReportController::class, 'index'])->name('employee.report.index');
    Route::get('/reports/employee-information/export',[EmployeeInformationReportController::class, 'export'])->name('employee.report.export');
    Route::get('/reports/employee-information/print',[EmployeeInformationReportController::class, 'print'])->name('employee.report.print');
    Route::get('/reports/overtime', [OvertimeReportController::class, 'index'])->name('reports.overtime.index')->middleware('can:overtimereport');
    Route::get('/reports/overtime/fetch', [OvertimeReportController::class, 'fetch'])->name('reports.overtime.fetch')->middleware('can:overtimereport');
    Route::get('/reports/overtime/print', [OvertimeReportController::class, 'print'])->name('reports.overtime.print')->middleware('can:overtimereport');
    Route::get('/reports/leave', [LeaveReportController::class, 'index'])->name('reports.leave.index')->middleware('can:leavereport');
    Route::get('/reports/leave/fetch', [LeaveReportController::class, 'fetch'])->name('reports.leave.fetch')->middleware('can:leavereport');
    Route::get('/reports/leave/print', [LeaveReportController::class, 'print'])->name('reports.leave.print')->middleware('can:leavereport');
    Route::get('/reports/thirteenth-month', [ThirteenthMonthController::class, 'index'])->name('reports.thirteenth.index')->middleware('can:thirteenthmonth');
    Route::get('/reports/thirteenth-month/fetch', [ThirteenthMonthController::class, 'fetch'])->name('reports.thirteenth.fetch')->middleware('can:thirteenthmonth');
    Route::get('/reports/thirteenth-month/export', [ThirteenthMonthController::class, 'export'])->name('reports.thirteenth.export')->middleware('can:thirteenthmonth');
    Route::get('/reports/thirteenth-month/print', [ThirteenthMonthController::class, 'print'])->name('reports.thirteenth.print')->middleware('can:thirteenthmonth');

    //v2 scheduler
    Route::prefix('employee-schedules')->group(function() {
        Route::get('/', [EmployeeScheduleController::class, 'index'])->name('employee-schedules.index');
        Route::get('/all', [EmployeeScheduleController::class, 'getSchedules'])->name('employee-schedules.get');
        Route::post('/store', [EmployeeScheduleController::class, 'store'])->name('employee-schedules.store');
        Route::get('/edit/{id}', [EmployeeScheduleController::class, 'edit'])->name('employee-schedules.edit');
        Route::put('/update/{id}', [EmployeeScheduleController::class, 'update'])->name('employee-schedules.update');
        Route::delete('/delete/{id}', [EmployeeScheduleController::class, 'destroy'])->name('employee-schedules.destroy');
    });

    Route::prefix('attendance')->group(function () {
        Route::post('/login', [AttendanceController::class, 'timeIn'])->name('attendance.timein');
        Route::post('/logout', [AttendanceController::class, 'timeOut'])->name('attendance.timeout');
        Route::get('/list', [AttendanceController::class, 'getAttendanceList'])->name('attendance.list');
    });

    Route::get('/attendance/viewer', [reportAttendanceCtrl::class, 'index'])->name('attendance.viewer');
    Route::post('/attendance/fetch', [reportAttendanceCtrl::class, 'fetchAttendance'])->name('attendance.fetch');
    Route::get('/payroll/compute', [PayrollController::class, 'computePayroll']);

    // Attendance import (schedule + home attendance + summaries)
    Route::get('/attendance-import', [AttendanceImportController::class, 'index'])->name('attendance-import.index')->middleware('can:attendanceimport');
    Route::get('/attendance-import/template', [AttendanceImportController::class, 'template'])->name('attendance-import.template')->middleware('can:attendanceimport');
    Route::post('/attendance-import/upload', [AttendanceImportController::class, 'import'])->name('attendance-import.upload')->middleware('can:attendanceimport');
    // Import history — pull up a past import and roll it back as a unit, then re-upload corrected
    Route::get('/attendance-import/history', [ImportHistoryController::class, 'index'])->defaults('module', 'attendance')->name('attendance-import.history')->middleware('can:attendanceimport');
    Route::get('/attendance-import/history/{id}', [ImportHistoryController::class, 'show'])->defaults('module', 'attendance')->name('attendance-import.history.show')->middleware('can:attendanceimport');
    Route::delete('/attendance-import/history/{id}', [ImportHistoryController::class, 'destroy'])->defaults('module', 'attendance')->name('attendance-import.history.destroy')->middleware('can:attendanceimport');
    Route::put('/attendance-import/history/row/{id}', [ImportHistoryController::class, 'updateRow'])->name('attendance-import.history.row.update')->middleware('can:attendanceimport');

    // Overtime import
    Route::get('/overtime-import', [OvertimeImportController::class, 'index'])->name('overtime-import.index')->middleware('can:overtimeimport');
    Route::get('/overtime-import/template', [OvertimeImportController::class, 'template'])->name('overtime-import.template')->middleware('can:overtimeimport');
    Route::post('/overtime-import/upload', [OvertimeImportController::class, 'import'])->name('overtime-import.upload')->middleware('can:overtimeimport');
    Route::get('/overtime-import/history', [ImportHistoryController::class, 'index'])->defaults('module', 'overtime')->name('overtime-import.history')->middleware('can:overtimeimport');
    Route::get('/overtime-import/history/{id}', [ImportHistoryController::class, 'show'])->defaults('module', 'overtime')->name('overtime-import.history.show')->middleware('can:overtimeimport');
    Route::delete('/overtime-import/history/{id}', [ImportHistoryController::class, 'destroy'])->defaults('module', 'overtime')->name('overtime-import.history.destroy')->middleware('can:overtimeimport');

    // Leave import
    Route::get('/leave-import', [LeaveImportController::class, 'index'])->name('leave-import.index')->middleware('can:leaveimport');
    Route::get('/leave-import/template', [LeaveImportController::class, 'template'])->name('leave-import.template')->middleware('can:leaveimport');
    Route::post('/leave-import/upload', [LeaveImportController::class, 'import'])->name('leave-import.upload')->middleware('can:leaveimport');
    Route::get('/leave-import/history', [ImportHistoryController::class, 'index'])->defaults('module', 'leave')->name('leave-import.history')->middleware('can:leaveimport');
    Route::get('/leave-import/history/{id}', [ImportHistoryController::class, 'show'])->defaults('module', 'leave')->name('leave-import.history.show')->middleware('can:leaveimport');
    Route::delete('/leave-import/history/{id}', [ImportHistoryController::class, 'destroy'])->defaults('module', 'leave')->name('leave-import.history.destroy')->middleware('can:leaveimport');

    // Schedule import
    Route::get('/schedule-import', [ScheduleImportController::class, 'index'])->name('schedule-import.index')->middleware('can:scheduleimport');
    Route::get('/schedule-import/template', [ScheduleImportController::class, 'template'])->name('schedule-import.template')->middleware('can:scheduleimport');
    Route::post('/schedule-import/upload', [ScheduleImportController::class, 'import'])->name('schedule-import.upload')->middleware('can:scheduleimport');
    Route::get('/schedule-import/history', [ImportHistoryController::class, 'index'])->defaults('module', 'schedule')->name('schedule-import.history')->middleware('can:scheduleimport');
    Route::get('/schedule-import/history/{id}', [ImportHistoryController::class, 'show'])->defaults('module', 'schedule')->name('schedule-import.history.show')->middleware('can:scheduleimport');
    Route::delete('/schedule-import/history/{id}', [ImportHistoryController::class, 'destroy'])->defaults('module', 'schedule')->name('schedule-import.history.destroy')->middleware('can:scheduleimport');

    // Schedule change requests
    Route::get('/schedulerequest/mine', [ScheduleRequestController::class, 'mine'])->name('schedule-request.mine')->middleware('can:createschedulechange');
    Route::get('/schedulerequest/current-schedule', [ScheduleRequestController::class, 'currentSchedule'])->name('schedule-request.current')->middleware('can:createschedulechange');
    Route::post('/schedulerequest/store', [ScheduleRequestController::class, 'store'])->name('schedule-request.store')->middleware('can:createschedulechange');
    Route::get('/pages/modules/schedulerequests', [ScheduleRequestController::class, 'pending'])->name('schedule-request.pending')->middleware('can:approveschedulechange');
    Route::post('/schedulerequest/updateStatus', [ScheduleRequestController::class, 'updateStatus'])->name('schedule-request.update')->middleware('can:approveschedulechange');

    // HR Control Center
    Route::get('/pages/management/hr-dashboard', [HrDashboardController::class, 'index'])->name('hr-dashboard.index')->middleware('can:hrdashboard');
    Route::get('/pages/management/audit-trail', [AuditController::class, 'index'])->name('audit-trail.index')->middleware('can:auditlog');
    Route::get('/pages/management/hr-dashboard/live', [HrDashboardController::class, 'live'])->name('hr-dashboard.live')->middleware('can:hrdashboard');
    Route::get('/pages/management/hr-dashboard/whoin', [HrDashboardController::class, 'whoIn'])->name('hr-dashboard.whoin')->middleware('can:hrdashboard');
    Route::get('/pages/management/hr-dashboard/dept', [HrDashboardController::class, 'deptEmployees'])->name('hr-dashboard.dept')->middleware('can:hrdashboard');
    Route::get('/payroll/approval/status', [PayrollApprovalController::class, 'status'])->name('payroll.approval.status');
    Route::post('/payroll/approve', [PayrollApprovalController::class, 'approve'])->name('payroll.approve')->middleware('can:approvepayroll');
    Route::post('/payroll/reopen', [PayrollApprovalController::class, 'reopen'])->name('payroll.reopen')->middleware('can:regeneratepayroll');
    Route::post('/payroll/delete', [PayrollController::class, 'destroyByPayDate'])->name('payroll.delete')->middleware('can:regeneratepayroll');
    Route::get('/payroll/fetch', [PayrollController::class, 'fetchPayroll']);
    Route::get('/payroll/details/by-payroll', [PayrollController::class, 'getDetailsByPayroll'])
    ->name('payroll.details.by-payroll');
    Route::get('/payroll/payslip', [PayrollController::class, 'payslip'])->name('payroll.payslip');

    // Payslip email automation
    Route::post('/payroll/payslip/send', [PayslipEmailController::class, 'sendBatch'])->name('payslip-email.send')->middleware('can:payslipemail');
    Route::get('/payroll/payslip/status', [PayslipEmailController::class, 'status'])->name('payslip-email.status')->middleware('can:payslipemail');
    Route::post('/payroll/payslip/{payroll}/resend', [PayslipEmailController::class, 'resend'])->name('payslip-email.resend')->middleware('can:payslipemail');
    Route::get('/payroll/payslip-email/settings', [PayslipEmailController::class, 'getSettings'])->name('payslip-email.settings.get')->middleware('can:payslipemail');
    Route::post('/payroll/payslip-email/settings', [PayslipEmailController::class, 'updateSettings'])->name('payslip-email.settings.update')->middleware('can:payslipemail');
    Route::get('/payroll/export/cash', [PayrollExportController::class, 'exportCash'])->name('payroll.export.cash');
    Route::get('/payroll/export/card', [PayrollExportController::class, 'exportCard'])->name('payroll.export.card');
    Route::get('/payroll/export/gov-dues', [PayrollExportController::class, 'exportGovDues'])->name('payroll.export.govdues');
    Route::get('/payroll-logs', [PayrollLogController::class, 'index'])->name('payroll-logs.index')->middleware('can:payrolllogs');
    Route::get('/payroll-logs/fetch', [PayrollLogController::class, 'fetch'])->name('payroll-logs.fetch')->middleware('can:payrolllogs');
    Route::get('/payroll-logs/print', [PayrollLogController::class, 'print'])->name('payroll-logs.print')->middleware('can:payrolllogs');
    


    // Notices / Memo — HR admin side (gated) + employee "My Notices" (auth-only)
    Route::get('pages/modules/notices', [NoticeController::class, 'index'])->name('notices.index')->middleware('can:noticemanagement');
    Route::get('/notices/list', [NoticeController::class, 'list'])->middleware('can:noticemanagement');
    Route::get('/notices/employees', [NoticeController::class, 'employees'])->middleware('can:noticemanagement');
    Route::post('/notices/save', [NoticeController::class, 'save'])->middleware('can:noticemanagement');
    Route::post('/notices/delete', [NoticeController::class, 'delete'])->middleware('can:noticemanagement');
    Route::get('/notices/recommendations', [NoticeController::class, 'recommendations'])->middleware('can:noticemanagement');
    Route::post('/notices/recommendation/resolve', [NoticeController::class, 'resolveRecommendation'])->middleware('can:noticemanagement');
    // Employee-facing: every authenticated employee can see their own notices.
    Route::get('pages/modules/mynotices', [NoticeController::class, 'mine'])->name('notices.mine');
    Route::get('/mynotices/list', [NoticeController::class, 'myList'])->name('notices.mine.list');

    // Certificate of Employment — HR admin side (gated) + employee "My COE" (auth-only)
    Route::get('pages/modules/coe', [CoeController::class, 'index'])->name('coe.index')->middleware('can:coemanagement');
    Route::get('/coe/list', [CoeController::class, 'list'])->middleware('can:coemanagement');
    Route::post('/coe/approve', [CoeController::class, 'approve'])->middleware('can:coemanagement');
    Route::post('/coe/reject', [CoeController::class, 'reject'])->middleware('can:coemanagement');
    // HR issues a COE for a separated employee (gated on offboarding clearance).
    Route::get('/coe/separated-employees', [CoeController::class, 'separatedEmployees'])->middleware('can:coemanagement');
    Route::post('/coe/issue', [CoeController::class, 'issue'])->middleware('can:coemanagement');
    // COE signatories (Settings) + the active list that feeds the approve/issue pickers.
    Route::get('pages/management/coe-signatories', [CoeSignatoryController::class, 'index'])->name('coe.signatories')->middleware('can:coemanagement');
    Route::get('/coe/signatories/list', [CoeSignatoryController::class, 'list'])->middleware('can:coemanagement');
    Route::get('/coe/signatories/active', [CoeSignatoryController::class, 'activeList'])->middleware('can:coemanagement');
    Route::post('/coe/signatories/save', [CoeSignatoryController::class, 'save'])->middleware('can:coemanagement');
    Route::post('/coe/signatories/delete', [CoeSignatoryController::class, 'delete'])->middleware('can:coemanagement');
    // Employee-facing: every authenticated employee can manage their own COE requests.
    Route::get('pages/modules/mycoe', [CoeController::class, 'mine'])->name('coe.mine');
    Route::get('/mycoe/list', [CoeController::class, 'myList'])->name('coe.mine.list');
    Route::get('/mycoe/requirements', [CoeController::class, 'requirements'])->name('coe.requirements');
    Route::post('/mycoe/store', [CoeController::class, 'store'])->name('coe.store');
    // PDF download — owner employee OR a COE manager (authorized inside the controller).
    Route::get('/coe/{coe}/pdf', [CoeController::class, 'pdf'])->name('coe.pdf');

    // Programs Management — tenure-milestone benefits (Workforce)
    Route::get('pages/modules/programs', [ProgramController::class, 'index'])->name('programs.index')->middleware('can:programs');
    Route::get('/programs/list', [ProgramController::class, 'list'])->middleware('can:programs');
    Route::post('/programs/save', [ProgramController::class, 'save'])->middleware('can:programs');
    Route::post('/programs/delete', [ProgramController::class, 'delete'])->middleware('can:programs');
    Route::get('/programs/eligibility', [ProgramController::class, 'eligibility'])->middleware('can:programs');
    Route::post('/programs/grant', [ProgramController::class, 'grant'])->middleware('can:programs');
    Route::post('/programs/revoke', [ProgramController::class, 'revoke'])->middleware('can:programs');

    Route::get('pages/modules/loanManagement', [LoanController::class, 'index'])->name('loans.index');
    Route::post('/loans/store', [LoanController::class, 'store'])->name('loans.store');
    Route::post('/loans/update', [LoanController::class, 'update'])->name('loans.update');
    Route::post('/loans/{id}/toggle', [LoanController::class, 'toggleStatus'])->name('loans.toggle');
    Route::delete('/loans/delete/{id}', [LoanController::class, 'destroy'])->name('loans.delete');

    // Pay Adjustments (HR additions/deductions per pay date)
    Route::get('pages/modules/payadjustments', [PayAdjustmentController::class, 'index'])->name('payadjustments.index')->middleware('can:payadjustments');
    Route::post('/payadjustments/store', [PayAdjustmentController::class, 'store'])->name('payadjustments.store')->middleware('can:payadjustments');
    Route::post('/payadjustments/update', [PayAdjustmentController::class, 'update'])->name('payadjustments.update')->middleware('can:payadjustments');
    Route::delete('/payadjustments/delete/{id}', [PayAdjustmentController::class, 'destroy'])->name('payadjustments.delete')->middleware('can:payadjustments');
    
    Route::resource('/overtime', OvertimeController::class);
    Route::put('/overtime/{overtime}/updatestatus', [OvertimeController::class, 'updateStatus'])->name('overtime.status.update');

    Route::get('/department/delete', [departmentCtrl::class, 'delete']); // Add this line
    Route::get('/position/delete', [positionCtrl::class, 'delete']);

    Route::group(['prefix' => 'pages/management/e201', 'middleware' => ['auth']], function () {
        
        // 1. The Main View (Loads the search page)
        Route::get('/', [EmployeeRecordController::class, 'index'])->name('e201.index');

        // 2. The Search/Get Function (The AJAX "Messenger")
        // This is what retrieves the full bio-data without refreshing
        Route::get('/details/{empID}', [EmployeeRecordController::class, 'getEmployeeDetails'])
            ->name('e201.details');

        // 3. Optional: Export to PDF
        Route::get('/print/{empID}', [EmployeeRecordController::class, 'printProfile'])
            ->name('e201.print');


    });
    
    // The {id} here corresponds to the empID passed from the frontend
    // Route::get('/admin/e201/fetch/{id}', [EmployeeRecordController::class, 'getE201Data'])->name('e201.fetch');
    

// Ensure this is OUTSIDE any other conflicting groups
    // Ensure this is OUTSIDE any other conflicting groups
    Route::get('admin/e201/fetch/{empID}', [EmployeeRecordController::class, 'getEmployeeDetails']);
    Route::get('admin/e201/edit/{user}', [EmployeeRecordController::class, 'editEmployee']);
    // Route to view the table
    Route::get('/pages/modules/adjustmentTime', [AttendanceController::class, 'index'])->name('attendance.index');
    // Route to handle the AJAX deduction save
    Route::post('/attendance/deductions', [AttendanceController::class, 'storeDeduction'])->name('attendance.deductions.store');
    // Route to delete a specific deduction
    Route::delete('/attendance/deductions/{id}', [AttendanceController::class, 'destroyDeduction'])->name('attendance.deductions.destroy');

    // validate email availability Feb 18 2026
    Route::post('/registerCtrl/checkEmailAvailability', [registerCtrl::class, 'checkEmailAvailability']);
    Route::get('/check-fullname', [registerCtrl::class, 'checkFullName']);
    Route::post('/update-password', [ProfileController::class, 'updatePassword'])->name('password.update');

    // Forced password change (one-time security stretch). Exempt from the
    // 'force.password' middleware so flagged users can actually reach it.
    Route::get('/force-password-change', [ProfileController::class, 'forceChangeForm'])->name('password.force');
    Route::post('/force-password-change/update', [ProfileController::class, 'forceUpdatePassword'])->name('password.force.update');

    // ============================================
    // KwHub - Community Platform Routes
    // ============================================

    // Page Views
    Route::get('/kubo', [KuBoController::class, 'feed'])->name('kubo.feed');
    Route::get('/kubo/explore', [KuBoController::class, 'explore'])->name('kubo.explore');
    Route::get('/kubo/notifications', [KuBoController::class, 'notifications'])->name('kubo.notifications');
    Route::get('/kubo/profile', [KuBoController::class, 'profile'])->name('kubo.profile');
    Route::get('/kubo/profile/{empID}', [KuBoController::class, 'profile'])->name('kubo.profile.user');
    Route::get('/kubo/hashtag/{tag}', [KuBoController::class, 'feed'])->name('kubo.hashtag');

    // AJAX API Endpoints
    Route::prefix('api/kubo')->group(function () {
        // Rate-limited KuBo API routes
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('/posts', [PostController::class, 'store'])->name('api.kubo.posts.store');
            Route::put('/posts/{post}', [PostController::class, 'update'])->name('api.kubo.posts.update');
            Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('api.kubo.posts.destroy');
            Route::post('/posts/{post}/pin', [PostController::class, 'pin'])->name('api.kubo.posts.pin');
            Route::post('/posts/{post}/react', [ReactionController::class, 'toggle'])->name('api.kubo.react');
            Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->name('api.kubo.comments.index');
            Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('api.kubo.comments.store');
            Route::put('/comments/{comment}', [CommentController::class, 'update'])->name('api.kubo.comments.update');
            Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('api.kubo.comments.destroy');
            Route::post('/comments/{comment}/reply', [CommentController::class, 'reply'])->name('api.kubo.comments.reply');
            Route::post('/posts/{post}/repost', [RepostController::class, 'store'])->name('api.kubo.repost');
            Route::post('/notifications/read', [NotificationController::class, 'markRead'])->name('api.kubo.notifications.read');
            Route::post('/upload/image', [ImageUploadController::class, 'store'])->name('api.kubo.upload.image');
            Route::post('/upload/images', [ImageUploadController::class, 'storeMultiple'])->name('api.kubo.upload.images');
        });
        Route::get('/feed', [FeedController::class, 'load'])->name('api.kubo.feed');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('api.kubo.notifications');
        Route::get('/notifications/count', [NotificationController::class, 'unreadCount'])->name('api.kubo.notifications.count');
        Route::get('/explore/trending', [ExploreController::class, 'trending'])->name('api.kubo.explore.trending');
        Route::get('/explore/popular', [ExploreController::class, 'popular'])->name('api.kubo.explore.popular');
        Route::get('/explore/photos', [ExploreController::class, 'photos'])->name('api.kubo.explore.photos');
        Route::get('/profile/posts', [KuBoProfileController::class, 'ownPosts'])->name('api.kubo.profile.own.posts');
        Route::get('/profile/photos', [KuBoProfileController::class, 'ownPhotos'])->name('api.kubo.profile.own.photos');
        Route::get('/profile/reposts', [KuBoProfileController::class, 'ownReposts'])->name('api.kubo.profile.own.reposts');
        Route::get('/profile/stats', [KuBoProfileController::class, 'ownStats'])->name('api.kubo.profile.own.stats');
        Route::get('/profile/{user}/posts', [KuBoProfileController::class, 'posts'])->name('api.kubo.profile.posts');
        Route::get('/profile/{user}/photos', [KuBoProfileController::class, 'photos'])->name('api.kubo.profile.photos');
        Route::get('/profile/{user}/reposts', [KuBoProfileController::class, 'reposts'])->name('api.kubo.profile.reposts');
        Route::get('/profile/{user}/stats', [KuBoProfileController::class, 'stats'])->name('api.kubo.profile.stats');
        Route::get('/hashtags/trending', [HashtagController::class, 'trending'])->name('api.kubo.hashtags.trending');
        Route::get('/hashtags/suggest', [HashtagController::class, 'suggest'])->name('api.kubo.hashtags.suggest');
        Route::post('/presence/ping', [PresenceController::class, 'ping'])->name('api.kubo.presence.ping');
        Route::get('/presence/online', [PresenceController::class, 'online'])->name('api.kubo.presence.online');
        Route::get('/conversations', [ChatController::class, 'conversations'])->name('api.kubo.conversations');
        Route::get('/messages/{empID}', [ChatController::class, 'messages'])->name('api.kubo.messages');
        Route::post('/messages/{empID}', [ChatController::class, 'send'])->name('api.kubo.messages.send');
    });

    // ─── Allowed IP Management ────────────────────────────────────────────────
    // Specific named routes MUST come before the resource so Laravel doesn't
    // swallow e.g. GET .../allowed-ips/dashboard as the resource's show() route.
    Route::prefix('pages/management/allowed-ips')->name('allowed-ips.')->group(function () {
        // Dashboard & stats
        Route::get('dashboard',       [AllowedIpController::class, 'dashboard'])->name('dashboard');
        Route::get('stats',           [AllowedIpController::class, 'stats'])->name('stats');

        // IP Access Logs
        Route::get('logs',            [AllowedIpController::class, 'logs'])->name('logs');
        Route::get('logs/export',     [AllowedIpController::class, 'exportLogs'])->name('logs.export');

        // Bulk actions
        Route::post('bulk/enable',    [AllowedIpController::class, 'bulkEnable'])->name('bulk.enable');
        Route::post('bulk/disable',   [AllowedIpController::class, 'bulkDisable'])->name('bulk.disable');
        Route::post('bulk/delete',    [AllowedIpController::class, 'bulkDelete'])->name('bulk.delete');

        // CSV Import
        Route::post('import',         [AllowedIpController::class, 'import'])->name('import');
        Route::get('import/template', [AllowedIpController::class, 'importTemplate'])->name('import.template');

        // Excel Export (IPs)
        Route::get('export',          [AllowedIpController::class, 'exportIps'])->name('export');

        // Toggle — after statics, before resource catch-all
        Route::put('{allowed_ip}/toggle', [AllowedIpController::class, 'toggle'])->name('toggle');
    });

    // Resource routes last — {allowed_ip} wildcard won't shadow the named routes above
    Route::resource('pages/management/allowed-ips', AllowedIpController::class)
        ->names('allowed-ips')
        ->parameters(['allowed-ips' => 'allowed_ip'])
        ->except(['show']);

});

