<?php

namespace App\Models;

use App\Models\Concerns\HasPublication;
use Database\Factories\PostFactory;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['category_id', 'title', 'slug', 'excerpt', 'content', 'cover_path', 'published_at'])]
class Post extends Model implements HasRichContent
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasPublication, InteractsWithRichContent;

    /** Disk gambar sampul (lihat FileUpload di PostResource). */
    public const COVER_DISK = 'public';

    protected static function booted(): void
    {
        static::saving(function (Post $post): void {
            $post->slug = $post->slug ?: Str::slug($post->title);
        });

        // Sampul yang diganti/tulisan yang dihapus: hapus file-nya juga (sama seperti foto pengurus).
        static::updated(function (Post $post): void {
            $old = $post->getOriginal('cover_path');
            if ($post->wasChanged('cover_path') && $old) {
                Storage::disk(self::COVER_DISK)->delete($old);
            }
        });

        static::deleted(function (Post $post): void {
            if ($post->cover_path) {
                Storage::disk(self::COVER_DISK)->delete($post->cover_path);
            }
        });
    }

    /**
     * Isi dirender lewat renderRichContent('content') → toHtml() Filament, yang menyanitasi HTML
     * (Symfony HtmlSanitizer). Jangan pernah menampilkan $post->content mentah di Blade.
     */
    protected function setUpRichContent(): void
    {
        $this->registerRichContent('content');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Album galeri dokumentasi yang ditautkan ke tulisan ini.
     *
     * @return HasMany<Album, $this>
     */
    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->cover_path
            ? Storage::disk(self::COVER_DISK)->url($this->cover_path)
            : null);
    }

    /**
     * Ringkasan untuk kartu daftar dan meta description: excerpt, atau awal isi (teks polos).
     *
     * @return Attribute<string, never>
     */
    protected function summary(): Attribute
    {
        // Teks polos; Blade tetap meng-escape saat ditampilkan dengan {{ }}. Spasi disisipkan sebelum
        // setiap tag supaya "</p><p>" tidak menempelkan kalimat ("admin.Paragraf").
        return Attribute::get(fn (): string => $this->excerpt
            ?: Str::limit(Str::squish(html_entity_decode(strip_tags(str_replace('<', ' <', $this->content)), ENT_QUOTES | ENT_HTML5)), 160));
    }
}
