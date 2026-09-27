<?php

namespace App\Models;

use App\Models\Concerns\HasPublication;
use App\Support\Images\ResponsiveVariants;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

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

        // Sampul baru: buat varian WebP untuk srcset (lihat refreshCoverVariants()).
        static::created(function (Post $post): void {
            if ($post->cover_path) {
                $post->refreshCoverVariants();
            }
        });

        // Sampul yang diganti/tulisan yang dihapus: hapus file-nya juga (sama seperti foto pengurus),
        // termasuk varian WebP-nya.
        static::updated(function (Post $post): void {
            if (! $post->wasChanged('cover_path')) {
                return;
            }

            $old = $post->getOriginal('cover_path');
            if ($old) {
                Storage::disk(self::COVER_DISK)->delete($old);
                app(ResponsiveVariants::class)->delete(self::COVER_DISK, $old, $post->getOriginal('cover_widths'));
            }
            $post->refreshCoverVariants();
        });

        static::deleted(function (Post $post): void {
            if ($post->cover_path) {
                Storage::disk(self::COVER_DISK)->delete($post->cover_path);
                app(ResponsiveVariants::class)->delete(self::COVER_DISK, $post->cover_path, $post->cover_widths);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cover_widths' => 'array',
        ];
    }

    /**
     * Buat ulang varian WebP sampul (400/800/1200 px) dan simpan lebarnya di cover_widths. Gagal
     * memproses (file rusak/hilang) tidak menggagalkan penyimpanan tulisan: cover_widths null dan
     * halaman memakai file sampul asli saja. Untuk tulisan lama:
     * Post::whereNotNull('cover_path')->get()->each->refreshCoverVariants().
     */
    public function refreshCoverVariants(): void
    {
        $variants = app(ResponsiveVariants::class);
        $widths = null;
        if ($this->cover_path) {
            $variants->delete(self::COVER_DISK, $this->cover_path, $this->cover_widths);
            try {
                $widths = $variants->generate(self::COVER_DISK, $this->cover_path);
            } catch (InvalidArgumentException $e) {
                Log::warning('Varian sampul tulisan gagal dibuat', ['post' => $this->id, 'error' => $e->getMessage()]);
            }
        }

        $this->forceFill(['cover_widths' => $widths])->saveQuietly();
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
     * Nilai srcset varian WebP sampul, atau null bila belum ada varian.
     *
     * @return Attribute<string|null, never>
     */
    protected function coverSrcset(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->cover_path && $this->cover_widths
            ? ResponsiveVariants::srcset(self::COVER_DISK, $this->cover_path, $this->cover_widths)
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
