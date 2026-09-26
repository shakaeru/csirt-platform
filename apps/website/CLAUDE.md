# apps/website — Website Profil UKM CSIRT

Aturan proyek (keamanan, Git, kerja remote ke VPS) ada di `CLAUDE.md` di root repo dan `docs/`. File ini hanya menambah konteks khusus aplikasi ini.

## Stack

- Laravel 13 (PHP ≥ 8.3; lokal WSL: 8.5), database lokal SQLite (`database/database.sqlite`).
- **Livewire 4** + Breeze stack **Livewire/Volt** — halaman auth publik di `resources/views/livewire/pages/auth/`, route di `routes/auth.php`.
- **Filament 5** — panel admin di `/admin` (`app/Providers/Filament/AdminPanelProvider.php`), login sendiri di `/admin/login`. Aset Filament di `public/{css,js,fonts}/filament` di-generate `filament:upgrade` (composer post-autoload-dump) dan di-ignore git.
- Tailwind CSS **v4** lewat `@tailwindcss/vite`. Tidak ada `tailwind.config.js`: token warna ada di blok `@theme` pada `resources/css/app.css`, disalin dari `assets/brand/design-tokens.css` (sumber kebenaran, di root repo).
- Flowbite 4 (plugin CSS + JS di-bundle dari npm, bukan CDN) dan GSAP (`window.gsap`).

## Layout

- `layouts/app.blade.php` — **layout utama situs publik** (navbar Flowbite + footer) dan default layout Livewire 4 (`layouts::app`). Halaman: `@extends('layouts.app')` + `@section('content')`; judul tab opsional lewat `@extends('layouts.app', ['title' => '...'])`.
- `layouts/dashboard.blade.php` — area user Breeze (`/dashboard`, `/profile`) lewat `<x-app-layout>`.
- `layouts/guest.blade.php` — halaman auth Breeze.

## Fitur

- **Struktur Organisasi** (`/struktur-organisasi`) — data dari Filament (grup menu "Struktur Organisasi"): `BoardPeriod` (hanya satu aktif), `Division`, `BoardMember` (bagian `App\Enums\BoardSection`: pembina/presidium/inti/divisi — urutan case = urutan tampil). Halaman publik hanya menampilkan periode aktif. Foto anggota di disk `public` (`storage/app/public/pengurus`) — butuh `php artisan storage:link`, dan URL foto dibangun dari `APP_URL` (harus benar di setiap environment). File foto otomatis dihapus saat diganti atau anggotanya dihapus.
  - **Import satu periode:** `php artisan struktur:import <daftar.txt> [--photos=<folder>] [--activate] [--dry-run]` — format file di docblock `App\Support\BoardRoster\RosterParser`; foto bernama `<Str::slug(nama)>.jpg|png|webp`, diluruskan (EXIF), dipotong persegi 600×600, di-encode ulang ke JPEG (metadata terbuang; butuh ekstensi PHP `gd` + `exif`). Aman dijalankan ulang (dicocokkan lewat slug nama). **File daftar dan foto asli jangan di-commit** — data pribadi; tes memakai nama fiktif.

## Aturan khusus

- **Animasi GSAP tidak boleh membuat konten tertahan tersembunyi.** Animasi `from` (mulai dari `opacity: 0`) perlu pengaman `setTimeout(() => tween.progress(1), …)` — renderer headless (mesin pencari, pratinjau) tidak menjalankan frame animasi. Hormati `prefers-reduced-motion`. Lihat contoh di `resources/js/app.js`.
- **Alpine jangan di-import di `resources/js/app.js`.** Livewire sudah membawa dan menjalankan Alpine sendiri; import terpisah = dua instance Alpine.
- **Warna pakai kelas `csirt-*`** (`bg-csirt-primary`, `text-csirt-navy`, dst.), bukan warna default Tailwind (`blue-600`, dsb.).
- Aset yang dipakai halaman (logo, gambar) disalin ke `public/images/`; jangan merujuk langsung ke `assets/brand/` di luar aplikasi.
- **Sebelum deploy ke production:** `App\Models\User` wajib mengimplementasikan `Filament\Models\Contracts\FilamentUser::canAccessPanel()`. Di luar environment `local`, Filament menolak (403) semua user yang belum lolos pengecekan ini — aman sebagai default, tapi admin pun tidak bisa masuk.

## Perintah

```bash
npm run build          # build aset (Vite)
npm run dev            # dev server Vite
php artisan serve      # server lokal
php artisan migrate    # JANGAN migrate:fresh tanpa konfirmasi — bisa menghapus data lokal
php artisan test
```
