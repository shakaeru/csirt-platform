<?php

namespace App\Console\Commands;

use App\Enums\BoardSection;
use App\Models\BoardMember;
use App\Models\BoardPeriod;
use App\Models\Division;
use App\Support\BoardRoster\MemberPhotoProcessor;
use App\Support\BoardRoster\RosterMember;
use App\Support\BoardRoster\RosterParser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Menyinkronkan struktur organisasi satu periode dari file .txt (format: RosterParser) dan
 * folder foto. File daftar dan foto sengaja di luar repo — data pribadi tidak masuk git.
 * Aman dijalankan ulang: anggota dicocokkan lewat slug nama, jadi tidak ada duplikat.
 */
#[Signature('struktur:import
    {file : File .txt daftar pengurus satu periode}
    {--photos= : Folder foto bernama <nama-slug>.jpg/.jpeg/.png/.webp}
    {--activate : Jadikan periode ini periode aktif (tampil di website)}
    {--dry-run : Tampilkan rencana perubahan tanpa menyimpan apa pun}')]
#[Description('Impor/sinkronkan struktur organisasi satu periode dari file .txt (+ foto opsional)')]
class ImportBoardRoster extends Command
{
    public function handle(RosterParser $parser, MemberPhotoProcessor $processor): int
    {
        $file = (string) $this->argument('file');
        if (! is_file($file) || ! is_readable($file)) {
            $this->error("File tidak bisa dibaca: {$file}");

            return self::FAILURE;
        }

        try {
            $roster = $parser->parse((string) file_get_contents($file));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $photoDir = $this->option('photos');
        if ($photoDir !== null && ! is_dir($photoDir)) {
            $this->error("Folder foto tidak ditemukan: {$photoDir}");

            return self::FAILURE;
        }
        ['photos' => $photos, 'ignored' => $ignoredFiles] = $photoDir !== null
            ? $processor->index($photoDir)
            : ['photos' => [], 'ignored' => []];

        $period = BoardPeriod::query()->where('name', $roster->period)->first();
        /** @var Collection<string, BoardMember> $existing */
        $existing = $period
            ? $period->members()->get()->keyBy(fn (BoardMember $member): string => Str::slug($member->name))
            : collect();
        /** @var Collection<string, RosterMember> $incoming */
        $incoming = collect($roster->members)->keyBy(fn (RosterMember $member): string => $member->slug());
        $toRemove = $existing->keys()->diff($incoming->keys())->values();

        $this->reportPlan($roster->period, $period === null, $incoming, $existing, $toRemove, $photoDir, $photos, $ignoredFiles);

        if ($this->option('dry-run')) {
            $this->warn('Dry run: tidak ada yang disimpan.');

            return self::SUCCESS;
        }

        /** @var array<string, BoardMember> $saved */
        $saved = DB::transaction(function () use ($roster, &$period, $existing, $incoming): array {
            $period ??= BoardPeriod::create(['name' => $roster->period]);

            $divisionIds = [];
            foreach (array_keys($roster->divisions) as $position => $name) {
                $division = Division::firstOrNew(['name' => $name]);
                $division->sort_order = $position + 1;
                if ($roster->divisions[$name] !== null) {
                    $division->description = $roster->divisions[$name];
                }
                $division->save();
                $divisionIds[$name] = $division->id;
            }

            $saved = [];
            foreach ($incoming as $slug => $member) {
                $record = $existing->get($slug) ?? new BoardMember(['board_period_id' => $period->id]);
                $record->fill([
                    'name' => $member->name,
                    'position' => $member->position,
                    'section' => $member->section,
                    'division_id' => $member->division !== null ? $divisionIds[$member->division] : null,
                    'sort_order' => $member->sortOrder,
                ])->save();
                $saved[$slug] = $record;
            }

            return $saved;
        });

        // Setelah commit: menghapus anggota juga menghapus file fotonya (event model), dan file
        // yang sudah terhapus tidak ikut kembali kalau transaksi dibatalkan.
        foreach ($toRemove as $slug) {
            $existing[$slug]->delete();
        }

        $processed = 0;
        foreach ($photos as $slug => $path) {
            if (! isset($saved[$slug])) {
                continue;
            }
            try {
                $saved[$slug]->update(['photo_path' => $processor->store($path, $slug)]);
                $processed++;
            } catch (InvalidArgumentException $e) {
                $this->warn('Foto dilewati: '.$e->getMessage());
            }
        }

        if ($this->option('activate')) {
            $period->update(['is_active' => true]);
        }

        $this->info(sprintf(
            'Selesai: periode %s, %d anggota tersimpan, %d dihapus, %d foto diproses%s.',
            $period->name, count($saved), $toRemove->count(), $processed,
            $period->fresh()->is_active ? ', periode AKTIF' : ' (periode belum aktif; jalankan ulang dengan --activate untuk menampilkannya)',
        ));

        return self::SUCCESS;
    }

    /**
     * @param  Collection<string, RosterMember>  $incoming
     * @param  Collection<string, BoardMember>  $existing
     * @param  Collection<int, string>  $toRemove
     * @param  array<string, string>  $photos
     * @param  list<string>  $ignoredFiles
     */
    private function reportPlan(string $period, bool $newPeriod, Collection $incoming, Collection $existing, Collection $toRemove, ?string $photoDir, array $photos, array $ignoredFiles): void
    {
        $this->line("Periode <info>{$period}</info>".($newPeriod ? ' (baru)' : ' (sudah ada, disinkronkan)'));

        $this->table(['Bagian', 'Anggota'], collect(BoardSection::cases())
            ->map(fn (BoardSection $section): array => [
                $section->getLabel(),
                $incoming->filter(fn (RosterMember $member): bool => $member->section === $section)->count(),
            ])
            ->all());

        $new = $incoming->keys()->diff($existing->keys());
        $this->line(sprintf('Anggota baru: %d, diperbarui: %d, dihapus: %d', $new->count(), $incoming->count() - $new->count(), $toRemove->count()));
        foreach ($toRemove as $slug) {
            $this->warn("  akan dihapus (tidak ada di file): {$existing[$slug]->name}");
        }

        if ($photoDir === null) {
            $this->line('Foto: tidak diproses (tanpa --photos).');

            return;
        }

        $matched = array_intersect_key($photos, $incoming->all());
        $this->line(sprintf('Foto cocok: %d dari %d anggota', count($matched), $incoming->count()));
        foreach ($incoming->keys()->diff(array_keys($photos)) as $slug) {
            $this->line("  belum ada foto: {$incoming[$slug]->name} → {$slug}.jpg");
        }
        foreach (array_diff(array_keys($photos), $incoming->keys()->all()) as $slug) {
            $this->warn('  file foto tidak cocok dengan nama mana pun (salah ketik?): '.basename($photos[$slug]));
        }
        foreach ($ignoredFiles as $name) {
            $this->warn("  diabaikan, format tidak didukung (pakai JPG/PNG/WebP): {$name}");
        }
    }
}
