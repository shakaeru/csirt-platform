{{-- Kartu satu album di halaman Galeri. Sampul = foto pertama album (relasi `cover`). --}}
@props(['album'])

<article {{ $attributes->class(['group overflow-hidden rounded-base border border-csirt-neutral-100 bg-csirt-white']) }}>
    <a href="{{ route('galeri.show', $album) }}" class="block">
        <div class="aspect-[4/3] overflow-hidden bg-csirt-navy">
            <img src="{{ $album->cover->thumb_url }}" alt="" loading="lazy"
                 class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105 motion-reduce:transition-none">
        </div>
        <div class="p-5">
            <h2 class="text-lg font-bold leading-snug text-csirt-navy group-hover:text-csirt-primary">{{ $album->title }}</h2>
            <p class="mt-2 text-sm text-csirt-neutral-600">
                <time datetime="{{ $album->event_date->toDateString() }}">{{ $album->event_date_label }}</time>
                <span aria-hidden="true" class="mx-1">·</span>{{ $album->photos_count }} foto
            </p>
        </div>
    </a>
</article>
