<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_members', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: periode/divisi yang masih punya anggota tidak bisa terhapus tanpa sengaja
            // (riwayat kepengurusan per periode harus tetap utuh).
            $table->foreignId('board_period_id')->constrained()->restrictOnDelete();
            $table->string('section', 10); // App\Enums\BoardSection
            $table->foreignId('division_id')->nullable()->constrained()->restrictOnDelete(); // wajib bila section = divisi
            $table->string('name');
            $table->string('position'); // jabatan, mis. "Ketua Umum", "Kepala Divisi"
            $table->string('photo_path')->nullable(); // disk public
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['board_period_id', 'section', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_members');
    }
};
