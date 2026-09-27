<?php

namespace App\Models;

use App\Enums\Permission;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Role panel admin. Hak aksesnya dicentang di panel (menu Akses → Role), disimpan sebagai daftar
 * value App\Enums\Permission. Role `is_super` (Super Admin) memiliki semua hak akses, tidak bisa
 * diubah atau dihapus, dan hanya bisa diberikan oleh Super Admin. `is_super` sengaja tidak fillable.
 */
#[Fillable(['name', 'description', 'permissions'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_super' => 'boolean',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public static function super(): self
    {
        return static::query()->where('is_super', true)->sole();
    }

    public function grants(Permission $permission): bool
    {
        return $this->is_super || in_array($permission->value, $this->permissions ?? [], true);
    }

    /**
     * @return list<Permission>
     */
    public function permissionList(): array
    {
        return $this->is_super
            ? Permission::cases()
            : array_values(array_filter(Permission::cases(), fn (Permission $permission): bool => $this->grants($permission)));
    }
}
