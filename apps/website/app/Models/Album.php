<?php

namespace App\Models;

use App\Models\Concerns\HasPublication;
use App\Support\Gallery\GalleryPhotoProcessor;
use Database\Factories\AlbumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Album galeri dokumentasi kegiatan. Status terbit sama dengan tulisan (HasPublication).
 * Semua file fotonya ada di satu folder disk publik: galeri/{id}/.
 */
#[Fillable(['post_id', 'title', 'slug', 'description', 'event_date', 'published_at'])]
class Album extends Model
{
    /** @use HasFactory<AlbumFactory> */
    use HasFactory, HasPublication;

    public const DISK = 'public';

    protected static function booted(): void
    {
        static::saving(function (Album $album): void {
            $album->slug = $album->slug ?: Str::slug($album->title);
        });

        // Baris photos terhapus lewat cascade DB (tanpa event model), jadi file-nya dibersihkan
        // di sini sekaligus satu folder.
        static::deleted(function (Album $album): void {
            Storage::disk(self::DISK)->deleteDirectory($album->photoDirectory());
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Tanpa urutan bawaan (supaya sortir tabel admin berlaku) — pakai ->ordered() untuk tampilan.
     *
     * @return HasMany<Photo, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    /**
     * Sampul = foto pertama menurut urutan.
     *
     * @return HasOne<Photo, $this>
     */
    public function cover(): HasOne
    {
        return $this->hasOne(Photo::class)->ofMany(['sort_order' => 'min', 'id' => 'min']);
    }

    public function photoDirectory(): string
    {
        return 'galeri/'.$this->getKey();
    }

    /**
     * Olah file gambar (luruskan, perkecil, buang metadata) lalu tambahkan sebagai foto terakhir.
     *
     * @throws \InvalidArgumentException bila file bukan gambar yang bisa diproses
     */
    public function addPhoto(string $source, ?string $caption = null, ?string $name = null): Photo
    {
        $stored = app(GalleryPhotoProcessor::class)->store($source, $this->photoDirectory(), $name);

        try {
            return $this->photos()->create([
                ...$stored,
                'caption' => $caption,
                'sort_order' => ((int) $this->photos()->max('sort_order')) + 1,
            ]);
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->delete([$stored['path'], $stored['thumb_path']]);

            throw $e;
        }
    }

    /**
     * Tanggal kegiatan untuk ditampilkan, mis. "12 Oktober 2026" (kolom date — tanpa konversi zona waktu).
     *
     * @return Attribute<string, never>
     */
    protected function eventDateLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->event_date->locale('id')->translatedFormat('j F Y'));
    }
}
