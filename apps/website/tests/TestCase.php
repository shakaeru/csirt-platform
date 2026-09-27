<?php

namespace Tests;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Login sebagai Super Admin panel Filament (terverifikasi, role Super Admin dari migrasi). */
    protected function actingAsAdmin(string $email = 'admin@csirt.test'): static
    {
        return $this->actingAs(User::factory()->create(['email' => $email, 'role_id' => Role::super()->getKey()]));
    }

    /** Login sebagai pengguna panel dengan role berisi hak akses tertentu saja. */
    protected function actingAsUserWith(Permission ...$permissions): User
    {
        $user = User::factory()->create(['role_id' => Role::factory()->with(...$permissions)->create()->getKey()]);
        $this->actingAs($user);

        return $user;
    }
}
