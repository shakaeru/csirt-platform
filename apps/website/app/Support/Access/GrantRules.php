<?php

namespace App\Support\Access;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Closure;

/**
 * Aturan validasi server untuk form Pengguna/Role: pilihan di form sudah disaring, tetapi request
 * bisa direkayasa, jadi pemberian role/hak akses tetap diperiksa di sini.
 */
final class GrantRules
{
    /** Hak akses yang boleh dicentang pengguna yang sedang login (Super Admin: semua). */
    public static function grantableOptions(): array
    {
        return Permission::options(self::actor()->effectivePermissions());
    }

    /** Daftar hak akses hanya boleh berisi value yang dikenal dan dimiliki pemberi. */
    public static function permissions(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_array($value) || ! self::actor()->canGrant($value)) {
                $fail('Hanya bisa memberikan hak akses yang Anda miliki sendiri.');
            }
        };
    }

    /**
     * Role harus boleh diberikan pemberi; Super Admin terakhir tidak boleh diturunkan.
     */
    public static function role(?User $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
            $role = filled($value) ? Role::query()->find($value) : null;
            if (filled($value) && ($role === null || ! self::actor()->canAssignRole($role))) {
                $fail('Role ini tidak bisa Anda berikan.');

                return;
            }

            if ($record?->isSuperAdmin() && ! $role?->is_super && User::superAdminCount() <= 1) {
                $fail('Ini satu-satunya Super Admin. Jadikan pengguna lain Super Admin dulu.');
            }
        };
    }

    private static function actor(): User
    {
        /** @var User */
        return auth()->user();
    }
}
