// Alpine sengaja TIDAK di-import di sini: Livewire sudah membawa dan menjalankan Alpine
// sendiri (window.Alpine). Import terpisah = dua instance Alpine di halaman Livewire.
// Livewire hanya menyuntikkan script-nya di halaman yang memuat komponen Livewire —
// kalau halaman publik tanpa komponen butuh Alpine, pasang @livewireScripts di layout-nya.
import 'flowbite';
import gsap from 'gsap';

window.gsap = gsap; // supaya bisa dipanggil langsung dari Blade/Alpine tanpa import berulang
