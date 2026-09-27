<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Halaman Tentang dan isi Beranda — teksnya dari config/profil.php. */
class TentangBerandaTest extends TestCase
{
    public function test_halaman_tentang_berisi_sejarah_visi_misi_dan_program_kerja(): void
    {
        $profil = config('profil');

        $response = $this->get('/tentang')
            ->assertOk()
            ->assertSee('Tentang UKM CSIRT')
            ->assertSee((string) $profil['tahun_komunitas'])
            ->assertSee((string) $profil['tahun_ukm'])
            ->assertSee('id="visi-misi"', false)
            ->assertSee('id="program-kerja"', false)
            ->assertSee($profil['visi'])
            ->assertSeeInOrder($profil['misi'])
            ->assertSeeInOrder(array_column($profil['program_kerja'], 'nama'))
            ->assertSee('Periode '.$profil['periode_program'])
            ->assertSee('href="'.route('struktur-organisasi').'"', false)
            ->assertSee('href="'.route('prestasi').'"', false);

        $this->assertCount(5, $profil['misi']);
        $this->assertCount(5, $profil['program_kerja']);
        foreach ($profil['program_kerja'] as $program) {
            $response->assertSee($program['deskripsi']);
        }
    }

    public function test_menu_tentang_aktif_di_navbar(): void
    {
        $this->get('/tentang')->assertSee('href="'.route('tentang').'" aria-current="page"', false);
        $this->get('/')->assertSee('href="'.route('tentang').'"', false);
    }

    public function test_beranda_menampilkan_visi_dan_tiga_program_unggulan(): void
    {
        $profil = config('profil');
        $unggulan = array_values(array_filter($profil['program_kerja'], fn (array $program): bool => $program['unggulan']));
        $lainnya = array_values(array_filter($profil['program_kerja'], fn (array $program): bool => ! $program['unggulan']));

        $this->assertCount(3, $unggulan);

        $response = $this->get('/')
            ->assertOk()
            ->assertSee('UKM CSIRT Politeknik Caltex Riau</h1>', false)
            ->assertSee($profil['visi'])
            ->assertSee('href="'.route('tentang').'#visi-misi"', false)
            ->assertSee('href="'.route('tentang').'#program-kerja"', false)
            ->assertSeeInOrder(array_column($unggulan, 'nama'))
            ->assertSee('href="'.route('berita.index').'"', false)
            ->assertSee('href="'.route('prestasi').'"', false);

        foreach ($lainnya as $program) {
            $response->assertDontSee($program['nama']);
        }
    }
}
