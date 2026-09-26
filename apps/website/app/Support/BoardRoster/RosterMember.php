<?php

namespace App\Support\BoardRoster;

use App\Enums\BoardSection;
use Illuminate\Support\Str;

final readonly class RosterMember
{
    public function __construct(
        public string $name,
        public string $position,
        public BoardSection $section,
        public ?string $division,
        public int $sortOrder,
    ) {}

    /** Kunci pencocokan anggota antar-import dan nama file foto: "Arkan Fawwaz Safi'i" → "arkan-fawwaz-safii". */
    public function slug(): string
    {
        return Str::slug($this->name);
    }
}
