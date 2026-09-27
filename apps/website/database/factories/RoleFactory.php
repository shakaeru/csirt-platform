<?php

namespace Database\Factories;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'description' => null,
            'permissions' => [],
        ];
    }

    public function with(Permission ...$permissions): static
    {
        return $this->state(fn (): array => ['permissions' => array_map(fn (Permission $permission): string => $permission->value, $permissions)]);
    }
}
