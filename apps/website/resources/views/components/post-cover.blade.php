{{-- Sampul tulisan: varian WebP 400/800/1200 px lewat srcset (bila sudah dibuat, lihat
     Post::refreshCoverVariants()), file asli sebagai cadangan. `sizes` = lebar tampil di tiap
     breakpoint, harus sesuai layout pemanggil. Atribut lain (class, loading, dsb.) diteruskan ke <img>.
     <picture> memakai display: contents supaya ukuran <img> tetap mengikuti pembungkusnya. --}}
@props(['post', 'sizes', 'alt' => ''])

<picture class="contents">
    @if ($post->cover_srcset)
        <source type="image/webp" srcset="{{ $post->cover_srcset }}" sizes="{{ $sizes }}">
    @endif
    <img src="{{ $post->cover_url }}" alt="{{ $alt }}" width="1200" height="675" {{ $attributes }}>
</picture>
