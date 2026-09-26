@extends('layouts.app', [
    'title' => 'Berita & Kegiatan',
    'description' => 'Berita, kegiatan, dan pengumuman UKM CSIRT Politeknik Caltex Riau.',
])

@section('content')
    <section class="bg-csirt-neutral-100">
        <div class="mx-auto max-w-screen-xl px-4 py-12 text-center lg:py-16">
            <h1 class="text-3xl font-extrabold tracking-tight text-csirt-navy md:text-4xl">Berita & Kegiatan</h1>
            <p class="mt-3 text-lg text-csirt-neutral-600">Kabar terbaru dan dokumentasi kegiatan UKM CSIRT.</p>
        </div>
    </section>

    <div class="mx-auto max-w-screen-xl px-4 py-10 lg:py-12">
        {{-- Filter kategori + pencarian. Semua lewat query string, jadi bisa dibagikan/di-bookmark. --}}
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <nav aria-label="Filter kategori" class="flex flex-wrap gap-2">
                @php $chip = 'rounded-full border px-4 py-1.5 text-sm font-medium'; @endphp
                <a href="{{ route('berita.index', array_filter(['q' => $search])) }}"
                   @class([$chip, 'border-csirt-primary bg-csirt-primary text-csirt-white' => ! $category, 'border-csirt-neutral-100 text-csirt-navy hover:border-csirt-primary-light' => $category])
                   @if (! $category) aria-current="page" @endif>Semua</a>
                @foreach ($categories as $item)
                    @php $active = $category?->is($item); @endphp
                    <a href="{{ route('berita.index', array_filter(['kategori' => $item->slug, 'q' => $search])) }}"
                       @class([$chip, 'border-csirt-primary bg-csirt-primary text-csirt-white' => $active, 'border-csirt-neutral-100 text-csirt-navy hover:border-csirt-primary-light' => ! $active])
                       @if ($active) aria-current="page" @endif>{{ $item->name }}</a>
                @endforeach
            </nav>

            <form method="GET" action="{{ route('berita.index') }}" role="search" class="flex w-full gap-2 lg:w-96">
                @if ($category)
                    <input type="hidden" name="kategori" value="{{ $category->slug }}">
                @endif
                <label for="q" class="sr-only">Cari tulisan</label>
                <input type="search" id="q" name="q" value="{{ $search }}" maxlength="100" placeholder="Cari berita atau kegiatan…"
                       class="block w-full rounded-base border border-csirt-neutral-100 bg-csirt-neutral-100 px-3 py-2 text-sm text-csirt-neutral-900 focus:border-csirt-primary focus:ring-csirt-primary">
                <button type="submit" class="rounded-base bg-csirt-primary px-4 py-2 text-sm font-medium text-csirt-white hover:bg-csirt-primary-lighter focus:outline-hidden focus:ring-4 focus:ring-csirt-primary-light">Cari</button>
            </form>
        </div>

        @if ($tag)
            <p class="mb-6 text-csirt-neutral-600">
                Tag: <span class="font-semibold text-csirt-navy">{{ $tag->name }}</span>
                <a href="{{ route('berita.index', array_filter(['kategori' => $category?->slug, 'q' => $search])) }}" class="ms-2 text-csirt-primary hover:underline">hapus filter</a>
            </p>
        @endif

        @if ($posts->isEmpty())
            <p class="py-16 text-center text-csirt-neutral-600">
                @if ($search !== '')
                    Tidak ada tulisan yang cocok dengan “{{ $search }}”.
                @else
                    Belum ada tulisan. Silakan kembali lagi nanti.
                @endif
            </p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post-card :post="$post" />
                @endforeach
            </div>

            {{ $posts->links('pagination.csirt') }}
        @endif
    </div>
@endsection
