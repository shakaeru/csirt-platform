<?php

namespace App\Support\Gallery;

use App\Models\Album;
use App\Support\Images\UprightImage;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menyiapkan foto galeri di server: diluruskan (EXIF), diperkecil tanpa dipotong, lalu di-encode
 * ulang ke JPEG — metadata asli, termasuk lokasi GPS dari kamera ponsel, tidak ikut tersimpan.
 * Browser admin sudah memperkecil foto sebelum diunggah (FileUpload), tapi langkah itu bisa
 * dilewati klien, jadi ukuran dan metadata tetap dipastikan di sini.
 */
final class GalleryPhotoProcessor
{
    /** Sisi terpanjang foto untuk tampilan layar penuh (lightbox). */
    public const MAX_SIDE = 2000;

    /** Sisi terpanjang thumbnail untuk grid. */
    public const THUMB_SIDE = 800;

    /**
     * @return array{path: string, thumb_path: string, width: int, height: int}
     *
     * @throws \InvalidArgumentException bila file bukan gambar yang bisa diproses
     */
    public function store(string $source, string $directory, ?string $name = null): array
    {
        $image = UprightImage::fromFile($source, $name);
        $full = $this->fit($image, self::MAX_SIDE);
        $thumb = $this->fit($full, self::THUMB_SIDE);
        unset($image); // lepas memori GD foto asli secepatnya

        // Nama acak: foto album draft/terjadwal sudah ada di disk publik, jangan sampai bisa ditebak.
        $file = Str::lower(Str::random(24)).'.jpg';
        $stored = [
            'path' => "{$directory}/{$file}",
            'thumb_path' => "{$directory}/thumb/{$file}",
            'width' => imagesx($full),
            'height' => imagesy($full),
        ];

        $disk = Storage::disk(Album::DISK);
        $disk->put($stored['path'], UprightImage::toJpeg($full, 82), 'public');
        $disk->put($stored['thumb_path'], UprightImage::toJpeg($thumb, 78), 'public');

        return $stored;
    }

    /** Perkecil supaya sisi terpanjang ≤ $maxSide (tidak memperbesar foto kecil). */
    private function fit(GdImage $image, int $maxSide): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxSide / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = UprightImage::canvas($targetWidth, $targetHeight); // PNG transparan → latar putih
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $canvas;
    }
}
