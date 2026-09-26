<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Data CONTOH untuk melihat halaman Berita & Kegiatan di lokal:
 *
 *     php artisan db:seed --class=DemoNewsSeeder
 *
 * Tidak dipanggil DatabaseSeeder dan menolak berjalan di production. Semua tulisan contoh
 * ber-slug "contoh-…" dan berjudul "Contoh: …"; menjalankan ulang menghapus contoh lama lebih
 * dulu. Cara menghapus semua contoh: lihat apps/website/README.md.
 */
class DemoNewsSeeder extends Seeder
{
    private const TAGS = ['CTF', 'Pelatihan', 'Keamanan Web', 'Forensik Digital'];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('DemoNewsSeeder hanya untuk lokal — dibatalkan di production.');

            return;
        }

        // Hapus contoh lama (event model ikut menghapus file sampulnya).
        Post::query()->where('slug', 'like', 'contoh-%')->get()->each->delete();

        $berita = Category::query()->where('slug', 'berita')->firstOrFail();
        $kegiatan = Category::query()->where('slug', 'kegiatan')->firstOrFail();
        $tags = collect(self::TAGS)->mapWithKeys(fn (string $name): array => [$name => Tag::query()->firstOrCreate(['name' => $name])]);

        $posts = [
            ['Contoh: Pelatihan Rutin Keamanan Web Pekan Ini', $kegiatan, 2, ['Pelatihan', 'Keamanan Web'], [18, 96, 165]],
            ['Contoh: Tim CSIRT PCR Ikut Kompetisi CTF Nasional', $berita, 5, ['CTF'], [7, 61, 83]],
            ['Contoh: Workshop Dasar Forensik Digital untuk Anggota Baru', $kegiatan, 9, ['Pelatihan', 'Forensik Digital'], [142, 189, 69]],
            ['Contoh: Pengumuman Rekrutmen Anggota Periode Baru', $berita, 14, [], null],
            ['Contoh: Dokumentasi Sesi Berbagi Write-up CTF', $kegiatan, 21, ['CTF'], null],
            ['Contoh: Tips Mengamankan Akun Kampus dengan 2FA', $berita, 30, ['Keamanan Web'], null],
        ];

        foreach ($posts as $index => [$title, $category, $daysAgo, $tagNames, $coverColor]) {
            $post = Post::query()->create([
                'category_id' => $category->id,
                'title' => $title,
                'slug' => 'contoh-'.($index + 1),
                'content' => $this->content($index === 0),
                'cover_path' => $coverColor ? $this->cover('contoh-'.($index + 1), $coverColor) : null,
                'published_at' => now()->subDays($daysAgo),
            ]);
            $post->tags()->sync($tags->only($tagNames)->pluck('id'));
        }

        // Draft dan terjadwal: ada di admin, TIDAK boleh tampil di halaman publik.
        Post::query()->create(['category_id' => $berita->id, 'title' => 'Contoh: Draft yang Belum Terbit', 'slug' => 'contoh-draft', 'content' => $this->content(false), 'published_at' => null]);
        Post::query()->create(['category_id' => $kegiatan->id, 'title' => 'Contoh: Kegiatan Terjadwal Minggu Depan', 'slug' => 'contoh-terjadwal', 'content' => $this->content(false), 'published_at' => now()->addWeek()]);

        $this->command->info('Data contoh Berita & Kegiatan dibuat: 6 terbit, 1 draft, 1 terjadwal.');
    }

    private function content(bool $rich): string
    {
        $intro = '<p>Ini adalah <strong>tulisan contoh</strong> untuk pratinjau tampilan halaman Berita &amp; Kegiatan. Isi sebenarnya ditulis pengurus lewat panel admin.</p>'
            .'<p>Paragraf kedua menunjukkan jarak antarparagraf dan panjang baris yang nyaman dibaca di layar lebar maupun ponsel.</p>';

        if (! $rich) {
            return $intro;
        }

        return $intro
            .'<h2>Materi yang dibahas</h2>'
            .'<ul><li>Pengenalan OWASP Top 10</li><li>Praktik <em>input validation</em></li><li>Studi kasus kerentanan XSS</li></ul>'
            .'<blockquote><p>Keamanan bukan produk, melainkan proses.</p></blockquote>'
            .'<h3>Contoh perintah</h3>'
            .'<pre><code>curl -I https://csirt.pcr.ac.id</code></pre>'
            .'<p>Informasi lebih lanjut: <a href="https://owasp.org/www-project-top-ten/">OWASP Top 10</a>.</p>';
    }

    /** Sampul contoh 1200×675: blok warna brand polos (tanpa aset eksternal). */
    private function cover(string $slug, array $rgb): string
    {
        $image = imagecreatetruecolor(1200, 675);
        imagefill($image, 0, 0, imagecolorallocate($image, ...$rgb));
        imagefilledrectangle($image, 0, 575, 1199, 674, imagecolorallocate($image, 197, 215, 61));
        ob_start();
        imagejpeg($image, null, 85);
        $path = "berita/{$slug}.jpg";
        Storage::disk(Post::COVER_DISK)->put($path, ob_get_clean(), 'public');

        return $path;
    }
}
