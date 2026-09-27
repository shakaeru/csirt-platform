<?php

namespace App\Policies;

use App\Enums\Permission;

class AchievementPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::Achievements;
    }
}
