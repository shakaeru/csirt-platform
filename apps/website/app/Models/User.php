<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Panel admin: hanya email di ADMIN_EMAILS (config/csirt.php) yang sudah terverifikasi —
     * berlaku di semua environment, termasuk local. Akun admin dibuat dengan
     * `php artisan admin:create`, yang langsung menandainya terverifikasi. Pendaftaran publik
     * (/register) sudah ditutup; syarat terverifikasi tetap dipertahankan sebagai lapis kedua —
     * kalau pendaftaran dibuka lagi, akun yang didaftarkan orang lain memakai email admin
     * tidak otomatis bisa masuk panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasVerifiedEmail()
            && in_array(Str::lower($this->email), config('csirt.admin_emails'), true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
