{{-- Kartu satu tulisan di daftar Berita & Kegiatan. Tanpa sampul → blok warna brand. --}}
@props(['post'])

<article {{ $attributes->class(['flex flex-col overflow-hidden rounded-base border border-csirt-neutral-100 bg-csirt-white']) }}>
    <a href="{{ route('berita.show', $post) }}" class="block aspect-video bg-csirt-navy" tabindex="-1" aria-hidden="true">
        @if ($post->cover_url)
            <img src="{{ $post->cover_url }}" alt="" width="1200" height="675" loading="lazy" class="h-full w-full object-cover">
        @else
            <span class="flex h-full items-center justify-center text-lg font-semibold text-csirt-white/80">{{ $post->category->name }}</span>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-5">
        <div class="mb-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
            <a href="{{ route('berita.index', ['kategori' => $post->category->slug]) }}"
               class="rounded bg-csirt-neutral-100 px-2 py-0.5 font-medium text-csirt-primary hover:underline">{{ $post->category->name }}</a>
            <time datetime="{{ $post->published_at->toIso8601String() }}" class="text-csirt-neutral-600">{{ $post->published_date }}</time>
        </div>
        <h2 class="text-lg font-bold leading-snug text-csirt-navy">
            <a href="{{ route('berita.show', $post) }}" class="hover:text-csirt-primary">{{ $post->title }}</a>
        </h2>
        <p class="mt-2 line-clamp-3 text-csirt-neutral-600">{{ $post->summary }}</p>
    </div>
</article>
