<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
