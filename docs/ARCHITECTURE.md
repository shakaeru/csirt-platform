# ARCHITECTURE.md — Strategi Domain, Routing, dan Reverse Proxy

## 1. Keputusan: Subdomain, bukan Sub-path

**Rekomendasi: gunakan subdomain per platform.**

| Platform | URL |
|---|---|
| Website Profil | `https://csirt.pcr.ac.id` |
| E-Learning | `https://learn.csirt.pcr.ac.id` |
| CTFd | `https://ctf.csirt.pcr.ac.id` |

### Alasan

1. **Kompatibilitas aplikasi.** [Pasti] CTFd secara resmi mendukung mode subdirectory lewat variabel `APPLICATION_ROOT` (mis. `/ctfd`), tapi itu membutuhkan konfigurasi tambahan yang harus konsisten di 3 tempat sekaligus: environment variable CTFd, header `X-Forwarded-Prefix` di reverse proxy, dan `REVERSE_PROXY=True` di config CTFd. Kalau salah satu meleset (mis. saat upgrade versi CTFd), aset statis atau WebSocket bisa rusak. Website dan e-learning (Laravel + Filament, PRD § 4.1–4.2) juga lebih sederhana di root domain/subdomain: Laravel/Livewire bisa berjalan di sub-path, tapi butuh penyesuaian `APP_URL`, URL aset, dan endpoint Livewire yang rawan terlewat saat upgrade.
2. **Isolasi cookie & sesi.** [Kemungkinan Besar] Dengan subdomain terpisah, cookie sesi CTFd (`ctf.csirt.pcr.ac.id`) secara default tidak bisa dibaca oleh e-learning atau sebaliknya, karena browser mengisolasi cookie per-origin kecuali sengaja di-share lewat `Domain=.csirt.pcr.ac.id`. Untuk CTFd yang menyimpan skor/kredensial peserta lomba, isolasi ini mengurangi risiko session collision atau cookie leakage antar aplikasi yang tidak seharusnya saling tahu.
3. **Konfigurasi reverse proxy lebih sederhana & tahan lama.** Routing berbasis `server_name` (subdomain) di Nginx tidak perlu path-rewriting atau `sub_filter` untuk memperbaiki URL absolut yang tertanam di HTML/JS aplikasi pihak ketiga — sumber bug paling umum pada setup sub-path.
4. **Independensi ke depan.** Tiap subdomain bisa dipindah ke VM/skala terpisah nanti (mis. kalau CTFd butuh resource lebih besar saat event) tanpa mengubah URL publik yang sudah dipakai peserta/anggota.

### Trade-off yang diterima

- Butuh DNS record tambahan untuk `learn` dan `ctf` (bukan hanya satu A record seperti sekarang) — lihat § 3.
- Sertifikat TLS perlu mencakup beberapa subdomain sekaligus (§ 4) — sedikit lebih rumit dibanding satu domain tunggal, tapi tetap otomatis lewat Let's Encrypt.

## 2. Topologi Docker

Mengikuti PRD § 4.3 ("CTFd ... dengan Docker network sendiri — jangan share database atau app server yang sama"), tiap aplikasi punya network sendiri. **Nginx adalah satu-satunya container yang join ke lebih dari satu network.**

```
                           Internet
                              │  80/443 - hanya nginx yang publish port ke host
                              ▼
    ┌────────────────────────────────────────────────────┐
    │                   nginx (infra/)                   │  reverse proxy + TLS termination
    └──────┬──────────────────┬──────────────────┬───────┘
           │                  │                  │
      net-website       net-elearning        net-ctfd
           ▼                  ▼                  ▼
    ┌──────────────┐   ┌──────────────┐   ┌──────────────┐
    │   website    │   │  elearning   │   │     ctfd     │
    └──────┬───────┘   └──────┬───────┘   └──────┬───────┘
           │ volume           │ internal         │ internal
           ▼                  ▼                  ▼
    ┌──────────────┐   ┌──────────────┐   ┌──────────────┐
    │    SQLite    │   │  db + redis  │   │  db + redis  │
    └──────────────┘   └──────────────┘   └──────────────┘
```

`internal` = network privat milik compose project masing-masing (`internal: true`), tidak di-join Nginx. Website tidak punya container db: database-nya SQLite, file di volume Docker (§ 6).

| Dari ↓ / Ke → | website | elearning | ctfd | db/redis |
|---|---|---|---|---|
| nginx | ✅ | ✅ | ✅ | ❌ |
| website | — | ❌ | ❌ | — (SQLite di volume sendiri) |
| elearning | ❌ | — | ❌ | hanya miliknya |
| ctfd | ❌ | ❌ | — | hanya miliknya |

### Aturan

1. **Hanya Nginx yang boleh punya `ports:`.** Container aplikasi tidak boleh publish port ke host — kalau publish, container lain bisa menjangkaunya lewat IP host dan isolasi network jadi tidak berarti.
2. **db/redis hanya join network `internal` project-nya**, tidak pernah join `net-*`.
3. **Nama service yang dipakai `proxy_pass` harus unik lintas aplikasi** (`website`, `elearning`, `ctfd`). Kalau dua aplikasi sama-sama pakai nama `app`, DNS Docker di container Nginx bisa me-resolve ke container yang salah.
4. **Nginx me-resolve upstream saat request, bukan saat start** (`resolver 127.0.0.11` + variabel di `proxy_pass`, lihat § 4). Tanpa ini Nginx gagal start kalau salah satu aplikasi mati — website yang down bisa ikut menjatuhkan CTFd, kebalikan dari tujuan isolasi.

### Setup

Buat network sekali di VPS:
```bash
docker network create net-website
docker network create net-elearning
docker network create net-ctfd
```

`infra/docker-compose.yml`:
```yaml
services:
  nginx:
    image: nginx:stable-alpine   # pin ke versi spesifik saat implementasi
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/conf.d:/etc/nginx/conf.d:ro
      - ./nginx/snippets:/etc/nginx/snippets:ro
    networks: [net-website, net-elearning, net-ctfd]
    restart: unless-stopped

networks:
  net-website:   { external: true }
  net-elearning: { external: true }
  net-ctfd:      { external: true }
```

Pola `apps/ctfd/docker-compose.yml` (website & elearning sama, dengan `net-*` masing-masing):
```yaml
services:
  ctfd:                         # nama ini yang dipakai proxy_pass
    networks: [net-ctfd, internal]
  db:
    networks: [internal]
  redis:
    networks: [internal]

networks:
  net-ctfd: { external: true }
  internal: { internal: true }  # jadi <project>_internal, tanpa akses keluar
```

### Risiko yang tersisa

- **Nginx tetap titik bersama.** Config error di `website.conf` yang di-reload bisa menjatuhkan ketiga subdomain. Selalu `nginx -t` sebelum reload; perlakukan restart Nginx saat lomba sama seperti restart `apps/ctfd`.
- **Kalau Nginx dikompromi**, penyerang bisa menjangkau ketiga aplikasi (tapi tidak db/redis-nya).
- **Isolasi ini level network, bukan resource.** Ketiga aplikasi tetap berbagi CPU/RAM/disk VPS. PRD menyebut "idealnya VPS terpisah" — pertimbangkan `mem_limit`/`cpus` untuk website & elearning menjelang lomba.

## 3. DNS yang Dibutuhkan

| Record | Tipe | Target | Status |
|---|---|---|---|
| `csirt.pcr.ac.id` | A | IP VPS | ✅ Sudah dikonfirmasi |
| `learn.csirt.pcr.ac.id` | A atau CNAME | IP VPS / `csirt.pcr.ac.id` | ⏳ Perlu diminta ke pengelola jaringan kampus |
| `ctf.csirt.pcr.ac.id` | A atau CNAME | IP VPS / `csirt.pcr.ac.id` | ⏳ Perlu diminta ke pengelola jaringan kampus |

[Menebak] Kalau pengelola jaringan kampus bisa menyediakan **wildcard record** (`*.csirt.pcr.ac.id` → IP VPS), itu lebih efisien karena mencakup subdomain baru di masa depan tanpa request DNS berulang — tapi ini kebijakan institusi, bukan sesuatu yang bisa Anda putuskan sepihak, jadi tanyakan opsi ini saat mengajukan permintaan.

## 4. Konfigurasi Nginx (Reverse Proxy)

Struktur file di `infra/`:
```
infra/
├── docker-compose.yml          # nginx + certbot (profile "tools")
├── certbot/
│   ├── www/                    # webroot ACME challenge (di-commit, kosong)
│   └── conf/                   # sertifikat & akun Let's Encrypt — di-ignore git, owner root
└── nginx/
    ├── conf.d/
    │   ├── 00-default.conf     # tolak Host (80) & SNI (443) yang tidak dikenal
    │   ├── website.conf
    │   ├── elearning.conf
    │   └── ctfd.conf
    ├── html/
    │   └── segera-hadir.html   # halaman fallback saat aplikasi tidak bisa dijangkau
    └── snippets/
        ├── proxy-headers.conf
        └── ssl-params.conf     # profil TLS intermediate Mozilla
```

**Halaman fallback.** Kalau container aplikasi belum ada atau mati, Nginx menampilkan `html/segera-hadir.html` dengan status `503` (`error_page 502 504 =503`), bukan halaman error mentah. Hanya error buatan Nginx sendiri yang ditangkap — respons error dari aplikasi tetap diteruskan apa adanya. Begitu aplikasi berjalan, halaman ini otomatis tidak tampil lagi. Pola yang sama bisa dipakai untuk halaman pemeliharaan CTFd (GUIDELINES § 6). Location fallback sengaja tanpa `add_header`: di Nginx, satu `add_header` di level location membuat semua `add_header` dari level server (termasuk HSTS) tidak terwarisi.

`infra/nginx/snippets/proxy-headers.conf` (dipakai bersama oleh ketiga config):
```nginx
proxy_set_header Host              $host;
proxy_set_header X-Real-IP         $remote_addr;
proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_http_version 1.1;
proxy_set_header Upgrade    $http_upgrade;
proxy_set_header Connection "upgrade";
```

`infra/nginx/conf.d/ctfd.conf` (contoh — dua lainnya polanya sama):
```nginx
server {
    listen 80;
    server_name ctf.csirt.pcr.ac.id;

    location /.well-known/acme-challenge/ {   # perpanjangan sertifikat (§ 5)
        root /var/www/certbot;
    }
    location / {
        return 301 https://ctf.csirt.pcr.ac.id$request_uri;
    }
}

server {
    listen 443 ssl;
    http2 on;
    server_name ctf.csirt.pcr.ac.id;

    ssl_certificate     /etc/letsencrypt/live/csirt.pcr.ac.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/csirt.pcr.ac.id/privkey.pem;
    include /etc/nginx/snippets/ssl-params.conf;

    client_max_body_size 50M;   # CTFd sering upload file challenge berukuran besar

    # Resolve saat request lewat DNS internal Docker, bukan saat start (§ 2 aturan 4)
    resolver 127.0.0.11 valid=10s ipv6=off;
    set $upstream_ctfd http://ctfd:8000;   # nama service di docker-compose apps/ctfd

    location / {
        include /etc/nginx/snippets/proxy-headers.conf;
        proxy_pass $upstream_ctfd;
    }
}
```

Catatan penting untuk CTFd: set `REVERSE_PROXY=True` (atau `1,1,1,1,1`) di environment variable CTFd sesuai dokumentasi resminya, supaya CTFd membaca IP asli peserta dari header `X-Forwarded-For`, bukan IP internal Nginx — ini relevan untuk rate-limiting dan audit log CSIRT.

### Batas ukuran upload (Website & E-Learning)

Batas body request dibuat kecil secara default (`client_max_body_size 1m`) dan hanya dilonggarkan di endpoint upload sementara Livewire/Filament — bukan di seluruh server — supaya endpoint publik (form kontak, login) tidak bisa dibanjiri body besar. Livewire 4 memakai prefix hash dari `APP_KEY` (`/livewire-<8 hex>/upload-file`), jadi Nginx mencocokkannya dengan regex — lihat `infra/nginx/conf.d/website.conf`.

Batas ini berlapis dan **harus diselaraskan** setiap kali salah satunya diubah:

| Lapisan | Setting | Nilai |
|---|---|---|
| Nginx edge (`infra/`) | `client_max_body_size` di location upload | `13m` |
| Container `apps/website` (web server + PHP) | `client_max_body_size`, `post_max_size`, `upload_max_filesize` | `13m`, `13M`, `12M` (env di `apps/website/docker-compose.yml`) |
| Livewire | `temporary_file_upload.rules` | `max:12288` (default, 12 MB) |
| Filament | `FileUpload::maxSize()` | ≤ 12288 KB |

Rekomendasi untuk development `apps/website`: aktifkan resize gambar di sisi browser sebelum upload (fitur resize pada komponen `FileUpload` Filament — cek nama method sesuai versi Filament yang dipakai). Foto kamera ponsel 5–12 MB bisa turun ke ratusan KB, sehingga upload lebih cepat, storage lebih hemat, dan batas di atas jarang tersentuh.

## 5. TLS / HTTPS

Satu sertifikat Let's Encrypt (multi-SAN, nama sertifikat `csirt.pcr.ac.id`) untuk semua subdomain, diterbitkan lewat certbot **dalam container** (service `certbot` di `infra/docker-compose.yml`, profile `tools`) dengan metode **webroot**. `certbot --nginx` tidak bisa dipakai karena Nginx berjalan di container, bukan di host.

Subdomain ditambahkan ke sertifikat bertahap, begitu DNS-nya aktif — certbot baru bisa memverifikasi domain (HTTP-01) setelah record-nya mengarah ke VPS (§ 3). Semua perintah dijalankan dari `/opt/csirt/infra` sebagai `deploy` (tanpa `sudo`):

```bash
# Uji dulu ke server staging (tidak menerbitkan sertifikat, tidak memakan rate limit)
docker compose run --rm certbot certonly --webroot -w /var/www/certbot \
  --cert-name csirt.pcr.ac.id -d csirt.pcr.ac.id --dry-run

# Penerbitan awal — saat ini hanya csirt.pcr.ac.id yang DNS-nya aktif
docker compose run --rm certbot certonly --webroot -w /var/www/certbot \
  --cert-name csirt.pcr.ac.id -d csirt.pcr.ac.id

# Saat learn.* / ctf.* aktif: perluas sertifikat yang sama (path di Nginx tidak berubah)
docker compose run --rm certbot certonly --webroot -w /var/www/certbot \
  --cert-name csirt.pcr.ac.id --expand \
  -d csirt.pcr.ac.id -d learn.csirt.pcr.ac.id -d ctf.csirt.pcr.ac.id
```

- **Penyimpanan:** sertifikat, private key, dan akun ACME di `infra/certbot/conf/` — di-ignore git, owner root; Nginx me-mount-nya read-only.
- **Perpanjangan:** cron user `deploy` menjalankan `scripts/renew-certs.sh` 2x sehari (`certbot renew`, lalu `nginx -t` dan reload graceful).
- **Pemantauan:** Let's Encrypt tidak lagi mengirim email pengingat kedaluwarsa (sejak 2025) — pantau masa berlaku sertifikat lewat monitoring (mis. Uptime Kuma). OCSP stapling tidak dipakai karena Let's Encrypt sudah menghentikan OCSP.
- **HSTS:** dimulai `max-age` pendek tanpa `includeSubDomains`; naikkan setelah HTTPS stabil, dan tambahkan `includeSubDomains` hanya setelah semua subdomain HTTPS.
- **Default server 443:** `ssl_reject_handshake` menolak SNI yang tidak dikenal, jadi akses lewat IP tidak menerima sertifikat apa pun.

## 6. Container Website (`apps/website`)

Prosedur deploy/update: `apps/website/README.md` § Deploy.

- **Image** (`apps/website/Dockerfile`, dibangun di VPS dengan `docker compose build`): tahap `node:24.21-alpine` membangun aset Vite, lalu `serversideup/php:8.5-fpm-nginx-trixie-v4.5.1` — Nginx + PHP-FPM dalam satu container, berjalan tanpa root, port 8080 (= upstream `website:8080` di `website.conf`). Lisensi image GPL-3.0; kita hanya menjalankannya, tidak mendistribusikan, jadi tidak ada kewajiban untuk kode aplikasi. Versi di-pin dan dinaikkan secara sadar.
- **Database: SQLite**, bukan MySQL/MariaDB seperti rencana awal PRD § 4.1 (keputusan 26 September 2026). Alasan: tanpa container db (RAM VPS disisakan untuk CTFd saat lomba), backup cukup satu file, sama persis dengan lokal/tes, dan beban tulis kecil (3 admin + sesi). Mode WAL + `busy_timeout` 5 detik (`.env`). Koneksi SQLite **persisten** dan PHP-FPM `dynamic` (`docker-compose.yml`): di disk VPS ini, menutup koneksi setelah menulis memicu checkpoint WAL + fsync ±0,3–0,9 detik, sehingga halaman dengan sesi sempat butuh 1–6 detik (diukur 27 September 2026; file statis 0,08 detik, `/up` 0,1 detik). Evaluasi ulang kalau beban tulis naik (mis. formulir pendaftaran dipindah dari Google Form ke website): pindah ke MariaDB = tambah service `db` di network `internal` + impor data.
- **Volume:** `storage` (`storage/app` — upload foto + file sementara Livewire) dan `database` (file SQLite); `docker compose down -v` menghapusnya. Backup harian lewat `scripts/backup-website.sh` ke `~deploy/backups/website` (database 30 hari, upload 7 salinan), disalin ke luar VPS secara manual — `apps/website/README.md` § Backup & pemulihan.
- **Rahasia:** `apps/website/.env` di VPS (owner `deploy`, mode 600), dibaca compose lewat `env_file`; template `.env.production.example`.
- **Proxy & header:**
  - Laravel mempercayai proxy hanya dari range network Docker (`172.16.0.0/12`, `192.168.0.0/16` — pool bawaan, `daemon.json` VPS tidak mengubahnya) dan hanya header `X-Forwarded-For` + `X-Forwarded-Proto`. `'*'` tidak dipakai: di Laravel 13 artinya semua IP dipercaya, sehingga IP pengunjung bisa dipalsukan. `X-Forwarded-Host/Port` tidak dipercaya karena Nginx edge meneruskannya dari klien apa adanya.
  - HSTS hanya dari Nginx edge (§ 5): HSTS bawaan image (`max-age` setahun + `includeSubDomains`) dihapus di `docker/nginx/security.conf`, supaya tidak mengunci `learn.*`/`ctf.*` ke HTTPS sebelum waktunya. Header lain bawaan image tetap: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`.
  - Real-IP bawaan image (`CF-Connecting-IP` dari range Docker) dimatikan (`docker/nginx/remoteip.conf`) — header itu bisa dikirim klien lewat edge.
- **Start container:** `php artisan optimize` (cache config/route/view) otomatis; migrasi **manual** setelah backup (`AUTORUN_LARAVEL_MIGRATION=false`). Healthcheck memakai route `/up` Laravel.
- **Resource:** `mem_limit: 512m`, PHP-FPM maks. 8 proses, `memory_limit` 256M — cukup untuk olah foto galeri (≤ 2000 px setelah diperkecil di browser). Sesuaikan setelah melihat RAM VPS dan menjelang lomba (§ 2 "Risiko yang tersisa").

## 7. Yang Masih Perlu Diputuskan

- Apakah root domain (`csirt.pcr.ac.id`) untuk Website Profil, atau justru dipakai sebagai landing page yang mengarahkan ke ketiga subdomain? [ISI DI SINI]
