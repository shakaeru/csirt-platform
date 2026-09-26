<?php

namespace Database\Factories;

use App\Enums\BoardSection;
use App\Models\BoardMember;
use App\Models\BoardPeriod;
use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoardMember>
 */
class BoardMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'board_period_id' => BoardPeriod::factory(),
            'section' => BoardSection::Inti,
            'division_id' => null,
            'name' => fake()->name(),
            'position' => 'Anggota',
            'photo_path' => null,
            'sort_order' => 0,
        ];
    }

    public function pembina(): static
    {
        return $this->state(fn (): array => ['section' => BoardSection::Pembina, 'position' => 'Pembina']);
    }

    public function inDivision(?Division $division = null): static
    {
        return $this->state(fn (): array => [
            'section' => BoardSection::Divisi,
            'division_id' => $division ?? Division::factory(),
        ]);
    }
}
