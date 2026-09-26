<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Jenis lomba di halaman Prestasi (filter ?jenis=). */
enum AchievementCategory: string implements HasLabel
{
    case Ctf = 'ctf';
    case Lomba = 'lomba';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ctf => 'CTF',
            self::Lomba => 'Lomba lain',
        };
    }
}
