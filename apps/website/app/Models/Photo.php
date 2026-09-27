<?php

namespace App\Models;

use App\Models\Concerns\HasResponsiveImage;
use Database\Factories\PhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Satu foto di album galeri. Dibuat lewat Album::addPhoto(), bukan diisi path langsung. */
#[Fillable(['album_id', 'path', 'thumb_path', 'width', 'height', 'caption', 'sort_order'])]
class Photo extends Model
{
    /** @use HasFactory<PhotoFactory> */
    use HasFactory, HasResponsiveImage;

    protected static function booted(): void
    {
        // Foto penuh dan varian WebP-nya dihapus HasResponsiveImage; thumbnail JPEG di sini.
        static::deleted(function (Photo $photo): void {
            Storage::disk(Album::DISK)->delete($photo->thumb_path);
        });
    }

    /**
     * Varian WebP 400/800/1200 dibuat dari foto penuh (maks. 2000 px) setelah respons unggah terkirim
     * (defer): sampai selesai, halaman memakai thumbnail/foto JPEG.
     */
    protected function responsiveImage(): array
    {
        return ['path' => 'path', 'widths' => 'variant_widths', 'disk' => Album::DISK, 'defer' => true];
    }

    /**
     * Berapa kali lebar kotak yang dibutuhkan foto ini bila dipotong object-cover ke kotak
     * berasio $boxRatio (lebar/tinggi), untuk mengalikan `sizes`. Foto 3:2 di kotak persegi = 1.5;
     * foto potret = 1 (lebarnya pas, tingginya yang terpotong).
     */
    public function cropFactor(float $boxRatio = 1.0): float
    {
        return round(max(1, ($this->width / max(1, $this->height)) / $boxRatio), 2);
    }

    /**
     * @return BelongsTo<Album, $this>
     */
    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk(Album::DISK)->url($this->path));
    }

    /**
     * srcset untuk grid dan lightbox: varian WebP, ditambah JPEG penuh sebagai kandidat terbesar
     * (layar lebar di lightbox). Null bila belum ada varian.
     *
     * @return Attribute<string|null, never>
     */
    protected function srcset(): Attribute
    {
        return Attribute::get(function (): ?string {
            $variants = $this->imageSrcset();
            if ($variants === null) {
                return null;
            }

            return $this->width > max($this->variant_widths)
                ? $variants.', '.$this->url." {$this->width}w"
                : $variants;
        });
    }

    /**
     * @return Attribute<string, never>
     */
    protected function thumbUrl(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk(Album::DISK)->url($this->thumb_path));
    }
}
