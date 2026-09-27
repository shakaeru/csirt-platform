<?php

namespace Tests\Feature;

use App\Models\Album;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTestImages;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use CreatesTestImages, RefreshDatabase;

    public function test_beranda_memakai_layout_utama(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('UKM CSIRT Politeknik Caltex Riau</h1>', false)
            ->assertSee('images/csirt-logo.webp', false)
            ->assertSee('data-collapse-toggle="navbar-main"', false)
            ->assertSee('data-animate="navbar"', false);
    }

    public function test_tanpa_album_hero_satu_kolom_tanpa_foto(): void
    {
        Album::factory()->create(); // album tanpa foto tidak dihitung

        $this->get('/')->assertOk()->assertDontSee('<figure', false)->assertDontSee('lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]', false);
    }

    /** Jumbotron: sampul album terbit terbaru (menurut tanggal kegiatan) di kolom kanan hero, dengan srcset. */
    public function test_hero_menampilkan_foto_album_terbaru_dengan_srcset(): void
    {
        $this->withoutDefer();
        Storage::fake(Album::DISK);
        $old = Album::factory()->create(['title' => 'Pelatihan Lama', 'event_date' => '2026-01-10']);
        $old->addPhoto($this->tempJpeg(1200, 800));
        $latest = Album::factory()->create(['title' => 'Workshop Terbaru', 'event_date' => '2026-09-20']);
        $photo = $latest->addPhoto($this->tempJpeg(2400, 1600))->fresh();
        Album::factory()->draft()->create(['event_date' => '2026-09-25'])->addPhoto($this->tempJpeg(800, 600));

        // Foto 3:2 di kotak 4:3: faktor 1.13 → 700 px × 1.13 = 791 px.
        $this->get('/')
            ->assertOk()
            ->assertSee('<figure>', false)
            ->assertSee('href="'.route('galeri.show', $latest).'"', false)
            ->assertSee('Workshop Terbaru')
            ->assertDontSee('Pelatihan Lama')
            ->assertSee('srcset="'.$photo->srcset.'" sizes="(min-width: 1280px) 791px, (min-width: 1024px) calc((100vw - 80px) * 7 / 12 * 1.13), calc((100vw - 32px) * 1.13)"', false)
            ->assertSee('src="'.$photo->url.'"', false)
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('alt="Dokumentasi Workshop Terbaru"', false);
    }

    private function tempJpeg(int $width, int $height): string
    {
        $path = tempnam(sys_get_temp_dir(), 'beranda-test-');
        file_put_contents($path, $this->plainJpeg($width, $height));

        return $path;
    }
}
