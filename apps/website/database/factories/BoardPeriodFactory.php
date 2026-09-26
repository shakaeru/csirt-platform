<?php

namespace Database\Factories;

use App\Models\BoardPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoardPeriod>
 */
class BoardPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2015, 2045);

        return [
            'name' => $year.'/'.($year + 1),
            'starts_on' => "{$year}-09-01",
            'ends_on' => ($year + 1).'-08-31',
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['is_active' => true]);
    }
}
