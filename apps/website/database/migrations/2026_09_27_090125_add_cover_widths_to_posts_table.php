<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Lebar varian WebP sampul yang sudah dibuat, mis. [400, 800, 1200] (App\Support\Images\ResponsiveVariants).
            // Null = belum ada varian; halaman memakai file sampul asli saja.
            $table->json('cover_widths')->nullable()->after('cover_path');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('cover_widths');
        });
    }
};
