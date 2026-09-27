<?php

namespace App\Support\Contact;

use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Contact person di halaman Kontak. Diisi admin di panel ("Kontak & Pendaftaran"), disimpan di
 * tabel settings — nomor HP pengurus sengaja tidak ada di repo (repo publik, dan contact person
 * berganti tiap periode kepengurusan).
 */
final readonly class ContactPerson
{
    public const SETTING_KEY = 'contact_people';

    /** Nomor untuk tautan wa.me/tel:, mis. "6281234567890"; null bila nomornya tidak valid. */
    public ?string $whatsappNumber;

    public function __construct(
        public string $name,
        public string $position,
        public string $phone, // tampil seperti diketik admin, mis. "0812-3456-7890"
    ) {
        $this->whatsappNumber = self::normalizePhone($phone);
    }

    /**
     * @return Collection<int, self>
     */
    public static function all(): Collection
    {
        return collect(Setting::get(self::SETTING_KEY, []))
            ->map(fn (array $item): self => new self($item['name'], $item['position'], $item['phone']))
            ->values();
    }

    /**
     * Nomor seluler Indonesia ("0812-3456-7890", "+62 812 3456 7890", …) → "6281234567890",
     * atau null bila bukan nomor seluler Indonesia.
     */
    public static function normalizePhone(string $phone): ?string
    {
        if (! preg_match('/^\+?[\d\s().-]+$/', trim($phone))) {
            return null;
        }

        $digits = (string) preg_replace('/\D/', '', $phone);
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return preg_match('/^628\d{7,11}$/', $digits) ? $digits : null;
    }

    public function whatsappUrl(): ?string
    {
        return $this->whatsappNumber ? 'https://wa.me/'.$this->whatsappNumber : null;
    }

    public function telUrl(): ?string
    {
        return $this->whatsappNumber ? 'tel:+'.$this->whatsappNumber : null;
    }
}
