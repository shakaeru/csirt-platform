# csirt-platform

Repositori platform digital UKM CSIRT — Politeknik Caltex Riau: dokumentasi proyek dan infrastruktur bersama (reverse proxy, TLS). Struktur kode aplikasi — tetap di repo ini (monorepo) atau repo terpisah per aplikasi — belum diputuskan (lihat `CLAUDE.md`).

## Status Layanan

Per 26 September 2026 — perbarui tabel ini setiap ada perubahan status.

| Platform | URL | Status |
|---|---|---|
| Website Profil | https://csirt.pcr.ac.id | HTTPS aktif (Let's Encrypt). Aplikasi belum di-deploy — pengunjung melihat halaman "Segera Hadir" |
| E-Learning | `learn.csirt.pcr.ac.id` | Menunggu DNS record dari pengelola jaringan kampus |
| CTFd | `ctf.csirt.pcr.ac.id` | Menunggu DNS record dari pengelola jaringan kampus |

## Struktur Direktori

```
csirt-platform/
├── README.md                   # File ini
├── CLAUDE.md                   # Instruksi untuk Claude Code (baca dulu sebelum kerja)
├── .gitignore                  # .env, kunci privat, sertifikat, state certbot
├── docs/
│   ├── PRD.md                  # Product Requirements Document
│   ├── GUIDELINES.md           # Aturan tim, akses SSH, workflow, insiden CTFd
│   └── ARCHITECTURE.md         # Domain, topologi Docker, Nginx, TLS
├── infra/                      # Reverse proxy bersama — satu-satunya yang publish port 80/443
│   ├── docker-compose.yml      # Nginx + certbot (profile "tools")
│   ├── nginx/
│   │   ├── conf.d/             # Satu file per subdomain + 00-default.conf
│   │   ├── html/               # Halaman fallback "segera hadir"
│   │   └── snippets/           # proxy-headers.conf, ssl-params.conf
│   └── certbot/
│       ├── www/                # Webroot ACME challenge
│       └── conf/               # Sertifikat & akun Let's Encrypt — tidak di-commit
├── apps/                       # Tiap aplikasi punya .env.example sendiri (GUIDELINES § 3)
│   ├── website/                # Laravel 13 + Livewire 4 (Breeze/Volt) + Filament 5 — lihat apps/website/CLAUDE.md
│   ├── elearning/              # Belum dibuat
│   └── ctfd/                   # Belum dibuat
└── scripts/
    └── renew-certs.sh          # Perpanjangan sertifikat + reload Nginx (cron user deploy)
```

## Alur Kerja Singkat

1. Kerja & edit kode dari WSL lokal (Claude Code jalan di sini, remote ke VPS lewat SSH untuk tugas infra).
2. Commit & push ke branch `feature/...` (dibuat dari `main`), lalu merge ke `main` lewat Pull Request — branch terhapus otomatis setelah merge.
3. Di VPS (user `deploy`, repo di `/opt/csirt`): `git -C /opt/csirt pull --ff-only`, lalu `docker compose up -d` di folder yang berubah (`infra/` atau `apps/<nama>`). Untuk `infra/`, jalankan `nginx -t` dulu — lihat `docs/ARCHITECTURE.md` § 2 "Risiko yang tersisa". Untuk `apps/website` (build image + migrasi): `apps/website/README.md` § Deploy.

## Operasional di VPS

Sebagai user `deploy`, dari `/opt/csirt/infra`:

| Keperluan | Perintah |
|---|---|
| Status container | `docker compose ps` |
| Uji config lalu reload Nginx (tanpa memutus koneksi) | `docker compose exec nginx nginx -t && docker compose exec nginx nginx -s reload` |
| Log Nginx | `docker compose logs --tail 50 nginx` |
| Info & masa berlaku sertifikat | `docker compose run --rm certbot certificates` |
| Log perpanjangan sertifikat (cron 03:17 & 15:17 UTC) | `tail -n 50 /home/deploy/renew-certs.log` |

Perintah yang butuh `sudo` (firewall, akun, paket) dijalankan anggota inti lewat akun pribadinya — lihat `docs/GUIDELINES.md` § 4.

Detail lengkap: `docs/PRD.md` (requirements), `docs/GUIDELINES.md` (aturan tim & environment), `docs/ARCHITECTURE.md` (infrastruktur).
