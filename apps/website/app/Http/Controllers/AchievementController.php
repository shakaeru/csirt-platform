<?php

namespace App\Http\Controllers;

use App\Enums\AchievementCategory;
use App\Enums\AchievementLevel;
use App\Models\Achievement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AchievementController extends Controller
{
    /**
     * Prestasi terbit, dikelompokkan per tahun (terbaru dulu). Filter: ?jenis=ctf|lomba.
     * Tanpa paginasi — jumlahnya puluhan per tahun, dan pengelompokan per tahun butuh daftar utuh.
     */
    public function __invoke(Request $request): View
    {
        $category = AchievementCategory::tryFrom((string) $request->query('jenis'));

        $years = Achievement::query()
            ->published()
            ->with('post')
            ->when($category, fn ($query) => $query->where('category', $category))
            ->orderByDesc('achieved_on')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (Achievement $achievement): int => $achievement->achieved_on->year);

        return view('prestasi', [
            'years' => $years,
            'category' => $category,
            // Ringkasan angka di header selalu dari semua prestasi terbit (tidak ikut filter).
            'stats' => [
                'total' => Achievement::query()->published()->count(),
                'ctf' => Achievement::query()->published()->where('category', AchievementCategory::Ctf)->count(),
                'national' => Achievement::query()->published()
                    ->whereIn('level', [AchievementLevel::Internasional, AchievementLevel::Nasional])
                    ->count(),
            ],
        ]);
    }
}
