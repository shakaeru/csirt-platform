# csirt-platform

Monorepo untuk 3 platform digital UKM CSIRT — Politeknik Caltex Riau.

## Struktur Direktori

```
csirt-platform/
├── README.md                  # File ini
├── CLAUDE.md                  # Instruksi untuk Claude Code (baca dulu sebelum kerja)
├── .gitignore
├── .env.example                # Template variabel environment (jangan isi nilai asli di sini)
├── docs/
│   ├── PRD.md                  # Product Requirements Document
│   └── GUIDELINES.md           # Aturan dasar proyek & workflow tim
├── infra/
│   ├── docker-compose.yml      # Reverse proxy bersama (nginx/traefik + TLS)
│   └── proxy/                  # Konfigurasi reverse proxy
├── apps/
│   ├── website/                # Website profil UKM
│   ├── elearning/               # Platform e-learning
│   │   └── docker-compose.yml
│   └── ctfd/                   # Platform CTFd
│       └── docker-compose.yml
└── scripts/
    └── deploy.sh                # Skrip bantu deployment ke VPS
```

## Alur Kerja Singkat

1. Kerja & edit kode dari WSL lokal (Claude Code jalan di sini, remote ke VPS lewat SSH untuk tugas infra).
2. Commit & push ke branch `feature/...`, merge ke `dev`, lalu ke `main` lewat Pull Request.
3. Di VPS: `git pull` lalu `docker compose up -d` pada sub-aplikasi yang berubah.

Detail lengkap: lihat `docs/PRD.md` (requirements) dan `docs/GUIDELINES.md` (aturan tim & environment).
