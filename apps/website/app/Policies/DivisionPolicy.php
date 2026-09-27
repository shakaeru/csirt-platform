<?php

namespace App\Policies;

use App\Enums\Permission;

class DivisionPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::Structure;
    }
}
