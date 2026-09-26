# Website Profil UKM CSIRT — `apps/website`

Laravel 13 · Livewire 4 · Filament 5 (panel admin) · Tailwind CSS 4 · Flowbite · GSAP.
Aturan proyek ada di `CLAUDE.md` (root repo) dan `apps/website/CLAUDE.md`.

## Prasyarat (WSL / Ubuntu)

- PHP 8.3+ dengan ekstensi `pdo_sqlite`, `intl`, `gd`, `exif`, `mbstring`, `xml`, `curl`, `zip`:
  ```bash
  sudo apt install php8.5-cli php8.5-sqlite3 php8.5-intl php8.5-gd php8.5-mbstring php8.5-xml php8.5-curl php8.5-zip
  ```
  (`exif` sudah termasuk di `php8.5-common`.) Cek: `php -m | grep -E '^(pdo_sqlite|intl|gd|exif)$'`
- Composer 2, Node.js 20+ dan npm.

## Menjalankan pertama kali

Dari folder `apps/website`:

```bash
composer install
npm install
cp .env.example .env              # sekali saja — .env tidak di-commit
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan storage:link          # supaya foto/gambar yang diupload bisa diakses
npm run build
```

Lalu buat akun admin (lihat [Akun admin](#akun-admin)) dan, bila perlu, [data contoh](#data-contoh).

## Menjalankan server lokal

```bash
php artisan serve
```

Buka **http://localhost:8000**. Port harus sama dengan `APP_URL` di `.env` (default `http://localhost:8000`) — URL gambar dibangun dari `APP_URL`, jadi kalau port berbeda gambar tidak tampil.

Saat mengubah Blade/CSS/JS, jalankan juga di terminal kedua supaya perubahan langsung terlihat:

```bash
npm run dev
```

Tanpa `npm run dev`, halaman memakai hasil `npm run build` terakhir.

| Halaman | Alamat |
|---|---|
| Beranda | http://localhost:8000/ |
| Struktur Organisasi | http://localhost:8000/struktur-organisasi |
| Berita & Kegiatan | http://localhost:8000/berita |
| Galeri | http://localhost:8000/galeri |
| Prestasi | http://localhost:8000/prestasi |
| Panel admin | http://localhost:8000/admin |

## Akun admin

Panel admin hanya untuk email di `ADMIN_EMAILS` yang sudah terverifikasi. Pendaftaran akun publik (`/register`) ditutup.

1. Tambahkan ke `.env`: `ADMIN_EMAILS=email-anda@contoh.id` (beberapa email dipisah koma).
2. Buat akunnya (password ditanyakan lewat prompt tersembunyi):
   ```bash
   php artisan admin:create email-anda@contoh.id
   ```
3. Login di http://localhost:8000/admin/login.

## Data contoh

**Berita & Kegiatan** — 6 tulisan terbit + 1 draft + 1 terjadwal, semua berjudul "Contoh: …" (hanya untuk lokal, ditolak di production):

```bash
php artisan db:seed --class=DemoNewsSeeder
```

Menjalankan ulang tidak menggandakan data. Menghapus semua contoh (termasuk file sampulnya):

```bash
php artisan tinker --execute="App\Models\Post::where('slug', 'like', 'contoh-%')->get()->each->delete();"
```

**Galeri** — 3 album terbit + 1 draft berisi "foto" warna bernomor, semua berjudul "Contoh: …". Jalankan setelah data contoh Berita supaya album pertama tertaut ke tulisan contoh:

```bash
php artisan db:seed --class=DemoGallerySeeder
```

Menghapus semua album contoh (termasuk folder fotonya):

```bash
php artisan tinker --execute="App\Models\Album::where('slug', 'like', 'contoh-%')->get()->each->delete();"
```

**Prestasi** — 5 prestasi terbit (2 tahun) + 1 draft, nama kompetisi "Contoh: …" dengan nama tim/anggota fiktif. Satu prestasi tertaut ke tulisan contoh bila data contoh Berita sudah ada:

```bash
php artisan db:seed --class=DemoAchievementSeeder
```

Menghapus semua prestasi contoh (termasuk fotonya):

```bash
php artisan tinker --execute="App\Models\Achievement::where('competition', 'like', 'Contoh:%')->get()->each->delete();"
```

**Struktur Organisasi** — impor dari file daftar pengurus (file dan foto disimpan di luar repo, jangan di-commit):

```bash
php artisan struktur:import /path/ke/daftar.txt --photos=/path/ke/folder-foto --dry-run   # cek dulu
php artisan struktur:import /path/ke/daftar.txt --photos=/path/ke/folder-foto --activate
```

## Tes

```bash
php artisan test
npm run build
```

## Setelah `git pull`

```bash
composer install && npm install && php artisan migrate && npm run build
```

## Masalah umum

| Gejala | Penyebab / solusi |
|---|---|
| `could not find driver` | Ekstensi `pdo_sqlite` belum ada — pasang `php8.5-sqlite3` |
| `/admin` → 403 | Email belum ada di `ADMIN_EMAILS`, atau akun belum dibuat lewat `admin:create`. Setelah mengubah `.env`: `php artisan config:clear` |
| Gambar/foto tidak tampil | Belum `php artisan storage:link`, atau port server beda dengan `APP_URL` |
| Perubahan tampilan tidak terlihat | Jalankan `npm run dev`, atau `npm run build` ulang |
