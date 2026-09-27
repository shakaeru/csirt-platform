<?php

namespace Tests\Concerns;

use GdImage;

/** Gambar uji buatan GD — tanpa file foto sungguhan (data pribadi) di repo. */
trait CreatesTestImages
{
    /** JPEG kiri merah / kanan biru dengan segmen APP1 EXIF berisi tag Orientation. */
    protected function jpegWithOrientation(int $width, int $height, int $orientation): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, intdiv($width, 2) - 1, $height - 1, imagecolorallocate($image, 255, 0, 0));
        imagefilledrectangle($image, intdiv($width, 2), 0, $width - 1, $height - 1, imagecolorallocate($image, 0, 0, 255));
        ob_start();
        imagejpeg($image, null, 95);
        $jpeg = ob_get_clean();

        // TIFF little-endian: header + IFD0 berisi satu entri Orientation (0x0112, SHORT).
        $tiff = "II*\x00".pack('V', 8).pack('v', 1).pack('vvVv', 0x0112, 3, 1, $orientation)."\x00\x00".pack('V', 0);
        $app1 = "\xFF\xE1".pack('n', 2 + 6 + strlen($tiff))."Exif\x00\x00".$tiff;

        return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
    }

    /** JPEG polos berukuran tertentu (isi gambar tidak penting, hanya dimensinya). */
    protected function plainJpeg(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 18, 96, 165));
        ob_start();
        imagejpeg($image, null, 85);

        return (string) ob_get_clean();
    }

    protected function dominant(GdImage $image, int $x, int $y): string
    {
        $rgb = imagecolorat($image, $x, $y);

        return (($rgb >> 16) & 0xFF) > ($rgb & 0xFF) ? 'merah' : 'biru';
    }
}
