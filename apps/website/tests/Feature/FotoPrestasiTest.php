<?php

namespace Tests\Feature;

use App\Enums\AchievementCategory;
use App\Enums\AchievementLevel;
use App\Filament\Resources\Achievements\Pages\CreateAchievement;
use App\Filament\Resources\Achievements\Pages\EditAchievement;
use App\Models\Achievement;
use App\Support\Images\ResponsiveVariants;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestImages;
use Tests\TestCase;

/**
 * Varian WebP foto prestasi untuk srcset. Aturan umumnya (tidak memperbesar, orientasi EXIF,
 * file rusak) sama dengan sampul tulisan dan diuji di SampulTulisanTest.
 */
class FotoPrestasiTest extends TestCase
{
    use CreatesTestImages, RefreshDatabase;

    private Filesystem $disk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disk = Storage::fake(Achievement::PHOTO_DISK);
    }

    public function test_foto_prestasi_dibuatkan_varian_dan_dipakai_di_srcset(): void
    {
        $this->disk->put('prestasi/foto.jpg', $this->plainJpeg(1200, 675));

        $achievement = Achievement::factory()->create(['photo_path' => 'prestasi/foto.jpg']);

        $this->assertSame([400, 800, 1200], $achievement->fresh()->photo_widths);
        $srcset = implode(', ', array_map(
            fn (int $width): string => $this->disk->url("prestasi/foto-{$width}.webp")." {$width}w",
            [400, 800, 1200],
        ));
        $this->get('/prestasi')
            ->assertSee('<source type="image/webp" srcset="'.$srcset.'" sizes="(min-width: 640px) 400px, calc(100vw - 32px)">', false)
            ->assertSee('src="'.$this->disk->url('prestasi/foto.jpg').'"', false);
    }

    public function test_varian_lama_dihapus_saat_foto_diganti_dan_saat_prestasi_dihapus(): void
    {
        $this->disk->put('prestasi/lama.jpg', $this->plainJpeg(1200, 675));
        $this->disk->put('prestasi/baru.jpg', $this->plainJpeg(1200, 675));
        $achievement = Achievement::factory()->create(['photo_path' => 'prestasi/lama.jpg']);

        $achievement->update(['photo_path' => 'prestasi/baru.jpg']);
        foreach (ResponsiveVariants::WIDTHS as $width) {
            $this->assertFalse($this->disk->exists("prestasi/lama-{$width}.webp"));
            $this->assertTrue($this->disk->exists("prestasi/baru-{$width}.webp"));
        }

        $achievement->delete();
        $this->assertSame([], $this->disk->allFiles('prestasi'));
    }

    public function test_unggah_dan_ganti_foto_lewat_panel_admin(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateAchievement::class)
            ->fillForm([
                'competition' => 'Cyber Contoh 2026',
                'result' => 'Juara 1',
                'category' => AchievementCategory::Ctf->value,
                'level' => AchievementLevel::Nasional->value,
                'achieved_on' => '2026-10-01',
                'photo_path' => UploadedFile::fake()->image('tim.jpg', 1200, 675),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $achievement = Achievement::query()->sole();
        $first = $achievement->photo_path;
        $this->assertSame([400, 800, 1200], $achievement->photo_widths);

        // Seperti di panel: foto lama dihapus (tombol ×), lalu file baru diunggah.
        Livewire::test(EditAchievement::class, ['record' => $achievement->getRouteKey()])
            ->set('data.photo_path', [])
            ->fillForm(['photo_path' => UploadedFile::fake()->image('sertifikat.png', 700, 394)])
            ->call('save')
            ->assertHasNoFormErrors();

        $achievement->refresh();
        $this->assertSame([400, 700], $achievement->photo_widths);
        $this->assertFalse($this->disk->exists($first));
        $this->assertFalse($this->disk->exists(ResponsiveVariants::path($first, 800)));
    }
}
