@extends('layouts.app', [
    'title' => $post->title,
    'description' => $post->summary,
    'ogType' => 'article',
    'ogImage' => $post->cover_url,
    'ogImageAlt' => $post->cover_url ? 'Sampul tulisan: '.$post->title : null,
])

@push('head')
    <meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
    <meta property="article:section" content="{{ $post->category->name }}">
    @foreach ($post->tags as $tag)
        <meta property="article:tag" content="{{ $tag->name }}">
    @endforeach
@endpush

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-10 lg:py-14">
        <nav aria-label="Breadcrumb" class="mb-6 text-sm text-csirt-neutral-600">
            <a href="{{ route('berita.index') }}" class="hover:text-csirt-primary">Berita & Kegiatan</a>
            <span aria-hidden="true" class="mx-2">/</span>
            <a href="{{ route('berita.index', ['kategori' => $post->category->slug]) }}" class="hover:text-csirt-primary">{{ $post->category->name }}</a>
        </nav>

        <header>
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-csirt-navy md:text-4xl">{{ $post->title }}</h1>
            <p class="mt-4 text-csirt-neutral-600">
                <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_date }}</time>
                <span aria-hidden="true" class="mx-2">·</span>{{ $post->category->name }}
            </p>
        </header>

        @if ($post->cover_url)
            {{-- Gambar terbesar di atas lipatan: dimuat lebih dulu (tanpa lazy). Lebar konten maks. 736 px (max-w-3xl). --}}
            <x-post-cover :post="$post" :alt="'Sampul tulisan: '.$post->title" fetchpriority="high"
                          class="mt-8 aspect-video w-full rounded-base object-cover"
                          sizes="(min-width: 768px) 736px, calc(100vw - 32px)" />
        @endif

        {{-- renderRichContent() = toHtml() Filament: HTML disanitasi (Symfony HtmlSanitizer).
             Jangan pernah mengganti ini dengan {!! $post->content !!}. --}}
        <div class="post-content mt-8">
            {!! $post->renderRichContent('content') !!}
        </div>

        @if ($post->tags->isNotEmpty())
            <footer class="mt-10 border-t border-csirt-neutral-100 pt-6">
                <h2 class="sr-only">Tag</h2>
                <ul class="flex flex-wrap gap-2">
                    @foreach ($post->tags as $tag)
                        <li>
                            <a href="{{ route('berita.index', ['tag' => $tag->slug]) }}"
                               class="rounded-full bg-csirt-neutral-100 px-3 py-1 text-sm text-csirt-navy hover:text-csirt-primary">#{{ $tag->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </footer>
        @endif

        @foreach ($albums as $album)
            {{-- Album galeri yang ditautkan ke tulisan ini (hanya yang terbit). Cuplikan saja, lengkapnya di halaman album. --}}
            <section class="mt-10 border-t border-csirt-neutral-100 pt-6" aria-labelledby="album-{{ $album->id }}">
                <h2 id="album-{{ $album->id }}" class="text-xl font-bold text-csirt-navy">Dokumentasi: {{ $album->title }}</h2>
                <div class="mt-4 grid grid-cols-3 gap-2">
                    @foreach ($album->photos as $photo)
                        <a href="{{ route('galeri.show', $album) }}" class="block aspect-square overflow-hidden rounded-base bg-csirt-neutral-100" tabindex="-1" aria-hidden="true">
                            <img src="{{ $photo->thumb_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        </a>
                    @endforeach
                </div>
                <p class="mt-3">
                    <a href="{{ route('galeri.show', $album) }}" class="font-medium text-csirt-primary hover:underline">Lihat semua {{ $album->photos_count }} foto →</a>
                </p>
            </section>
        @endforeach

        <p class="mt-10">
            <a href="{{ route('berita.index') }}" class="font-medium text-csirt-primary hover:underline">← Kembali ke Berita & Kegiatan</a>
        </p>
    </article>
@endsection
