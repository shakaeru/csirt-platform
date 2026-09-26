<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Status tulisan — tidak disimpan, diturunkan dari published_at (lihat Post::status()):
 * null = draft, masa depan = terjadwal, sudah lewat = terbit. Jadwal terbit jalan tanpa cron.
 */
enum PostStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Scheduled = 'terjadwal';
    case Published = 'terbit';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Terjadwal',
            self::Published => 'Terbit',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Scheduled => 'warning',
            self::Published => 'success',
        };
    }
}
