<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * Data CONTOH untuk melihat halaman Galeri di lokal:
 *
 *     php artisan db:seed --class=DemoGallerySeeder
 *
 * Tidak dipanggil DatabaseSeeder dan menolak berjalan di production. Semua album contoh ber-slug
 * "contoh-…" dan berjudul "Contoh: …"; menjalankan ulang menghapus contoh lama lebih dulu. Album
 * pertama ditautkan ke tulisan contoh-1 bila DemoNewsSeeder sudah dijalankan. Cara menghapus
 * semua contoh: lihat apps/website/README.md.
 */
class DemoGallerySeeder extends Seeder
{
    /** Warna "foto" contoh — nuansa brand, supaya tiap foto bisa dibedakan di lightbox. */
    private const COLORS = [[7, 61, 83], [18, 96, 165], [142, 189, 69], [36, 52, 71], [197, 215, 61], [64, 132, 196]];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('DemoGallerySeeder hanya untuk lokal, dibatalkan di production.');

            return;
        }

        // Hapus contoh lama (event model ikut menghapus folder fotonya).
        Album::query()->where('slug', 'like', 'contoh-%')->get()->each->delete();

        $post = Post::query()->where('slug', 'contoh-1')->first();

        $albums = [
            ['Contoh: Pelatihan Rutin Keamanan Web', 'contoh-pelatihan-web', 2, 9, $post?->id, "Dokumentasi pelatihan rutin pekan ini.\nFoto dan keterangan hanya contoh."],
            ['Contoh: Kompetisi CTF Nasional', 'contoh-ctf-nasional', 20, 7, null, null],
            ['Contoh: Workshop Forensik Digital', 'contoh-workshop-forensik', 45, 5, null, null],
        ];

        foreach ($albums as $index => [$title, $slug, $daysAgo, $count, $postId, $description]) {
            $album = Album::query()->create([
                'post_id' => $postId,
                'title' => $title,
                'slug' => $slug,
                'description' => $description,
                'event_date' => now()->subDays($daysAgo)->toDateString(),
                'published_at' => now()->subDays($daysAgo),
            ]);
            $this->addPhotos($album, $count, colorOffset: $index * 2);
        }

        // Draft: ada di admin, TIDAK boleh tampil di halaman publik.
        $draft = Album::query()->create(['title' => 'Contoh: Album Draft', 'slug' => 'contoh-album-draft', 'event_date' => now()->toDateString(), 'published_at' => null]);
        $this->addPhotos($draft, 2);

        $this->command->info('Data contoh Galeri dibuat: 3 album terbit, 1 draft'.($post ? ' (album pertama ditautkan ke tulisan contoh-1).' : '.'));
    }

    private function addPhotos(Album $album, int $count, int $colorOffset = 0): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $source = tempnam(sys_get_temp_dir(), 'galeri-contoh-');
            file_put_contents($source, $this->image($i, $colorOffset));
            $album->addPhoto($source, $i === 1 ? 'Contoh keterangan foto, diisi admin di panel.' : null);
            unlink($source);
        }
    }

    /**
     * "Foto" contoh bernomor, bergantian lanskap/potret/persegi supaya grid dan lightbox teruji
     * dengan berbagai orientasi. Digambar kecil lalu diperbesar tanpa haluskan → angka tetap tajam.
     */
    private function image(int $number, int $colorOffset = 0): string
    {
        [$width, $height] = [[160, 107], [107, 160], [140, 140]][($number - 1) % 3];
        $small = imagecreatetruecolor($width, $height);
        imagefill($small, 0, 0, imagecolorallocate($small, ...self::COLORS[($number - 1 + $colorOffset) % count(self::COLORS)]));
        imagefilledrectangle($small, 0, $height - 12, $width - 1, $height - 1, imagecolorallocate($small, 197, 215, 61));
        imagestring($small, 5, intdiv($width, 2) - 8, intdiv($height, 2) - 8, '#'.$number, imagecolorallocate($small, 255, 255, 255));

        $image = imagecreatetruecolor($width * 10, $height * 10);
        imagecopyresized($image, $small, 0, 0, 0, 0, $width * 10, $height * 10, $width, $height);
        ob_start();
        imagejpeg($image, null, 85);

        return (string) ob_get_clean();
    }
}
