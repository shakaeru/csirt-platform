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

- **Beranda** (`/`) dan **Tentang** (`/tentang`, anchor `#visi-misi` dan `#program-kerja`) — halaman statis. Teks sejarah, visi, misi, dan program kerja dari **`config/profil.php`** (satu sumber untuk kedua halaman; program `unggulan` = 3 cuplikan di Beranda). Mengubah teks = ubah file itu lalu deploy. Teks h1 Beranda dicek `HomePageTest`.
- **Struktur Organisasi** (`/struktur-organisasi`) — data dari Filament (grup menu "Struktur Organisasi"): `BoardPeriod` (hanya satu aktif), `Division`, `BoardMember` (bagian `App\Enums\BoardSection`: pembina/presidium/inti/divisi — urutan case = urutan tampil). Halaman publik hanya menampilkan periode aktif. Foto anggota di disk `public` (`storage/app/public/pengurus`) — butuh `php artisan storage:link`, dan URL foto dibangun dari `APP_URL` (harus benar di setiap environment). File foto otomatis dihapus saat diganti atau anggotanya dihapus.
  - **Import satu periode:** `php artisan struktur:import <daftar.txt> [--photos=<folder>] [--activate] [--dry-run]` — format file di docblock `App\Support\BoardRoster\RosterParser`; foto bernama `<Str::slug(nama)>.jpg|png|webp`, diluruskan (EXIF), dipotong persegi 600×600, di-encode ulang ke JPEG (metadata terbuang; butuh ekstensi PHP `gd` + `exif`). Aman dijalankan ulang (dicocokkan lewat slug nama). **File daftar dan foto asli jangan di-commit** — data pribadi; tes memakai nama fiktif.
- **Berita & Kegiatan** (`/berita`, `/berita/{slug}`) — satu model `Post` dengan `Category` (bawaan dari migrasi: Berita, Kegiatan) dan `Tag`. Status diturunkan dari `published_at` (lihat "Status terbit" di bawah): null = draft, masa depan = terjadwal, lewat = terbit — halaman publik hanya `Post::published()`, draft/terjadwal 404. Filter `?kategori=`, `?tag=`, `?q=`. Data contoh lokal: `DemoNewsSeeder` (lihat README).
  - **Isi tulisan WAJIB dirender lewat `$post->renderRichContent('content')`** (sanitasi Symfony HtmlSanitizer). Jangan pernah `{!! $post->content !!}` — celah XSS. Ada tesnya di `NewsTest`.
  - Sisip gambar di RichEditor sengaja dimatikan (`fileAttachments(false)`): tanpa FileAttachmentProvider, file lampiran yang dihapus tidak pernah dibersihkan dari disk publik. Gambar = sampul (`cover_path`, dihapus otomatis saat diganti/tulisan dihapus). Mengaktifkan sisip gambar butuh provider (mis. plugin Spatie Media Library).
  - Tipografi isi: kelas `.post-content` di `resources/css/app.css` (tanpa `@tailwindcss/typography`).
- **Galeri** (`/galeri`, `/galeri/{slug}`) — `Album` (status terbit sama dengan tulisan, `post_id` opsional ke tulisan terkait) berisi `Photo`. Publik hanya album terbit yang punya foto; urut `event_date` terbaru. Tautan album ↔ tulisan hanya tampil bila keduanya terbit; halaman tulisan menampilkan cuplikan 6 foto per album. Sampul = foto pertama menurut `sort_order` (diatur dengan seret di admin). Data contoh lokal: `DemoGallerySeeder`.
  - **Foto wajib lewat `Album::addPhoto()`** → `GalleryPhotoProcessor`: diluruskan (EXIF), diperkecil ke maks. 2000 px + thumbnail 800 px, di-encode ulang ke JPEG (metadata/GPS terbuang), nama file acak di `galeri/{album_id}/`. Jangan menyimpan file upload apa adanya ke disk publik.
  - Unggah di admin: aksi "Unggah foto" di tabel Foto (maks. 30 file sekali unggah, `storeFiles(false)` → diproses di action). Browser memperkecil dulu; server tetap memproses ulang karena langkah browser bisa dilewati.
  - File dihapus lewat event model: `Photo` menghapus dua file-nya, `Album` menghapus seluruh foldernya (baris `photos` terhapus lewat cascade DB tanpa event).
  - Lightbox: PhotoSwipe 5 (`resources/js/gallery.js`), dimuat dynamic import hanya di halaman yang punya `[data-gallery]`. Tanpa JS, tautan thumbnail membuka foto penuh.
- **Prestasi** (`/prestasi`) — model `Achievement`: prestasi anggota/tim UKM di lomba (bukan hasil event CTF yang diselenggarakan UKM — itu cukup Berita + tautan scoreboard). Jenis `AchievementCategory` (CTF/lomba lain, filter `?jenis=`), tingkat `AchievementLevel`, dikelompokkan per tahun `achieved_on`, tanpa halaman detail (tautan langsung: `/prestasi#prestasi-{id}`). Ringkasan angka di header dihitung dari semua prestasi terbit. Anggota ditulis bebas (satu nama per baris → `member_list`); semua isian teks polos. `result_url` hanya http/https (`url:http,https`). Foto opsional 16:9 seperti sampul tulisan, dihapus saat diganti/prestasinya dihapus. Tautan ke tulisan hanya bila tulisannya terbit. Data contoh lokal: `DemoAchievementSeeder`.
- **Kontak** (`/kontak`) — kanal resmi (email, Instagram, LinkedIn) dari `config/kontak.php`; contact person dan status pendaftaran dari panel admin → **Kontak & Pendaftaran** (`App\Filament\Pages\ManageContact`), disimpan di tabel `settings` (`App\Models\Setting`, key → JSON) supaya berubah tanpa deploy. **Nama dan nomor HP contact person jangan dimasukkan ke repo** (repo publik) — tes memakai nama/nomor fiktif. Nomor divalidasi sebagai seluler Indonesia (`ContactPerson::normalizePhone()`) → tautan `tel:` dan `wa.me`.
  - Pendaftaran anggota = tautan **Google Form** (hanya https); website tidak menyimpan data pendaftar (keputusan di `docs/PRD.md` § 4.1). `RegistrationStatus`: saklar mati → "Belum dibuka"; saklar nyala + hari terakhir (WIB) sudah lewat → otomatis "Sudah ditutup".
- **SEO** (`App\Support\Seo`, `config/seo.php`, head di `layouts/app.blade.php`): judul `<judul> | CSIRT PCR` (Beranda: `seo.home_title`), meta description unik per halaman, canonical + `og:url` dari **APP_URL** (bukan host request), Open Graph/Twitter Card (gambar default `public/images/og-default.jpg` 1200×630; tulisan/album memakai sampulnya), JSON-LD Organization + WebSite di Beranda. Halaman daftar mengirim `page` dan `canonicalQuery` (hanya parameter filter); hasil pencarian `noindex`. Halaman baru: isi `title` dan `description` sendiri, satu `<h1>`, dan tambahkan ke `SitemapController` bila publik. Dijaga `SeoTest` (judul/deskripsi unik, satu h1, alt pada semua `<img>`).
  - `/sitemap.xml` dinamis (halaman utama + tulisan/album terbit); `public/robots.txt` statis dan menunjuk sitemap production.
  - **Favicon/ikon** di `public/` dibuat dari `assets/brand/csirt-favicon.jpg` (dipotong ke lambang, 16/32/48 di `favicon.ico`, 180 apple-touch, 192/512 manifest). Gambar statis di-cache browser setahun (`immutable`), jadi **setiap kali ikon diganti, naikkan `seo.icon_version`** (dan `?v=` di `site.webmanifest`).
  - `<html lang="id">` ditulis tetap di layout publik; `APP_LOCALE` hanya memengaruhi panel admin.
- **Gaya teks:** tanpa em dash (—) di teks yang tampil ke pengguna (situs, admin, pesan CLI); pakai koma, titik, atau titik dua. Hindari pembuka generik, rangkaian tiga kata sifat yang dipaksakan, dan buzzword. Visi/misi di `config/profil.php` adalah teks resmi, jangan diubah tanpa persetujuan pengurus.
- **Status terbit** (tulisan, album, prestasi): trait `App\Models\Concerns\HasPublication` — scope `published()`, `wherePublicationStatus()`, `status()`/`isPublished()`, accessor `published_date`; enum `App\Enums\PublicationStatus`.
- **Olah gambar di server:** `App\Support\Images\UprightImage` (baca JPG/PNG/WebP, luruskan EXIF, tolak resolusi yang tidak muat di `memory_limit`) — dipakai foto pengurus dan galeri.
- **Zona waktu:** database/aplikasi UTC; tampilan dan input WIB (`config('csirt.timezone')`) — admin lewat `FilamentTimezone` (AppServiceProvider), publik lewat accessor seperti `Post::published_date`.

## Aturan khusus

- **Animasi GSAP tidak boleh membuat konten tertahan tersembunyi.** Animasi `from` (mulai dari `opacity: 0`) perlu pengaman `setTimeout(() => tween.progress(1), …)` — renderer headless (mesin pencari, pratinjau) tidak menjalankan frame animasi. Hormati `prefers-reduced-motion`. Lihat contoh di `resources/js/app.js`.
- **Alpine jangan di-import di `resources/js/app.js`.** Livewire sudah membawa dan menjalankan Alpine sendiri; import terpisah = dua instance Alpine.
- **Warna pakai kelas `csirt-*`** (`bg-csirt-primary`, `text-csirt-navy`, dst.), bukan warna default Tailwind (`blue-600`, dsb.).
- Aset yang dipakai halaman (logo, gambar) disalin ke `public/images/`; jangan merujuk langsung ke `assets/brand/` di luar aplikasi. Logo navbar: `csirt-logo.webp` + cadangan `csirt-logo-440.png` (440×142, diperkecil dari aslinya di `assets/brand`).
- **Akses panel admin (`/admin`):** hanya email di `ADMIN_EMAILS` (`.env`, dipisah koma — email tidak disimpan di repo) **dan** sudah terverifikasi — lihat `User::canAccessPanel()`. Berlaku di semua environment, termasuk local. Akun admin dibuat dengan `php artisan admin:create <email>` (password lewat prompt tersembunyi; akun langsung terverifikasi). Menjalankan `admin:create` untuk email yang sudah terdaftar mengambil alih akunnya (password baru, sesi lama diputus). Di tes: `$this->actingAsAdmin()`.
- **Pendaftaran akun publik (`/register`) ditutup** — akun hanya dibuat operator. Syarat terverifikasi di atas tetap dipertahankan sebagai lapis kedua kalau pendaftaran suatu saat dibuka lagi. Jangan membuka `/register` tanpa keputusan tim.

## Deploy (production)

Container `website` (Dockerfile + docker-compose.yml di folder ini), serversideup/php fpm-nginx di-pin, port 8080, SQLite + upload di volume. Prosedur: README § Deploy; arsitektur: `docs/ARCHITECTURE.md` § 6.

- **Proxy:** `trustProxies()` di `bootstrap/app.php` hanya range network Docker dan hanya header For + Proto. **Jangan** ganti ke `'*'` (di Laravel 13 = percaya semua IP → IP pengunjung bisa dipalsukan lewat X-Forwarded-For) dan jangan tambahkan X-Forwarded-Host/Port (host header injection). Dijaga `TrustedProxyTest`.
- **HSTS hanya di Nginx edge.** `docker/nginx/security.conf` sengaja menghapus HSTS bawaan image (`includeSubDomains` setahun); `docker/nginx/remoteip.conf` sengaja kosong (real-IP bawaan image bisa dipalsukan).
- `.env` production dibaca compose (`env_file`) — tanpa interpolasi `${...}`; config di-cache saat start, jadi perubahan `.env` butuh `up -d --force-recreate`. Variabel baru yang wajib di production → tambahkan juga ke `.env.production.example`.
- **SQLite production = koneksi persisten** (`DB_PERSISTENT=true`, PHP-FPM `dynamic`, di `docker-compose.yml`). Jangan kembalikan ke `ondemand`/non-persisten: tiap request yang menulis (sesi!) lalu menutup koneksi memicu checkpoint + fsync, di disk VPS ini 1–6 detik per halaman. Tes dan lokal tetap non-persisten (tes memakai `:memory:`).
- Migrasi **tidak** otomatis saat start (`AUTORUN_LARAVEL_MIGRATION=false`) — `scripts/deploy-website.sh` mem-backup database lalu menjalankannya di container sekali-jalan sebelum container diganti. Migrasi harus aman dijalankan saat kode lama masih melayani (tambah tabel/kolom; hapus/ubah kolom butuh rencana terpisah).
- Backup/pemulihan: `scripts/backup-website.sh` dan `scripts/restore-website.sh` (README § Backup & pemulihan). Data yang disimpan di luar SQLite dan `storage/app/public` **tidak** ikut ter-backup — perbarui skripnya bila menambah lokasi data.
- Batas upload container (`NGINX_CLIENT_MAX_BODY_SIZE`, `PHP_POST_MAX_SIZE`, `PHP_UPLOAD_MAX_FILE_SIZE`) harus selaras dengan tabel ARCHITECTURE § 4.
- Ekstensi PHP baru (mis. untuk paket composer) → `install-php-extensions` di Dockerfile, bukan hanya di WSL.

## Perintah

```bash
npm run build          # build aset (Vite)
npm run dev            # dev server Vite
php artisan serve      # server lokal
php artisan migrate    # JANGAN migrate:fresh tanpa konfirmasi — bisa menghapus data lokal
php artisan test
```
