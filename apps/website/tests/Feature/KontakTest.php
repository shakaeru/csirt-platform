<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageContact;
use App\Models\Setting;
use App\Models\User;
use App\Support\Contact\ContactPerson;
use App\Support\Contact\RegistrationStatus;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Halaman Kontak (/kontak) dan pengaturannya di panel admin. Nama dan nomor di sini fiktif. */
class KontakTest extends TestCase
{
    use RefreshDatabase;

    public function test_menampilkan_email_media_sosial_dan_pendaftaran_belum_dibuka(): void
    {
        $kontak = config('kontak');

        $this->get('/kontak')
            ->assertOk()
            ->assertSee('href="mailto:'.$kontak['email'].'"', false)
            ->assertSee($kontak['email'])
            ->assertSee('href="'.$kontak['instagram']['url'].'"', false)
            ->assertSee($kontak['instagram']['akun'])
            ->assertSee('href="'.$kontak['linkedin']['url'].'"', false)
            ->assertSee('id="pendaftaran"', false)
            ->assertSee('Belum dibuka')
            ->assertDontSee('Isi Formulir Pendaftaran')
            ->assertDontSee('Contact Person'); // belum diisi admin → bagiannya tidak tampil
    }

    public function test_menu_kontak_aktif_di_navbar(): void
    {
        $this->get('/kontak')->assertSee('href="'.route('kontak').'" aria-current="page"', false);
        $this->get('/')->assertSee('href="'.route('kontak').'"', false);
    }

    public function test_contact_person_tampil_dengan_tautan_telepon_dan_whatsapp(): void
    {
        Setting::put(ContactPerson::SETTING_KEY, [
            ['name' => 'Rina Fiktif', 'position' => 'Sekretaris', 'phone' => '0812-3456-7890'],
            ['name' => 'Budi Contoh', 'position' => 'Kepala Divisi Humas', 'phone' => '+62 813 0000 1111'],
        ]);

        $this->get('/kontak')
            ->assertSee('Contact Person')
            ->assertSeeInOrder(['Rina Fiktif', 'Sekretaris', '0812-3456-7890', 'Budi Contoh', 'Kepala Divisi Humas', '+62 813 0000 1111'])
            ->assertSee('href="tel:+6281234567890"', false)
            ->assertSee('href="https://wa.me/6281234567890"', false)
            ->assertSee('href="https://wa.me/6281300001111"', false);
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function nomorTelepon(): array
    {
        return [
            'format lokal bertanda hubung' => ['0812-3456-7890', '6281234567890'],
            'format internasional' => ['+62 812 3456 7890', '6281234567890'],
            'tanpa plus' => ['6281234567890', '6281234567890'],
            'bukan seluler' => ['0761-53939', null],
            'terlalu pendek' => ['0812-345', null],
            'ada huruf' => ['0812-3456-789O', null],
            'kode negara lain' => ['+1 202 555 0100', null],
        ];
    }

    #[DataProvider('nomorTelepon')]
    public function test_normalisasi_nomor_seluler(string $input, ?string $expected): void
    {
        $this->assertSame($expected, ContactPerson::normalizePhone($input));
    }

    public function test_pendaftaran_dibuka_menampilkan_tombol_formulir(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1));
        Setting::put(RegistrationStatus::SETTING_KEY, [
            'enabled' => true,
            'form_url' => 'https://forms.gle/ContohFormulir',
            'closes_on' => '2026-10-15',
        ]);

        $this->get('/kontak')
            ->assertSee('Dibuka')
            ->assertSee('Isi Formulir Pendaftaran')
            ->assertSee('href="https://forms.gle/ContohFormulir"', false)
            ->assertSee('hingga Kamis, 15 Oktober 2026')
            ->assertSee('Formulir dibuka di forms.gle')
            ->assertDontSee('Belum dibuka');
    }

    /** Hari terakhir dihitung dalam WIB: 15 Oktober 23.30 WIB masih buka, 16 Oktober 00.30 WIB sudah tutup. */
    public function test_pendaftaran_otomatis_tertutup_setelah_hari_terakhir_wib(): void
    {
        Setting::put(RegistrationStatus::SETTING_KEY, [
            'enabled' => true,
            'form_url' => 'https://forms.gle/ContohFormulir',
            'closes_on' => '2026-10-15',
        ]);

        $this->travelTo('2026-10-15 16:30:00'); // UTC = 23.30 WIB
        $this->assertTrue(RegistrationStatus::current()->isOpen());

        $this->travelTo('2026-10-15 17:30:00'); // UTC = 16 Oktober 00.30 WIB
        $this->assertFalse(RegistrationStatus::current()->isOpen());
        $this->get('/kontak')
            ->assertSee('Sudah ditutup')
            ->assertSee('ditutup pada 15 Oktober 2026')
            ->assertDontSee('Isi Formulir Pendaftaran')
            ->assertDontSee('forms.gle/ContohFormulir');
    }

    public function test_saklar_mati_berarti_belum_dibuka_walau_tautan_ada(): void
    {
        Setting::put(RegistrationStatus::SETTING_KEY, ['enabled' => false, 'form_url' => 'https://forms.gle/ContohFormulir', 'closes_on' => null]);

        $this->get('/kontak')
            ->assertSee('Belum dibuka')
            ->assertDontSee('forms.gle/ContohFormulir');
    }

    public function test_admin_menyimpan_pendaftaran_dan_contact_person(): void
    {
        $this->actingAsAdmin();
        $this->get(ManageContact::getUrl())->assertOk();
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(ManageContact::class)
            ->fillForm([
                'registration' => ['enabled' => true, 'form_url' => 'https://forms.gle/ContohFormulir', 'closes_on' => '2026-10-15'],
                'contact_people' => [
                    ['name' => ' Rina Fiktif ', 'position' => 'Sekretaris', 'phone' => '0812-3456-7890'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $this->assertSame(
            ['enabled' => true, 'form_url' => 'https://forms.gle/ContohFormulir', 'closes_on' => '2026-10-15'],
            Setting::get(RegistrationStatus::SETTING_KEY),
        );
        $this->assertSame(
            [['name' => 'Rina Fiktif', 'position' => 'Sekretaris', 'phone' => '0812-3456-7890']],
            Setting::get(ContactPerson::SETTING_KEY),
        );

        // Isian tersimpan muncul lagi saat halaman admin dibuka ulang.
        Livewire::test(ManageContact::class)
            ->assertSchemaStateSet(['registration.form_url' => 'https://forms.gle/ContohFormulir']);
    }

    public function test_validasi_tautan_formulir_dan_nomor(): void
    {
        $this->actingAsAdmin();
        $undoRepeaterFake = Repeater::fake();

        // Dibuka tanpa tautan.
        Livewire::test(ManageContact::class)
            ->fillForm(['registration' => ['enabled' => true, 'form_url' => null]])
            ->call('save')
            ->assertHasFormErrors(['registration.form_url' => 'required']);

        // Hanya https.
        foreach (['http://forms.gle/ContohFormulir', 'javascript:alert(1)', 'bukan tautan'] as $url) {
            Livewire::test(ManageContact::class)
                ->fillForm(['registration' => ['enabled' => true, 'form_url' => $url]])
                ->call('save')
                ->assertHasFormErrors(['registration.form_url']);
        }

        // Nomor harus seluler Indonesia.
        Livewire::test(ManageContact::class)
            ->fillForm(['contact_people' => [['name' => 'Rina Fiktif', 'position' => 'Sekretaris', 'phone' => '0761-53939']]])
            ->call('save')
            ->assertHasFormErrors(['contact_people.0.phone']);

        $undoRepeaterFake();

        $this->assertSame(0, Setting::query()->count());
    }

    public function test_pengaturan_kontak_hanya_untuk_admin(): void
    {
        $this->get(ManageContact::getUrl())->assertRedirect();

        config(['csirt.admin_emails' => ['admin@csirt.test']]);
        $this->actingAs(User::factory()->create(['email' => 'orang@lain.test']))
            ->get(ManageContact::getUrl())
            ->assertForbidden();
    }
}
