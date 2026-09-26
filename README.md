# csirt-platform

Repositori platform digital UKM CSIRT — Politeknik Caltex Riau: dokumentasi proyek dan infrastruktur bersama (reverse proxy, TLS). Struktur kode aplikasi — tetap di repo ini (monorepo) atau repo terpisah per aplikasi — belum diputuskan (lihat `CLAUDE.md`).

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
│   │   └── snippets/           # proxy-headers.conf, ssl-params.conf
│   └── certbot/
│       ├── www/                # Webroot ACME challenge
│       └── conf/               # Sertifikat & akun Let's Encrypt — tidak di-commit
├── apps/                       # Belum dibuat — tiap aplikasi punya docker-compose.yml
│   ├── website/                #   dan .env.example sendiri (GUIDELINES § 3)
│   ├── elearning/
│   └── ctfd/
└── scripts/
    └── renew-certs.sh          # Perpanjangan sertifikat + reload Nginx (cron user deploy)
```

## Alur Kerja Singkat

1. Kerja & edit kode dari WSL lokal (Claude Code jalan di sini, remote ke VPS lewat SSH untuk tugas infra).
2. Commit & push ke branch `feature/...`, merge ke `dev`, lalu ke `main` lewat Pull Request.
3. Di VPS (user `deploy`, repo di `/opt/csirt`): `git -C /opt/csirt pull --ff-only`, lalu `docker compose up -d` di folder yang berubah (`infra/` atau `apps/<nama>`). Untuk `infra/`, jalankan `nginx -t` dulu — lihat `docs/ARCHITECTURE.md` § 2 "Risiko yang tersisa".

Detail lengkap: `docs/PRD.md` (requirements), `docs/GUIDELINES.md` (aturan tim & environment), `docs/ARCHITECTURE.md` (infrastruktur).
