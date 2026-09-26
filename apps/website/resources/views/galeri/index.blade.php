@extends('layouts.app', [
    'title' => 'Galeri',
    'description' => 'Dokumentasi foto kegiatan UKM CSIRT Politeknik Caltex Riau.',
])

@section('content')
    <section class="bg-csirt-neutral-100">
        <div class="mx-auto max-w-screen-xl px-4 py-12 text-center lg:py-16">
            <h1 class="text-3xl font-extrabold tracking-tight text-csirt-navy md:text-4xl">Galeri</h1>
            <p class="mt-3 text-lg text-csirt-neutral-600">Dokumentasi kegiatan UKM CSIRT.</p>
        </div>
    </section>

    <div class="mx-auto max-w-screen-xl px-4 py-10 lg:py-12">
        @if ($albums->isEmpty())
            <p class="py-16 text-center text-csirt-neutral-600">Belum ada album. Silakan kembali lagi nanti.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($albums as $album)
                    <x-album-card :album="$album" />
                @endforeach
            </div>

            {{ $albums->links('pagination.csirt') }}
        @endif
    </div>
@endsection
