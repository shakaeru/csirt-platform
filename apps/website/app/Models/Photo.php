<?php

namespace App\Models;

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
    use HasFactory;

    protected static function booted(): void
    {
        static::deleted(function (Photo $photo): void {
            Storage::disk(Album::DISK)->delete([$photo->path, $photo->thumb_path]);
        });
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
     * @return Attribute<string, never>
     */
    protected function thumbUrl(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk(Album::DISK)->url($this->thumb_path));
    }
}
