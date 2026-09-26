<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Membuat akun admin panel Filament (atau mereset akun yang sudah ada). Email harus tercantum di
 * ADMIN_EMAILS — lihat User::canAccessPanel(). Password diminta lewat prompt tersembunyi, jadi
 * tidak tercatat di riwayat shell.
 */
#[Signature('admin:create
    {email : Email admin — harus tercantum di ADMIN_EMAILS}
    {--name= : Nama tampilan (ditanyakan bila kosong)}')]
#[Description('Buat akun admin panel Filament, atau reset password akun yang sudah ada')]
class CreateAdmin extends Command
{
    public const PASSWORD_QUESTION = 'Password (min. 12 karakter, huruf dan angka)';

    public const CONFIRM_QUESTION = 'Ulangi password';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error("Email tidak valid: {$email}");

            return self::FAILURE;
        }

        if (! in_array($email, config('csirt.admin_emails'), true)) {
            $this->error("{$email} belum ada di ADMIN_EMAILS (.env). Tambahkan dulu — jalankan `php artisan config:clear` bila config di-cache.");

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        if ($user !== null && ! $this->confirm("Akun {$email} sudah ada. Reset password-nya, tandai terverifikasi, dan putus semua sesi aktifnya?")) {
            $this->warn('Dibatalkan — tidak ada yang diubah.');

            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Nama', $user?->name)));
        $password = (string) $this->secret(self::PASSWORD_QUESTION);
        $confirmation = (string) $this->secret(self::CONFIRM_QUESTION);

        $validator = Validator::make(
            ['name' => $name, 'password' => $password, 'password_confirmation' => $confirmation],
            ['name' => ['required', 'max:255'], 'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()]],
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user ??= new User(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'password' => $password, // di-hash oleh cast `hashed`
            'email_verified_at' => now(), // dibuat operator di server — tidak lewat verifikasi email
            'remember_token' => Str::random(60), // cookie "ingat saya" lama tidak berlaku lagi
        ])->save();

        // Akun yang sudah ada (mis. dibuat orang lain memakai email admin — /register sudah ditutup,
        // tapi akun lama bisa saja tersisa): putus sesinya supaya pemilik lama tidak tetap login
        // setelah password direset.
        $sessions = config('session.driver') === 'database'
            ? DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete()
            : 0;

        $this->info("Akun admin {$email} siap".($sessions > 0 ? " — {$sessions} sesi lama diputus" : '').'. Login di /admin/login.');

        return self::SUCCESS;
    }
}
