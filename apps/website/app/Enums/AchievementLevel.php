<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Tingkat lomba. Urutan case = dari tertinggi. */
enum AchievementLevel: string implements HasLabel
{
    case Internasional = 'internasional';
    case Nasional = 'nasional';
    case Regional = 'regional';
    case Kampus = 'kampus';

    public function getLabel(): string
    {
        return match ($this) {
            self::Internasional => 'Internasional',
            self::Nasional => 'Nasional',
            self::Regional => 'Regional/Provinsi',
            self::Kampus => 'Internal kampus',
        };
    }
}
