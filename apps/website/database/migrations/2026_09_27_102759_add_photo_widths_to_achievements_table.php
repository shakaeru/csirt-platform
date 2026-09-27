<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            // Lebar varian WebP foto yang sudah dibuat, mis. [400, 800, 1200] (App\Models\Concerns\HasResponsiveImage).
            // Null = belum ada varian; halaman memakai file foto asli saja.
            $table->json('photo_widths')->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropColumn('photo_widths');
        });
    }
};
