<?php

namespace App\Support\Images;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Varian WebP beberapa lebar dari satu gambar di disk, untuk srcset. Contoh: berita/abc.jpg →
 * berita/abc-400.webp, berita/abc-800.webp, berita/abc-1200.webp. File asli tidak diubah (tetap
 * jadi src cadangan dan og:image). Hasil encode ulang GD tidak membawa metadata EXIF/GPS.
 */
final class ResponsiveVariants
{
    /** Lebar varian; yang lebih besar dari gambar asli dilewati (tidak memperbesar). */
    public const WIDTHS = [400, 800, 1200];

    private const QUALITY = 80;

    /**
     * @return list<int> lebar yang berhasil dibuat, urut naik
     *
     * @throws InvalidArgumentException bila file bukan gambar yang bisa diproses
     */
    public function generate(string $disk, string $path): array
    {
        $storage = Storage::disk($disk);
        if (! $storage->exists($path)) {
            throw new InvalidArgumentException("{$path}: file tidak ditemukan.");
        }

        $image = UprightImage::fromFile($storage->path($path), basename($path));
        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);

        // Gambar lebih sempit dari varian terbesar: lebar aslinya ikut jadi varian terbesar.
        $widths = array_values(array_unique([
            ...array_filter(self::WIDTHS, fn (int $width): bool => $width <= $sourceWidth),
            min($sourceWidth, max(self::WIDTHS)),
        ]));
        sort($widths);

        foreach ($widths as $width) {
            $height = max(1, (int) round($sourceHeight * $width / $sourceWidth));
            $canvas = UprightImage::canvas($width, $height); // PNG transparan → latar putih
            imagecopyresampled($canvas, $image, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

            ob_start();
            imagewebp($canvas, null, self::QUALITY);
            $storage->put(self::path($path, $width), (string) ob_get_clean(), 'public');
        }

        return $widths;
    }

    /**
     * @param  list<int>|null  $widths
     */
    public function delete(string $disk, string $path, ?array $widths): void
    {
        if ($widths) {
            Storage::disk($disk)->delete(array_map(fn (int $width): string => self::path($path, $width), $widths));
        }
    }

    /**
     * Nilai atribut srcset, mis. "https://…/abc-400.webp 400w, https://…/abc-800.webp 800w".
     *
     * @param  list<int>  $widths
     */
    public static function srcset(string $disk, string $path, array $widths): string
    {
        return implode(', ', array_map(
            fn (int $width): string => Storage::disk($disk)->url(self::path($path, $width))." {$width}w",
            $widths,
        ));
    }

    public static function path(string $path, int $width): string
    {
        $dot = strrpos($path, '.');
        $base = $dot === false || $dot < (int) strrpos($path, '/') ? $path : substr($path, 0, $dot);

        return "{$base}-{$width}.webp";
    }
}
