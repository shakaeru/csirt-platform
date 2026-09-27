{{--
    Layout utama situs publik. Juga default layout Livewire 4 (`layouts::app`).
    Konten halaman: @extends('layouts.app') + @section('content'), atau {{ $slot }}
    untuk komponen Livewire full-page. Warna hanya dari design tokens (kelas csirt-*).

    Parameter opsional (@extends('layouts.app', [...])), lihat juga App\Support\Seo:
      title          judul halaman; tanpa title = judul Beranda
      description    meta description, unik per halaman
      page           nomor halaman daftar berpaginasi (masuk judul, deskripsi, dan canonical)
      canonicalQuery parameter URL yang membedakan isi, mis. ['kategori' => 'kegiatan']
      noindex        true untuk hasil pencarian
      ogType         'article' untuk tulisan (default 'website')
      ogImage        URL absolut gambar share (default config seo.image, 1200×630)
      ogImageAlt     teks alternatif gambar share
    Tag tambahan di <head> (JSON-LD, article:*): @push('head').
--}}
@php
    $navItems = [
        ['label' => 'Beranda', 'href' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => 'Tentang', 'href' => route('tentang'), 'active' => request()->routeIs('tentang')],
        ['label' => 'Struktur Organisasi', 'href' => route('struktur-organisasi'), 'active' => request()->routeIs('struktur-organisasi')],
        ['label' => 'Berita & Kegiatan', 'href' => route('berita.index'), 'active' => request()->routeIs('berita.*')],
        ['label' => 'Galeri', 'href' => route('galeri.index'), 'active' => request()->routeIs('galeri.*')],
        ['label' => 'Prestasi', 'href' => route('prestasi'), 'active' => request()->routeIs('prestasi')],
        ['label' => 'Kontak', 'href' => route('kontak'), 'active' => request()->routeIs('kontak')],
    ];
    $page = max(1, (int) ($page ?? 1));
    $seoTitle = \App\Support\Seo::title($title ?? null, $page);
    $seoDescription = \App\Support\Seo::description($description ?? null, $page);
    $canonicalUrl = \App\Support\Seo::canonical([...($canonicalQuery ?? []), 'page' => $page > 1 ? $page : null]);
    $shareTitle = $title ?? config('seo.home_title');
    $shareImage = $ogImage ?? \App\Support\Seo::url(config('seo.image'));
    $kontak = config('kontak');
@endphp
<!DOCTYPE html>
{{-- Seluruh isi situs publik berbahasa Indonesia (APP_LOCALE tetap mengatur bahasa panel admin). --}}
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if (filled(config('seo.google_site_verification')))
        <meta name="google-site-verification" content="{{ config('seo.google_site_verification') }}">
    @endif
    @if ($noindex ?? false)
        <meta name="robots" content="noindex, follow">
    @endif

    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:site_name" content="{{ config('seo.organization') }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="{{ $shareTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $shareImage }}">
    @unless (isset($ogImage))
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endunless
    <meta property="og:image:alt" content="{{ $ogImageAlt ?? 'Logo UKM CSIRT Politeknik Caltex Riau' }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $shareTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $shareImage }}">
    @stack('head')

    @include('layouts.partials.icons')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-csirt-white font-sans text-csirt-neutral-900 antialiased">
    {{-- Navbar: komponen "Default navbar" Flowbite v4, warna dipetakan ke token csirt-*.
         Breakpoint menu horizontal dinaikkan md → lg karena ada 7 item. --}}
    <nav data-animate="navbar" class="sticky top-0 z-20 w-full border-b border-csirt-neutral-100 bg-csirt-white">
        <div class="mx-auto flex max-w-screen-xl flex-wrap items-center justify-between p-4">
            <a href="{{ route('home') }}" class="flex items-center">
                {{-- 440×142 (cukup untuk tinggi 48 px di layar 3x); asli 880×284 ada di assets/brand. --}}
                <picture>
                    <source srcset="{{ asset('images/csirt-logo.webp') }}" type="image/webp">
                    <img src="{{ asset('images/csirt-logo-440.png') }}" width="440" height="142" class="h-10 w-auto sm:h-12"
                         alt="Logo UKM CSIRT Politeknik Caltex Riau">
                </picture>
            </a>
            <button data-collapse-toggle="navbar-main" type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-base p-2 text-sm text-csirt-navy hover:bg-csirt-neutral-100 focus:outline-hidden focus:ring-2 focus:ring-csirt-primary-light lg:hidden"
                    aria-controls="navbar-main" aria-expanded="false">
                <span class="sr-only">Buka menu utama</span>
                <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/></svg>
            </button>
            <div class="hidden w-full lg:block lg:w-auto" id="navbar-main">
                <ul class="mt-4 flex flex-col rounded-base border border-csirt-neutral-100 bg-csirt-neutral-100 p-4 font-medium lg:mt-0 lg:flex-row lg:space-x-8 lg:border-0 lg:bg-csirt-white lg:p-0 rtl:space-x-reverse">
                    @foreach ($navItems as $item)
                        <li>
                            @if ($item['active'] ?? false)
                                <a href="{{ $item['href'] }}" aria-current="page"
                                   class="block rounded bg-csirt-primary px-3 py-2 text-csirt-white lg:bg-transparent lg:p-0 lg:text-csirt-primary">{{ $item['label'] }}</a>
                            @else
                                <a href="{{ $item['href'] }}"
                                   class="block rounded px-3 py-2 text-csirt-navy hover:bg-csirt-white lg:border-0 lg:p-0 lg:hover:bg-transparent lg:hover:text-csirt-primary">{{ $item['label'] }}</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </nav>

    <main class="flex-1">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    {{-- Footer: identitas, tautan ke semua halaman (membantu crawler), dan kanal resmi. --}}
    <footer class="border-t border-csirt-neutral-100 bg-csirt-neutral-100">
        <div class="mx-auto max-w-screen-xl px-4 py-10">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="font-bold text-csirt-navy">{{ config('seo.organization') }}</p>
                    <p class="mt-1 text-sm text-csirt-neutral-600">Computer Security Incident Response Team</p>
                    <address class="mt-4 text-sm not-italic leading-relaxed text-csirt-neutral-600">
                        Kampus Politeknik Caltex Riau<br>
                        {{ $kontak['alamat']['jalan'] }}<br>
                        {{ $kontak['alamat']['kota'] }}, {{ $kontak['alamat']['provinsi'] }} {{ $kontak['alamat']['kode_pos'] }}
                    </address>
                </div>
                <nav aria-label="Tautan halaman">
                    <p class="text-sm font-semibold text-csirt-navy">Halaman</p>
                    <ul class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        @foreach ($navItems as $item)
                            <li><a href="{{ $item['href'] }}" class="text-csirt-neutral-600 hover:text-csirt-primary hover:underline">{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
                <div>
                    <p class="text-sm font-semibold text-csirt-navy">Hubungi kami</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="mailto:{{ $kontak['email'] }}" class="text-csirt-neutral-600 hover:text-csirt-primary hover:underline">{{ $kontak['email'] }}</a></li>
                        <li><a href="{{ $kontak['instagram']['url'] }}" target="_blank" rel="noopener noreferrer" class="text-csirt-neutral-600 hover:text-csirt-primary hover:underline">Instagram {{ $kontak['instagram']['akun'] }}<span class="sr-only"> (membuka situs lain)</span></a></li>
                        <li><a href="{{ $kontak['linkedin']['url'] }}" target="_blank" rel="noopener noreferrer" class="text-csirt-neutral-600 hover:text-csirt-primary hover:underline">LinkedIn {{ $kontak['linkedin']['akun'] }}<span class="sr-only"> (membuka situs lain)</span></a></li>
                        <li><a href="{{ route('kontak') }}#pendaftaran" class="text-csirt-neutral-600 hover:text-csirt-primary hover:underline">Info pendaftaran anggota</a></li>
                    </ul>
                </div>
            </div>
            <p class="mt-8 border-t border-csirt-white pt-6 text-sm text-csirt-neutral-600">
                © {{ now()->year }} {{ config('seo.organization') }}
            </p>
        </div>
    </footer>
</body>
</html>
