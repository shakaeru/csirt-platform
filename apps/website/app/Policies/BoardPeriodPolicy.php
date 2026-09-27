<?php

namespace App\Policies;

use App\Enums\Permission;

class BoardPeriodPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::Structure;
    }
}
