{{-- Halaman Tentang. Teks visi, misi, dan program kerja dari config/profil.php (juga dipakai Beranda). --}}
@extends('layouts.app', [
    'title' => 'Tentang UKM CSIRT',
    'description' => 'Sejarah UKM CSIRT Politeknik Caltex Riau sejak '.config('profil.tahun_komunitas').', visi dan misi organisasi, serta program kerja periode '.config('profil.periode_program').'.',
])

@section('content')
    @php($profil = config('profil'))

    <section class="bg-csirt-neutral-100">
        <div class="mx-auto max-w-screen-xl px-4 py-12 text-center lg:py-16">
            <h1 class="text-3xl font-extrabold tracking-tight text-csirt-navy md:text-4xl">Tentang UKM CSIRT</h1>
            <p class="mx-auto mt-3 max-w-2xl text-lg text-csirt-neutral-600">Computer Security Incident Response Team, unit kegiatan mahasiswa keamanan siber di Politeknik Caltex Riau.</p>
        </div>
    </section>

    <div class="mx-auto max-w-screen-xl space-y-16 px-4 py-12 lg:space-y-20 lg:py-16">
        <section aria-labelledby="sejarah" class="grid gap-8 lg:grid-cols-3 lg:gap-12">
            <div class="lg:col-span-2">
                <h2 id="sejarah" class="text-2xl font-bold text-csirt-navy">Sejarah Singkat</h2>
                <p class="mt-4 text-lg leading-relaxed text-csirt-neutral-900">
                    UKM CSIRT berawal dari komunitas mahasiswa Politeknik Caltex Riau yang tertarik pada keamanan siber sejak {{ $profil['tahun_komunitas'] }},
                    lalu resmi menjadi Unit Kegiatan Mahasiswa pada {{ $profil['tahun_ukm'] }}. Sejak itu anggotanya rutin berlatih bersama
                    dan mengikuti kompetisi keamanan siber seperti Capture The Flag (CTF).
                </p>
            </div>
            <ol class="space-y-4 border-s-2 border-csirt-secondary ps-6 lg:self-center">
                <li>
                    <p class="text-2xl font-extrabold text-csirt-primary">{{ $profil['tahun_komunitas'] }}</p>
                    <p class="text-csirt-neutral-600">Berdiri sebagai komunitas mahasiswa</p>
                </li>
                <li>
                    <p class="text-2xl font-extrabold text-csirt-primary">{{ $profil['tahun_ukm'] }}</p>
                    <p class="text-csirt-neutral-600">Resmi menjadi Unit Kegiatan Mahasiswa</p>
                </li>
            </ol>
        </section>

        <section id="visi-misi" aria-labelledby="visi-misi-judul" class="scroll-mt-28">
            <h2 id="visi-misi-judul" class="text-2xl font-bold text-csirt-navy">Visi &amp; Misi</h2>
            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <div class="rounded-base bg-csirt-navy p-6 text-csirt-white lg:p-8">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-csirt-accent-lime">Visi</h3>
                    <p class="mt-3 text-lg leading-relaxed">{{ $profil['visi'] }}</p>
                </div>
                <div class="rounded-base border border-csirt-neutral-100 bg-csirt-white p-6 lg:p-8">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-csirt-primary">Misi</h3>
                    <ol class="mt-3 space-y-3">
                        @foreach ($profil['misi'] as $misi)
                            <li class="flex gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-csirt-neutral-100 text-sm font-bold text-csirt-navy">{{ $loop->iteration }}</span>
                                <span class="pt-0.5 text-csirt-neutral-900">{{ $misi }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </section>

        <section id="program-kerja" aria-labelledby="program-kerja-judul" class="scroll-mt-28">
            <h2 id="program-kerja-judul" class="text-2xl font-bold text-csirt-navy">Program Kerja</h2>
            <p class="mt-2 text-csirt-neutral-600">Periode {{ $profil['periode_program'] }}</p>
            <ol class="mt-6 divide-y divide-csirt-neutral-100 rounded-base border border-csirt-neutral-100 bg-csirt-white">
                @foreach ($profil['program_kerja'] as $program)
                    <li class="flex gap-4 p-5 sm:p-6">
                        <span class="w-8 shrink-0 text-2xl font-extrabold text-csirt-primary-light">{{ $loop->iteration }}</span>
                        <div>
                            <h3 class="text-lg font-bold text-csirt-navy">{{ $program['nama'] }}</h3>
                            <p class="mt-1 text-csirt-neutral-600">{{ $program['deskripsi'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        <section aria-labelledby="kenali" class="rounded-base bg-csirt-neutral-100 px-6 py-10 text-center sm:px-10">
            <h2 id="kenali" class="text-2xl font-bold text-csirt-navy">Pengurus dan prestasi anggota</h2>
            <p class="mx-auto mt-2 max-w-xl text-csirt-neutral-600">Susunan pengurus periode ini ada di halaman Struktur Organisasi, sedangkan hasil kompetisi anggota dicatat di halaman Prestasi.</p>
            <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('struktur-organisasi') }}" class="rounded-base bg-csirt-primary px-5 py-3 font-medium text-csirt-white hover:bg-csirt-primary-lighter focus:outline-hidden focus:ring-4 focus:ring-csirt-primary-light">Struktur Organisasi</a>
                <a href="{{ route('prestasi') }}" class="rounded-base border border-csirt-primary px-5 py-3 font-medium text-csirt-primary hover:bg-csirt-white focus:outline-hidden focus:ring-4 focus:ring-csirt-primary-light">Prestasi</a>
            </div>
        </section>
    </div>
@endsection
