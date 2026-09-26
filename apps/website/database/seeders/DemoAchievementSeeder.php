<?php

namespace Database\Seeders;

use App\Enums\AchievementCategory;
use App\Enums\AchievementLevel;
use App\Models\Achievement;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Data CONTOH untuk melihat halaman Prestasi di lokal:
 *
 *     php artisan db:seed --class=DemoAchievementSeeder
 *
 * Tidak dipanggil DatabaseSeeder dan menolak berjalan di production. Semua prestasi contoh
 * ber-nama kompetisi "Contoh: …" dengan nama tim/anggota fiktif; menjalankan ulang menghapus
 * contoh lama lebih dulu. Satu prestasi ditautkan ke tulisan contoh-2 bila DemoNewsSeeder sudah
 * dijalankan. Cara menghapus semua contoh: lihat apps/website/README.md.
 */
class DemoAchievementSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('DemoAchievementSeeder hanya untuk lokal — dibatalkan di production.');

            return;
        }

        // Hapus contoh lama (event model ikut menghapus file fotonya).
        Achievement::query()->where('competition', 'like', 'Contoh:%')->get()->each->delete();

        $post = Post::query()->where('slug', 'contoh-2')->first();
        $year = now()->year;

        $items = [
            ['Contoh: Kompetisi CTF Nasional', 'Juara 2', AchievementCategory::Ctf, AchievementLevel::Nasional, 'Penyelenggara Contoh', "{$year}-08-17", 'Tim Contoh Alfa', "Anggota Satu\nAnggota Dua\nAnggota Tiga", 'Kategori mahasiswa, 350 tim peserta.', 'https://ctftime.org/', $post?->id, true],
            ['Contoh: CTF Regional Sumatera', 'Juara 1', AchievementCategory::Ctf, AchievementLevel::Regional, null, "{$year}-05-03", 'Tim Contoh Beta', "Anggota Empat\nAnggota Lima", null, null, null, false],
            ['Contoh: Lomba Esai Keamanan Siber', 'Finalis', AchievementCategory::Lomba, AchievementLevel::Nasional, 'Kampus Contoh', "{$year}-02-20", null, 'Anggota Enam', null, null, null, false],
            ['Contoh: International CTF Qualifier', 'Peringkat 42 dari 900 tim', AchievementCategory::Ctf, AchievementLevel::Internasional, null, ($year - 1).'-11-12', 'Tim Contoh Alfa', "Anggota Satu\nAnggota Tujuh", null, 'https://ctftime.org/', null, false],
            ['Contoh: CTF Internal PCR', 'Juara 3', AchievementCategory::Ctf, AchievementLevel::Kampus, 'UKM CSIRT PCR', ($year - 1).'-06-30', 'Tim Contoh Gamma', "Anggota Delapan\nAnggota Sembilan\nAnggota Sepuluh", null, null, null, true],
        ];

        foreach ($items as $index => [$competition, $result, $category, $level, $organizer, $date, $team, $members, $description, $url, $postId, $withPhoto]) {
            Achievement::query()->create([
                'competition' => $competition,
                'result' => $result,
                'category' => $category,
                'level' => $level,
                'organizer' => $organizer,
                'achieved_on' => $date,
                'team_name' => $team,
                'members' => $members,
                'description' => $description,
                'result_url' => $url,
                'post_id' => $postId,
                'photo_path' => $withPhoto ? $this->photo('contoh-'.($index + 1)) : null,
                'published_at' => now()->subDay(),
            ]);
        }

        // Draft: ada di admin, TIDAK boleh tampil di halaman publik.
        Achievement::query()->create([
            'competition' => 'Contoh: Prestasi Draft', 'result' => 'Juara 1', 'category' => AchievementCategory::Ctf,
            'level' => AchievementLevel::Nasional, 'achieved_on' => now()->toDateString(), 'published_at' => null,
        ]);

        $this->command->info('Data contoh Prestasi dibuat: 5 terbit, 1 draft'.($post ? ' (satu ditautkan ke tulisan contoh-2).' : '.'));
    }

    /** Foto contoh 1200×675: blok warna brand polos (tanpa aset eksternal). */
    private function photo(string $name): string
    {
        $image = imagecreatetruecolor(1200, 675);
        imagefill($image, 0, 0, imagecolorallocate($image, 7, 61, 83));
        imagefilledrectangle($image, 0, 575, 1199, 674, imagecolorallocate($image, 197, 215, 61));
        ob_start();
        imagejpeg($image, null, 85);
        $path = "prestasi/{$name}.jpg";
        Storage::disk(Achievement::PHOTO_DISK)->put($path, ob_get_clean(), 'public');

        return $path;
    }
}
