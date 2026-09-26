<?php

namespace App\Support\BoardRoster;

use App\Enums\BoardSection;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Parser file .txt daftar pengurus. Format:
 *
 *     Periode: 2026/2027
 *
 *     Pembina:
 *     Nama Lengkap — Jabatan
 *
 *     Divisi Humas:
 *     Deskripsi divisi (opsional, baris tanpa pemisah).
 *     Nama Lengkap — Kepala Divisi
 *
 * Bagian: Pembina, Presidium, Pengurus Inti, atau "Divisi <nama>". Pemisah nama–jabatan:
 * "—", "–", atau "-" yang diapit spasi. Baris kosong dan baris diawali "#" diabaikan, begitu
 * juga judul sebelum bagian pertama. Parser sengaja ketat: baris yang tidak dikenali ditolak
 * dengan nomor barisnya, bukan ditebak.
 */
final class RosterParser
{
    private const SEPARATOR = '/\s+[—–-]\s+/u';

    /** Batas panjang agar baris deskripsi yang kebetulan memuat " - " tidak terbaca sebagai anggota. */
    private const MAX_NAME_LENGTH = 100;

    private const MAX_POSITION_LENGTH = 60;

    public function parse(string $text): Roster
    {
        $period = null;
        $section = null;
        $division = null;
        $divisions = [];
        $members = [];
        $orderInGroup = [];
        $lineOfSlug = [];

        foreach (preg_split('/\R/u', $text) as $index => $raw) {
            $line = trim($raw);
            $number = $index + 1;

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^Periode\s*:\s*(.+)$/iu', $line, $match)) {
                $period = trim($match[1]);

                continue;
            }

            if (str_ends_with($line, ':')) {
                [$section, $division] = $this->section(trim(rtrim($line, ':')), $number);
                if ($division !== null) {
                    $divisions[$division] ??= null;
                }

                continue;
            }

            if ($section === null) {
                continue; // judul dokumen sebelum bagian pertama
            }

            $member = $this->member($line);
            if ($member !== null) {
                [$name, $position] = $member;
                $slug = Str::slug($name);
                if (isset($lineOfSlug[$slug])) {
                    throw new InvalidArgumentException("Baris {$number}: \"{$name}\" sama dengan nama di baris {$lineOfSlug[$slug]} — nama harus unik dalam satu periode.");
                }
                $lineOfSlug[$slug] = $number;

                $group = $section->value.'|'.$division;
                $orderInGroup[$group] = ($orderInGroup[$group] ?? 0) + 1;
                $members[] = new RosterMember($name, $position, $section, $division, $orderInGroup[$group]);

                continue;
            }

            if ($section === BoardSection::Divisi) {
                $divisions[$division] = trim(($divisions[$division] ?? '').' '.$line);

                continue;
            }

            throw new InvalidArgumentException("Baris {$number} tidak dikenali (format anggota: \"Nama — Jabatan\"): {$line}");
        }

        if ($period === null) {
            throw new InvalidArgumentException('Baris "Periode: ..." tidak ditemukan.');
        }

        if ($members === []) {
            throw new InvalidArgumentException('Tidak ada anggota yang terbaca.');
        }

        return new Roster($period, $divisions, $members);
    }

    /**
     * @return array{0: BoardSection, 1: string|null}
     */
    private function section(string $header, int $number): array
    {
        $normalized = Str::lower($header);

        return match (true) {
            $normalized === 'pembina' => [BoardSection::Pembina, null],
            $normalized === 'presidium' => [BoardSection::Presidium, null],
            in_array($normalized, ['pengurus inti', 'inti'], true) => [BoardSection::Inti, null],
            str_starts_with($normalized, 'divisi ') => [BoardSection::Divisi, $header],
            default => throw new InvalidArgumentException("Baris {$number}: bagian \"{$header}\" tidak dikenal — gunakan Pembina, Presidium, Pengurus Inti, atau Divisi <nama>."),
        };
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function member(string $line): ?array
    {
        $parts = preg_split(self::SEPARATOR, $line, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$name, $position] = array_map('trim', $parts);
        if ($name === '' || $position === '' || mb_strlen($name) > self::MAX_NAME_LENGTH || mb_strlen($position) > self::MAX_POSITION_LENGTH) {
            return null;
        }

        return [$name, $position];
    }
}
