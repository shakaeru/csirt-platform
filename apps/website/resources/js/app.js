// Alpine sengaja TIDAK di-import di sini: Livewire sudah membawa dan menjalankan Alpine
// sendiri (window.Alpine). Import terpisah = dua instance Alpine di halaman Livewire.
// Livewire hanya menyuntikkan script-nya di halaman yang memuat komponen Livewire —
// kalau halaman publik tanpa komponen butuh Alpine, pasang @livewireScripts di layout-nya.
import 'flowbite';
import gsap from 'gsap';

window.gsap = gsap; // supaya bisa dipanggil langsung dari Blade/Alpine tanpa import berulang

// Proof-of-concept GSAP: navbar fade-in saat halaman dimuat. Script modul Vite berjalan
// setelah HTML selesai di-parse, jadi elemen sudah ada. Dilewati kalau pengguna memilih
// mengurangi animasi (prefers-reduced-motion).
const navbar = document.querySelector('[data-animate="navbar"]');
if (navbar && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const intro = gsap.from(navbar, { opacity: 0, y: -12, duration: 0.6, ease: 'power2.out', clearProps: 'opacity,transform' });
    // Pengaman: di lingkungan yang tidak menjalankan frame animasi (renderer headless mesin
    // pencari, layanan screenshot/pratinjau) navbar bisa tertahan hampir tak terlihat.
    // Setelah 1,5 detik paksa ke kondisi akhir; di browser normal animasi sudah selesai.
    setTimeout(() => intro.progress(1), 1500);
}

// Galeri: lightbox PhotoSwipe hanya dimuat di halaman yang membutuhkannya (code-splitting Vite).
const gallery = document.querySelector('[data-gallery]');
if (gallery) {
    import('./gallery').then(({ initGallery }) => initGallery(gallery));
}
