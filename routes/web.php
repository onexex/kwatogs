<?php


use App\Models\User;
use App\Http\Controllers\hmoCtrl;
use App\Http\Controllers\silCtrl;
use App\Http\Controllers\sssCtrl;
use App\Http\Controllers\pageCtrl;
use App\Http\Controllers\roleCtrl;
use App\Http\Controllers\loginCtrl;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\archiveCtrl;
use App\Http\Controllers\companyCtrl;
use App\Http\Controllers\empStatCtrl;
use App\Http\Controllers\homeDarCtrl;
use App\Http\Controllers\jobleveCtrl;
use App\Http\Controllers\pagibigCtrl;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\agenciesCtrl;
use App\Http\Controllers\earlyoutCtrl;
use App\Http\Controllers\positionCtrl;
use App\Http\Controllers\registerCtrl;
use App\Http\Controllers\workTimeCtrl;
use App\Http\Controllers\leavetypeCtrl;
use App\Http\Controllers\otfillingCtrl;
use App\Http\Controllers\departmentCtrl;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\philhealthCtrl;
use App\Http\Controllers\empSchedulerCtrl;
use App\Http\Controllers\eovalidationCtrl;
use App\Http\Controllers\relationshipCtrl;
use App\Http\Controllers\holidayLoggerCtrl;
use App\Http\Controllers\obValidationsCtrl;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\classiticationCtrl;
use App\Http\Controllers\homeAttendanceCtrl;

 
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\leavevalidationCtrl;
use App\Http\Controllers\liloValidationsCtrl;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\parentalSettingsCtrl;
use App\Http\Controllers\reportAttendanceCtrl;
use App\Http\Controllers\Roles\RolesController;
use App\Http\Controllers\EmployeeRecordController;
use App\Http\Controllers\EmployeeScheduleController;
use App\Http\Controllers\LeaveCreditAllocationController;
use App\Http\Controllers\Roles\EmployeeRoleController;

Route::get('/r', function () {
    $updated = User::where('id', 1)->update([
        'password' => Hash::make('1') // change as needed
    ]);

    return $updated 
        ? "Password for user ID 1 has been reset successfully."
        : "User not found.";
});

Route::get('/auth/login', function () {return view('login.login');});
Route::post('/loginSystem',[loginCtrl::class, 'loginSystem']);
Route::get('/logoutSystem',[loginCtrl::class, 'logoutSystem']);
// Route::get('/', function () {return view('home');});


Route::group(['middleware'=>['AuthCheck']], function(){

    ##

    Route::post('/earlyout/submit',[earlyoutCtrl::class, 'submit']);
    ##

    Route::get('/login/testmoto',[pageCtrl::class, 'test']);


    Route::get('/', function () {return view('home');});
    Route::get('/users/manage',[pageCtrl::class, 'indexUsers']);
    Route::get('/pages/test',[pageCtrl::class, 'alas']);
    //pages
    Route::get('/pages/management/time',[pageCtrl::class, 'officetime']);
    Route::get('/pages/management/companies',[pageCtrl::class, 'companies']);
    Route::get('/pages/management/classification',[pageCtrl::class, 'classification']);
    Route::get('/pages/management/e201',[pageCtrl::class, 'e201']);
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

    //classification
    Route::post('/classification/create_update',[classiticationCtrl::class, 'create_update']);
    Route::get('/classification/get_all',[classiticationCtrl::class, 'get_all']);
    Route::get('/classification/delete',[classiticationCtrl::class, 'delete']);
    Route::get('/classification/edit',[classiticationCtrl::class, 'edit']);

    //payroll
    Route::get('/pages/modules/payroll',[pageCtrl::class, 'payroll']);

    //functions
    Route::post('/function/generateEmpid',[registerCtrl::class, 'generateEmpID']);
    Route::post('/enroll/save',[registerCtrl::class, 'create']);
    Route::post('/employee/update',[registerCtrl::class, 'update']);


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
    Route::get('/pages/management/philhealth',[pageCtrl::class, 'philhealth']);
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
    Route::get('/pages/management/accessrights',[EmployeeRoleController::class, 'index']);
    Route::get('/pages/management/departments',[pageCtrl::class, 'departments']);
    Route::get('/pages/management/relationship',[pageCtrl::class, 'relationship']);
    Route::get('/pages/management/leavevalidations',[pageCtrl::class, 'leavevalidations']);
    Route::get('/pages/management/holidaylogger',[pageCtrl::class, 'holidaylogger']);
    Route::get('/pages/management/obvalidations',[pageCtrl::class, 'obvalidations']);
    Route::get('/pages/management/ssscontribution',[pageCtrl::class, 'ssscontribution']);
    Route::get('/pages/management/pagibigcontribution',[pageCtrl::class, 'pagibigcontribution']);
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
    Route::get('/pages/modules/leaveApplication',[pageCtrl::class, 'leaveApplication']);
    Route::get('/pages/modules/debitAdvise',[pageCtrl::class, 'debitAdvise']);
    Route::get('/pages/modules/checkRegister',[pageCtrl::class, 'checkRegister']);

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

    Route::post('/settings/eo_validation',[eovalidationCtrl::class, 'create_update']);
    Route::get('/getEOValidation',[eovalidationCtrl::class, 'getall']);
    Route::get('/updateEO',[eovalidationCtrl::class, 'edit']);

    Route::post('/settings/SSS',[sssCtrl::class, 'create_update']);
    Route::get('/getSSS',[sssCtrl::class, 'getall']);
    Route::get('/updateSSS',[sssCtrl::class, 'edit']);

    Route::post('/settings/Pagibig',[pagibigCtrl::class, 'create_update']);
    Route::get('/getPagibig',[pagibigCtrl::class, 'getall']);
    Route::get('/updatePagibig',[pagibigCtrl::class, 'edit']);

    Route::post('/settings/Philhealth',[philhealthCtrl::class, 'create_update']);
    Route::get('/getPhilhealth',[philhealthCtrl::class, 'getall']);
    Route::get('/updatePhilhealth',[philhealthCtrl::class, 'edit']);

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

    //HOME ATTENDANCE LOG
    Route::post('/home/create_attendance',[homeAttendanceCtrl::class, 'create_attendance']);
    Route::get('/home/mdl_attendance',[homeAttendanceCtrl::class, 'log_attendance']);
    Route::get('/home/getall_attendance',[homeAttendanceCtrl::class, 'getall_attendance']);
    Route::get('/home/filter_attendance',[homeAttendanceCtrl::class, 'filter_attendance']);

    //ARCHIVE MANAGEMENT
    Route::post('/archive/create_update',[archiveCtrl::class, 'create_update']);
    Route::get('/archive/getall',[archiveCtrl::class, 'getall']);
    Route::get('/archive/edit',[archiveCtrl::class, 'edit']);
    Route::post('/archive/search',[archiveCtrl::class, 'search']);

    ///REPORTS
    // Route::post('/ViewReportAttend',[reportAttendanceCtrl::class, 'viewreportattend']);
    Route::get('/task/search',[reportAttendanceCtrl::class,'searchTask']);


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
    Route::get('/payroll/fetch', [PayrollController::class, 'fetchPayroll']);


    Route::get('pages/modules/loanManagement', [LoanController::class, 'index'])->name('loans.index');
    Route::post('/loans/store', [LoanController::class, 'store'])->name('loans.store');
    Route::post('/loans/update', [LoanController::class, 'update'])->name('loans.update');
    Route::delete('/loans/delete/{id}', [LoanController::class, 'destroy'])->name('loans.delete');
    
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

});

