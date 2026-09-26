<?php

namespace Tests\Feature;

use App\Enums\BoardSection;
use App\Models\BoardMember;
use App\Models\BoardPeriod;
use App\Models\Division;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesTestImages;
use Tests\TestCase;

// Nama-nama di sini fiktif — daftar pengurus asli tidak disimpan di repo.
class ImportBoardRosterTest extends TestCase
{
    use CreatesTestImages, RefreshDatabase;

    private const ROSTER = <<<'TXT'
        STRUKTUR ORGANISASI UKM CSIRT
        Periode: 2030/2031

        Pembina:
        Dr. Contoh Pembina, M.Kom. — Pembina UKM

        Presidium:
        Andi Presidium — Ketua Presidium

        Pengurus Inti:
        Budi Ketua — Ketua
        Citra Sekretaris — Sekretaris

        Divisi Humas:
        Menjalin komunikasi dengan pihak luar.
        Dewi Humas — Kepala Divisi
        Eko Humas — Anggota

        Divisi Publikasi:
        Fajar Publikasi — Kepala Divisi
        TXT;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(BoardMember::PHOTO_DISK);
        $this->dir = sys_get_temp_dir().'/roster-test-'.Str::random(8);
        File::makeDirectory($this->dir.'/foto', recursive: true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_import_membuat_periode_divisi_dan_anggota(): void
    {
        $this->artisan('struktur:import', ['file' => $this->roster(), '--activate' => true])->assertSuccessful();

        $period = BoardPeriod::query()->where('name', '2030/2031')->sole();
        $this->assertTrue($period->is_active);
        $this->assertSame(7, $period->members()->count());

        $pembina = BoardMember::query()->where('name', 'Dr. Contoh Pembina, M.Kom.')->sole();
        $this->assertSame(BoardSection::Pembina, $pembina->section);
        $this->assertSame(BoardSection::Presidium, BoardMember::query()->where('name', 'Andi Presidium')->sole()->section);
        $this->assertSame(2, BoardMember::query()->where('name', 'Citra Sekretaris')->sole()->sort_order);

        $humas = Division::query()->where('name', 'Divisi Humas')->sole();
        $this->assertSame('Menjalin komunikasi dengan pihak luar.', $humas->description);
        $this->assertSame(1, $humas->sort_order);
        $this->assertSame(2, Division::query()->where('name', 'Divisi Publikasi')->sole()->sort_order);
        $this->assertSame($humas->id, BoardMember::query()->where('name', 'Eko Humas')->sole()->division_id);

        $this->get('/struktur-organisasi')->assertOk()
            ->assertSeeInOrder(['Dr. Contoh Pembina', 'Andi Presidium', 'Budi Ketua', 'Divisi Humas', 'Dewi Humas', 'Divisi Publikasi']);
    }

    public function test_import_ulang_menyinkronkan_tanpa_duplikat(): void
    {
        $this->artisan('struktur:import', ['file' => $this->roster()])->assertSuccessful();

        $edited = str_replace(["Budi Ketua — Ketua", "Eko Humas — Anggota\n"], ["Budi Ketua — Ketua Umum", ''], self::ROSTER);
        $this->artisan('struktur:import', ['file' => $this->roster($edited)])
            ->expectsOutputToContain('akan dihapus (tidak ada di file): Eko Humas')
            ->assertSuccessful();

        $this->assertSame(6, BoardMember::query()->count());
        $this->assertSame('Ketua Umum', BoardMember::query()->where('name', 'Budi Ketua')->sole()->position);
        $this->assertFalse(BoardMember::query()->where('name', 'Eko Humas')->exists());
        $this->assertSame(1, BoardPeriod::query()->count());
    }

    /**
     * Sumber 800×800: kiri merah, kanan biru. Orientation 6 (putar 90° searah jarum jam) → kiri
     * menjadi atas; orientation 8 (berlawanan jarum jam) → kiri menjadi bawah.
     */
    public function test_foto_diluruskan_dipotong_600_dan_metadata_dibuang(): void
    {
        foreach ([6 => ['atas' => 'merah', 'bawah' => 'biru'], 8 => ['atas' => 'biru', 'bawah' => 'merah']] as $orientation => $expected) {
            File::cleanDirectory($this->dir.'/foto');
            file_put_contents($this->dir.'/foto/budi-ketua.jpg', $this->jpegWithOrientation(800, 800, $orientation));

            $this->artisan('struktur:import', ['file' => $this->roster(), '--photos' => $this->dir.'/foto'])->assertSuccessful();

            $path = Storage::disk(BoardMember::PHOTO_DISK)->path(BoardMember::query()->where('name', 'Budi Ketua')->sole()->photo_path);
            [$width, $height, $type] = getimagesize($path);
            $this->assertSame([600, 600, IMAGETYPE_JPEG], [$width, $height, $type]);

            $image = imagecreatefromjpeg($path);
            $this->assertSame($expected['atas'], $this->dominant($image, 300, 100), "orientation {$orientation}: atas");
            $this->assertSame($expected['bawah'], $this->dominant($image, 300, 500), "orientation {$orientation}: bawah");
            $this->assertArrayNotHasKey('Orientation', @exif_read_data($path) ?: []);
        }
    }

    public function test_foto_lama_terhapus_saat_import_foto_baru(): void
    {
        file_put_contents($this->dir.'/foto/budi-ketua.jpg', $this->jpegWithOrientation(700, 700, 1));
        $this->artisan('struktur:import', ['file' => $this->roster(), '--photos' => $this->dir.'/foto'])->assertSuccessful();
        $first = BoardMember::query()->where('name', 'Budi Ketua')->sole()->photo_path;

        $this->artisan('struktur:import', ['file' => $this->roster(), '--photos' => $this->dir.'/foto'])->assertSuccessful();
        $second = BoardMember::query()->where('name', 'Budi Ketua')->sole()->photo_path;

        $this->assertNotSame($first, $second);
        Storage::disk(BoardMember::PHOTO_DISK)->assertMissing($first);
        Storage::disk(BoardMember::PHOTO_DISK)->assertExists($second);
    }

    public function test_file_foto_tidak_cocok_dan_format_tidak_didukung_dilaporkan(): void
    {
        file_put_contents($this->dir.'/foto/budi-ketuaa.jpg', $this->jpegWithOrientation(700, 700, 1));
        file_put_contents($this->dir.'/foto/citra-sekretaris.heic', 'bukan-jpg');

        $this->artisan('struktur:import', ['file' => $this->roster(), '--photos' => $this->dir.'/foto'])
            ->expectsOutputToContain('tidak cocok dengan nama mana pun (salah ketik?): budi-ketuaa.jpg')
            ->expectsOutputToContain('format tidak didukung (pakai JPG/PNG/WebP): citra-sekretaris.heic')
            ->expectsOutputToContain('belum ada foto: Budi Ketua → budi-ketua.jpg')
            ->assertSuccessful();

        $this->assertNull(BoardMember::query()->where('name', 'Budi Ketua')->sole()->photo_path);
    }

    public function test_dry_run_tidak_menyimpan_apa_pun(): void
    {
        $this->artisan('struktur:import', ['file' => $this->roster(), '--dry-run' => true, '--activate' => true])
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        $this->assertSame(0, BoardPeriod::query()->count());
        $this->assertSame(0, BoardMember::query()->count());
        $this->assertSame(0, Division::query()->count());
    }

    public function test_file_tidak_valid_ditolak_dengan_nomor_baris(): void
    {
        $cases = [
            "Periode: 2030/2031\n\nPengurus Inti:\nBudi Ketua tanpa jabatan" => 'Baris 4 tidak dikenali',
            "Periode: 2030/2031\n\nBendahara Umum:\nBudi — Bendahara" => 'Baris 3: bagian "Bendahara Umum" tidak dikenal',
            "Periode: 2030/2031\n\nPengurus Inti:\nBudi Ketua — Ketua\nBudi Ketua — Wakil" => 'sama dengan nama di baris 4',
            "Pengurus Inti:\nBudi Ketua — Ketua" => 'Periode: ...',
        ];

        foreach ($cases as $text => $message) {
            $this->artisan('struktur:import', ['file' => $this->roster($text)])
                ->expectsOutputToContain($message)
                ->assertFailed();
        }

        $this->assertSame(0, BoardMember::query()->count());
    }

    private function roster(string $text = self::ROSTER): string
    {
        $path = $this->dir.'/roster-'.Str::random(6).'.txt';
        file_put_contents($path, $text);

        return $path;
    }
}
