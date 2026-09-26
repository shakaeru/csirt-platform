<?php

namespace App\Http\Controllers;

use App\Enums\BoardSection;
use App\Models\BoardMember;
use App\Models\BoardPeriod;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class OrganizationStructureController extends Controller
{
    /**
     * Halaman publik Struktur Organisasi — hanya periode yang aktif.
     */
    public function __invoke(): View
    {
        $period = BoardPeriod::query()->active()->first();

        /** @var Collection<int, BoardMember> $members */
        $members = $period
            ? $period->members()->with('division')->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        return view('struktur-organisasi', [
            'period' => $period,
            // Bagian non-divisi (Pembina, Presidium, Pengurus Inti) sesuai urutan case enum:
            // ['Pembina' => Collection<BoardMember>, ...]. Bagian baru di enum otomatis ikut tampil.
            'groups' => collect(BoardSection::cases())
                ->reject(fn (BoardSection $section): bool => $section === BoardSection::Divisi)
                ->mapWithKeys(fn (BoardSection $section): array => [
                    $section->getLabel() => $members->where('section', $section)->values(),
                ]),
            // [['division' => Division, 'members' => Collection<BoardMember>], ...] — hanya divisi yang punya anggota.
            'divisions' => $members->where('section', BoardSection::Divisi)
                ->groupBy('division_id')
                ->map(fn (Collection $group): array => ['division' => $group->first()->division, 'members' => $group])
                ->sortBy(fn (array $group): string => sprintf('%05d-%s', $group['division']->sort_order, $group['division']->name))
                ->values(),
        ]);
    }
}
