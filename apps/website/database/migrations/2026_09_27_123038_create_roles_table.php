<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Role panel admin. `permissions` = daftar value App\Enums\Permission yang dicentang di panel.
        // Role `is_super` (Super Admin) memiliki semua hak akses dan tidak bisa diubah/dihapus.
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description')->nullable();
            $table->json('permissions');
            $table->boolean('is_super')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            // Tanpa role = tidak bisa masuk panel admin. Role yang masih dipakai tidak bisa dihapus.
            $table->foreignId('role_id')->nullable()->after('email_verified_at')->constrained()->restrictOnDelete();
            // Hak akses tambahan khusus pengguna ini, di luar role-nya.
            $table->json('permissions')->nullable()->after('role_id');
        });

        // Role bawaan, sesuai struktur UKM (bisa diubah di panel: menu Akses → Role).
        $now = now();
        DB::table('roles')->insert([
            [
                'name' => 'Super Admin',
                'description' => 'Technical Lead. Semua hak akses, termasuk pengguna dan role.',
                'permissions' => json_encode([]),
                'is_super' => true,
            ],
            [
                'name' => 'Admin',
                'description' => 'Pengurus inti. Semua konten, struktur organisasi, dan kontak.',
                'permissions' => json_encode(['posts.manage', 'taxonomy.manage', 'albums.manage', 'achievements.manage', 'structure.manage', 'contact.manage']),
                'is_super' => false,
            ],
            [
                'name' => 'Editor',
                'description' => 'Divisi humas/publikasi. Berita, galeri, dan prestasi.',
                'permissions' => json_encode(['posts.manage', 'taxonomy.manage', 'albums.manage', 'achievements.manage']),
                'is_super' => false,
            ],
        ]);
        DB::table('roles')->update(['created_at' => $now, 'updated_at' => $now]);

        // Admin yang sudah ada (email di ADMIN_EMAILS, sebelum RBAC semuanya punya akses penuh) menjadi
        // Super Admin supaya tidak ada yang terkunci saat deploy. Turunkan role-nya lewat panel bila perlu.
        $emails = config('csirt.admin_emails', []);
        if ($emails !== []) {
            DB::table('users')
                ->whereIn(DB::raw('lower(email)'), $emails)
                ->update(['role_id' => DB::table('roles')->where('is_super', true)->value('id')]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn('permissions');
        });

        Schema::dropIfExists('roles');
    }
};
