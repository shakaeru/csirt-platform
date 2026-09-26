<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique(); // mis. "2026/2027"
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(false)->index(); // hanya satu yang aktif (dijaga di model)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_periods');
    }
};
