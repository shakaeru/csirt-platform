{{-- Gambar unggahan dengan varian WebP lewat srcset (lihat App\Models\Concerns\HasResponsiveImage),
     file asli sebagai cadangan. `sizes` = lebar tampil di tiap breakpoint, harus sesuai layout pemanggil.
     Atribut lain (width, height, class, loading, fetchpriority) diteruskan ke <img>. <picture> memakai
     display: contents supaya ukuran <img> tetap mengikuti pembungkusnya. --}}
@props(['src', 'srcset' => null, 'sizes', 'alt' => ''])

<picture class="contents">
    @if ($srcset)
        <source type="image/webp" srcset="{{ $srcset }}" sizes="{{ $sizes }}">
    @endif
    <img src="{{ $src }}" alt="{{ $alt }}" {{ $attributes }}>
</picture>
