<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Album>
 */
class AlbumFactory extends Factory
{
    /**
     * Define the model's default state. Default: sudah terbit, tanpa foto (lihat withPhotos()).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => null,
            'title' => fake()->unique()->sentence(4),
            'description' => null,
            'event_date' => fake()->dateTimeBetween('-1 year', '-1 week')->format('Y-m-d'),
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

    /** Baris foto tanpa file sungguhan — cukup untuk tes tampilan (disk di-fake). */
    public function withPhotos(int $count = 3): static
    {
        return $this->has(
            Photo::factory()->count($count)->sequence(fn ($sequence): array => ['sort_order' => $sequence->index + 1]),
        );
    }
}
