<?php

namespace App\Policies;

use App\Enums\Permission;

class AlbumPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::Albums;
    }
}
