# Project Guidelines — Platform UKM CSIRT

## 1. Struktur Environment

| Environment | Lokasi | Tujuan |
|---|---|---|
| Local dev | WSL (laptop Anda) | Menulis kode, menjalankan Claude Code, testing awal |
| Production | VPS | Melayani trafik publik (website, e-learning, CTFd) |
| Staging | [ISI DI SINI — opsional, disarankan minimal untuk `apps/ctfd` sebelum event besar] | Uji coba sebelum ke production |

Kode dan dokumen **selalu** ditulis/diedit dari WSL lalu di-push ke Git remote (GitHub/GitLab privat). VPS hanya melakukan `git pull` — tidak ada edit langsung di VPS.

## 2. Git Workflow

- `main` = production-ready, hanya menerima merge lewat Pull Request (bukan push langsung)
- `dev` = branch integrasi antar anggota
- `feature/<nama-singkat>` = kerja per fitur/perbaikan
- Commit message: `<tipe>: <deskripsi singkat>` — tipe: `feat`, `fix`, `docs`, `chore`, `refactor`, `security`

## 3. Secrets & Konfigurasi

- Semua kredensial (API key, database password, dsb.) disimpan di file `.env` per sub-aplikasi — **tidak pernah** di-commit.
- Sediakan `.env.example` berisi nama variabel tanpa nilai asli, sebagai referensi anggota tim lain.
- `.gitignore` di root wajib mengecualikan `.env`, `*.key`, `*.pem`.

## 4. Review & Akses

- [ISI DI SINI — siapa saja yang punya akses SSH ke VPS? Apakah pakai user terpisah per anggota atau satu user `deploy` bersama?]
- [ISI DI SINI — apakah perubahan ke `apps/ctfd` butuh approval tambahan mengingat sensitivitasnya terhadap downtime saat lomba?]

## 5. Dokumentasi

- Setiap sub-aplikasi (`apps/website`, `apps/elearning`, `apps/ctfd`) punya `README.md` sendiri: cara menjalankan lokal, environment variable yang dibutuhkan, dependency khusus.
- Perubahan pada scope/fitur wajib tercermin di `docs/PRD.md` — jangan biarkan PRD basi (out of date).

## 6. Insiden & Downtime (khusus CTFd)

- [ISI DI SINI — prosedur darurat kalau CTFd down saat kompetisi berlangsung: siapa yang dihubungi, ada backup/failover atau tidak]
- Disarankan: snapshot VPS sebelum event besar dimulai, dan rencana rollback cepat kalau deployment terakhir bermasalah.

## 7. Yang Masih Perlu Diputuskan Tim

- Siapa saja anggota yang akan diberi akses SSH/Git?
- Apakah perlu environment staging terpisah, atau cukup testing lokal di WSL sebelum deploy ke production?
