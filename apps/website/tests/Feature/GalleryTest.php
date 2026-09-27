<?php

namespace Tests\Feature;

use App\Filament\Resources\Albums\AlbumResource;
use App\Filament\Resources\Albums\Pages\CreateAlbum;
use App\Filament\Resources\Albums\Pages\EditAlbum;
use App\Filament\Resources\Albums\Pages\ListAlbums;
use App\Filament\Resources\Albums\RelationManagers\PhotosRelationManager;
use App\Models\Album;
use App\Models\Photo;
use App\Models\Post;
use App\Support\Images\ResponsiveVariants;
use Database\Seeders\DemoGallerySeeder;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestImages;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use CreatesTestImages, RefreshDatabase;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(Album::DISK);
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), array_filter($this->tempFiles, is_file(...)));
        parent::tearDown();
    }

    public function test_galeri_hanya_menampilkan_album_terbit_berisi_foto_kegiatan_terbaru_dulu(): void
    {
        Album::factory()->withPhotos()->create(['title' => 'Album Lama', 'event_date' => '2026-01-10']);
        Album::factory()->withPhotos()->create(['title' => 'Album Baru', 'event_date' => '2026-08-17']);
        Album::factory()->create(['title' => 'Album Kosong']);
        Album::factory()->draft()->withPhotos()->create(['title' => 'Album Draft']);
        Album::factory()->scheduled()->withPhotos()->create(['title' => 'Album Terjadwal']);

        $this->get('/galeri')
            ->assertOk()
            ->assertSeeInOrder(['Album Baru', '17 Agustus 2026', '3 foto', 'Album Lama', '10 Januari 2026'])
            ->assertDontSee('Album Kosong')
            ->assertDontSee('Album Draft')
            ->assertDontSee('Album Terjadwal');
    }

    public function test_album_draft_dan_terjadwal_404_bila_dibuka_langsung(): void
    {
        $this->get('/galeri/'.Album::factory()->draft()->withPhotos()->create()->slug)->assertNotFound();
        $this->get('/galeri/'.Album::factory()->scheduled()->withPhotos()->create()->slug)->assertNotFound();
    }

    public function test_halaman_album_menampilkan_foto_berurutan_untuk_lightbox(): void
    {
        $album = Album::factory()->create(['description' => "Baris satu\n<script>alert(1)</script>"]);
        Photo::factory()->for($album)->create(['sort_order' => 2, 'caption' => 'Foto kedua', 'width' => 1333, 'height' => 2000]);
        Photo::factory()->for($album)->create(['sort_order' => 1, 'caption' => 'Foto pertama']);

        $this->get('/galeri/'.$album->slug)
            ->assertOk()
            ->assertSee('data-gallery', false)
            ->assertSeeInOrder(['data-caption="Foto pertama"', 'data-caption="Foto kedua"'], false)
            ->assertSee('data-pswp-width="1333" data-pswp-height="2000"', false)
            ->assertSee('Baris satu')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_album_dan_tulisan_saling_menautkan_hanya_bila_keduanya_terbit(): void
    {
        $post = Post::factory()->create(['title' => 'Laporan Latihan CTF']);
        $album = Album::factory()->for($post)->withPhotos(8)->create(['title' => 'Foto Latihan CTF']);
        Album::factory()->for($post)->draft()->withPhotos()->create(['title' => 'Album Belum Terbit']);

        $html = $this->get('/berita/'.$post->slug)
            ->assertOk()
            ->assertSee('Dokumentasi: Foto Latihan CTF')
            ->assertSee('Lihat semua 8 foto')
            ->assertDontSee('Album Belum Terbit')
            ->getContent();
        $this->assertSame(6, substr_count($html, '/storage/galeri/test/thumb/'), 'cuplikan dibatasi 6 foto');

        $this->get('/galeri/'.$album->slug)->assertSee('Baca tulisannya: Laporan Latihan CTF');

        $post->update(['published_at' => null]);
        $this->get('/galeri/'.$album->slug)->assertOk()->assertDontSee('Baca tulisannya');
    }

    public function test_foto_diluruskan_diperkecil_dan_metadata_dibuang(): void
    {
        $album = Album::factory()->create();
        $disk = Storage::disk(Album::DISK);

        // Orientation 6: 3000×2000 tersimpan miring → tegak 2000×3000 → diperkecil 1333×2000.
        $photo = $album->addPhoto($this->tempFile($this->jpegWithOrientation(3000, 2000, 6)), 'Keterangan');

        $this->assertSame([1333, 2000], [$photo->width, $photo->height]);
        $this->assertSame([1333, 2000, IMAGETYPE_JPEG], array_slice(getimagesize($disk->path($photo->path)), 0, 3));
        $this->assertSame([533, 800], array_slice(getimagesize($disk->path($photo->thumb_path)), 0, 2));
        $this->assertStringStartsWith("galeri/{$album->id}/", $photo->path);
        $this->assertStringNotContainsString('Exif', $disk->get($photo->path));
        $this->assertStringNotContainsString('Exif', $disk->get($photo->thumb_path));

        $image = imagecreatefromjpeg($disk->path($photo->path));
        $this->assertSame('merah', $this->dominant($image, 666, 300)); // kiri sumber → atas
        $this->assertSame('biru', $this->dominant($image, 666, 1700));

        // Foto kecil tidak diperbesar; masuk di urutan terakhir.
        $small = $album->addPhoto($this->tempFile($this->jpegWithOrientation(640, 480, 1)));
        $this->assertSame([640, 480], [$small->width, $small->height]);
        $this->assertSame($photo->sort_order + 1, $small->sort_order);
    }

    public function test_file_ditolak_bila_bukan_gambar_atau_resolusi_terlalu_besar(): void
    {
        $album = Album::factory()->create();

        try {
            $album->addPhoto($this->tempFile('<svg onload="alert(1)"></svg>'), name: 'bukan-foto.jpg');
            $this->fail('File bukan gambar seharusnya ditolak.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('bukan-foto.jpg', $e->getMessage());
        }

        // 4000×3000 butuh ±120 MB memori GD; batasi memory_limit supaya tidak cukup.
        $big = $this->tempFile($this->jpegWithOrientation(4000, 3000, 1));
        $limit = ini_get('memory_limit');
        ini_set('memory_limit', (string) (memory_get_usage() + 50 * 1024 * 1024));
        try {
            $album->addPhoto($big, name: 'besar.jpg');
            $this->fail('Foto beresolusi terlalu besar seharusnya ditolak.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('resolusi terlalu besar', $e->getMessage());
        } finally {
            ini_set('memory_limit', $limit);
        }

        $this->assertSame(0, $album->photos()->count());
        $this->assertSame([], Storage::disk(Album::DISK)->allFiles());
    }

    public function test_file_foto_terhapus_saat_foto_atau_album_dihapus(): void
    {
        $disk = Storage::disk(Album::DISK);
        $album = Album::factory()->create();
        $first = $album->addPhoto($this->tempFile($this->jpegWithOrientation(120, 80, 1)));
        $second = $album->addPhoto($this->tempFile($this->jpegWithOrientation(120, 80, 1)));

        $first->delete();
        $disk->assertMissing([$first->path, $first->thumb_path]);
        $disk->assertExists([$second->path, $second->thumb_path]);

        $album->delete();
        $this->assertFalse($disk->directoryExists($album->photoDirectory()));
        $this->assertSame(0, Photo::query()->count());
    }

    public function test_tulisan_dihapus_album_tetap_ada_tanpa_tautan(): void
    {
        $post = Post::factory()->create();
        $album = Album::factory()->for($post)->create();

        $post->delete();

        $this->assertNull($album->fresh()->post_id);
    }

    public function test_admin_mengunggah_mengubah_keterangan_dan_menghapus_foto(): void
    {
        $this->actingAsAdmin();
        $this->withoutDefer(); // varian (biasanya setelah respons) dibuat langsung supaya bisa diperiksa
        $album = Album::factory()->draft()->create();
        $manager = fn () => Livewire::test(PhotosRelationManager::class, ['ownerRecord' => $album, 'pageClass' => EditAlbum::class]);

        $manager()
            ->callTableAction('unggah', data: ['files' => [
                UploadedFile::fake()->createWithContent('satu.jpg', $this->jpegWithOrientation(2400, 1600, 1)),
                UploadedFile::fake()->createWithContent('dua.jpg', $this->jpegWithOrientation(800, 600, 1)),
            ]])
            ->assertHasNoTableActionErrors()
            ->assertNotified('2 foto ditambahkan');

        $photos = $album->photos()->ordered()->get();
        $this->assertSame([[2000, 1333], [800, 600]], $photos->map(fn (Photo $photo): array => [$photo->width, $photo->height])->all());
        $this->assertSame([[400, 800, 1200], [400, 800]], $photos->pluck('variant_widths')->all());

        $manager()->call('updateTableColumnState', 'caption', (string) $photos[0]->getKey(), 'Pembukaan pelatihan');
        $this->assertSame('Pembukaan pelatihan', $photos[0]->fresh()->caption);

        // Hapus massal lewat tabel: file ikut terhapus (DeleteBulkAction menghapus per model).
        $manager()->callTableBulkAction(DeleteBulkAction::class, $photos);
        $this->assertSame(0, $album->photos()->count());
        $this->assertSame([], Storage::disk(Album::DISK)->allFiles());
    }

    public function test_admin_tidak_bisa_mengunggah_svg(): void
    {
        $this->actingAsAdmin();
        $album = Album::factory()->create();

        Livewire::test(PhotosRelationManager::class, ['ownerRecord' => $album, 'pageClass' => EditAlbum::class])
            ->callTableAction('unggah', data: ['files' => [
                UploadedFile::fake()->createWithContent('gambar.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>'),
            ]])
            ->assertHasTableActionErrors(['files']);

        $this->assertSame(0, $album->photos()->count());
    }

    public function test_halaman_admin_album_dan_filter_status(): void
    {
        $this->actingAsAdmin();
        $published = Album::factory()->withPhotos()->create();
        $draft = Album::factory()->draft()->create();

        foreach ([AlbumResource::getUrl('index'), AlbumResource::getUrl('create'), AlbumResource::getUrl('edit', ['record' => $published])] as $url) {
            $this->get($url)->assertOk();
        }

        Livewire::test(ListAlbums::class)
            ->filterTable('status', 'draft')
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$published]);
    }

    /** Kolom date tanpa jam: tidak boleh ikut dikonversi WIB → UTC (bisa mundur sehari). */
    public function test_admin_membuat_album_tanggal_kegiatan_tidak_bergeser(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateAlbum::class)
            ->fillForm([
                'title' => 'Malam Keakraban',
                'slug' => 'malam-keakraban',
                'event_date' => '2026-10-01',
                'published_at' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $album = Album::query()->where('slug', 'malam-keakraban')->sole();
        $this->assertSame('2026-10-01', $album->event_date->toDateString());
        $this->assertNull($album->published_at);
    }

    public function test_seeder_contoh_hanya_untuk_lokal(): void
    {
        $this->seed(DemoGallerySeeder::class);
        $this->assertSame(3, Album::query()->published()->count());
        $this->seed(DemoGallerySeeder::class); // dijalankan ulang: tidak menggandakan
        $this->assertSame(4, Album::query()->count());
        $this->assertSame(23, Photo::query()->count());

        Album::query()->get()->each->delete();
        $this->app['env'] = 'production';
        $this->artisan('db:seed', ['--class' => DemoGallerySeeder::class, '--force' => true])
            ->expectsOutputToContain('hanya untuk lokal');
        $this->assertSame(0, Album::query()->count());
    }

    /** Foto 3:2 di kotak persegi: sizes dikali 1.5; lightbox memakai srcset yang sama + JPEG penuh. */
    public function test_foto_dibuatkan_varian_webp_untuk_grid_kartu_album_dan_lightbox(): void
    {
        $this->withoutDefer();
        $album = Album::factory()->create();
        $disk = Storage::disk(Album::DISK);

        $photo = $album->addPhoto($this->tempFile($this->jpegWithOrientation(2400, 1600, 1)));

        $this->assertSame([400, 800, 1200], $photo->fresh()->variant_widths);
        foreach ([400 => 267, 800 => 533, 1200 => 800] as $width => $height) {
            $this->assertSame([$width, $height, IMAGETYPE_WEBP], array_slice(getimagesize($disk->path(ResponsiveVariants::path($photo->path, $width))), 0, 3));
        }

        $photo->refresh();
        $srcset = $disk->url(ResponsiveVariants::path($photo->path, 400)).' 400w, '
            .$disk->url(ResponsiveVariants::path($photo->path, 800)).' 800w, '
            .$disk->url(ResponsiveVariants::path($photo->path, 1200)).' 1200w, '
            .$photo->url.' 2000w';
        $this->assertSame($srcset, $photo->srcset);
        $this->assertSame(1.5, $photo->cropFactor());

        $this->get('/galeri/'.$album->slug)
            ->assertSee('data-pswp-srcset="'.$srcset.'"', false)
            ->assertSee('<source type="image/webp" srcset="'.$srcset.'" sizes="(min-width: 1280px) 455px, (min-width: 1024px) calc((100vw - 68px) / 4 * 1.5),', false)
            ->assertSee('src="'.$photo->thumb_url.'"', false);

        // Kartu album 4:3: faktor 1.5 / (4/3) = 1.13.
        $this->get('/galeri')->assertSee('sizes="(min-width: 1280px) 452px, (min-width: 1024px) calc((100vw - 80px) / 3 * 1.13),', false);

        // Foto dihapus: foto penuh, thumbnail, dan variannya ikut hilang.
        $photo->delete();
        $this->assertSame([], $disk->allFiles("galeri/{$album->id}"));
    }

    /** Foto potret: lebarnya pas di kotak persegi (faktor 1); JPEG penuh 1333 px jadi kandidat terbesar. */
    public function test_foto_potret_tidak_memperbesar_sizes(): void
    {
        $this->withoutDefer();
        $album = Album::factory()->create();

        $photo = $album->addPhoto($this->tempFile($this->jpegWithOrientation(1600, 2400, 1)))->fresh();

        $this->assertSame([1333, 2000], [$photo->width, $photo->height]);
        $this->assertSame([400, 800, 1200], $photo->variant_widths);
        $this->assertSame(1.0, $photo->cropFactor());
        $this->assertStringEndsWith($photo->url.' 1333w', $photo->srcset);
        $this->get('/galeri/'.$album->slug)->assertSee('sizes="(min-width: 1280px) 303px, (min-width: 1024px) calc((100vw - 68px) / 4 * 1),', false);
    }

    /**
     * Varian galeri dibuat setelah respons (defer), bukan saat unggah: unggah 30 foto di VPS tidak
     * boleh ikut menunggu encode WebP. Sampai selesai, halaman memakai thumbnail JPEG.
     */
    public function test_varian_foto_dibuat_setelah_respons_dan_file_tetap_bersih(): void
    {
        $album = Album::factory()->create();
        $disk = Storage::disk(Album::DISK);

        $photo = $album->addPhoto($this->tempFile($this->jpegWithOrientation(2400, 1600, 1)));
        $removed = $album->addPhoto($this->tempFile($this->jpegWithOrientation(800, 600, 1)));

        $this->assertNull($photo->fresh()->variant_widths);
        $this->assertFalse($disk->exists(ResponsiveVariants::path($photo->path, 400)));
        $this->assertCount(2, app(DeferredCallbackCollection::class));

        // Foto kedua dihapus sebelum gilirannya: callback-nya dilewati tanpa error.
        $removed->delete();

        // Halaman dirender sebelum varian ada (JPEG saja); antrean defer jalan setelah respons.
        $this->get('/galeri/'.$album->slug)
            ->assertOk()
            ->assertSee('src="'.$photo->thumb_url.'"', false)
            ->assertDontSee('data-pswp-srcset', false);

        $this->assertSame([400, 800, 1200], $photo->fresh()->variant_widths);
        $this->assertTrue($disk->exists(ResponsiveVariants::path($photo->path, 1200)));
        $this->assertCount(5, $disk->allFiles("galeri/{$album->id}")); // foto, thumbnail, 3 varian

        // Model di memori basi (variant_widths masih null): varian tetap ikut terhapus.
        $photo->delete();
        $this->assertSame([], $disk->allFiles("galeri/{$album->id}"));
    }

    private function tempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'galeri-test-');
        file_put_contents($path, $contents);

        return $this->tempFiles[] = $path;
    }
}
