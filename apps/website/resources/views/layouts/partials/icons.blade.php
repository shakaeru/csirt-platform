{{-- Favicon dan ikon aplikasi, dibuat dari assets/brand/csirt-favicon.jpg. Gambar statis di-cache
     browser setahun, jadi ?v= wajib dinaikkan (config seo.icon_version) setiap kali ikon diganti. --}}
@php($iconVersion = config('seo.icon_version'))
<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $iconVersion }}" sizes="16x16 32x32 48x48">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v={{ $iconVersion }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v={{ $iconVersion }}">
{{-- Google Search memakai favicon berukuran kelipatan 48 px. --}}
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}?v={{ $iconVersion }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v={{ $iconVersion }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}?v={{ $iconVersion }}">
<meta name="theme-color" content="#073d53">
