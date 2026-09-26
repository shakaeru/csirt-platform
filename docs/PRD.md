# Product Requirements Document (PRD) — Platform UKM CSIRT

> Status: DRAFT / SCAFFOLD. Bagian bertanda `[ISI DI SINI]` belum final dan perlu dilengkapi sebelum development dimulai.

## 1. Latar Belakang

UKM CSIRT (Computer Security Incident Response Team) — Politeknik Caltex Riau membutuhkan infrastruktur digital untuk tiga kebutuhan:
1. Website profil organisasi
2. Platform e-learning untuk pelatihan rutin anggota
3. Platform CTFd untuk kompetisi keamanan siber (Jeopardy-style CTF)

Ketiganya di-host pada satu VPS + domain yang sama.

## 2. Tujuan Proyek

Tujuan Utama
Membangun infrastruktur digital yang terpusat, aman, dan berkinerja tinggi guna mendukung kegiatan operasional, edukasi, serta kompetisi keamanan siber secara profesional bagi UKM CSIRT Politeknik Caltex Riau.

Tujuan Khusus (Berdasarkan Modul)

Website Profil Organisasi (Public Facing):
Membangun wajah digital representatif bagi UKM CSIRT PCR yang berfungsi sebagai pusat informasi publik. Tujuannya adalah untuk meningkatkan visibilitas organisasi, mempublikasikan kegiatan dan pencapaian, serta memfasilitasi komunikasi untuk rekrutmen anggota baru dan kemitraan eksternal.

Platform E-Learning (Internal Education):
Menyediakan lingkungan pembelajaran terstruktur bagi anggota aktif. Tujuan utamanya adalah untuk memusatkan kurikulum pelatihan (modul keamanan siber, write-ups, tutorial), memastikan transfer pengetahuan berjalan sistematis, dan memantau progres kompetensi teknis masing-masing anggota.

Platform CTFd (Competition & Evaluation):
Menyediakan infrastruktur kompetisi Jeopardy-style Capture The Flag (CTF) yang stabil dan terstandarisasi. Tujuannya adalah untuk mendukung program kerja perlombaan (baik internal maupun eksternal), serta menjadi wadah evaluasi praktis bagi anggota dalam menyelesaikan kasus-kasus cybersecurity.

Tujuan Teknis & Operasional

Keamanan Prioritas Utama (Security by Design): Sebagai representasi organisasi keamanan siber, infrastruktur yang dibangun harus mencerminkan standar praktik keamanan terbaik, termasuk konfigurasi firewall yang ketat, manajemen akses via SSH Keys, perlindungan data, dan arsitektur server yang tahan terhadap serangan dasar.

Keberlanjutan & Pemeliharaan (Maintainability): Membangun lingkungan yang terdokumentasi dengan baik (melalui PRD dan panduan teknis) agar mudah dikelola, dipelihara, dan diwariskan kepada pengurus UKM generasi berikutnya tanpa hambatan teknis yang berarti.

Efisiensi Sumber Daya: Mengoptimalkan satu VPS (Virtual Private Server) untuk menjalankan tiga layanan utama secara berdampingan tanpa mengorbankan performa server saat diakses secara bersamaan, terutama saat perlombaan berlangsung.

## 3. Stakeholder

| Peran | Nama/Jabatan | Tanggung Jawab |
|---|---|---|
| Technical Lead | Shafwan Khairullah | Infrastruktur, keamanan, deployment |
| Content Lead | Hasbi Ray Chaibir | Konten website |
| E-Learning Lead | Abiyu Nayaka | Materi e-learning |
| CTF Event Lead | Michael Louis Dalen | Pengelola event CTF |

## 4. Sub-Proyek

### 4.1 Website Profil UKM
- **Target pengguna:** Calon anggota, anggota aktif, alumni, sponsor/mitra, pengurus fakultas/universitas, dan pengunjung umum.
- **Fitur wajib (MVP):**
  - [ ] Halaman utama / profil UKM
  - [ ] Struktur organisasi
  - [ ] Berita/kegiatan (bisa jadi mini-CMS: kategori, tag, pencarian)
  - [ ] Kontak/formulir pendaftaran anggota (dengan validasi Laravel Form Request + notifikasi email ke pengurus)
  - [x] Galeri dokumentasi kegiatan (PR #17)
  - [ ] Halaman prestasi/achievement (khususnya untuk menunjukkan hasil CTF — menghubungkan sub-proyek 4.1 dan 4.3)
- **Fitur lanjutan (nice-to-have):**
  - Integrasi feed media sosial (Instagram Graph API)
  - Newsletter/mailing list (Laravel Notifications + queue)
  - Multi-bahasa (ID/EN) via Laravel Localization
  - Dashboard analytics pengunjung sederhana
- **Tech stack:**
  - Laravel (versi stabil terbaru saat mulai development — cek rilis LTS terkini)
  - Filament PHP sebagai admin panel/CMS internal
  - Blade + Livewire/Alpine.js untuk interaktivitas ringan tanpa perlu SPA penuh
  - MySQL/MariaDB
  - Nginx + PHP-FPM, deployment manual atau via Laravel Forge kalau budget ada
- **Non-functional requirements:** uptime standar, tidak kritikal terhadap waktu tertentu.

### 4.2 Platform E-Learning
- **Target pengguna:** Anggota aktif UKM.
- **Fitur wajib (MVP):**
  - [ ] Manajemen materi/modul pelatihan (upload PDF/video/slide, versi materi)
  - [ ] Progress tracking anggota (per modul, per course)
  - [ ] Quiz/assessment engine (multiple choice minimal, auto-grading)
  - [ ] Role-based access (admin, instruktur/mentor, peserta) via Spatie Laravel-Permission
  - [ ] Forum diskusi/komentar per modul
- **Fitur lanjutan (nice-to-have):**
  - Sertifikat otomatis (generate PDF via DomPDF/Snappy setelah course selesai)
  - Leaderboard/gamifikasi ringan (poin, badge) — bisa dikaitkan dengan hasil CTF internal
  - Notifikasi progress via email
  - Integrasi tampilan statistik CTFd (misal: leaderboard CTF ditampilkan di dashboard e-learning)
- **Tech stack:**
  - Laravel + Filament (admin/instruktur panel)
  - Livewire untuk interaksi quiz real-time
  - Laravel Sanctum jika nanti perlu API mobile
  - MySQL, Redis (cache + queue untuk grading/generate sertifikat)
- **Non-functional requirements:**
  - Backup rutin data progress/nilai (harian, retensi minimal 30 hari)
  - Keamanan RBAC ketat antara peserta dan instruktur
  - Skalabilitas moderate menjelang deadline tugas (potensi lonjakan trafik singkat)

### 4.3 Platform CTFd
- **Target pengguna:** Peserta kompetisi (internal & eksternal).
- **Fitur wajib (MVP):**
  - [ ] Setup CTFd standar (challenge, scoreboard, teams)
  - [ ] Registrasi peserta (gunakan fitur registrasi bawaan CTFd, atau integrasi form dari Website Profil untuk konsistensi data — pilih salah satu, jangan dua sumber data)
  - [ ] Sponsor banner/halaman sponsor statis (ditempel di landing page CTFd)
  - [ ] Integrasi pembayaran hanya jika lomba berbayar (Midtrans/Xendit)
- **Non-functional requirements (KRITIKAL):**
  - Harus stabil dan tidak down selama periode lomba berlangsung
  - Load-testing wajib sebelum event (k6 atau Locust, simulasikan beban puncak submission flag bersamaan)
  - Isolasi infrastruktur: CTFd idealnya di VPS/container terpisah dari Website Profil dan E-Learning, dengan Docker network sendiri — jangan share database atau app server yang sama, agar bug/downtime di platform lain tidak berdampak ke CTFd saat lomba
  - Reverse proxy dengan rate limiting (Nginx + fail2ban) untuk mencegah brute-force flag submission
  - Backup/snapshot VPS sebelum dan selama event (interval singkat, misal tiap 1-2 jam saat lomba berjalan)
  - Monitoring real-time (Uptime Kuma atau Grafana+Prometheus) dengan alert ke Telegram/Discord pengurus
  - Estimasi jumlah peserta bersamaan: **[ISI DI SINI]** — krusial untuk sizing VPS dan load test

## 4.4 Arsitektur Domain & Routing

> Detail teknis reverse proxy, konfigurasi Nginx, dan TLS ada di `docs/ARCHITECTURE.md`. Bagian ini hanya ringkasan keputusan arsitektur untuk konteks requirement.

-   **Domain utama:** `csirt.pcr.ac.id`
-   **DNS A Record** domain utama sudah diarahkan ke IP VPS (dikonfirmasi oleh pengelola jaringan kampus).
-   **Strategi routing:** subdomain per platform (bukan sub-path), dengan pemetaan sebagai berikut:

Platform

Subdomain

Website Profil

`csirt.pcr.ac.id` (root domain)

E-Learning

`learn.csirt.pcr.ac.id`

CTFd

`ctf.csirt.pcr.ac.id`

-   **Konsekuensi requirement:** perlu permintaan tambahan ke pengelola jaringan kampus untuk DNS record `learn.csirt.pcr.ac.id` dan `ctf.csirt.pcr.ac.id` (A record ke IP VPS yang sama, atau CNAME ke `csirt.pcr.ac.id`) — lihat `docs/ARCHITECTURE.md` § DNS.
-   Alasan pemilihan subdomain vs sub-path dijelaskan di `docs/ARCHITECTURE.md`.

## 5. Timeline & Milestone

| Fase | Target Selesai | Status |
|---|---|---|
| Environment setup (VPS, akses aman, firewall) | Selesai | ✅ |
| Struktur proyek & dokumentasi | Berjalan | 🔄 |
| Website Profil | 4–6 minggu setelah requirement final | ⏳ |
| E-Learning | 8–12 minggu setelah Website Profil selesai | ⏳ |
| CTFd (setup + load test) | 2–4 minggu, selesai minimal 2 minggu sebelum event pertama | ⏳ |

## 6. Success Metrics
- Jumlah anggota aktif yang menggunakan e-learning (target retensi, misal >70% anggota baru login dalam 1 bulan)
- Completion rate modul e-learning
- Jumlah peserta CTF (internal & eksternal) dibanding target
- Uptime CTFd selama periode lomba (target ≥99.5%)
- Waktu respons rata-rata website di bawah threshold tertentu (misal <500ms)
- Jumlah pendaftar anggota baru via formulir website

## 7. Out of Scope (untuk fase ini)
- Aplikasi mobile native
- Integrasi payment gateway untuk e-learning (kecuali CTFd berbayar)
- Sertifikasi standar SCORM/xAPI penuh
- Single Sign-On (SSO) terpadu lintas ketiga platform (isolasi CTFd lebih diprioritaskan daripada kenyamanan SSO)
- Fitur AI/rekomendasi otomatis

## 8. Open Questions
- Berapa estimasi peserta CTF bersamaan pada event terbesar yang direncanakan?
- Apakah lomba CTF akan berbayar (butuh payment gateway) atau gratis?
- Apakah dibutuhkan konsistensi akun (SSO) antara Website Profil dan E-Learning, atau cukup terpisah?
- Berapa budget VPS/infrastruktur yang tersedia (menentukan spesifikasi untuk load CTFd)?
- Siapa yang bertanggung jawab menjaga CTFd selama lomba berlangsung (on-call incident response)?