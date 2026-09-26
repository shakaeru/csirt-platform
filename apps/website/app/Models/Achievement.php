<?php

namespace App\Models;

use App\Enums\AchievementCategory;
use App\Enums\AchievementLevel;
use App\Models\Concerns\HasPublication;
use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Prestasi anggota/tim UKM di lomba (CTF dan lomba lain). Status terbit sama dengan tulisan
 * (HasPublication). Semua isian teks polos — ditampilkan lewat {{ }}.
 */
#[Fillable(['competition', 'result', 'category', 'level', 'organizer', 'achieved_on', 'team_name', 'members', 'description', 'photo_path', 'result_url', 'post_id', 'published_at'])]
class Achievement extends Model
{
    /** @use HasFactory<AchievementFactory> */
    use HasFactory, HasPublication;

    /** Disk foto prestasi (lihat FileUpload di AchievementForm). */
    public const PHOTO_DISK = 'public';

    protected static function booted(): void
    {
        // Foto yang diganti/prestasi yang dihapus: hapus file-nya juga (sama seperti sampul tulisan).
        static::updated(function (Achievement $achievement): void {
            $old = $achievement->getOriginal('photo_path');
            if ($achievement->wasChanged('photo_path') && $old) {
                Storage::disk(self::PHOTO_DISK)->delete($old);
            }
        });

        static::deleted(function (Achievement $achievement): void {
            if ($achievement->photo_path) {
                Storage::disk(self::PHOTO_DISK)->delete($achievement->photo_path);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => AchievementCategory::class,
            'level' => AchievementLevel::class,
            'achieved_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Daftar nama anggota dari isian "satu nama per baris".
     *
     * @return Attribute<list<string>, never>
     */
    protected function memberList(): Attribute
    {
        return Attribute::get(fn (): array => array_values(array_filter(
            array_map(trim(...), preg_split('/\R/', (string) $this->members)),
            fn (string $name): bool => $name !== '',
        )));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->photo_path
            ? Storage::disk(self::PHOTO_DISK)->url($this->photo_path)
            : null);
    }

    /**
     * Bulan dan tahun untuk ditampilkan, mis. "Agustus 2026" (kolom date — tanpa konversi zona waktu).
     *
     * @return Attribute<string, never>
     */
    protected function achievedMonth(): Attribute
    {
        return Attribute::get(fn (): string => $this->achieved_on->locale('id')->translatedFormat('F Y'));
    }
}
