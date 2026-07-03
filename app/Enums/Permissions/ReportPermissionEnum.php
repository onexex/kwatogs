<?php

namespace App\Enums\Permissions;

use App\Traits\EnumToArray;

enum ReportPermissionEnum: string
{
    use EnumToArray;

    case attendance = 'Attendace';
    case employeeinformation = 'Employee Information';
    case overtimereport = 'Overtime Report';
    case leavereport = 'Leave Report';
    case thirteenthmonth = '13th Month Pay';
    case birreport = 'BIR Withholding Report';
    case sssreport = 'SSS Contribution Report';
    case philhealthreport = 'PhilHealth Contribution Report';
    case pagibigreport = 'Pag-IBIG Contribution Report';
    case payrollregister = 'Payroll Register';
    case payrolljournal = 'Payroll Journal';
    case loanledger = 'Loan Ledger';
    case dtrreport = 'Daily Time Record (DTR)';
    case tardinessreport = 'Tardiness & Absences';
    case headcountreport = 'Headcount & Turnover';
    case leaveledger = 'Leave Ledger';
    case noticesreport = 'Disciplinary Notices Summary';
    case coereport = 'COE Issuance Log';
    case finalpayreport = 'Final Pay Computation';
}
