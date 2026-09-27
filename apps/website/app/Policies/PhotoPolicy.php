<?php

namespace App\Policies;

use App\Enums\Permission;

class PhotoPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::Albums;
    }
}
