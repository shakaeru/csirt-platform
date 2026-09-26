# apps/website — Website Profil UKM CSIRT

Aturan proyek (keamanan, Git, kerja remote ke VPS) ada di `CLAUDE.md` di root repo dan `docs/`. File ini hanya menambah konteks khusus aplikasi ini.

## Stack

- Laravel 13 (PHP ≥ 8.3; lokal WSL: 8.5), database lokal SQLite (`database/database.sqlite`).
- **Livewire 4** + Breeze stack **Livewire/Volt** — halaman auth publik di `resources/views/livewire/pages/auth/`, route di `routes/auth.php`.
- **Filament 5** — panel admin di `/admin` (`app/Providers/Filament/AdminPanelProvider.php`), login sendiri di `/admin/login`. Aset Filament di `public/{css,js,fonts}/filament` di-generate `filament:upgrade` (composer post-autoload-dump) dan di-ignore git.
- Tailwind CSS **v4** lewat `@tailwindcss/vite`. Tidak ada `tailwind.config.js`: token warna ada di blok `@theme` pada `resources/css/app.css`, disalin dari `assets/brand/design-tokens.css` (sumber kebenaran, di root repo).
- Flowbite 4 (plugin CSS + JS di-bundle dari npm, bukan CDN) dan GSAP (`window.gsap`).

## Aturan khusus

- **Alpine jangan di-import di `resources/js/app.js`.** Livewire sudah membawa dan menjalankan Alpine sendiri; import terpisah = dua instance Alpine.
- **Warna pakai kelas `csirt-*`** (`bg-csirt-primary`, `text-csirt-navy`, dst.), bukan warna default Tailwind (`blue-600`, dsb.).
- Aset yang dipakai halaman (logo, gambar) disalin ke `public/images/`; jangan merujuk langsung ke `assets/brand/` di luar aplikasi.
- **Sebelum deploy ke production:** `App\Models\User` wajib mengimplementasikan `Filament\Models\Contracts\FilamentUser::canAccessPanel()`. Di luar environment `local`, Filament menolak (403) semua user yang belum lolos pengecekan ini — aman sebagai default, tapi admin pun tidak bisa masuk.

## Perintah

```bash
npm run build          # build aset (Vite)
npm run dev            # dev server Vite
php artisan serve      # server lokal
php artisan migrate    # JANGAN migrate:fresh tanpa konfirmasi — bisa menghapus data lokal
php artisan test
```
