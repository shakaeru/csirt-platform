<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * Menu Akses → Pengguna. Selain hak akses "kelola pengguna": pengguna non-Super Admin hanya bisa
 * mengurus akun yang hak aksesnya tidak melebihi miliknya sendiri (dan bukan Super Admin), tidak
 * ada yang bisa menghapus akunnya sendiri, dan Super Admin terakhir tidak bisa dihapus.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission(Permission::Users);
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission(Permission::Users);
    }

    public function view(User $actor, User $target): bool
    {
        return $this->manages($actor, $target);
    }

    public function update(User $actor, User $target): bool
    {
        return $this->manages($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->manages($actor, $target)
            && $actor->isNot($target)
            && ! ($target->isSuperAdmin() && User::superAdminCount() <= 1);
    }

    /** Hapus massal tidak disediakan: tiap akun dicek satu per satu lewat delete(). */
    public function deleteAny(User $actor): bool
    {
        return false;
    }

    private function manages(User $actor, User $target): bool
    {
        return $actor->hasPermission(Permission::Users)
            && ($actor->isSuperAdmin() || (! $target->isSuperAdmin() && $actor->canGrant($target->effectivePermissions())));
    }
}
