<?php

namespace Database\Factories;

use App\Enums\AchievementCategory;
use App\Enums\AchievementLevel;
use App\Models\Achievement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
{
    /**
     * Define the model's default state. Default: sudah terbit.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'competition' => 'Kompetisi '.fake()->unique()->words(3, true),
            'result' => fake()->randomElement(['Juara 1', 'Juara 2', 'Juara 3', 'Finalis']),
            'category' => AchievementCategory::Ctf,
            'level' => AchievementLevel::Nasional,
            'organizer' => null,
            'achieved_on' => fake()->dateTimeBetween('-2 years', '-1 week')->format('Y-m-d'),
            'team_name' => null,
            'members' => null,
            'description' => null,
            'photo_path' => null,
            'result_url' => null,
            'post_id' => null,
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => ['published_at' => now()->addWeek()]);
    }
}
