{{--
    Layout utama situs publik. Juga default layout Livewire 4 (`layouts::app`).
    Konten halaman: @extends('layouts.app') + @section('content'), atau {{ $slot }}
    untuk komponen Livewire full-page. Opsional: @extends('layouts.app', ['title' => '...', 'description' => '...']).
    Warna hanya dari design tokens (kelas csirt-*), bukan warna default Tailwind.
--}}
@php
    // Item dengan href '#' = halamannya menyusul.
    $navItems = [
        ['label' => 'Beranda', 'href' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => 'Tentang', 'href' => route('tentang'), 'active' => request()->routeIs('tentang')],
        ['label' => 'Struktur Organisasi', 'href' => route('struktur-organisasi'), 'active' => request()->routeIs('struktur-organisasi')],
        ['label' => 'Berita & Kegiatan', 'href' => route('berita.index'), 'active' => request()->routeIs('berita.*')],
        ['label' => 'Galeri', 'href' => route('galeri.index'), 'active' => request()->routeIs('galeri.*')],
        ['label' => 'Prestasi', 'href' => route('prestasi'), 'active' => request()->routeIs('prestasi')],
        ['label' => 'Kontak', 'href' => '#'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title.' — ' : '' }}UKM CSIRT Politeknik Caltex Riau</title>
    @isset($description)
        <meta name="description" content="{{ $description }}">
    @endisset

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-csirt-white font-sans text-csirt-neutral-900 antialiased">
    {{-- Navbar: komponen "Default navbar" Flowbite v4, warna dipetakan ke token csirt-*.
         Breakpoint menu horizontal dinaikkan md → lg karena ada 7 item. --}}
    <nav data-animate="navbar" class="sticky top-0 z-20 w-full border-b border-csirt-neutral-100 bg-csirt-white">
        <div class="mx-auto flex max-w-screen-xl flex-wrap items-center justify-between p-4">
            <a href="{{ route('home') }}" class="flex items-center">
                <img src="{{ asset('images/csirt-logo.png') }}" width="880" height="284" class="h-10 w-auto sm:h-12"
                     alt="UKM CSIRT — Computer Security Incident Response Team, Politeknik Caltex Riau">
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

    {{-- Footer sederhana, struktur dari "Default footer" Flowbite. --}}
    <footer class="border-t border-csirt-neutral-100 bg-csirt-neutral-100">
        <div class="mx-auto w-full max-w-screen-xl p-4 md:flex md:items-center md:justify-between">
            <span class="text-sm text-csirt-neutral-600 sm:text-center">
                © {{ now()->year }} <a href="{{ route('home') }}" class="hover:underline">UKM CSIRT Politeknik Caltex Riau</a>
            </span>
            <p class="mt-3 text-sm text-csirt-neutral-600 md:mt-0">Computer Security Incident Response Team</p>
        </div>
    </footer>
</body>
</html>
