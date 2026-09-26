@extends('layouts.app', ['title' => $post->title, 'description' => $post->summary])

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
            <img src="{{ $post->cover_url }}" alt="" width="1200" height="675" class="mt-8 aspect-video w-full rounded-base object-cover">
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

        <p class="mt-10">
            <a href="{{ route('berita.index') }}" class="font-medium text-csirt-primary hover:underline">← Kembali ke Berita & Kegiatan</a>
        </p>
    </article>
@endsection
