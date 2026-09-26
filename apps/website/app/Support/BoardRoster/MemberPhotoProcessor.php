<?php

namespace App\Support\BoardRoster;

use App\Models\BoardMember;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Menyiapkan foto anggota dari file mentah untuk perintah struktur:import — setara dengan yang
 * dilakukan FileUpload Filament di browser: dipotong persegi di tengah dan diperkecil ke 600×600.
 * Foto di-encode ulang ke JPEG, jadi metadata (termasuk lokasi GPS dari kamera ponsel) terbuang.
 */
final class MemberPhotoProcessor
{
    public const SIZE = 600;

    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Petakan file foto di folder ke slug nama (nama file tanpa ekstensi).
     *
     * @return array{photos: array<string, string>, ignored: list<string>} photos: slug => path;
     *                                                                      ignored: file berformat tidak didukung
     */
    public function index(string $directory): array
    {
        $photos = [];
        $ignored = [];

        foreach (glob(rtrim($directory, '/').'/*') ?: [] as $path) {
            if (! is_file($path)) {
                continue;
            }

            $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));
            if (! in_array($extension, self::EXTENSIONS, true)) {
                $ignored[] = basename($path);

                continue;
            }

            $photos[Str::lower(pathinfo($path, PATHINFO_FILENAME))] ??= $path;
        }

        return ['photos' => $photos, 'ignored' => $ignored];
    }

    /**
     * Proses satu foto dan simpan ke disk foto anggota. Mengembalikan path relatif untuk photo_path.
     */
    public function store(string $source, string $slug): string
    {
        $info = @getimagesize($source);
        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new InvalidArgumentException(basename($source).': bukan gambar JPG/PNG/WebP yang valid.');
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
        };
        if (! $image instanceof GdImage) {
            throw new InvalidArgumentException(basename($source).': gambar gagal dibaca.');
        }

        if ($info[2] === IMAGETYPE_JPEG) {
            $image = $this->applyExifOrientation($image, $source);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $side = min($width, $height);
        $target = min(self::SIZE, $side); // tidak memperbesar foto kecil

        $square = imagecreatetruecolor($target, $target);
        imagefill($square, 0, 0, imagecolorallocate($square, 255, 255, 255)); // latar putih untuk PNG transparan
        imagecopyresampled($square, $image, 0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2), $target, $target, $side, $side);

        ob_start();
        imagejpeg($square, null, 85);
        $jpeg = ob_get_clean();

        $path = 'pengurus/'.$slug.'-'.Str::lower(Str::random(8)).'.jpg';
        Storage::disk(BoardMember::PHOTO_DISK)->put($path, $jpeg, 'public');

        return $path;
    }

    /**
     * Foto kamera ponsel sering disimpan miring dengan tag Orientation EXIF; GD mengabaikan tag itu.
     */
    private function applyExifOrientation(GdImage $image, string $source): GdImage
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
