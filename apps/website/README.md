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
| Tentang | http://localhost:8000/tentang |
| Struktur Organisasi | http://localhost:8000/struktur-organisasi |
| Berita & Kegiatan | http://localhost:8000/berita |
| Galeri | http://localhost:8000/galeri |
| Prestasi | http://localhost:8000/prestasi |
| Kontak | http://localhost:8000/kontak |
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

## Kontak & pendaftaran anggota

Contact person dan status pendaftaran di halaman Kontak diatur di panel admin → **Kontak & Pendaftaran** (langsung berlaku, tanpa deploy). Email dan media sosial ada di `config/kontak.php`. Nama dan nomor contact person hanya diisi lewat panel — jangan ditulis ke repo (repo publik).

Formulir pendaftaran memakai **Google Form**, bukan formulir di website (alasan: `docs/PRD.md` § 4.1). Saat pendaftaran dibuka:

1. Buat Google Form dengan akun Google milik UKM, bukan akun pribadi pengurus (supaya tidak hilang saat pengurusnya lulus); tambahkan pengurus terkait sebagai editor. Di tab **Respons**, tautkan ke Google Spreadsheet.
2. Panel admin → Kontak & Pendaftaran: nyalakan **Pendaftaran dibuka**, isi tautan formulir (`https://forms.gle/…`), dan bila perlu hari terakhir pendaftaran.
3. Setelah pendaftaran selesai: matikan "Menerima respons" di Google Form **dan** matikan saklar di panel admin. Hari terakhir yang diisi membuat halaman Kontak otomatis menampilkan "Sudah ditutup", tetapi Google Form-nya tidak ikut tertutup.

Spreadsheet respons berisi data pribadi pendaftar: bagikan hanya ke pengurus yang memprosesnya, dan tetapkan kapan dihapus (mis. setelah seleksi selesai). Butuh `.xlsx`: di Spreadsheet, File → Download → Microsoft Excel.

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

## Deploy (production)

Website berjalan sebagai container `website` (image dari `Dockerfile`: Nginx + PHP-FPM 8.5, port 8080) di network `net-website`. Nginx edge (`infra/`) meneruskan https://csirt.pcr.ac.id ke `website:8080`; selama container itu belum jalan, pengunjung melihat halaman "Segera Hadir". Data ada di dua volume: `storage` (upload) dan `database` (SQLite). Detail dan alasannya: `docs/ARCHITECTURE.md` § 6.

Semua perintah di bawah dijalankan sebagai `deploy` di VPS, dari `/opt/csirt/apps/website`.

### Deploy pertama (sekali)

```bash
git -C /opt/csirt pull --ff-only
cp .env.production.example .env && chmod 600 .env
echo "base64:$(openssl rand -base64 32)"   # salin hasilnya ke APP_KEY di .env
nano .env                                  # isi APP_KEY dan ADMIN_EMAILS
docker compose build
# Migrasi di container sekali-jalan: belum terhubung ke Nginx, jadi situs belum live.
docker compose run --rm --no-deps website php artisan migrate --force
docker compose up -d                       # live — "Segera Hadir" hilang
docker compose ps                          # tunggu status (healthy)
```

Lalu buat akun admin, satu per email di `ADMIN_EMAILS` (password lewat prompt, jadi perlu terminal interaktif — dari WSL: `ssh -t csirt-vps '…'`):

```bash
docker compose exec website php artisan admin:create <email>
```

### Update setelah PR di-merge

```bash
/opt/csirt/scripts/deploy-website.sh
```

Urutannya: `git pull` → backup database → build image → migrasi (container sekali-jalan) → container baru → cek `https://csirt.pcr.ac.id` lewat Nginx edge. Hanya jalan bila ada commit baru yang menyentuh `apps/website` (`--force` untuk memaksa). Build di VPS ini lambat (disk lambat untuk fsync) — layer yang tidak berubah diambil dari cache. Di akhir, skrip mencetak perintah rollback.

### Backup & pemulihan

`/opt/csirt/scripts/backup-website.sh` menyimpan ke `~/backups/website/` (user `deploy`, mode 700):

| Isi | File | Retensi |
|---|---|---|
| Database — salinan konsisten (`VACUUM INTO`), dicek `integrity_check` | `db/website-<UTC>.sqlite.gz` | 30 hari |
| Upload (`storage/app/public`) — hanya bila ada perubahan | `storage/website-storage-<UTC>.tar` | 7 salinan terakhir |

Berjalan harian lewat cron `deploy` (02:45 WIB) dan otomatis sebelum setiap deploy (database saja):

```
45 19 * * * /opt/csirt/scripts/backup-website.sh >> /home/deploy/backup-website.log 2>&1
```

Backup ini masih di disk VPS yang sama — **salin ke luar VPS** secara berkala (minimal mingguan), dari WSL:

```bash
rsync -av csirt-vps:backups/website/ ~/csirt-backups/website/
```

Pemulihan (meminta konfirmasi; database saat ini di-backup dulu, situs mati sebentar):

```bash
/opt/csirt/scripts/restore-website.sh db ~/backups/website/db/website-<UTC>.sqlite.gz
/opt/csirt/scripts/restore-website.sh storage ~/backups/website/storage/website-storage-<UTC>.tar
```

### Operasional

| Keperluan | Perintah |
|---|---|
| Status & health | `docker compose ps` |
| Log aplikasi + Nginx internal | `docker compose logs --tail 100 website` |
| Setelah mengubah `.env` (config di-cache saat start) | `docker compose up -d --force-recreate` |
| Perintah artisan | `docker compose exec website php artisan <perintah>` |
| Rollback kode | `git -C /opt/csirt checkout <commit>` lalu `docker compose up -d --build` (migrasi tidak ikut mundur) |
| Log backup harian | `tail -n 20 /home/deploy/backup-website.log` |

**Jangan** menjalankan seeder contoh (`Demo*Seeder`) di production, dan jangan `docker compose down -v` — `-v` menghapus volume berisi database dan semua upload.
