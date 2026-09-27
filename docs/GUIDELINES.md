# Project Guidelines — Platform UKM CSIRT

## 1. Struktur Environment

| Environment | Lokasi | Tujuan |
|---|---|---|
| Local dev | WSL (laptop Anda) | Menulis kode, menjalankan Claude Code, testing awal |
| Production | VPS | Melayani trafik publik (website, e-learning, CTFd) |
| Staging | Subdomain/subdirectory terpisah di VPS yang sama (mis. `staging.domain.org`), dijalankan via Docker Compose dengan port & database berbeda dari production | Uji coba sebelum ke production, **wajib** untuk `apps/ctfd` H-3 sebelum event dimulai |

Kode dan dokumen **selalu** ditulis/diedit dari WSL lalu di-push ke Git remote (GitHub/GitLab privat). VPS hanya melakukan `git pull` — tidak ada edit langsung di VPS.

## 2. Git Workflow

- `main` = production-ready, hanya menerima merge lewat Pull Request (bukan push langsung). VPS menarik dari `main`, jadi setiap merge berarti siap di-deploy.
- `feature/<nama-singkat>` = kerja per fitur/perbaikan — dibuat dari `main` terbaru dan di-PR langsung ke `main` (tidak ada branch integrasi `dev`). Branch terhapus otomatis setelah PR di-merge (pengaturan repo).
- Commit message: `<tipe>: <deskripsi singkat>` — tipe: `feat`, `fix`, `docs`, `chore`, `refactor`, `security`

## 3. Secrets & Konfigurasi

- Semua kredensial (API key, database password, dsb.) disimpan di file `.env` per sub-aplikasi — **tidak pernah** di-commit.
- Sediakan `.env.example` berisi nama variabel tanpa nilai asli, sebagai referensi anggota tim lain.
- `.gitignore` di root wajib mengecualikan `.env`, `*.key`, `*.pem`.

## 4. Review & Akses

- Akses SSH untuk **login interaktif/administrasi** menggunakan **user terpisah per anggota** (bukan satu user bersama), dengan public key masing-masing didaftarkan di `~/.ssh/authorized_keys`. Ini wajib untuk audit trail — kalau terjadi insiden (misal file production ter-hapus atau service down), harus bisa dilacak siapa yang login terakhir.
  - Anggota inti (core team/pengurus divisi teknis) → akses penuh (bisa `sudo`).
  - Anggota kontributor → akses terbatas ke direktori `apps/<nama-app>` masing-masing, tanpa `sudo`.
- **Akun layanan `deploy`** khusus untuk deployment — `git pull`, `docker compose`, skrip deploy, CI, dan sesi Claude Code — bukan untuk administrasi sistem.
  - `deploy` ada di grup `docker`, yang **setara root tanpa password**: siapa pun yang memegang private key-nya praktis punya root di VPS. Perlakukan key ini seperti kredensial root.
  - Tiap pemakai (anggota, CI) memakai **key sendiri** di `authorized_keys` milik `deploy`, bukan satu key bersama — log SSH mencatat fingerprint key per login, jadi audit trail tetap jalan dan akses bisa dicabut per orang.
  - Target hardening: setelah akun pribadi anggota inti dengan `sudo` tersedia, keluarkan `deploy` dari grup `sudo` — akun deployment cukup grup `docker`.
  - **Lokasi di VPS:** repo di `/opt/csirt` (owner `deploy:deploy`, mode `750` — akun lain tidak bisa membaca `.env` aplikasi). Repo di-clone dengan deploy key **read-only** `/home/deploy/.ssh/csirt-platform-deploy` (di GitHub: "csirt-vps deploy (read-only)"), dipasang lewat `core.sshCommand` repo — cukup `git -C /opt/csirt pull --ff-only`. `--ff-only` membuat pull gagal dengan jelas kalau ada perubahan lokal di VPS, bukan diam-diam membuat merge commit.
  - Deploy key tidak bisa push — perubahan tetap lewat PR dari WSL (§ 1). Kalau VPS diduga bocor: cabut key di GitHub (Settings → Deploy keys, atau `gh repo deploy-key delete <id>`), lalu buat key baru di VPS.
- Perubahan ke `apps/ctfd` **wajib approval minimal 1 reviewer lain** (bukan self-merge), khususnya untuk PR yang menyentuh konfigurasi container, port, atau plugin — karena downtime saat lomba berdampak langsung ke peserta eksternal, beda risikonya dengan `apps/website` atau `apps/elearning`.

## 5. Dokumentasi

- Setiap sub-aplikasi (`apps/website`, `apps/elearning`, `apps/ctfd`) punya `README.md` sendiri: cara menjalankan lokal, environment variable yang dibutuhkan, dependency khusus.
- Perubahan pada scope/fitur wajib tercermin di `docs/PRD.md` — jangan biarkan PRD basi (out of date).

## 6. Insiden & Downtime (khusus CTFd)

- **PIC on-call**: Technical Lead (Shafwan Khairullah) sebagai PIC utama, Content Lead (Hasbi Ray Chaibir) sebagai PIC cadangan (lihat catatan risiko di bagian #7 — wajib dilatih rollback sebelum event). Kontak (nomor WA/Telegram) dicatat di `docs/RUNBOOK-CTFD.md`, bukan hanya di kepala satu orang.
- **Eskalasi**: peserta/panitia lapangan → PIC on-call → (kalau PIC tidak merespons dalam X menit) → penanggung jawab teknis utama.
- **Snapshot VPS** diambil H-1 sebelum event dimulai (checklist wajib pra-event, bukan sekadar saran).
- **Rollback plan**: siapkan command/script rollback yang sudah diuji di staging (`git checkout <commit-terakhir-stabil> && docker compose up -d --build`), supaya saat downtime tidak improvisasi di bawah tekanan.
- **Failover**: kalau tidak ada budget server cadangan, minimal siapkan mode "maintenance page" statis yang bisa di-deploy cepat kalau CTFd benar-benar down, agar peserta tidak melihat error mentah.
- Disarankan: snapshot VPS sebelum event besar dimulai, dan rencana rollback cepat kalau deployment terakhir bermasalah.

## 7. Yang Masih Perlu Diputuskan Tim

Berdasarkan tabel stakeholder di `docs/PRD.md`, berikut pemetaan awal ke akses teknis — **konfirmasi ulang di rapat tim**, terutama kolom akses SSH dan approval, karena PRD hanya mendefinisikan tanggung jawab fungsional, bukan level akses infrastruktur:

| Peran (PRD) | Nama/Jabatan | Level Akses SSH | Scope | Approval `apps/ctfd`? | Panel admin website (`/admin`) |
|---|---|---|---|---|---|
| Technical Lead | Shafwan Khairullah | Penuh (`sudo`) | Seluruh VPS (infrastruktur, keamanan, deployment) | Ya — approver utama, semua perubahan `apps/ctfd` | Ya |
| Content Lead | Hasbi Ray Chaibir | Terbatas (`apps/website`) + **`sudo` sebagai PIC cadangan insiden** | `apps/website`, plus akses darurat ke seluruh VPS saat Technical Lead berhalangan | Tidak relevan untuk operasional harian | Tidak |
| E-Learning Lead | Abiyu Nayaka | Terbatas | `apps/elearning` saja | Tidak relevan | Ya |
| CTF Event Lead | Michael Louis Dalen | **Tidak ada akses SSH** — hanya panel admin CTFd dan panel admin website | Konten challenge via panel admin CTFd | Tidak relevan (tidak menyentuh server) | Ya |

> ⚠️ **Catatan risiko (perlu mitigasi):** Content Lead ditunjuk sebagai PIC cadangan dengan akses `sudo`, padahal perannya tidak berkaitan dengan infrastruktur. Agar akses ini tidak hanya jadi formalitas, **wajib**:
> - Content Lead dilatih menjalankan skrip rollback (lihat bagian #6) minimal sekali sebelum event besar, bukan hanya diberi kredensial.
> - Buat `docs/RUNBOOK-CTFD.md` dengan langkah rollback yang bisa diikuti tanpa pemahaman mendalam soal Docker/Git (command siap-pakai, bukan penjelasan konsep).
> - Technical Lead tetap jadi kontak utama; akses Content Lead murni untuk skenario Technical Lead benar-benar tidak bisa dihubungi.

**Keputusan final (per rapat tim):**
- Staging cukup di VPS yang sama (opsi bagian #1) untuk saat ini — evaluasi ulang kalau resource jadi masalah saat testing bersamaan dengan production.
- CTF Event Lead tidak memerlukan akses SSH; akses panel admin CTFd sudah cukup untuk mengelola challenge.
- Panel admin website (`/admin`, Filament) memakai role dan hak akses (RBAC, sejak 27 September 2026): akun dibuat Super Admin di panel (menu Akses → Pengguna) dan hanya bisa masuk bila punya role; tiap menu dan aksi dibatasi hak akses role-nya (dicek di server). Role bawaan: **Super Admin** (Technical Lead, semua hak akses), **Admin** (pengurus inti: konten, struktur, kontak), **Editor** (humas/publikasi: berita, galeri, prestasi); isi hak akses tiap role bisa diubah di panel. Super Admin pertama dibuat lewat SSH: email dicantumkan di `ADMIN_EMAILS` pada `.env` production (bukan di repo), lalu `php artisan admin:create <email>`. Pendaftaran akun publik (`/register`) ditutup.