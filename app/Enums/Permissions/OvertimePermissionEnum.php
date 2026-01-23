<?php 

namespace App\Enums\Permissions;

use App\Traits\EnumToArray;

enum OvertimePermissionEnum: string
{
    use EnumToArray;

    case createovertime = 'Create Overtime';

    case cancelovertime = 'Cancel Overtime';

    case approveovertime = 'Approve Overtime';

    case disapproveovertime = 'DisApprove Overtime';

    case approvecfoovertime = 'CFO Approve Overtime';
}