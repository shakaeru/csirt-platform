<?php

namespace App\Models\Concerns;

use App\Support\Images\ResponsiveVariants;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Satu gambar unggahan di disk publik (sampul tulisan, foto prestasi) beserta varian WebP untuk
 * srcset (App\Support\Images\ResponsiveVariants). Model menyebut kolomnya lewat responsiveImage().
 *
 * - Gambar baru atau diganti: varian dibuat, lebar yang jadi disimpan di kolom lebar.
 * - Gambar diganti, dikosongkan, atau modelnya dihapus: file lama dan variannya ikut dihapus,
 *   supaya gambar yang sudah tidak dipakai tidak tetap bisa diakses publik lewat URL lama.
 * - Gagal memproses (file rusak/hilang) tidak menggagalkan penyimpanan: kolom lebar null dan
 *   halaman memakai file asli saja.
 *
 * Model lama tanpa varian (tinker): Model::whereNotNull('<kolom path>')->get()->each->refreshImageVariants().
 */
trait HasResponsiveImage
{
    /**
     * @return array{path: string, widths: string, disk: string} nama kolom path, kolom lebar (JSON), dan disk
     */
    abstract protected function responsiveImage(): array;

    protected static function bootHasResponsiveImage(): void
    {
        static::created(function (self $model): void {
            if ($model->getAttribute($model->responsiveImage()['path'])) {
                $model->refreshImageVariants();
            }
        });

        static::updated(function (self $model): void {
            ['path' => $pathColumn, 'widths' => $widthsColumn, 'disk' => $disk] = $model->responsiveImage();
            if (! $model->wasChanged($pathColumn)) {
                return;
            }

            $old = $model->getOriginal($pathColumn);
            if ($old) {
                Storage::disk($disk)->delete($old);
                app(ResponsiveVariants::class)->delete($disk, $old, $model->getOriginal($widthsColumn));
            }
            $model->refreshImageVariants();
        });

        static::deleted(function (self $model): void {
            ['path' => $pathColumn, 'widths' => $widthsColumn, 'disk' => $disk] = $model->responsiveImage();
            $path = $model->getAttribute($pathColumn);
            if ($path) {
                Storage::disk($disk)->delete($path);
                app(ResponsiveVariants::class)->delete($disk, $path, $model->getAttribute($widthsColumn));
            }
        });
    }

    protected function initializeHasResponsiveImage(): void
    {
        $this->mergeCasts([$this->responsiveImage()['widths'] => 'array']);
    }

    /** Buat ulang varian WebP dan simpan lebarnya (tanpa memicu event model). */
    public function refreshImageVariants(): void
    {
        ['path' => $pathColumn, 'widths' => $widthsColumn, 'disk' => $disk] = $this->responsiveImage();
        $path = $this->getAttribute($pathColumn);
        $variants = app(ResponsiveVariants::class);

        $widths = null;
        if ($path) {
            $variants->delete($disk, $path, $this->getAttribute($widthsColumn));
            try {
                $widths = $variants->generate($disk, $path);
            } catch (InvalidArgumentException $e) {
                Log::warning('Varian gambar gagal dibuat', ['model' => static::class, 'id' => $this->getKey(), 'error' => $e->getMessage()]);
            }
        }

        $this->forceFill([$widthsColumn => $widths])->saveQuietly();
    }

    /** Nilai srcset varian WebP, atau null bila belum ada varian. */
    public function imageSrcset(): ?string
    {
        ['path' => $pathColumn, 'widths' => $widthsColumn, 'disk' => $disk] = $this->responsiveImage();
        $path = $this->getAttribute($pathColumn);
        $widths = $this->getAttribute($widthsColumn);

        return $path && $widths ? ResponsiveVariants::srcset($disk, $path, $widths) : null;
    }
}
