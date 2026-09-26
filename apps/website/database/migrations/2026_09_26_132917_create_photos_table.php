<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            // File foto ikut dihapus lewat event model Album/Photo (cascade DB saja tidak menyentuh disk).
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            $table->string('path'); // disk public, galeri/{album_id}/…; sisi terpanjang maks. 2000 px
            $table->string('thumb_path'); // galeri/{album_id}/thumb/…; maks. 800 px
            $table->unsignedSmallInteger('width'); // ukuran `path` — dibutuhkan lightbox PhotoSwipe
            $table->unsignedSmallInteger('height');
            $table->string('caption', 200)->nullable();
            $table->unsignedInteger('sort_order')->default(0); // foto pertama = sampul album
            $table->timestamps();

            $table->index(['album_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
