<?php

namespace Tests\Feature;

use App\Enums\BoardSection;
use App\Filament\Resources\BoardMembers\Pages\ManageBoardMembers;
use App\Models\BoardMember;
use App\Models\BoardPeriod;
use App\Models\Division;
use App\Models\User;
use Filament\Actions\CreateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_tampilan_kosong_bila_belum_ada_periode_aktif(): void
    {
        BoardMember::factory()->create(['name' => 'Anggota Periode Nonaktif']);

        $this->get('/struktur-organisasi')
            ->assertOk()
            ->assertSee('Struktur organisasi sedang diperbarui')
            ->assertDontSee('Anggota Periode Nonaktif');
    }

    public function test_menampilkan_periode_aktif_berurutan_per_bagian(): void
    {
        $period = BoardPeriod::factory()->active()->create(['name' => '2026/2027']);
        $web = Division::factory()->create(['name' => 'Divisi Web', 'sort_order' => 2]);
        $forensik = Division::factory()->create(['name' => 'Divisi Forensik', 'sort_order' => 1]);

        BoardMember::factory()->for($period, 'period')->inDivision($web)->create(['name' => 'Wulan Web']);
        BoardMember::factory()->for($period, 'period')->inDivision($forensik)->create(['name' => 'Fajar Forensik']);
        BoardMember::factory()->for($period, 'period')->create(['name' => 'Sari Sekretaris', 'position' => 'Sekretaris', 'sort_order' => 2]);
        BoardMember::factory()->for($period, 'period')->create(['name' => 'Kiki Ketua', 'position' => 'Ketua Umum', 'sort_order' => 1]);
        BoardMember::factory()->for($period, 'period')->pembina()->create(['name' => 'Pak Pembina']);
        BoardMember::factory()->for($period, 'period')->presidium()->create(['name' => 'Putri Presidium']);
        BoardMember::factory()->create(['name' => 'Orang Periode Lama']);

        $this->get('/struktur-organisasi')
            ->assertOk()
            ->assertSee('Kepengurusan periode 2026/2027')
            ->assertSeeInOrder([
                'Pembina', 'Pak Pembina',
                'Presidium', 'Putri Presidium',
                'Pengurus Inti', 'Kiki Ketua', 'Sari Sekretaris',
                'Divisi Forensik', 'Fajar Forensik',
                'Divisi Web', 'Wulan Web',
            ])
            ->assertDontSee('Orang Periode Lama');
    }

    public function test_hanya_satu_periode_aktif(): void
    {
        $lama = BoardPeriod::factory()->active()->create();
        $baru = BoardPeriod::factory()->active()->create();

        $this->assertFalse($lama->fresh()->is_active);
        $this->assertTrue($baru->fresh()->is_active);
    }

    public function test_bagian_selain_divisi_tidak_menyimpan_divisi(): void
    {
        $member = BoardMember::factory()->inDivision()->create();

        $member->update(['section' => BoardSection::Inti]);

        $this->assertNull($member->fresh()->division_id);
    }

    public function test_file_foto_dihapus_saat_diganti_dan_saat_anggota_dihapus(): void
    {
        $disk = Storage::fake(BoardMember::PHOTO_DISK);
        $disk->put('pengurus/lama.jpg', 'x');
        $disk->put('pengurus/baru.jpg', 'x');
        $member = BoardMember::factory()->create(['photo_path' => 'pengurus/lama.jpg']);

        $member->update(['photo_path' => 'pengurus/baru.jpg']);
        $disk->assertMissing('pengurus/lama.jpg');
        $disk->assertExists('pengurus/baru.jpg');

        $member->delete();
        $disk->assertMissing('pengurus/baru.jpg');
    }

    public function test_halaman_admin_struktur_organisasi_bisa_dibuka(): void
    {
        // Tanpa FilamentUser, Filament hanya mengizinkan akses di env local (middleware Authenticate Filament).
        config(['app.env' => 'local']);
        $this->actingAs(User::factory()->create());

        foreach (['/admin/board-members', '/admin/board-periods', '/admin/divisions'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_form_admin_divisi_wajib_hanya_untuk_bagian_divisi(): void
    {
        config(['app.env' => 'local']);
        $this->actingAs(User::factory()->create());
        $period = BoardPeriod::factory()->active()->create();

        Livewire::test(ManageBoardMembers::class)
            ->callAction(CreateAction::class, data: [
                'board_period_id' => $period->id, 'section' => BoardSection::Divisi->value,
                'name' => 'Tanpa Divisi', 'position' => 'Anggota', 'sort_order' => 0,
            ])
            ->assertHasFormErrors(['division_id' => 'required']);

        Livewire::test(ManageBoardMembers::class)
            ->callAction(CreateAction::class, data: [
                'board_period_id' => $period->id, 'section' => BoardSection::Inti->value,
                'name' => 'Ketua Baru', 'position' => 'Ketua Umum', 'sort_order' => 0,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('board_members', ['name' => 'Ketua Baru', 'section' => 'inti', 'division_id' => null]);
        $this->assertDatabaseMissing('board_members', ['name' => 'Tanpa Divisi']);
    }
}
