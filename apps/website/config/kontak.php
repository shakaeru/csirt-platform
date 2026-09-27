<?php

/*
|--------------------------------------------------------------------------
| Kanal resmi UKM CSIRT: halaman Kontak, footer, dan data terstruktur (JSON-LD)
|--------------------------------------------------------------------------
|
| Hanya kanal milik organisasi (publik). Contact person (nama + nomor HP pengurus) dan status
| pendaftaran TIDAK di sini, tetapi di panel admin → "Kontak & Pendaftaran": repo ini publik, jadi
| nomor HP pribadi tidak boleh masuk riwayat git, dan keduanya berubah tanpa perlu deploy.
|
*/

return [

    'email' => 'csirt@pcr.ac.id',

    'instagram' => [
        'akun' => '@csirt_pcr',
        'url' => 'https://www.instagram.com/csirt_pcr/',
    ],

    'linkedin' => [
        'akun' => 'csirt-pcr',
        'url' => 'https://id.linkedin.com/company/csirt-pcr',
    ],

    // Alamat kampus Politeknik Caltex Riau (sumber: pcr.ac.id), tampil di footer dan JSON-LD.
    'alamat' => [
        'jalan' => 'Jl. Umban Sari No. 1, Umban Sari, Rumbai',
        'kota' => 'Pekanbaru',
        'provinsi' => 'Riau',
        'kode_pos' => '28265',
    ],

];
