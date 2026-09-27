<?php

/*
|--------------------------------------------------------------------------
| SEO situs publik
|--------------------------------------------------------------------------
|
| Dipakai layouts/app.blade.php (judul, meta, Open Graph) dan App\Support\Seo (URL kanonik,
| JSON-LD Organization/WebSite di Beranda). URL absolut dibangun dari APP_URL.
|
*/

return [

    // Nama situs di hasil pencarian Google (WebSite.name) dan akhiran judul tiap halaman.
    'site_name' => 'CSIRT PCR',

    'organization' => 'UKM CSIRT Politeknik Caltex Riau',

    'alternate_names' => [
        'CSIRT Politeknik Caltex Riau',
        'Computer Security Incident Response Team Politeknik Caltex Riau',
    ],

    // Judul Beranda (halaman lain: "<judul> | CSIRT PCR").
    'home_title' => 'CSIRT PCR | UKM Keamanan Siber Politeknik Caltex Riau',

    // Cadangan untuk halaman yang tidak mengisi deskripsi sendiri.
    'description' => 'UKM CSIRT Politeknik Caltex Riau (CSIRT PCR), unit kegiatan mahasiswa di bidang keamanan siber.',

    // Gambar share default (og:image), 1200×630. Tulisan dan album memakai sampulnya sendiri.
    'image' => 'images/og-default.jpg',

    // Naikkan setiap kali file favicon/ikon di public/ diganti: gambar statis di-cache browser
    // setahun (immutable), jadi URL-nya harus berubah.
    'icon_version' => '2',

    // Kode verifikasi Google Search Console, metode "Tag HTML" untuk properti awalan URL
    // https://csirt.pcr.ac.id/ (isi atribut content saja). Bukan rahasia: tampil di HTML setiap
    // halaman. Jangan dihapus selama properti masih dipakai, karena Google memeriksanya ulang.
    'google_site_verification' => 'nlaaB8O7hbl8NIrLkNvRahuZfo704fA8pzOCkzoc6q4',

    'parent_organization' => [
        'name' => 'Politeknik Caltex Riau',
        'url' => 'https://pcr.ac.id',
    ],

];
