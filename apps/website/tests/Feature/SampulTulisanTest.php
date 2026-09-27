<?php

namespace Tests\Feature;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Category;
use App\Models\Post;
use App\Support\Images\ResponsiveVariants;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestImages;
use Tests\TestCase;

/** Varian WebP sampul tulisan untuk srcset (ResponsiveVariants + Post::refreshCoverVariants()). */
class SampulTulisanTest extends TestCase
{
    use CreatesTestImages, RefreshDatabase;

    private Filesystem $disk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disk = Storage::fake(Post::COVER_DISK);
    }

    public function test_sampul_baru_dibuatkan_varian_webp_dan_dipakai_di_srcset(): void
    {
        $this->putJpeg('berita/sampul.jpg', 1200, 675);
        $post = Post::factory()->create(['title' => 'Workshop CTF & Forensik', 'cover_path' => 'berita/sampul.jpg']);

        $this->assertSame([400, 800, 1200], $post->fresh()->cover_widths);
        foreach ([400 => 225, 800 => 450, 1200 => 675] as $width => $height) {
            $info = getimagesizefromstring($this->disk->get("berita/sampul-{$width}.webp"));
            $this->assertSame([$width, $height, IMAGETYPE_WEBP], [$info[0], $info[1], $info[2]]);
        }

        $srcset = $this->disk->url('berita/sampul-400.webp').' 400w, '
            .$this->disk->url('berita/sampul-800.webp').' 800w, '
            .$this->disk->url('berita/sampul-1200.webp').' 1200w';

        $this->get('/berita')
            ->assertSee('<source type="image/webp" srcset="'.$srcset.'" sizes="(min-width: 1280px) 400px,', false)
            ->assertSee('src="'.$this->disk->url('berita/sampul.jpg').'"', false)
            ->assertSee('loading="lazy"', false);

        $this->get('/berita/'.$post->slug)
            ->assertSee('srcset="'.$srcset.'" sizes="(min-width: 768px) 736px, calc(100vw - 32px)"', false)
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('alt="Sampul tulisan: Workshop CTF &amp; Forensik"', false)
            ->assertDontSee('&amp;amp;', false);
    }

    /** Jalur sungguhan: FileUpload Filament menyimpan file dulu, baru event model membuat varian. */
    public function test_unggah_dan_ganti_sampul_lewat_panel_admin(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Pelatihan Rutin Oktober',
                'slug' => 'pelatihan-rutin-oktober',
                'content' => '<p>Isi laporan.</p>',
                'category_id' => Category::query()->where('slug', 'kegiatan')->value('id'),
                'cover_path' => UploadedFile::fake()->image('sampul.jpg', 1200, 675),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug', 'pelatihan-rutin-oktober')->sole();
        $first = $post->cover_path;
        $this->assertSame([400, 800, 1200], $post->cover_widths);
        $this->assertTrue($this->disk->exists(ResponsiveVariants::path($first, 800)));

        // Seperti di panel: sampul lama dihapus (tombol ×), lalu file baru diunggah.
        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->set('data.cover_path', [])
            ->fillForm(['cover_path' => UploadedFile::fake()->image('baru.png', 1000, 563)])
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();
        $this->assertNotSame($first, $post->cover_path);
        $this->assertSame([400, 800, 1000], $post->cover_widths);
        $this->assertFalse($this->disk->exists(ResponsiveVariants::path($first, 800)));
        $this->assertTrue($this->disk->exists(ResponsiveVariants::path($post->cover_path, 1000)));
    }

    public function test_gambar_kecil_tidak_diperbesar(): void
    {
        $this->putJpeg('berita/kecil.jpg', 900, 506);

        $post = Post::factory()->create(['cover_path' => 'berita/kecil.jpg']);

        $this->assertSame([400, 800, 900], $post->fresh()->cover_widths);
        $this->assertFalse($this->disk->exists('berita/kecil-1200.webp'));
    }

    /** Foto ponsel dengan EXIF Orientation 6 (diputar 90°): varian harus tegak. */
    public function test_varian_mengikuti_orientasi_exif(): void
    {
        $this->disk->put('berita/miring.jpg', $this->jpegWithOrientation(1200, 675, 6));

        $post = Post::factory()->create(['cover_path' => 'berita/miring.jpg']);

        $this->assertSame([400, 675], $post->fresh()->cover_widths);
        [$width, $height] = getimagesizefromstring($this->disk->get('berita/miring-400.webp'));
        $this->assertGreaterThan($width, $height);
    }

    public function test_varian_lama_dihapus_saat_sampul_diganti_dikosongkan_atau_tulisan_dihapus(): void
    {
        $this->putJpeg('berita/lama.jpg', 1200, 675);
        $this->putJpeg('berita/baru.jpg', 1200, 675);
        $post = Post::factory()->create(['cover_path' => 'berita/lama.jpg']);

        $post->update(['cover_path' => 'berita/baru.jpg']);
        $this->assertFalse($this->disk->exists('berita/lama.jpg'));
        foreach (ResponsiveVariants::WIDTHS as $width) {
            $this->assertFalse($this->disk->exists("berita/lama-{$width}.webp"));
            $this->assertTrue($this->disk->exists("berita/baru-{$width}.webp"));
        }

        $post->update(['cover_path' => null]);
        $this->assertNull($post->fresh()->cover_widths);
        $this->assertSame([], $this->disk->allFiles('berita'));

        $this->putJpeg('berita/lain.jpg', 1200, 675);
        Post::factory()->create(['cover_path' => 'berita/lain.jpg'])->delete();
        $this->assertSame([], $this->disk->allFiles('berita'));
    }

    public function test_file_rusak_tidak_menggagalkan_penyimpanan(): void
    {
        Log::spy();
        $this->disk->put('berita/rusak.jpg', 'bukan gambar');

        $post = Post::factory()->create(['cover_path' => 'berita/rusak.jpg']);

        $this->assertNull($post->fresh()->cover_widths);
        Log::shouldHaveReceived('warning')->once();
        $this->get('/berita/'.$post->slug)
            ->assertOk()
            ->assertSee('src="'.$this->disk->url('berita/rusak.jpg').'"', false)
            ->assertDontSee('sizes="(min-width: 768px) 736px', false); // tanpa <source> sampul (logo navbar punya <source> sendiri)
    }

    private function putJpeg(string $path, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 18, 96, 165));
        ob_start();
        imagejpeg($image, null, 85);
        $this->disk->put($path, (string) ob_get_clean());
    }
}
