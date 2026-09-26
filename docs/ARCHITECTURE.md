# ARCHITECTURE.md — Strategi Domain, Routing, dan Reverse Proxy

## 1. Keputusan: Subdomain, bukan Sub-path

**Rekomendasi: gunakan subdomain per platform.**

| Platform | URL |
|---|---|
| Website Profil | `https://csirt.pcr.ac.id` |
| E-Learning | `https://learn.csirt.pcr.ac.id` |
| CTFd | `https://ctf.csirt.pcr.ac.id` |

### Alasan

1. **Kompatibilitas aplikasi.** [Pasti] CTFd secara resmi mendukung mode subdirectory lewat variabel `APPLICATION_ROOT` (mis. `/ctfd`), tapi itu membutuhkan konfigurasi tambahan yang harus konsisten di 3 tempat sekaligus: environment variable CTFd, header `X-Forwarded-Prefix` di reverse proxy, dan `REVERSE_PROXY=True` di config CTFd. Kalau salah satu meleset (mis. saat upgrade versi CTFd), aset statis atau WebSocket bisa rusak. Platform e-learning (tergantung pilihan tech stack — Moodle dan sejenisnya punya masalah serupa) umumnya juga mengasumsikan berjalan di root domain/subdomain, bukan di sub-path.
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
           │ internal         │ internal         │ internal
           ▼                  ▼                  ▼
    ┌──────────────┐   ┌──────────────┐   ┌──────────────┐
    │      db      │   │  db + redis  │   │  db + redis  │
    └──────────────┘   └──────────────┘   └──────────────┘
```

`internal` = network privat milik compose project masing-masing (`internal: true`), tidak di-join Nginx.

| Dari ↓ / Ke → | website | elearning | ctfd | db/redis |
|---|---|---|---|---|
| nginx | ✅ | ✅ | ✅ | ❌ |
| website | — | ❌ | ❌ | hanya miliknya |
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
├── docker-compose.yml
└── nginx/
    ├── conf.d/
    │   ├── website.conf
    │   ├── elearning.conf
    │   └── ctfd.conf
    └── snippets/
        └── proxy-headers.conf
```

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
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name ctf.csirt.pcr.ac.id;

    ssl_certificate     /etc/letsencrypt/live/csirt.pcr.ac.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/csirt.pcr.ac.id/privkey.pem;

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

## 5. TLS / HTTPS

Karena ketiga subdomain berada di bawah domain yang sama, satu sertifikat Let's Encrypt bisa mencakup semuanya sekaligus (multi-SAN certificate), tanpa perlu wildcard:

```bash
sudo certbot certonly --nginx \
  -d csirt.pcr.ac.id \
  -d learn.csirt.pcr.ac.id \
  -d ctf.csirt.pcr.ac.id
```

[Pasti] Certbot baru bisa memverifikasi domain (HTTP-01 challenge) setelah DNS record subdomain-nya benar-benar aktif dan mengarah ke VPS — jadi langkah ini menunggu § 3 selesai.

## 6. Yang Masih Perlu Diputuskan

- Apakah root domain (`csirt.pcr.ac.id`) untuk Website Profil, atau justru dipakai sebagai landing page yang mengarahkan ke ketiga subdomain? [ISI DI SINI]
- Auto-renewal certbot: pastikan `systemctl status certbot.timer` aktif setelah setup awal.
