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
- **Berita & Kegiatan** (`/berita`, `/berita/{slug}`) — satu model `Post` dengan `Category` (bawaan dari migrasi: Berita, Kegiatan) dan `Tag`. Status diturunkan dari `published_at` (`App\Enums\PostStatus`): null = draft, masa depan = terjadwal, lewat = terbit — halaman publik hanya `Post::published()`, draft/terjadwal 404. Filter `?kategori=`, `?tag=`, `?q=`. Data contoh lokal: `DemoNewsSeeder` (lihat README).
  - **Isi tulisan WAJIB dirender lewat `$post->renderRichContent('content')`** (sanitasi Symfony HtmlSanitizer). Jangan pernah `{!! $post->content !!}` — celah XSS. Ada tesnya di `NewsTest`.
  - Sisip gambar di RichEditor sengaja dimatikan (`fileAttachments(false)`): tanpa FileAttachmentProvider, file lampiran yang dihapus tidak pernah dibersihkan dari disk publik. Gambar = sampul (`cover_path`, dihapus otomatis saat diganti/tulisan dihapus). Mengaktifkan sisip gambar butuh provider (mis. plugin Spatie Media Library).
  - Tipografi isi: kelas `.post-content` di `resources/css/app.css` (tanpa `@tailwindcss/typography`).
- **Zona waktu:** database/aplikasi UTC; tampilan dan input WIB (`config('csirt.timezone')`) — admin lewat `FilamentTimezone` (AppServiceProvider), publik lewat accessor seperti `Post::published_date`.

## Aturan khusus

- **Animasi GSAP tidak boleh membuat konten tertahan tersembunyi.** Animasi `from` (mulai dari `opacity: 0`) perlu pengaman `setTimeout(() => tween.progress(1), …)` — renderer headless (mesin pencari, pratinjau) tidak menjalankan frame animasi. Hormati `prefers-reduced-motion`. Lihat contoh di `resources/js/app.js`.
- **Alpine jangan di-import di `resources/js/app.js`.** Livewire sudah membawa dan menjalankan Alpine sendiri; import terpisah = dua instance Alpine.
- **Warna pakai kelas `csirt-*`** (`bg-csirt-primary`, `text-csirt-navy`, dst.), bukan warna default Tailwind (`blue-600`, dsb.).
- Aset yang dipakai halaman (logo, gambar) disalin ke `public/images/`; jangan merujuk langsung ke `assets/brand/` di luar aplikasi.
- **Akses panel admin (`/admin`):** hanya email di `ADMIN_EMAILS` (`.env`, dipisah koma — email tidak disimpan di repo) **dan** sudah terverifikasi — lihat `User::canAccessPanel()`. Berlaku di semua environment, termasuk local. Akun admin dibuat dengan `php artisan admin:create <email>` (password lewat prompt tersembunyi; akun langsung terverifikasi). Menjalankan `admin:create` untuk email yang sudah terdaftar mengambil alih akunnya (password baru, sesi lama diputus). Di tes: `$this->actingAsAdmin()`.
- **Pendaftaran akun publik (`/register`) ditutup** — akun hanya dibuat operator. Syarat terverifikasi di atas tetap dipertahankan sebagai lapis kedua kalau pendaftaran suatu saat dibuka lagi. Jangan membuka `/register` tanpa keputusan tim.

## Perintah

```bash
npm run build          # build aset (Vite)
npm run dev            # dev server Vite
php artisan serve      # server lokal
php artisan migrate    # JANGAN migrate:fresh tanpa konfirmasi — bisa menghapus data lokal
php artisan test
```
