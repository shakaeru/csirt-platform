@extends('layouts.app', [
    'title' => 'Prestasi'.($category ? ': '.$category->getLabel() : ''),
    'description' => $category
        ? 'Daftar prestasi anggota UKM CSIRT Politeknik Caltex Riau, kategori '.$category->getLabel().'.'
        : 'Prestasi anggota UKM CSIRT Politeknik Caltex Riau di kompetisi CTF dan lomba lainnya.',
    'canonicalQuery' => ['jenis' => $category?->value],
])

@section('content')
    <section class="bg-csirt-neutral-100">
        <div class="mx-auto max-w-screen-xl px-4 py-12 text-center lg:py-16">
            <h1 class="text-3xl font-extrabold tracking-tight text-csirt-navy md:text-4xl">Prestasi</h1>
            <p class="mt-3 text-lg text-csirt-neutral-600">Capaian anggota UKM CSIRT di kompetisi CTF dan lomba lainnya.</p>

            @if ($stats['total'] > 0)
                <dl class="mx-auto mt-8 grid max-w-3xl grid-cols-3 gap-3 sm:gap-4">
                    @foreach ([['Prestasi', $stats['total']], ['Prestasi CTF', $stats['ctf']], ['Nasional & internasional', $stats['national']]] as [$label, $value])
                        {{-- column-reverse: angka tampil di atas label; justify-end = rapat ke atas. --}}
                        <div class="flex flex-col-reverse justify-end rounded-base bg-csirt-white p-4">
                            <dt class="mt-1 text-xs text-csirt-neutral-600 sm:text-sm">{{ $label }}</dt>
                            <dd class="text-3xl font-extrabold text-csirt-primary">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-screen-xl px-4 py-10 lg:py-12">
        <nav aria-label="Filter jenis" class="mb-8 flex flex-wrap gap-2">
            @php $chip = 'rounded-full border px-4 py-1.5 text-sm font-medium'; @endphp
            <a href="{{ route('prestasi') }}"
               @class([$chip, 'border-csirt-primary bg-csirt-primary text-csirt-white' => ! $category, 'border-csirt-neutral-100 text-csirt-navy hover:border-csirt-primary-light' => $category])
               @if (! $category) aria-current="page" @endif>Semua</a>
            @foreach (\App\Enums\AchievementCategory::cases() as $item)
                @php $active = $category === $item; @endphp
                <a href="{{ route('prestasi', ['jenis' => $item->value]) }}"
                   @class([$chip, 'border-csirt-primary bg-csirt-primary text-csirt-white' => $active, 'border-csirt-neutral-100 text-csirt-navy hover:border-csirt-primary-light' => ! $active])
                   @if ($active) aria-current="page" @endif>{{ $item->getLabel() }}</a>
            @endforeach
        </nav>

        @forelse ($years as $year => $achievements)
            <section aria-labelledby="tahun-{{ $year }}" class="mb-12 last:mb-0">
                <h2 id="tahun-{{ $year }}" class="mb-6 flex items-center gap-4 text-2xl font-extrabold text-csirt-navy">
                    {{ $year }}<span aria-hidden="true" class="h-px flex-1 bg-csirt-neutral-100"></span>
                </h2>
                <div class="grid gap-6 lg:grid-cols-2">
                    @foreach ($achievements as $achievement)
                        <x-achievement-card :achievement="$achievement" />
                    @endforeach
                </div>
            </section>
        @empty
            <p class="py-16 text-center text-csirt-neutral-600">Belum ada prestasi yang dicatat. Silakan kembali lagi nanti.</p>
        @endforelse
    </div>
@endsection
