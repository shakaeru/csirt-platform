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

```
                        ┌─────────────────────┐
   Internet ──443/80──▶ │  nginx (infra/)      │  (reverse proxy + TLS termination)
                        └─────────┬────────────┘
                                  │ docker network: csirt-edge (bridge, external)
             ┌────────────────────┼────────────────────┐
             ▼                    ▼                     ▼
      apps/website          apps/elearning         apps/ctfd
      (container)           (container)            (containers: web, db, redis)
```

- Satu Docker network eksternal bersama (`csirt-edge`) supaya container Nginx bisa menjangkau ketiga aplikasi lewat DNS internal Docker (nama service), tanpa expose port aplikasi langsung ke publik.
- Tiap `apps/*` punya `docker-compose.yml` sendiri, tapi join ke network yang sama:
  ```yaml
  networks:
    csirt-edge:
      external: true
  ```
- Buat network sekali di VPS:
  ```bash
  docker network create csirt-edge
  ```

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

    location / {
        include /etc/nginx/snippets/proxy-headers.conf;
        proxy_pass http://ctfd:8000;   # nama service di docker-compose apps/ctfd
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
