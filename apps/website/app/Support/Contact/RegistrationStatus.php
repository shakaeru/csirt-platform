<?php

namespace App\Support\Contact;

use App\Models\Setting;
use Carbon\CarbonImmutable;

/**
 * Status pendaftaran anggota di halaman Kontak, diatur admin di panel ("Kontak & Pendaftaran").
 * Formulirnya Google Form (jawaban ke Google Spreadsheet) — website hanya menautkan, tidak
 * menyimpan data pendaftar. Keputusan dan alasannya: docs/PRD.md § 4.1.
 */
final readonly class RegistrationStatus
{
    public const SETTING_KEY = 'registration';

    public function __construct(
        public bool $enabled, // saklar "Pendaftaran dibuka" di admin
        public ?string $formUrl,
        public ?CarbonImmutable $closesOn, // hari terakhir pendaftaran (WIB), inklusif
    ) {}

    public static function current(): self
    {
        $data = Setting::get(self::SETTING_KEY, []);

        return new self(
            enabled: (bool) ($data['enabled'] ?? false),
            formUrl: filled($data['form_url'] ?? null) ? $data['form_url'] : null,
            closesOn: filled($data['closes_on'] ?? null)
                ? CarbonImmutable::parse($data['closes_on'], config('csirt.timezone'))->locale('id')
                : null,
        );
    }

    public function isOpen(): bool
    {
        return $this->enabled && $this->formUrl !== null && ! $this->hasEnded();
    }

    /** Saklar masih "dibuka" tetapi hari terakhirnya (WIB) sudah lewat — tampil sebagai ditutup. */
    public function hasEnded(): bool
    {
        return $this->enabled
            && $this->closesOn !== null
            && now(config('csirt.timezone'))->toDateString() > $this->closesOn->toDateString();
    }

    /** Domain tujuan formulir, ditampilkan di bawah tombol supaya pendaftar tahu ke mana diarahkan. */
    public function formHost(): ?string
    {
        return $this->formUrl ? parse_url($this->formUrl, PHP_URL_HOST) : null;
    }
}
