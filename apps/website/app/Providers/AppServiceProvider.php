<?php

namespace App\Providers;

use App\Support\Filament\AssetManagerWithoutInter;
use Filament\Support\Assets\AssetManager;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Menggantikan binding scoped dari SupportServiceProvider (provider paket didaftarkan lebih
        // dulu): panel admin tidak memasang stylesheet font Inter bawaan Filament.
        $this->app->scoped(AssetManager::class, fn (): AssetManager => new AssetManagerWithoutInter);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Panel admin menampilkan & menerima tanggal/jam dalam WIB; database tetap UTC.
        FilamentTimezone::set(config('csirt.timezone'));
    }
}
