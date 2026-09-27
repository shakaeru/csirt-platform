<?php

namespace App\Policies;

use App\Enums\Permission;

class PostPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::Posts;
    }
}
