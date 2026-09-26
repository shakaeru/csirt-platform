# CLAUDE.md — Instruksi Proyek untuk Claude Code

Dibaca otomatis oleh Claude Code setiap sesi dimulai di repo ini. Lihat juga `docs/PRD.md` dan `docs/GUIDELINES.md`.

## Konteks Proyek

Platform digital UKM CSIRT (Politeknik Caltex Riau), terdiri dari 3 sub-aplikasi yang berjalan sebagai container terpisah di satu VPS:
- `apps/website` — profil organisasi
- `apps/elearning` — pelatihan anggota
- `apps/ctfd` — kompetisi CTF (Jeopardy-style), **paling sensitif terhadap downtime**

## Cara Kerja Remote (WAJIB DIBACA)

Sesi ini kemungkinan dijalankan dari WSL lokal, mengeksekusi perintah ke VPS lewat alias SSH `csirt-vps` (lihat `~/.ssh/config`).

- **State shell TIDAK bertahan** antar panggilan `ssh csirt-vps "..."` yang terpisah. Working directory, virtualenv, dan sesi sudo akan reset tiap invocation baru.
- Gabungkan langkah-langkah yang berurutan dalam **satu** perintah, contoh:
  ```
  ssh csirt-vps "cd /opt/csirt/apps/ctfd && docker compose up -d && docker compose logs --tail 20"
  ```
- Untuk perubahan multi-baris/skrip, buat file lokal dulu, `scp` ke VPS, baru dieksekusi sekali — jangan kirim banyak perintah kecil bolak-balik.
- Jangan asumsikan direktori kerja tetap sama dari command sebelumnya kecuali di-`cd` ulang secara eksplisit dalam invocation yang sama.

## Aturan Keamanan (TIDAK BOLEH DILANGGAR TANPA KONFIRMASI EKSPLISIT)

1. **Jangan pernah** menjalankan perintah sebagai `root` atau `sudo su -`. Gunakan user SSH pribadi anggota yang menjalankan sesi (bukan user `deploy` bersama — lihat `docs/GUIDELINES.md` § 4), dan `sudo` hanya bila level akses user tersebut mengizinkan.
2. **Jangan** mengubah `/etc/ssh/sshd_config`, aturan UFW, atau konfigurasi firewall lain tanpa menjelaskan dulu perubahannya dan menunggu konfirmasi.
3. **Jangan** commit file `.env`, kredensial, private key, atau secrets apa pun. Gunakan `.env.example` sebagai template.
4. **Jangan** menjalankan `docker compose down`/restart pada `apps/ctfd` di jam-jam kompetisi berlangsung tanpa konfirmasi eksplisit — downtime di sini berdampak langsung ke peserta lomba.
5. **Jangan** force-push ke branch `main`.
6. Kalau ragu apakah suatu perubahan bersifat merusak (destructive) — tanya dulu, jangan asumsi.

## Konvensi Proyek

- **Git:** branch `main` (production/stable), `dev` (integrasi), `feature/<nama>` untuk kerja individu. Commit message pakai [Conventional Commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`, `docs:`, dst.) — [Menebak: sesuaikan kalau tim UKM sudah punya konvensi lain].
- **Docker:** setiap sub-aplikasi punya `docker-compose.yml` sendiri di foldernya masing-masing; `infra/docker-compose.yml` hanya untuk reverse proxy bersama.
- **Struktur folder:** lihat `README.md` di root.

## Definition of Done (sementara — lengkapi sesuai kebutuhan tim)

- [ ] Perubahan sudah diuji lokal (WSL/staging) sebelum di-deploy ke VPS
- [ ] Tidak ada secrets ter-commit (`git diff` dicek dulu)
- [ ] Dokumentasi relevan (`docs/PRD.md`, README subfolder) diperbarui bila ada perubahan fitur

## Yang Masih Perlu Dilengkapi

- Struktur repo aplikasi Laravel (monorepo vs repo per app) & konfigurasi panel Filament — tech stack sudah final di `docs/PRD.md` § 4.1–4.2
- Strategi backup & rollback untuk `apps/ctfd` sebelum event besar
- CI/CD (saat ini deployment manual via `git pull` + `docker compose up -d` di VPS)
