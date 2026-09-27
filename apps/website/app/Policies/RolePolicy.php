<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;

/**
 * Menu Akses → Role. Role Super Admin tidak bisa diubah atau dihapus; pengguna non-Super Admin
 * hanya bisa mengubah role yang hak aksesnya sudah ia miliki semua; role yang masih dipakai tidak
 * bisa dihapus.
 */
class RolePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission(Permission::Roles);
    }

    public function view(User $actor): bool
    {
        return $actor->hasPermission(Permission::Roles);
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission(Permission::Roles);
    }

    public function update(User $actor, Role $role): bool
    {
        return $actor->hasPermission(Permission::Roles)
            && ! $role->is_super
            && $actor->canGrant($role->permissions ?? []);
    }

    public function delete(User $actor, Role $role): bool
    {
        return $this->update($actor, $role) && $role->users()->doesntExist();
    }

    public function deleteAny(User $actor): bool
    {
        return false;
    }
}
