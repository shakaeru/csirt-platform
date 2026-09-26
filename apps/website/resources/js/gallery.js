// Lightbox galeri (PhotoSwipe 5). Dimuat lewat dynamic import dari app.js hanya di halaman yang
// punya [data-gallery], jadi halaman lain tidak ikut memuat JS/CSS-nya.
// Markup: <a href="foto.jpg" data-pswp-width data-pswp-height [data-caption]><img thumbnail></a>.
import PhotoSwipeLightbox from 'photoswipe/lightbox';
import 'photoswipe/style.css';

export function initGallery(gallery) {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const lightbox = new PhotoSwipeLightbox({
        gallery,
        children: 'a[data-pswp-width]',
        pswpModule: () => import('photoswipe'),
        showHideAnimationType: reduceMotion ? 'none' : 'zoom',
        bgOpacity: 1,
        // Ruang untuk tombol (layar lebar) dan keterangan foto, supaya tidak menutupi foto.
        paddingFn: (viewportSize, itemData) => {
            const wide = viewportSize.x >= 768;
            const caption = Boolean(itemData.element?.dataset.caption);

            return {
                top: wide ? 48 : 0,
                bottom: caption ? 64 : (wide ? 48 : 0),
                left: wide ? 72 : 0,
                right: wide ? 72 : 0,
            };
        },
        // Teks tombol (title/aria-label) dalam bahasa Indonesia.
        closeTitle: 'Tutup',
        zoomTitle: 'Perbesar',
        arrowPrevTitle: 'Foto sebelumnya',
        arrowNextTitle: 'Foto berikutnya',
        errorMsg: 'Foto tidak dapat dimuat.',
        indexIndicatorSep: ' / ',
    });

    // Keterangan foto (data-caption) di bawah layar. textContent, bukan innerHTML.
    lightbox.on('uiRegister', () => {
        lightbox.pswp.ui.registerElement({
            name: 'caption',
            appendTo: 'root',
            onInit: (element, pswp) => {
                pswp.on('change', () => {
                    const caption = pswp.currSlide?.data.element?.dataset.caption ?? '';
                    element.textContent = caption;
                    element.hidden = caption === '';
                });
            },
        });
    });

    lightbox.init();
}
