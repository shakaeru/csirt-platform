<?php

namespace App\Support\Filament;

use Filament\Support\Assets\AssetManager;
use Filament\Support\Assets\Font;

/**
 * Filament core selalu mendaftarkan font Inter (FilamentServiceProvider), dan @filamentStyles
 * memasang stylesheet @font-face-nya di semua halaman panel walaupun panel memakai font lain.
 * Situs ini tidak memakai Inter (CLAUDE.md § Tipografi), jadi aset font itu disaring di sini.
 * Aset lain (JS, tema, CSS) tidak tersentuh. Dipasang di AppServiceProvider::register().
 *
 * Akibat samping yang disengaja: `filament:assets` tidak lagi menyalin file font Inter ke
 * public/fonts/filament. Preload font bawaan panel mengembalikan [] bila font tidak ada.
 */
class AssetManagerWithoutInter extends AssetManager
{
    public function getFonts(?array $packages = null): array
    {
        return array_values(array_filter(
            parent::getFonts($packages),
            fn (Font $font): bool => ! ($font->getPackage() === 'filament/filament' && $font->getId() === 'inter'),
        ));
    }
}
