@extends('layouts.app', [
    'title' => $album->title,
    'description' => $album->description
        ? Str::limit(Str::squish($album->description), 160)
        : $album->photos->count().' foto dokumentasi '.$album->title.', '.$album->event_date_label.'.',
    'ogImage' => $album->photos->first()?->url,
    'ogImageAlt' => $album->photos->first()?->caption ?? 'Foto dokumentasi '.$album->title,
])

@section('content')
    <div class="mx-auto max-w-screen-xl px-4 py-10 lg:py-14">
        <nav aria-label="Breadcrumb" class="mb-6 text-sm text-csirt-neutral-600">
            <a href="{{ route('galeri.index') }}" class="hover:text-csirt-primary">Galeri</a>
            <span aria-hidden="true" class="mx-2">/</span>
            <span class="text-csirt-neutral-900">{{ $album->title }}</span>
        </nav>

        <header class="max-w-3xl">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-csirt-navy md:text-4xl">{{ $album->title }}</h1>
            <p class="mt-4 text-csirt-neutral-600">
                <time datetime="{{ $album->event_date->toDateString() }}">{{ $album->event_date_label }}</time>
                <span aria-hidden="true" class="mx-2">·</span>{{ $album->photos->count() }} foto
            </p>
            @if ($album->description)
                {{-- Teks polos: di-escape Blade, baris baru dipertahankan lewat CSS. --}}
                <p class="mt-4 whitespace-pre-line text-lg text-csirt-neutral-900">{{ $album->description }}</p>
            @endif
            @if ($post)
                <p class="mt-4">
                    <a href="{{ route('berita.show', $post) }}" class="font-medium text-csirt-primary hover:underline">Baca tulisannya: {{ $post->title }} →</a>
                </p>
            @endif
        </header>

        @if ($album->photos->isEmpty())
            <p class="py-16 text-center text-csirt-neutral-600">Belum ada foto di album ini.</p>
        @else
            {{-- data-gallery: lightbox PhotoSwipe (resources/js/gallery.js). Tanpa JavaScript,
                 tautan tetap membuka foto ukuran penuh. --}}
            <div data-gallery class="mt-8 grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-3 lg:grid-cols-4">
                @foreach ($album->photos as $photo)
                    {{-- Kotak persegi: 2 kolom < 640 px, 3 kolom, lalu 4 kolom (maks. 303 px), dikali cropFactor()
                         karena foto dipotong object-cover. Lightbox memakai srcset yang sama (data-pswp-srcset). --}}
                    @php($f = $photo->cropFactor())
                    <a href="{{ $photo->url }}"
                       data-pswp-width="{{ $photo->width }}" data-pswp-height="{{ $photo->height }}" data-cropped="true"
                       @if ($photo->srcset) data-pswp-srcset="{{ $photo->srcset }}" @endif
                       @if ($photo->caption) data-caption="{{ $photo->caption }}" @endif
                       class="group block aspect-square overflow-hidden rounded-base bg-csirt-neutral-100 focus:outline-hidden focus:ring-4 focus:ring-csirt-primary-light">
                        <x-responsive-image :src="$photo->thumb_url" :srcset="$photo->srcset" loading="lazy"
                                            :alt="$photo->caption ?? 'Foto '.$loop->iteration.' dari album '.$album->title"
                                            :sizes="'(min-width: 1280px) '.round(303 * $f).'px, (min-width: 1024px) calc((100vw - 68px) / 4 * '.$f.'), (min-width: 640px) calc((100vw - 56px) / 3 * '.$f.'), calc((100vw - 40px) / 2 * '.$f.')'"
                                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105 motion-reduce:transition-none" />
                    </a>
                @endforeach
            </div>
        @endif

        <p class="mt-10">
            <a href="{{ route('galeri.index') }}" class="font-medium text-csirt-primary hover:underline">← Kembali ke Galeri</a>
        </p>
    </div>
@endsection
