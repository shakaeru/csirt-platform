<?php

namespace App\Enums;

/**
 * Hak akses panel admin. Daftarnya tetap di kode (setiap hak akses diperiksa oleh kode: policy dan
 * halaman Filament); role mana yang memilikinya diatur di panel (Role::$permissions), bukan di sini.
 * Menambah hak akses baru = tambah case + labelnya + pemeriksaannya (policy/canAccess).
 */
enum Permission: string
{
    case Posts = 'posts.manage';
    case Taxonomy = 'taxonomy.manage';
    case Albums = 'albums.manage';
    case Achievements = 'achievements.manage';
    case Structure = 'structure.manage';
    case Contact = 'contact.manage';
    case Users = 'users.manage';
    case Roles = 'roles.manage';

    public function label(): string
    {
        return match ($this) {
            self::Posts => 'Kelola berita & kegiatan',
            self::Taxonomy => 'Kelola kategori & tag',
            self::Albums => 'Kelola galeri (album & foto)',
            self::Achievements => 'Kelola prestasi',
            self::Structure => 'Kelola struktur organisasi',
            self::Contact => 'Kelola kontak & status pendaftaran',
            self::Users => 'Kelola pengguna',
            self::Roles => 'Kelola role & hak akses',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Posts => 'Membuat, mengubah, menerbitkan, dan menghapus tulisan.',
            self::Taxonomy => 'Kategori dan tag tulisan.',
            self::Albums => 'Album galeri, unggah dan hapus foto.',
            self::Achievements => 'Daftar prestasi di halaman Prestasi.',
            self::Structure => 'Periode kepengurusan, divisi, dan anggota pengurus.',
            self::Contact => 'Contact person dan saklar pendaftaran anggota di halaman Kontak.',
            self::Users => 'Membuat akun admin dan mengatur role serta hak aksesnya (sebatas hak akses sendiri).',
            self::Roles => 'Membuat dan mengubah role beserta daftar hak aksesnya (sebatas hak akses sendiri).',
        };
    }

    /**
     * Pilihan untuk CheckboxList: value => label.
     *
     * @param  list<self>|null  $only
     * @return array<string, string>
     */
    public static function options(?array $only = null): array
    {
        return collect($only ?? self::cases())
            ->mapWithKeys(fn (self $permission): array => [$permission->value => $permission->label()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $permission): array => [$permission->value => $permission->description()])
            ->all();
    }
}
