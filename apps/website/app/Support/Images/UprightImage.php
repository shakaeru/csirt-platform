<?php

namespace App\Support\Images;

use GdImage;
use InvalidArgumentException;

/**
 * Membaca file JPG/PNG/WebP ke GD dengan posisi tegak sesuai tag Orientation EXIF. Dipakai
 * pengolah foto pengurus dan galeri; keduanya meng-encode ulang hasilnya ke JPEG, jadi metadata
 * asli (termasuk lokasi GPS dari kamera ponsel) tidak ikut tersimpan.
 */
final class UprightImage
{
    /**
     * @throws InvalidArgumentException bila file bukan JPG/PNG/WebP yang valid
     */
    public static function fromFile(string $source, ?string $name = null): GdImage
    {
        $name ??= basename($source);
        $info = @getimagesize($source);
        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new InvalidArgumentException($name.': bukan gambar JPG/PNG/WebP yang valid.');
        }
        self::ensureMemoryFor($info[0], $info[1], $name);

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
        };
        if (! $image instanceof GdImage) {
            throw new InvalidArgumentException($name.': gambar gagal dibaca.');
        }

        return $info[2] === IMAGETYPE_JPEG ? self::applyExifOrientation($image, $source) : $image;
    }

    /** Encode ke JPEG. Transparansi PNG/WebP sudah diratakan ke putih oleh pemanggil. */
    public static function toJpeg(GdImage $image, int $quality = 85): string
    {
        ob_start();
        imagejpeg($image, null, $quality);

        return (string) ob_get_clean();
    }

    /** Kanvas truecolor berlatar putih (untuk PNG/WebP transparan). */
    public static function canvas(int $width, int $height): GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));

        return $canvas;
    }

    /**
     * GD menyimpan ±5 byte per piksel, dan memutar (EXIF) butuh salinan kedua: foto 50 MP bisa
     * menghabiskan memory_limit PHP-FPM dan berakhir error 500. Tolak lebih dulu dengan pesan jelas.
     * Di CLI (memory_limit -1, mis. struktur:import) tidak ada batas.
     */
    private static function ensureMemoryFor(int $width, int $height, string $name): void
    {
        $limit = ini_parse_quantity((string) ini_get('memory_limit'));
        if ($limit <= 0) {
            return;
        }

        if (memory_get_usage() + $width * $height * 5 * 2 > $limit) {
            throw new InvalidArgumentException("{$name}: resolusi terlalu besar ({$width}×{$height}) untuk diproses server — perkecil dulu.");
        }
    }

    /**
     * Foto kamera ponsel sering disimpan miring dengan tag Orientation EXIF; GD mengabaikan tag itu.
     */
    private static function applyExifOrientation(GdImage $image, string $source): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($source)['Orientation'] ?? 1);

        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        // imagerotate: sudut positif = berlawanan jarum jam.
        $angle = match ($orientation) {
            3, 4 => 180,
            6, 7 => -90, // 90° searah jarum jam (7 = cermin + putar searah jarum jam)
            5, 8 => 90,  // 90° berlawanan jarum jam (5 = cermin + putar berlawanan = transpose)
            default => 0,
        };

        return $angle === 0 ? $image : imagerotate($image, $angle, 0);
    }
}
