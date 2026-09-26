<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: kategori yang masih dipakai tulisan tidak bisa terhapus tanpa sengaja.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->string('excerpt', 300)->nullable(); // kosong → diambil dari awal isi
            $table->longText('content'); // HTML dari RichEditor Filament; dirender lewat toHtml() (disanitasi)
            $table->string('cover_path')->nullable(); // disk public
            // null = draft; di masa depan = terjadwal; sudah lewat = terbit. Disimpan UTC.
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
