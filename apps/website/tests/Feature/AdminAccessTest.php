<?php

namespace Tests\Feature;

use App\Console\Commands\CreateAdmin;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN = 'admin@csirt.test';

    private const PASSWORD = 'rahasia-panjang-123';

    protected function setUp(): void
    {
        parent::setUp();
        config(['csirt.admin_emails' => [self::ADMIN]]);
    }

    /** Sejak RBAC: akses panel = email terverifikasi + punya role; ADMIN_EMAILS tidak lagi menentukan. */
    public function test_pengguna_terverifikasi_dengan_role_bisa_masuk_panel(): void
    {
        $this->actingAs(User::factory()->create(['email' => 'editor@csirt.test', 'role_id' => Role::query()->where('name', 'Editor')->value('id')]))
            ->get('/admin')
            ->assertOk();
    }

    public function test_tanpa_role_ditolak_walau_email_ada_di_admin_emails(): void
    {
        $this->actingAs(User::factory()->create(['email' => self::ADMIN]))->get('/admin')->assertForbidden();
    }

    public function test_punya_role_tapi_belum_terverifikasi_ditolak(): void
    {
        $this->actingAs(User::factory()->unverified()->create(['role_id' => Role::super()->getKey()]))->get('/admin')->assertForbidden();
    }

    public function test_env_local_tidak_membuka_panel_untuk_pengguna_tanpa_role(): void
    {
        config(['app.env' => 'local']); // perilaku default Filament (izinkan semua di local) tidak berlaku
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_admin_create_membuat_akun_terverifikasi(): void
    {
        $this->artisan('admin:create', ['email' => self::ADMIN, '--name' => 'Admin CSIRT'])
            ->expectsQuestion(CreateAdmin::PASSWORD_QUESTION, self::PASSWORD)
            ->expectsQuestion(CreateAdmin::CONFIRM_QUESTION, self::PASSWORD)
            ->assertSuccessful();

        $user = User::query()->where('email', self::ADMIN)->sole();
        $this->assertTrue($user->isSuperAdmin(), 'admin:create = jalur SSH untuk Super Admin');
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_admin_create_menolak_email_di_luar_daftar_dan_password_lemah(): void
    {
        $this->artisan('admin:create', ['email' => 'orang@lain.test', '--name' => 'X'])
            ->expectsOutputToContain('belum ada di ADMIN_EMAILS')
            ->assertFailed();

        $this->artisan('admin:create', ['email' => self::ADMIN, '--name' => 'Admin'])
            ->expectsQuestion(CreateAdmin::PASSWORD_QUESTION, 'pendek1')
            ->expectsQuestion(CreateAdmin::CONFIRM_QUESTION, 'pendek1')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Akun memakai email admin sudah ada tapi dibuat orang lain (mis. sisa sebelum /register ditutup).
     * Akun itu tidak bisa masuk panel (belum terverifikasi); admin:create mengambil alih: password
     * baru, terverifikasi, dan sesi pemilik lama diputus.
     */
    public function test_admin_create_mengambil_alih_akun_yang_didaftarkan_orang_lain(): void
    {
        config(['session.driver' => 'database']);
        $squatter = User::factory()->unverified()->create(['email' => self::ADMIN, 'password' => 'punya-penyusup-1']);
        DB::table('sessions')->insert(['id' => 'sesi-penyusup', 'user_id' => $squatter->id, 'payload' => '', 'last_activity' => time()]);
        $this->assertFalse($squatter->canAccessPanel(Filament::getPanel('admin')));

        $this->artisan('admin:create', ['email' => self::ADMIN])
            ->expectsConfirmation('Akun '.self::ADMIN.' sudah ada. Reset password-nya, tandai terverifikasi, dan putus semua sesi aktifnya?', 'yes')
            ->expectsQuestion('Nama', 'Admin CSIRT')
            ->expectsQuestion(CreateAdmin::PASSWORD_QUESTION, self::PASSWORD)
            ->expectsQuestion(CreateAdmin::CONFIRM_QUESTION, self::PASSWORD)
            ->assertSuccessful();

        $admin = $squatter->fresh();
        $this->assertSame('Admin CSIRT', $admin->name);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check(self::PASSWORD, $admin->password));
        $this->assertFalse(Hash::check('punya-penyusup-1', $admin->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-penyusup']);
    }
}
