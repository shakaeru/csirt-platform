<?php

/*
|--------------------------------------------------------------------------
| Profil UKM CSIRT: halaman Tentang dan Beranda
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
            'deskripsi' => 'Sesi belajar berkala untuk anggota, dari materi dasar sampai topik lanjutan keamanan siber. Setiap sesi diisi dengan praktik.',
            'unggulan' => true,
        ],
        [
            'nama' => 'Pertemuan Perdana CSIRT',
            'deskripsi' => 'Pertemuan pertama di awal periode kepengurusan. Anggota baru berkenalan dengan pengurus dan mendapat gambaran kegiatan selama setahun.',
            'unggulan' => false,
        ],
        [
            'nama' => 'Seminar dan Workshop Kolaborasi',
            'deskripsi' => 'Seminar dan workshop yang diadakan bersama komunitas atau perusahaan di bidang keamanan siber, sekaligus kesempatan anggota menambah relasi.',
            'unggulan' => false,
        ],
        [
            'nama' => 'Workshop Cybersecurity',
            'deskripsi' => 'Pelatihan intensif yang membahas satu topik keamanan siber secara mendalam, dari konsep sampai praktik.',
            'unggulan' => true,
        ],
        [
            'nama' => 'Lomba CTF Jeopardy',
            'deskripsi' => 'Kompetisi Capture The Flag format Jeopardy yang diselenggarakan CSIRT. Peserta mengerjakan soal kriptografi, web, forensik, dan kategori lain untuk mengumpulkan poin.',
            'unggulan' => true,
        ],
    ],

];
