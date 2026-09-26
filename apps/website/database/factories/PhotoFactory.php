<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Baris foto tanpa file di disk. Foto sungguhan dibuat lewat Album::addPhoto().
 *
 * @extends Factory<Photo>
 */
class PhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $file = Str::lower(Str::random(24)).'.jpg';

        return [
            'album_id' => Album::factory(),
            'path' => "galeri/test/{$file}",
            'thumb_path' => "galeri/test/thumb/{$file}",
            'width' => 2000,
            'height' => 1333,
            'caption' => null,
            'sort_order' => 1,
        ];
    }
}
