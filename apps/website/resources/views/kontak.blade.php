{{-- Halaman Kontak. Kanal resmi dari config/kontak.php; contact person dan status pendaftaran dari panel
     admin "Kontak & Pendaftaran" (nomor HP pengurus sengaja tidak di repo). Ikon: Flowbite Icons (MIT). --}}
@extends('layouts.app', [
    'title' => 'Kontak',
    'description' => 'Cara menghubungi pengurus UKM CSIRT Politeknik Caltex Riau dan status pendaftaran anggota baru.',
])

@section('content')
    <section class="bg-csirt-neutral-100">
        <div class="mx-auto max-w-screen-xl px-4 py-12 text-center lg:py-16">
            <h1 class="text-3xl font-extrabold tracking-tight text-csirt-navy md:text-4xl">Kontak</h1>
            <p class="mx-auto mt-3 max-w-2xl text-lg text-csirt-neutral-600">Pertanyaan tentang kegiatan atau pendaftaran anggota bisa langsung disampaikan ke pengurus. Pengumuman terbaru dimuat di Instagram {{ $kontak['instagram']['akun'] }}.</p>
        </div>
    </section>

    <div class="mx-auto max-w-screen-xl space-y-16 px-4 py-12 lg:space-y-20 lg:py-16">
        <section id="pendaftaran" aria-labelledby="pendaftaran-judul" class="scroll-mt-28 rounded-base bg-csirt-navy px-6 py-8 text-csirt-white sm:px-10 lg:py-10">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between lg:gap-12">
                <div class="max-w-3xl">
                    @if ($registration->isOpen())
                        <p class="inline-flex items-center gap-2 rounded-full bg-csirt-secondary px-3 py-1 text-sm font-semibold text-csirt-navy">
                            <span class="h-2 w-2 rounded-full bg-csirt-navy" aria-hidden="true"></span>Dibuka
                        </p>
                    @else
                        <p class="inline-flex items-center gap-2 rounded-full bg-csirt-white/10 px-3 py-1 text-sm font-semibold text-csirt-accent-lime">
                            <svg aria-hidden="true" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            {{ $registration->hasEnded() ? 'Sudah ditutup' : 'Belum dibuka' }}
                        </p>
                    @endif

                    <h2 id="pendaftaran-judul" class="mt-4 text-2xl font-bold">Pendaftaran Anggota Baru</h2>

                    @if ($registration->isOpen())
                        <p class="mt-2 text-lg leading-relaxed text-csirt-white/90">
                            Pendaftaran anggota baru UKM CSIRT sedang dibuka{{ $registration->closesOn ? ' hingga '.$registration->closesOn->translatedFormat('l, j F Y') : '' }}.
                        </p>
                    @elseif ($registration->hasEnded())
                        <p class="mt-2 text-lg leading-relaxed text-csirt-white/90">
                            Pendaftaran anggota baru periode ini sudah ditutup pada {{ $registration->closesOn->translatedFormat('j F Y') }}.
                            Pengumuman berikutnya disampaikan lewat Instagram {{ $kontak['instagram']['akun'] }} dan halaman ini.
                        </p>
                    @else
                        <p class="mt-2 text-lg leading-relaxed text-csirt-white/90">
                            Saat ini pendaftaran anggota baru UKM CSIRT <strong class="font-semibold text-csirt-white">belum dibuka</strong>.
                            Jadwal pembukaan diumumkan lewat Instagram {{ $kontak['instagram']['akun'] }} dan halaman ini.
                        </p>
                    @endif
                </div>

                <div class="shrink-0">
                    @if ($registration->isOpen())
                        <a href="{{ $registration->formUrl }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 rounded-base bg-csirt-accent-lime px-6 py-3 font-semibold text-csirt-navy hover:bg-csirt-secondary focus:outline-hidden focus:ring-4 focus:ring-csirt-primary-light">
                            Isi Formulir Pendaftaran
                            <svg aria-hidden="true" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 14v4.8a1.2 1.2 0 0 1-1.2 1.2H5.2A1.2 1.2 0 0 1 4 18.8V7.2A1.2 1.2 0 0 1 5.2 6h4.6m4.4-2H20v5.8m-7.1-.7L19.5 4"/></svg>
                            <span class="sr-only"> (membuka situs lain)</span>
                        </a>
                        <p class="mt-2 text-sm text-csirt-white/70">Formulir dibuka di {{ $registration->formHost() }}</p>
                    @else
                        <a href="{{ $kontak['instagram']['url'] }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 rounded-base border border-csirt-accent-lime px-5 py-3 font-medium text-csirt-accent-lime hover:bg-csirt-white/10 focus:outline-hidden focus:ring-4 focus:ring-csirt-primary-light">
                            <svg aria-hidden="true" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M3 8a5 5 0 0 1 5-5h8a5 5 0 0 1 5 5v8a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5V8Zm5-3a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H8Zm7.597 2.214a1 1 0 0 1 1-1h.01a1 1 0 1 1 0 2h-.01a1 1 0 0 1-1-1ZM12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm-5 3a5 5 0 1 1 10 0 5 5 0 0 1-10 0Z" clip-rule="evenodd"/></svg>
                            Pantau {{ $kontak['instagram']['akun'] }}
                            <span class="sr-only"> (membuka situs lain)</span>
                        </a>
                    @endif
                </div>
            </div>
        </section>

        @if ($contactPeople->isNotEmpty())
            <section aria-labelledby="contact-person">
                <h2 id="contact-person" class="text-2xl font-bold text-csirt-navy">Contact Person</h2>
                <p class="mt-1 text-csirt-neutral-600">Untuk pertanyaan seputar kegiatan, kerja sama, dan pendaftaran anggota.</p>
                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    @foreach ($contactPeople as $person)
                        <article class="flex gap-4 rounded-base border border-csirt-neutral-100 bg-csirt-white p-6">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-csirt-neutral-100 text-csirt-primary" aria-hidden="true">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.427 14.768 17.2 13.542a1.733 1.733 0 0 0-2.45 0l-.613.613a1.732 1.732 0 0 1-2.45 0l-1.838-1.84a1.735 1.735 0 0 1 0-2.452l.612-.613a1.735 1.735 0 0 0 0-2.452L9.237 5.572a1.6 1.6 0 0 0-2.45 0c-3.223 3.2-1.702 6.896 1.519 10.117 3.22 3.221 6.914 4.745 10.12 1.535a1.601 1.601 0 0 0 0-2.456Z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <h3 class="text-lg font-bold text-csirt-navy">{{ $person->name }}</h3>
                                <p class="text-csirt-neutral-600">{{ $person->position }}</p>
                                @if ($person->telUrl())
                                    <a href="{{ $person->telUrl() }}" class="mt-3 inline-block text-lg font-semibold tabular-nums text-csirt-primary hover:underline">{{ $person->phone }}</a>
                                @else
                                    <p class="mt-3 text-lg font-semibold tabular-nums text-csirt-navy">{{ $person->phone }}</p>
                                @endif
                                @if ($person->whatsappUrl())
                                    <div class="mt-3">
                                        <a href="{{ $person->whatsappUrl() }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-2 rounded-base bg-csirt-primary px-4 py-2 text-sm font-medium text-csirt-white hover:bg-csirt-primary-lighter focus:outline-hidden focus:ring-4 focus:ring-csirt-primary-light">
                                            <svg aria-hidden="true" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M12 4a8 8 0 0 0-6.895 12.06l.569.718-.697 2.359 2.32-.648.379.243A8 8 0 1 0 12 4ZM2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10a9.96 9.96 0 0 1-5.016-1.347l-4.948 1.382 1.426-4.829-.006-.007-.033-.055A9.958 9.958 0 0 1 2 12Z" clip-rule="evenodd"/><path fill="currentColor" d="M16.735 13.492c-.038-.018-1.497-.736-1.756-.83a1.008 1.008 0 0 0-.34-.075c-.196 0-.362.098-.49.291-.146.217-.587.732-.723.886-.018.02-.042.045-.057.045-.013 0-.239-.093-.307-.123-1.564-.68-2.751-2.313-2.914-2.589-.023-.04-.024-.057-.024-.057.005-.021.058-.074.085-.101.08-.079.166-.182.249-.283l.117-.14c.121-.14.175-.25.237-.375l.033-.066a.68.68 0 0 0-.02-.64c-.034-.069-.65-1.555-.715-1.711-.158-.377-.366-.552-.655-.552-.027 0 0 0-.112.005-.137.005-.883.104-1.213.311-.35.22-.94.924-.94 2.16 0 1.112.705 2.162 1.008 2.561l.041.06c1.161 1.695 2.608 2.951 4.074 3.537 1.412.564 2.081.63 2.461.63.16 0 .288-.013.4-.024l.072-.007c.488-.043 1.56-.599 1.804-1.276.192-.534.243-1.117.115-1.329-.088-.144-.239-.216-.43-.308Z"/></svg>
                                            Chat WhatsApp
                                            <span class="sr-only"> dengan {{ $person->name }} (membuka WhatsApp)</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section aria-labelledby="kanal-resmi">
            <h2 id="kanal-resmi" class="text-2xl font-bold text-csirt-navy">Email &amp; Media Sosial</h2>
            <div class="mt-6 grid gap-6 md:grid-cols-3">
                @php
                    $channels = [
                        [
                            'label' => 'Email',
                            'value' => $kontak['email'],
                            'href' => 'mailto:'.$kontak['email'],
                            'external' => false,
                            'icon' => 'email',
                        ],
                        [
                            'label' => 'Instagram',
                            'value' => $kontak['instagram']['akun'],
                            'href' => $kontak['instagram']['url'],
                            'external' => true,
                            'icon' => 'instagram',
                        ],
                        [
                            'label' => 'LinkedIn',
                            'value' => $kontak['linkedin']['akun'],
                            'href' => $kontak['linkedin']['url'],
                            'external' => true,
                            'icon' => 'linkedin',
                        ],
                    ];
                @endphp
                @foreach ($channels as $channel)
                    <a href="{{ $channel['href'] }}" @if ($channel['external']) target="_blank" rel="noopener noreferrer" @endif
                       class="group flex items-center gap-4 rounded-base border border-csirt-neutral-100 bg-csirt-white p-6 hover:border-csirt-primary-light focus:outline-hidden focus:ring-4 focus:ring-csirt-primary-light">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-csirt-neutral-100 text-csirt-primary group-hover:bg-csirt-primary group-hover:text-csirt-white" aria-hidden="true">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                @switch($channel['icon'])
                                    @case('email')
                                        <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m3.5 5.5 7.893 6.036a1 1 0 0 0 1.214 0L20.5 5.5M4 19h16a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z"/>
                                        @break
                                    @case('instagram')
                                        <path fill="currentColor" fill-rule="evenodd" d="M3 8a5 5 0 0 1 5-5h8a5 5 0 0 1 5 5v8a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5V8Zm5-3a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H8Zm7.597 2.214a1 1 0 0 1 1-1h.01a1 1 0 1 1 0 2h-.01a1 1 0 0 1-1-1ZM12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm-5 3a5 5 0 1 1 10 0 5 5 0 0 1-10 0Z" clip-rule="evenodd"/>
                                        @break
                                    @case('linkedin')
                                        <path fill="currentColor" fill-rule="evenodd" d="M12.51 8.796v1.697a3.738 3.738 0 0 1 3.288-1.684c3.455 0 4.202 2.16 4.202 4.97V19.5h-3.2v-5.072c0-1.21-.244-2.766-2.128-2.766-1.827 0-2.139 1.317-2.139 2.676V19.5h-3.19V8.796h3.168ZM7.2 6.106a1.61 1.61 0 0 1-.988 1.483 1.595 1.595 0 0 1-1.743-.348A1.607 1.607 0 0 1 5.6 4.5a1.601 1.601 0 0 1 1.6 1.606Z" clip-rule="evenodd"/>
                                        <path fill="currentColor" d="M7.2 8.809H4V19.5h3.2V8.809Z"/>
                                        @break
                                @endswitch
                            </svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm text-csirt-neutral-600">{{ $channel['label'] }}</span>
                            <span class="block break-words font-semibold text-csirt-navy group-hover:text-csirt-primary">{{ $channel['value'] }}</span>
                            @if ($channel['external'])
                                <span class="sr-only"> (membuka situs lain)</span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
@endsection
