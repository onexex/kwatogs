<?php 

namespace App\Enums\Permissions;

use App\Traits\EnumToArray;

enum PagePermissionsEnum: string
{
    use EnumToArray;

    case home = 'Home';
    case registration = 'Registration';
    // case checkregister = 'Check Register';
    case e201 = 'E-201';
    // case earlyout = 'Earlyout';
    case enrollemployee = 'Enroll Employee';
    case leaveapplication = 'Leave Application';
    case pendingleaverequests = 'Pending Leave Requests';
    // case memorandum = 'Memo Generator';
    case obttracker = 'Official Business Trip';
    case overtime = 'Overtime';
    case pendingovertimerequests = 'Pending Overtime Requests';
    case payroll = 'Payroll System';
    case hrdashboard = 'HR Dashboard';
    case auditlog = 'Audit Trail';
    case payrolllogs = 'Payroll Logs';
    case payadjustments = 'Pay Adjustments';
    case approvepayroll = 'Approve Payroll';
    case regeneratepayroll = 'Regenerate / Override Payroll';
    case attendanceimport = 'Attendance Import';
    case scheduleimport = 'Schedule Import';
    case createschedulechange = 'Schedule Change Request';
    case approveschedulechange = 'Pending Schedule Requests';
    // case debitadvise = 'Debit Advise';
    // case sendobt = 'Send to OBT';
    case accessrights = 'Access Rights';
    // case agencies = 'Agencies';
    // case archive = 'Archive Management';
    case classification = 'Classification';
    case companies = 'Companies';
    case govdues = 'Government Dues';
    case departments = 'Departments';
    case e201document = 'E-201 Document';
    case employeestatus = 'Employee Status';
    // case hmo = 'HMOs';
    case holidaylogger = 'Holiday Logger';
    // case joblevels = 'Job Level';
    case leavevalidations = 'Leave Validation';
    case lilovalidations = 'Lilo Validation';
    case obvalidations = 'OB Validation';
    case otfiling = 'OT Filing Maintenance';
    case pagibigcontribution = 'Pagibig Contribution';
    // case parentalsetting = 'Parental Settings';
    case philhealth = 'Philhealth Contribution';
    case positions = 'Position';
    // case relationship = 'Relationship';
    case employeeschedules = 'Scheduler';
    // case scheduletime = 'Schedule Time';
    // case sil = 'SIL Loan';
    case ssscontribution = 'SSS Contribution';
    // case leavetypes = 'Types of Leaves';
    case userroles = 'User Roles';
    case attendance = 'Attendance Viewer';
    // case laboratory = 'Laboratory';
    case loanmanagement = 'Loan Management';
    case admine201 = 'Admin E-201';
    case manageemployeestatus = 'Manage Employee Status';
    case leavecreditallocation = 'Leave Credit Allocations';
    case manual_entry = 'HR Manual Time Adjustment';
    case databasebackup = 'Database Backup';
    case databasebackupcreate = 'Database Backup Create';
    case databasebackuprestore = 'Database Backup Restore';
    case databasebackupdelete = 'Database Backup Delete';
    case mailintegration = 'Mail Integration';
    case payslipemail = 'Payslip Email Sending';
    case kuboaccess = 'KuBo - Community Access';
    case kuboadmin = 'KuBo - Admin (Pin/Delete)';
    case allowedips = 'IP Restriction Management';
    case allowedipslogs = 'IP Access Logs';
    case bypass_ip_restriction = 'Bypass IP Restriction';
    case maintenancemode = 'Maintenance Mode';
    case maintenancebypass = 'Maintenance Mode Bypass';
    case programs = 'Programs Management';
    case noticemanagement = 'Notices Management';
    case coemanagement = 'COE Management';

}
