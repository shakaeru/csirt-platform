@extends('layouts.app', ['title' => 'Struktur Organisasi'])

@section('content')
    <section class="bg-csirt-neutral-100">
        <div class="mx-auto max-w-screen-xl px-4 py-12 text-center lg:py-16">
            <h1 class="text-3xl font-extrabold tracking-tight text-csirt-navy md:text-4xl">Struktur Organisasi</h1>
            @if ($period)
                <p class="mt-3 text-lg text-csirt-neutral-600">Kepengurusan periode {{ $period->name }}</p>
            @endif
        </div>
    </section>

    @if ($pembina->isEmpty() && $inti->isEmpty() && $divisions->isEmpty())
        <section class="mx-auto max-w-screen-xl px-4 py-16 text-center">
            <p class="text-csirt-neutral-600">Struktur organisasi sedang diperbarui. Silakan kembali lagi nanti.</p>
        </section>
    @else
        <div class="mx-auto max-w-screen-xl space-y-16 px-4 py-12 lg:py-16">
            @foreach (['Pembina' => $pembina, 'Pengurus Inti' => $inti] as $heading => $members)
                @if ($members->isNotEmpty())
                    <section aria-labelledby="{{ Str::slug($heading) }}">
                        <h2 id="{{ Str::slug($heading) }}" class="mb-8 text-center text-2xl font-bold text-csirt-navy">{{ $heading }}</h2>
                        {{-- flex-wrap rata tengah: 2 per baris di ponsel, baris terakhir yang tidak penuh tetap di tengah. --}}
                        <div class="flex flex-wrap justify-center gap-x-6 gap-y-10 sm:gap-x-12">
                            @foreach ($members as $member)
                                <x-member-card :member="$member" large class="w-[calc(50%-0.75rem)] sm:w-48" />
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach

            @foreach ($divisions as $group)
                <section aria-labelledby="divisi-{{ $group['division']->id }}">
                    <div class="mb-8 text-center">
                        <h2 id="divisi-{{ $group['division']->id }}" class="text-2xl font-bold text-csirt-navy">{{ $group['division']->name }}</h2>
                        @if ($group['division']->description)
                            <p class="mx-auto mt-2 max-w-2xl text-csirt-neutral-600">{{ $group['division']->description }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap justify-center gap-x-6 gap-y-10">
                        @foreach ($group['members'] as $member)
                            <x-member-card :member="$member" class="w-[calc(50%-0.75rem)] sm:w-44" />
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
@endsection
