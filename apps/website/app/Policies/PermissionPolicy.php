<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * Policy resource konten: semua aksi Filament (lihat, buat, ubah, hapus, urutkan, dst.) butuh satu
 * hak akses yang sama. Dicek di server oleh Filament untuk halaman maupun aksi, jadi tahu URL-nya
 * saja tidak cukup.
 */
abstract class PermissionPolicy
{
    abstract protected function permission(): Permission;

    protected function allowed(User $user): bool
    {
        return $user->hasPermission($this->permission());
    }

    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function view(User $user): bool
    {
        return $this->allowed($user);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user);
    }

    public function update(User $user): bool
    {
        return $this->allowed($user);
    }

    public function delete(User $user): bool
    {
        return $this->allowed($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function restore(User $user): bool
    {
        return $this->allowed($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function forceDelete(User $user): bool
    {
        return $this->allowed($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function replicate(User $user): bool
    {
        return $this->allowed($user);
    }

    public function reorder(User $user): bool
    {
        return $this->allowed($user);
    }
}
