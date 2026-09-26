<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            // Tulisan terkait (opsional). Tulisan dihapus → album tetap ada, tautannya saja yang hilang.
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->text('description')->nullable(); // teks polos
            $table->date('event_date'); // tanggal kegiatan — urutan tampil di halaman galeri
            // Sama dengan posts: null = draft; di masa depan = terjadwal; sudah lewat = terbit. UTC.
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('albums');
    }
};
