<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Bagian halaman Struktur Organisasi. Urutan case = urutan tampil di halaman publik.
 */
enum BoardSection: string implements HasLabel
{
    case Pembina = 'pembina';
    case Inti = 'inti';
    case Divisi = 'divisi';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pembina => 'Pembina',
            self::Inti => 'Pengurus Inti',
            self::Divisi => 'Divisi',
        };
    }
}
