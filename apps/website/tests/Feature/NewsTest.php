<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Database\Seeders\DemoNewsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_kategori_bawaan_berita_dan_kegiatan_tersedia(): void
    {
        $this->assertSame(['berita', 'kegiatan'], Category::query()->orderBy('sort_order')->pluck('slug')->all());
    }

    public function test_daftar_hanya_menampilkan_yang_terbit_terbaru_dulu(): void
    {
        Post::factory()->create(['title' => 'Tulisan Lama', 'published_at' => now()->subDays(10)]);
        Post::factory()->create(['title' => 'Tulisan Baru', 'published_at' => now()->subDay()]);
        Post::factory()->draft()->create(['title' => 'Masih Draft']);
        Post::factory()->scheduled()->create(['title' => 'Masih Terjadwal']);

        $this->get('/berita')
            ->assertOk()
            ->assertSeeInOrder(['Tulisan Baru', 'Tulisan Lama'])
            ->assertDontSee('Masih Draft')
            ->assertDontSee('Masih Terjadwal');
    }

    public function test_draft_dan_terjadwal_404_bila_dibuka_langsung(): void
    {
        $this->get('/berita/'.Post::factory()->draft()->create()->slug)->assertNotFound();
        $this->get('/berita/'.Post::factory()->scheduled()->create()->slug)->assertNotFound();
    }

    public function test_isi_tulisan_disanitasi_dari_xss(): void
    {
        $post = Post::factory()->create([
            'content' => '<p>Paragraf aman</p><script>alert("xss")</script><p onclick="alert(1)">Klik</p><a href="javascript:alert(1)">tautan</a><img src="x" onerror="alert(1)">',
        ]);

        $html = $this->get('/berita/'.$post->slug)->assertOk()->assertSee('Paragraf aman')->getContent();

        $this->assertStringNotContainsString('alert("xss")', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_filter_kategori_tag_dan_pencarian(): void
    {
        $kegiatan = Category::query()->where('slug', 'kegiatan')->sole();
        $ctf = Tag::factory()->create(['name' => 'CTF']);
        $a = Post::factory()->for($kegiatan)->create(['title' => 'Latihan Soal Kriptografi']);
        $a->tags()->attach($ctf);
        Post::factory()->create(['title' => 'Rapat Pengurus Bulanan']);

        $this->get('/berita?kategori=kegiatan')->assertSee('Latihan Soal Kriptografi')->assertDontSee('Rapat Pengurus Bulanan');
        $this->get('/berita?tag=ctf')->assertSee('Latihan Soal Kriptografi')->assertDontSee('Rapat Pengurus Bulanan');
        $this->get('/berita?q=kripto')->assertSee('Latihan Soal Kriptografi')->assertDontSee('Rapat Pengurus Bulanan');
        $this->get('/berita?q=tidakadaapapun')->assertSee('Tidak ada tulisan yang cocok');
    }

    public function test_status_dan_ringkasan(): void
    {
        $this->assertSame(PostStatus::Draft, Post::factory()->draft()->make()->status());
        $this->assertSame(PostStatus::Scheduled, Post::factory()->scheduled()->make()->status());
        $this->assertSame(PostStatus::Published, Post::factory()->make()->status());

        $this->assertSame('Ringkasan manual', Post::factory()->make(['excerpt' => 'Ringkasan manual'])->summary);
        $this->assertSame('Tom & Jerry di CTF', Post::factory()->make(['excerpt' => null, 'content' => '<p>Tom &amp; Jerry <strong>di</strong> CTF</p>'])->summary);
        $this->assertSame('Kalimat satu. Kalimat dua.', Post::factory()->make(['excerpt' => null, 'content' => '<p>Kalimat satu.</p><p>Kalimat dua.</p>'])->summary);
    }

    public function test_file_sampul_dihapus_saat_diganti_dan_saat_tulisan_dihapus(): void
    {
        $disk = Storage::fake(Post::COVER_DISK);
        $disk->put('berita/lama.jpg', 'x');
        $disk->put('berita/baru.jpg', 'x');
        $post = Post::factory()->create(['cover_path' => 'berita/lama.jpg']);

        $post->update(['cover_path' => 'berita/baru.jpg']);
        $disk->assertMissing('berita/lama.jpg');

        $post->delete();
        $disk->assertMissing('berita/baru.jpg');
    }

    public function test_halaman_admin_berita_bisa_dibuka(): void
    {
        $this->actingAsAdmin();

        foreach (['/admin/posts', '/admin/posts/create', '/admin/categories', '/admin/tags'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    /** Admin mengisi jam terbit dalam WIB; database menyimpan UTC (WIB = UTC+7). */
    public function test_admin_membuat_tulisan_terjadwal_dengan_jam_wib(): void
    {
        $this->actingAsAdmin();
        $tag = Tag::factory()->create(['name' => 'Pelatihan']);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Jadwal Pelatihan Oktober',
                'slug' => 'jadwal-pelatihan-oktober',
                'content' => '<p>Isi pengumuman.</p>',
                'category_id' => Category::query()->where('slug', 'kegiatan')->value('id'),
                'tags' => [$tag->id],
                'published_at' => '2030-10-01 08:00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug', 'jadwal-pelatihan-oktober')->sole();
        $this->assertSame('2030-10-01 01:00:00', $post->published_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame(PostStatus::Scheduled, $post->status());
        $this->assertSame(['Pelatihan'], $post->tags->pluck('name')->all());
    }

    public function test_seeder_contoh_hanya_untuk_lokal(): void
    {
        Storage::fake(Post::COVER_DISK);

        $this->seed(DemoNewsSeeder::class);
        $this->assertSame(6, Post::query()->published()->count());
        $this->seed(DemoNewsSeeder::class); // dijalankan ulang: tidak menggandakan
        $this->assertSame(8, Post::query()->count());

        // Di production, db:seed sendiri sudah meminta konfirmasi; --force melewatinya supaya yang
        // teruji adalah penjaga di dalam seeder.
        Post::query()->delete();
        $this->app['env'] = 'production';
        $this->artisan('db:seed', ['--class' => DemoNewsSeeder::class, '--force' => true])
            ->expectsOutputToContain('hanya untuk lokal');
        $this->assertSame(0, Post::query()->count());
    }
}
