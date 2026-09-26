<?php

namespace App\Support\BoardRoster;

/**
 * Hasil parsing file daftar pengurus satu periode (lihat RosterParser).
 */
final readonly class Roster
{
    /**
     * @param  array<string, string|null>  $divisions  nama divisi => deskripsi (urutan = urutan di file)
     * @param  list<RosterMember>  $members
     */
    public function __construct(
        public string $period,
        public array $divisions,
        public array $members,
    ) {}
}
