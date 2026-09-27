{{-- Beranda. Teks visi dan program kerja dari config/profil.php (sumber yang sama dengan halaman Tentang).
     Teks h1 dicek HomePageTest; ubah tesnya juga bila h1 diganti. JSON-LD Organization/WebSite: App\Support\Seo. --}}
@extends('layouts.app', [
    'description' => 'UKM CSIRT Politeknik Caltex Riau (CSIRT PCR) adalah unit kegiatan mahasiswa bidang keamanan siber. Anggotanya berlatih rutin dan ikut kompetisi Capture The Flag.',
])

@push('head')
    <script type="application/ld+json">{!! \App\Support\Seo::jsonLd(\App\Support\Seo::organizationSchema()) !!}</script>
@endpush

@section('content')
    @php
        $profil = config('profil');
        $unggulan = collect($profil['program_kerja'])->where('unggulan', true)->take(3);
    @endphp

    <section class="bg-csirt-neutral-100">
        <div class="mx-auto max-w-screen-xl px-4 py-16 text-center lg:py-24">
            <h1 class="text-3xl font-extrabold tracking-tight text-csirt-navy md:text-4xl lg:text-5xl">UKM CSIRT Politeknik Caltex Riau</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-csirt-neutral-600 lg:text-xl">
                Computer Security Incident Response Team (CSIRT) adalah unit kegiatan mahasiswa Politeknik Caltex Riau di bidang keamanan siber.
                Anggotanya belajar lewat pelatihan rutin dan menguji kemampuan di kompetisi Capture The Flag (CTF).
            </p>
            <a href="{{ route('tentang') }}" class="mt-8 inline-block rounded-base bg-csirt-primary px-6 py-3 font-medium text-csirt-white hover:bg-csirt-primary-lighter focus:outline-hidden focus:ring-4 focus:ring-csirt-primary-light">Tentang UKM CSIRT</a>
        </div>
    </section>

    <div class="mx-auto max-w-screen-xl space-y-16 px-4 py-12 lg:space-y-20 lg:py-16">
        <section aria-labelledby="visi" class="rounded-base bg-csirt-navy px-6 py-10 sm:px-10 lg:py-14">
            <h2 id="visi" class="text-sm font-semibold uppercase tracking-wider text-csirt-accent-lime">Visi</h2>
            <p class="mt-4 max-w-4xl text-xl leading-relaxed text-csirt-white lg:text-2xl">{{ $profil['visi'] }}</p>
            <a href="{{ route('tentang') }}#visi-misi" class="mt-6 inline-block font-medium text-csirt-accent-lime hover:underline">Baca visi &amp; misi lengkap →</a>
        </section>

        <section aria-labelledby="program-unggulan">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 id="program-unggulan" class="text-2xl font-bold text-csirt-navy">Program Kerja Unggulan</h2>
                    <p class="mt-1 text-csirt-neutral-600">Periode {{ $profil['periode_program'] }}</p>
                </div>
                <a href="{{ route('tentang') }}#program-kerja" class="font-medium text-csirt-primary hover:underline">Lihat semua program kerja →</a>
            </div>
            <div class="mt-6 grid gap-6 md:grid-cols-3">
                @foreach ($unggulan as $program)
                    <article class="rounded-base border border-csirt-neutral-100 bg-csirt-white p-6">
                        <h3 class="text-lg font-bold text-csirt-navy">{{ $program['nama'] }}</h3>
                        <p class="mt-2 text-csirt-neutral-600">{{ $program['deskripsi'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="ikuti" class="rounded-base bg-csirt-neutral-100 px-6 py-10 text-center sm:px-10">
            <h2 id="ikuti" class="text-2xl font-bold text-csirt-navy">Laporan kegiatan dan hasil lomba</h2>
            <p class="mx-auto mt-2 max-w-xl text-csirt-neutral-600">Setiap kegiatan dilaporkan di Berita beserta foto dokumentasinya. Hasil lomba yang diikuti anggota dicatat di halaman Prestasi.</p>
            <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('berita.index') }}" class="rounded-base bg-csirt-primary px-5 py-3 font-medium text-csirt-white hover:bg-csirt-primary-lighter focus:outline-hidden focus:ring-4 focus:ring-csirt-primary-light">Berita &amp; Kegiatan</a>
                <a href="{{ route('galeri.index') }}" class="rounded-base border border-csirt-primary px-5 py-3 font-medium text-csirt-primary hover:bg-csirt-white focus:outline-hidden focus:ring-4 focus:ring-csirt-primary-light">Galeri</a>
                <a href="{{ route('prestasi') }}" class="rounded-base border border-csirt-primary px-5 py-3 font-medium text-csirt-primary hover:bg-csirt-white focus:outline-hidden focus:ring-4 focus:ring-csirt-primary-light">Prestasi</a>
            </div>
        </section>

        <section aria-labelledby="bergabung" class="flex flex-col gap-4 rounded-base border border-csirt-neutral-100 px-6 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-10">
            <div>
                <h2 id="bergabung" class="text-2xl font-bold text-csirt-navy">Ingin bergabung?</h2>
                <p class="mt-1 text-csirt-neutral-600">Status pendaftaran anggota baru dan kontak pengurus ada di halaman Kontak.</p>
            </div>
            <a href="{{ route('kontak') }}#pendaftaran" class="shrink-0 rounded-base bg-csirt-primary px-5 py-3 text-center font-medium text-csirt-white hover:bg-csirt-primary-lighter focus:outline-hidden focus:ring-4 focus:ring-csirt-primary-light">Info pendaftaran</a>
        </section>
    </div>
@endsection
