<?php

namespace Tests\Feature;

use App\Enums\AchievementCategory;
use App\Enums\AchievementLevel;
use App\Filament\Resources\Achievements\AchievementResource;
use App\Filament\Resources\Achievements\Pages\CreateAchievement;
use App\Filament\Resources\Achievements\Pages\ListAchievements;
use App\Models\Achievement;
use App\Models\Post;
use Database\Seeders\DemoAchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

// Nama tim/anggota di sini fiktif.
class AchievementTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_prestasi_terbit_dikelompokkan_per_tahun_terbaru_dulu(): void
    {
        Achievement::factory()->create(['competition' => 'Lomba Lama', 'achieved_on' => '2025-03-01']);
        Achievement::factory()->create(['competition' => 'Lomba Awal Tahun', 'achieved_on' => '2026-01-15']);
        Achievement::factory()->create(['competition' => 'Lomba Akhir Tahun', 'achieved_on' => '2026-11-20']);
        Achievement::factory()->draft()->create(['competition' => 'Masih Draft']);
        Achievement::factory()->scheduled()->create(['competition' => 'Masih Terjadwal']);

        $this->get('/prestasi')
            ->assertOk()
            ->assertSeeInOrder(['2026', 'Lomba Akhir Tahun', 'November 2026', 'Lomba Awal Tahun', '2025', 'Lomba Lama'])
            ->assertDontSee('Masih Draft')
            ->assertDontSee('Masih Terjadwal');
    }

    public function test_filter_jenis_dan_ringkasan_angka(): void
    {
        Achievement::factory()->create(['competition' => 'CTF Internasional', 'level' => AchievementLevel::Internasional]);
        Achievement::factory()->create(['competition' => 'CTF Kampus', 'level' => AchievementLevel::Kampus]);
        Achievement::factory()->create(['competition' => 'Lomba Esai', 'category' => AchievementCategory::Lomba]);
        Achievement::factory()->draft()->create(); // tidak ikut dihitung

        $this->get('/prestasi')->assertViewHas('stats', ['total' => 3, 'ctf' => 2, 'national' => 2]);

        $this->get('/prestasi?jenis=lomba')
            ->assertSee('Lomba Esai')
            ->assertDontSee('CTF Kampus')
            ->assertViewHas('stats', ['total' => 3, 'ctf' => 2, 'national' => 2]); // ringkasan tidak ikut filter
        $this->get('/prestasi?jenis=tidak-ada')->assertOk()->assertSee('Lomba Esai')->assertSee('CTF Kampus');
    }

    public function test_isian_teks_polos_di_escape_dan_anggota_per_baris(): void
    {
        $achievement = Achievement::factory()->create([
            'team_name' => '<b>Tim</b>',
            'members' => "Anggota Satu\r\n\r\n  Anggota Dua  \n<script>alert(1)</script>",
            'description' => '<img src=x onerror=alert(1)>',
        ]);

        $this->assertSame(['Anggota Satu', 'Anggota Dua', '<script>alert(1)</script>'], $achievement->member_list);

        $this->get('/prestasi')
            ->assertOk()
            ->assertSee('Anggota Satu, Anggota Dua')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x', false)
            ->assertDontSee('<b>Tim</b>', false);
    }

    public function test_tautan_berita_hanya_bila_tulisan_terbit(): void
    {
        $post = Post::factory()->create();
        Achievement::factory()->for($post)->create(['result_url' => 'https://ctftime.org/event/1']);

        $this->get('/prestasi')
            ->assertSee(route('berita.show', $post), false)
            ->assertSee('href="https://ctftime.org/event/1" target="_blank" rel="noopener noreferrer"', false);

        $post->update(['published_at' => null]);
        $this->get('/prestasi')->assertDontSee('Baca beritanya');
    }

    public function test_tautan_hasil_hanya_http_atau_https(): void
    {
        $this->actingAsAdmin();

        foreach (['javascript:alert(1)', 'ftp://contoh.id/hasil', 'data:text/html,halo'] as $url) {
            Livewire::test(CreateAchievement::class)
                ->fillForm(['competition' => 'Kompetisi Uji', 'result' => 'Juara 1', 'result_url' => $url])
                ->call('create')
                ->assertHasFormErrors(['result_url']);
        }

        $this->assertSame(0, Achievement::query()->count());
    }

    /** Kolom date tanpa jam: tidak boleh ikut dikonversi WIB → UTC (bisa mundur sehari). */
    public function test_admin_membuat_prestasi_langsung_terbit(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateAchievement::class)
            ->fillForm([
                'competition' => 'Cyber Contoh 2026',
                'result' => 'Juara 2',
                'category' => AchievementCategory::Ctf->value,
                'level' => AchievementLevel::Nasional->value,
                'achieved_on' => '2026-10-01',
                'members' => "Anggota Satu\nAnggota Dua",
                'result_url' => 'https://ctftime.org/event/1',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $achievement = Achievement::query()->sole();
        $this->assertSame('2026-10-01', $achievement->achieved_on->toDateString());
        $this->assertTrue($achievement->isPublished(), 'default "Terbit pada" = sekarang');
        $this->get('/prestasi')->assertSee('Cyber Contoh 2026');
    }

    public function test_halaman_admin_dan_filter(): void
    {
        $this->actingAsAdmin();
        $ctf = Achievement::factory()->create();
        $lomba = Achievement::factory()->draft()->create(['category' => AchievementCategory::Lomba]);

        foreach ([AchievementResource::getUrl('index'), AchievementResource::getUrl('create'), AchievementResource::getUrl('edit', ['record' => $ctf])] as $url) {
            $this->get($url)->assertOk();
        }

        Livewire::test(ListAchievements::class)
            ->filterTable('category', AchievementCategory::Lomba->value)
            ->assertCanSeeTableRecords([$lomba])
            ->assertCanNotSeeTableRecords([$ctf])
            ->resetTableFilters()
            ->filterTable('status', 'draft')
            ->assertCanSeeTableRecords([$lomba])
            ->assertCanNotSeeTableRecords([$ctf]);
    }

    public function test_file_foto_dihapus_saat_diganti_dan_saat_prestasi_dihapus(): void
    {
        $disk = Storage::fake(Achievement::PHOTO_DISK);
        $disk->put('prestasi/lama.jpg', 'x');
        $disk->put('prestasi/baru.jpg', 'x');
        $achievement = Achievement::factory()->create(['photo_path' => 'prestasi/lama.jpg']);

        $achievement->update(['photo_path' => 'prestasi/baru.jpg']);
        $disk->assertMissing('prestasi/lama.jpg');

        $achievement->delete();
        $disk->assertMissing('prestasi/baru.jpg');
    }

    public function test_tulisan_dihapus_prestasi_tetap_ada(): void
    {
        $post = Post::factory()->create();
        $achievement = Achievement::factory()->for($post)->create();

        $post->delete();

        $this->assertNull($achievement->fresh()->post_id);
    }

    public function test_menu_prestasi_di_navbar(): void
    {
        $this->get('/')->assertSee('href="'.route('prestasi').'"', false);
        // Item navbar aktif (chip filter "Semua" juga ber-aria-current, tapi atributnya di baris lain).
        $this->get('/prestasi')->assertSee('href="'.route('prestasi').'" aria-current="page"', false);
    }

    public function test_seeder_contoh_hanya_untuk_lokal(): void
    {
        Storage::fake(Achievement::PHOTO_DISK);

        $this->seed(DemoAchievementSeeder::class);
        $this->assertSame(5, Achievement::query()->published()->count());
        $this->seed(DemoAchievementSeeder::class); // dijalankan ulang: tidak menggandakan
        $this->assertSame(6, Achievement::query()->count());

        Achievement::query()->get()->each->delete();
        $this->app['env'] = 'production';
        $this->artisan('db:seed', ['--class' => DemoAchievementSeeder::class, '--force' => true])
            ->expectsOutputToContain('hanya untuk lokal');
        $this->assertSame(0, Achievement::query()->count());
    }
}
