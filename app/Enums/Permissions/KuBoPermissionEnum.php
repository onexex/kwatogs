<?php 

namespace App\Enums\Permissions;

use App\Traits\EnumToArray;

enum KuBoPermissionEnum: string
{
    use EnumToArray;

    case kuboaccess = 'KuBo - Community Access';
    case kubochat = 'KuBo - Chat';
    case kuboadmin = 'KuBo - Admin (Pin/Delete)';
}