<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin panel Filament (/admin)
    |--------------------------------------------------------------------------
    |
    | Email yang boleh masuk panel admin, dipisah koma, dari ADMIN_EMAILS di .env.
    | Sengaja di .env, bukan di repo — daftar email pribadi tidak masuk git.
    | Akun admin dibuat dengan `php artisan admin:create <email>`. Lihat User::canAccessPanel().
    |
    */

    'admin_emails' => array_values(array_filter(array_map(
        fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', '')),
    ))),

];
