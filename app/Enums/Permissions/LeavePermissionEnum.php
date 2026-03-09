<?php 

namespace App\Enums\Permissions;

use App\Traits\EnumToArray;

enum LeavePermissionEnum: string
{
    use EnumToArray;

    case createleave = 'Create leave';

    case cancelleave = 'Cancel leave';

    case approveleave = 'Approve leave';

    case disapproveleave = 'DisApprove leave';

    case approvecfoleave = 'CFO Approve leave';
}