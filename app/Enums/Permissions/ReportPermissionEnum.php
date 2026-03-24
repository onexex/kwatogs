<?php 

namespace App\Enums\Permissions;

use App\Traits\EnumToArray;

enum ReportPermissionEnum: string
{
    use EnumToArray;

    case attendance = 'Attendace';
    case employeeinformation = 'Employee Information';
}