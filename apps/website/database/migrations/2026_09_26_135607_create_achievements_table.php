<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('competition', 200); // nama kompetisi, mis. "Cyber Jawara 2026"
            $table->string('result', 100); // capaian, mis. "Juara 2", "Finalis", "Peringkat 12 dari 350 tim"
            $table->string('category', 20); // App\Enums\AchievementCategory
            $table->string('level', 20); // App\Enums\AchievementLevel
            $table->string('organizer', 150)->nullable();
            $table->date('achieved_on'); // tanggal pengumuman/pelaksanaan — pengelompokan per tahun
            $table->string('team_name', 100)->nullable();
            $table->text('members')->nullable(); // nama anggota, satu per baris (teks bebas)
            $table->text('description')->nullable(); // teks polos
            $table->string('photo_path')->nullable(); // disk public, prestasi/…
            $table->string('result_url', 500)->nullable(); // hanya http/https (divalidasi di form)
            // Tulisan terkait (opsional). Tulisan dihapus → prestasi tetap ada, tautannya saja yang hilang.
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            // Sama dengan posts/albums: null = draft; di masa depan = terjadwal; sudah lewat = terbit. UTC.
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();

            $table->index('achieved_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
