<?php

namespace App\Models;

use App\Enums\BoardSection;
use Database\Factories\BoardMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['board_period_id', 'section', 'division_id', 'name', 'position', 'photo_path', 'sort_order'])]
class BoardMember extends Model
{
    /** @use HasFactory<BoardMemberFactory> */
    use HasFactory;

    /** Disk tempat foto anggota disimpan (lihat FileUpload di BoardMemberResource). */
    public const PHOTO_DISK = 'public';

    protected static function booted(): void
    {
        // Divisi hanya relevan untuk bagian Divisi.
        static::saving(function (BoardMember $member): void {
            if ($member->section !== BoardSection::Divisi) {
                $member->division_id = null;
            }
        });

        // Foto yang diganti/anggota yang dihapus: hapus file-nya juga, supaya foto orang yang
        // sudah tidak menjabat tidak tetap bisa diakses publik lewat URL lama.
        static::updated(function (BoardMember $member): void {
            $old = $member->getOriginal('photo_path');
            if ($member->wasChanged('photo_path') && $old) {
                Storage::disk(self::PHOTO_DISK)->delete($old);
            }
        });

        static::deleted(function (BoardMember $member): void {
            if ($member->photo_path) {
                Storage::disk(self::PHOTO_DISK)->delete($member->photo_path);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section' => BoardSection::class,
        ];
    }

    /**
     * @return BelongsTo<BoardPeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(BoardPeriod::class, 'board_period_id');
    }

    /**
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
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
     * Inisial untuk avatar pengganti bila belum ada foto, mis. "Budi Santoso" → "BS".
     *
     * @return Attribute<string, never>
     */
    protected function initials(): Attribute
    {
        return Attribute::get(fn (): string => Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode(''));
    }
}
