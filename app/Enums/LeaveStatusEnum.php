<?php 

namespace App\Enums;

use App\Traits\EnumToArray;

enum LeaveStatusEnum: string
{
    use EnumToArray;

    case FORAPPROVAL = 'FOR APPROVAL';

    case CANCELED = 'CANCELED';

    case APPROVED = 'APPROVED';

    case APPROVEDBYCEO = 'APPROVED BY CEO';

    case DISAPPROVED = 'DISAPPROVED';
}