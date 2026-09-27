<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Permission;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role_id', 'permissions'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Panel admin: email terverifikasi dan punya role (berlaku di semua environment, termasuk
     * local). Menu dan aksi di dalamnya dibatasi hak akses role (policy di app/Policies). Akun
     * dibuat Super Admin di panel (menu Akses → Pengguna) atau `php artisan admin:create` (Super
     * Admin, email harus di ADMIN_EMAILS); keduanya langsung terverifikasi. Pendaftaran publik
     * (/register) ditutup; syarat terverifikasi tetap dipertahankan sebagai lapis kedua.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasVerifiedEmail() && $this->role_id !== null;
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public static function superAdminCount(): int
    {
        return static::query()->whereHas('role', fn ($query) => $query->where('is_super', true))->count();
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->role?->is_super;
    }

    /** Super Admin: semua. Lainnya: hak akses role + hak akses tambahan milik pengguna ini. */
    public function hasPermission(Permission $permission): bool
    {
        return $this->isSuperAdmin()
            || (bool) $this->role?->grants($permission)
            || in_array($permission->value, $this->permissions ?? [], true);
    }

    /**
     * @return list<Permission>
     */
    public function effectivePermissions(): array
    {
        return array_values(array_filter(Permission::cases(), $this->hasPermission(...)));
    }

    /**
     * Bolehkah pengguna ini memberikan hak akses tersebut ke orang lain / ke role? Hanya yang ia
     * miliki sendiri, supaya pemegang "kelola pengguna/role" tidak bisa menaikkan hak aksesnya.
     *
     * @param  iterable<Permission|string>  $permissions
     */
    public function canGrant(iterable $permissions): bool
    {
        foreach ($permissions as $permission) {
            $permission = $permission instanceof Permission ? $permission : Permission::tryFrom($permission);
            if ($permission === null || ! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /** Role Super Admin hanya bisa diberikan Super Admin; role lain bila hak aksesnya dimiliki pemberi. */
    public function canAssignRole(Role $role): bool
    {
        return $role->is_super ? $this->isSuperAdmin() : $this->canGrant($role->permissions ?? []);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }
}
