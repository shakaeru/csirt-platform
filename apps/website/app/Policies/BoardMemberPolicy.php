<?php

namespace App\Policies;

use App\Enums\Permission;

class BoardMemberPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::Structure;
    }
}
