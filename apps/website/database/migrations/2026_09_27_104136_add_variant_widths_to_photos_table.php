<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            // Lebar varian WebP foto yang sudah dibuat, mis. [400, 800, 1200] (App\Models\Concerns\HasResponsiveImage).
            // Null = belum ada varian; halaman memakai thumbnail/foto JPEG saja.
            $table->json('variant_widths')->nullable()->after('thumb_path');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('variant_widths');
        });
    }
};
