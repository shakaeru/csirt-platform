import './bootstrap';
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
import 'flowbite';
import gsap from 'gsap';
window.gsap = gsap; // supaya bisa dipanggil langsung dari Blade/Alpine tanpa import berulang