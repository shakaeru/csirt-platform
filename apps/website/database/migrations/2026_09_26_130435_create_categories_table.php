<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('slug', 60)->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Kategori bawaan (keputusan: Berita & Kegiatan = satu jenis tulisan, dibedakan kategori).
        // Kategori lain bisa ditambah lewat panel admin.
        DB::table('categories')->insert([
            ['name' => 'Berita', 'slug' => 'berita', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kegiatan', 'slug' => 'kegiatan', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
