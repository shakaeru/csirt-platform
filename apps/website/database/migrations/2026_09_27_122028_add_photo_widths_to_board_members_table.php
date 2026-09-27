<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('board_members', function (Blueprint $table) {
            // Lebar varian WebP foto pengurus, mis. [128, 256, 384] (App\Models\Concerns\HasResponsiveImage).
            // Null = belum ada varian; kartu memakai foto JPEG 600×600 saja.
            $table->json('photo_widths')->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('board_members', function (Blueprint $table) {
            $table->dropColumn('photo_widths');
        });
    }
};
