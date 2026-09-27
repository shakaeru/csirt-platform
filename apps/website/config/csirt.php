<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin panel Filament (/admin)
    |--------------------------------------------------------------------------
    |
    | Email yang boleh dijadikan Super Admin lewat `php artisan admin:create <email>` (jalur SSH),
    | dipisah koma, dari ADMIN_EMAILS di .env. Sengaja di .env, bukan di repo: daftar email pribadi
    | tidak masuk git. Akses panel sendiri ditentukan role (RBAC), lihat User::canAccessPanel().
    |
    */

    'admin_emails' => array_values(array_filter(array_map(
        fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', '')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Zona waktu tampilan
    |--------------------------------------------------------------------------
    |
    | Database dan aplikasi tetap UTC (config/app.php). Tanggal/jam ditampilkan dan diisi dalam
    | zona ini — panel admin lewat FilamentTimezone (AppServiceProvider), halaman publik lewat
    | ->timezone(config('csirt.timezone')). Contoh: jadwal terbit berita diisi dalam WIB.
    |
    */

    'timezone' => env('DISPLAY_TIMEZONE', 'Asia/Jakarta'),

];
