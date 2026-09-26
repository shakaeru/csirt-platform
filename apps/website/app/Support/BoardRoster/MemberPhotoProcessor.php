<?php

namespace App\Support\BoardRoster;

use App\Models\BoardMember;
use App\Support\Images\UprightImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menyiapkan foto anggota dari file mentah untuk perintah struktur:import — setara dengan yang
 * dilakukan FileUpload Filament di browser: dipotong persegi di tengah dan diperkecil ke 600×600.
 * Foto diluruskan (EXIF) dan di-encode ulang ke JPEG lewat UprightImage, jadi metadata (termasuk
 * lokasi GPS dari kamera ponsel) terbuang.
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
        $image = UprightImage::fromFile($source);

        $width = imagesx($image);
        $height = imagesy($image);
        $side = min($width, $height);
        $target = min(self::SIZE, $side); // tidak memperbesar foto kecil

        $square = UprightImage::canvas($target, $target);
        imagecopyresampled($square, $image, 0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2), $target, $target, $side, $side);

        $path = 'pengurus/'.$slug.'-'.Str::lower(Str::random(8)).'.jpg';
        Storage::disk(BoardMember::PHOTO_DISK)->put($path, UprightImage::toJpeg($square), 'public');

        return $path;
    }
}
