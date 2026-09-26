<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Pendaftaran akun publik ditutup (lihat routes/auth.php) — akun dibuat operator.
 */
class RegistrationTest extends TestCase
{
    public function test_pendaftaran_publik_ditutup(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['email' => 'x@example.com'])->assertNotFound();
        $this->assertFileDoesNotExist(resource_path('views/livewire/pages/auth/register.blade.php'));
    }
}
