<?php

/*
|--------------------------------------------------------------------------
| Profil UKM CSIRT — halaman Tentang dan Beranda
|--------------------------------------------------------------------------
|
| Satu sumber teks untuk resources/views/tentang.blade.php dan home.blade.php, supaya visi,
| misi, dan program kerja tidak berbeda antarhalaman. Mengubahnya = ubah file ini lalu deploy
| (config di-cache saat container start).
|
*/

return [

    'tahun_komunitas' => 2013,

    'tahun_ukm' => 2014,

    'visi' => 'Menjadikan UKM CSIRT Politeknik Caltex Riau sebagai organisasi yang aktif, profesional, inovatif, '
        .'dan kolaboratif dalam mengembangkan kompetensi keamanan siber, serta menjadi wadah bagi mahasiswa '
        .'untuk bertumbuh, berprestasi, dan memberikan dampak nyata.',

    'misi' => [
        'Mengembangkan kompetensi anggota melalui program yang terarah.',
        'Membangun budaya kolaborasi dan berbagi pengetahuan.',
        'Meningkatkan prestasi melalui kompetisi, riset, dan sertifikasi.',
        'Memperluas kerja sama dengan komunitas dan industri.',
        'Menerapkan tata kelola organisasi yang profesional dan transparan.',
    ],

    'periode_program' => '2026/2027',

    // 'unggulan' => tampil sebagai cuplikan di Beranda (3 program).
    'program_kerja' => [
        [
            'nama' => 'Pelatihan Rutin CSIRT',
            'deskripsi' => 'Sesi belajar berkala untuk anggota, dari materi dasar hingga lanjutan keamanan siber, disertai praktik langsung.',
            'unggulan' => true,
        ],
        [
            'nama' => 'Pertemuan Perdana CSIRT',
            'deskripsi' => 'Pertemuan pembuka periode kepengurusan untuk menyambut anggota baru, mengenalkan pengurus, dan memaparkan rencana kegiatan.',
            'unggulan' => false,
        ],
        [
            'nama' => 'Seminar dan Workshop Kolaborasi',
            'deskripsi' => 'Kegiatan bersama komunitas dan industri untuk berbagi pengetahuan dan memperluas jejaring anggota.',
            'unggulan' => false,
        ],
        [
            'nama' => 'Workshop Cybersecurity',
            'deskripsi' => 'Pelatihan intensif dengan tema keamanan siber tertentu, dari konsep hingga praktik langsung.',
            'unggulan' => true,
        ],
        [
            'nama' => 'Lomba CTF Jeopardy',
            'deskripsi' => 'Kompetisi Capture The Flag format Jeopardy: peserta memecahkan tantangan kriptografi, web, forensik, dan kategori lain untuk mengumpulkan poin.',
            'unggulan' => true,
        ],
    ],

];
