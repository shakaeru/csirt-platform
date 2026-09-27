<?php

namespace App\Models\Concerns;

use App\Support\Images\ResponsiveVariants;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

use function Illuminate\Support\defer;

/**
 * Satu gambar unggahan di disk publik (sampul tulisan, foto prestasi, foto galeri) beserta varian WebP untuk
 * srcset (App\Support\Images\ResponsiveVariants). Model menyebut kolomnya lewat responsiveImage().
 *
 * - Gambar baru atau diganti: varian dibuat, lebar yang jadi disimpan di kolom lebar.
 * - Gambar diganti, dikosongkan, atau modelnya dihapus: file lama dan variannya ikut dihapus,
 *   supaya gambar yang sudah tidak dipakai tidak tetap bisa diakses publik lewat URL lama.
 * - Gagal memproses (file rusak/hilang) tidak menggagalkan penyimpanan: kolom lebar null dan
 *   halaman memakai file asli saja.
 * - 'defer' => true (foto galeri): varian dibuat setelah respons terkirim ke browser (atau setelah
 *   perintah artisan selesai), supaya unggah banyak foto sekaligus tidak menunggu encode WebP. CPU
 *   VPS lambat: 30 foto ±48 s bila langsung, sedangkan proxy edge memutus request di 60 s.
 *
 * Model lama tanpa varian (tinker): Model::whereNotNull('<kolom path>')->get()->each->refreshImageVariants().
 */
trait HasResponsiveImage
{
    /**
     * Nama kolom path, kolom lebar (JSON), disk, dan (opsional) apakah varian dibuat setelah respons.
     *
     * @return array{path: string, widths: string, disk: string, defer?: bool}
     */
    abstract protected function responsiveImage(): array;

    protected static function bootHasResponsiveImage(): void
    {
        static::created(function (self $model): void {
            if ($model->getAttribute($model->responsiveImage()['path'])) {
                $model->scheduleImageVariants();
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
                app(ResponsiveVariants::class)->delete($disk, $old);
            }
            $model->scheduleImageVariants();
        });

        static::deleted(function (self $model): void {
            ['path' => $pathColumn, 'widths' => $widthsColumn, 'disk' => $disk] = $model->responsiveImage();
            $path = $model->getAttribute($pathColumn);
            if ($path) {
                Storage::disk($disk)->delete($path);
                app(ResponsiveVariants::class)->delete($disk, $path);
            }
        });
    }

    protected function initializeHasResponsiveImage(): void
    {
        $this->mergeCasts([$this->responsiveImage()['widths'] => 'array']);
    }

    /** Langsung, atau setelah respons bila responsiveImage()['defer'] (lihat docblock trait). */
    protected function scheduleImageVariants(): void
    {
        if (! ($this->responsiveImage()['defer'] ?? false)) {
            $this->refreshImageVariants();

            return;
        }

        // Dimuat ulang saat dijalankan: modelnya bisa sudah berubah atau terhapus. Batas waktu PHP
        // di-reset per gambar supaya antrean panjang tidak terpotong max_execution_time di tengah.
        $key = $this->getKey();
        defer(function () use ($key): void {
            set_time_limit(60);
            static::query()->find($key)?->refreshImageVariants();
        });
    }

    /** Buat ulang varian WebP dan simpan lebarnya (tanpa memicu event model). */
    public function refreshImageVariants(): void
    {
        ['path' => $pathColumn, 'widths' => $widthsColumn, 'disk' => $disk] = $this->responsiveImage();
        $path = $this->getAttribute($pathColumn);
        $variants = app(ResponsiveVariants::class);

        $widths = null;
        if ($path) {
            $variants->delete($disk, $path);
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
