{{-- Satu prestasi di halaman Prestasi. Semua isian teks polos (di-escape); id untuk tautan #prestasi-{id}. --}}
@props(['achievement'])

<article id="prestasi-{{ $achievement->id }}" {{ $attributes->class(['flex scroll-mt-28 flex-col overflow-hidden rounded-base border border-csirt-neutral-100 bg-csirt-white sm:flex-row']) }}>
    @if ($achievement->photo_url)
        <div class="aspect-video bg-csirt-navy sm:aspect-auto sm:w-56 sm:shrink-0">
            {{-- < 640 px: foto selebar kartu. ≥ 640 px: kolom 224 px (sm:w-56) setinggi kartu dan dipotong
                 object-cover, jadi lebar gambar yang tampil bisa ±400 px pada kartu yang tinggi. --}}
            <x-responsive-image :src="$achievement->photo_url" :srcset="$achievement->photo_srcset" width="1200" height="675" loading="lazy"
                                class="h-full w-full object-cover" sizes="(min-width: 640px) 400px, calc(100vw - 32px)" />
        </div>
    @endif
    <div class="flex flex-1 flex-col p-5">
        <p><span class="inline-block rounded bg-csirt-accent-lime px-2.5 py-1 text-sm font-bold text-csirt-navy">{{ $achievement->result }}</span></p>
        <h3 class="mt-3 text-lg font-bold leading-snug text-csirt-navy">{{ $achievement->competition }}</h3>
        <p class="mt-1 text-sm text-csirt-neutral-600">
            @if ($achievement->organizer){{ $achievement->organizer }}<span aria-hidden="true" class="mx-1">·</span>@endif{{ $achievement->level->getLabel() }}<span aria-hidden="true" class="mx-1">·</span>{{ $achievement->category->getLabel() }}<span aria-hidden="true" class="mx-1">·</span><time datetime="{{ $achievement->achieved_on->format('Y-m') }}">{{ $achievement->achieved_month }}</time>
        </p>

        @if ($achievement->team_name || $achievement->member_list)
            <dl class="mt-3 space-y-1 text-sm text-csirt-neutral-900">
                @if ($achievement->team_name)
                    <div><dt class="inline font-semibold">Tim:</dt> <dd class="inline">{{ $achievement->team_name }}</dd></div>
                @endif
                @if ($achievement->member_list)
                    <div><dt class="inline font-semibold">Anggota:</dt> <dd class="inline">{{ implode(', ', $achievement->member_list) }}</dd></div>
                @endif
            </dl>
        @endif

        @if ($achievement->description)
            <p class="mt-3 whitespace-pre-line text-sm text-csirt-neutral-600">{{ $achievement->description }}</p>
        @endif

        @php $post = $achievement->post?->isPublished() ? $achievement->post : null; @endphp
        @if ($post || $achievement->result_url)
            <p class="mt-auto flex flex-wrap gap-x-5 gap-y-1 pt-4 text-sm font-medium">
                @if ($post)
                    <a href="{{ route('berita.show', $post) }}" class="text-csirt-primary hover:underline">Baca beritanya →</a>
                @endif
                @if ($achievement->result_url)
                    <a href="{{ $achievement->result_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-csirt-primary hover:underline">Lihat hasil<svg aria-hidden="true" class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 14v4.8a1.2 1.2 0 0 1-1.2 1.2H5.2A1.2 1.2 0 0 1 4 18.8V7.2A1.2 1.2 0 0 1 5.2 6h4.6m4.4-2H20v5.8m-7.1-.7L19.5 4"/></svg><span class="sr-only"> (membuka situs lain)</span></a>
                @endif
            </p>
        @endif
    </div>
</article>
