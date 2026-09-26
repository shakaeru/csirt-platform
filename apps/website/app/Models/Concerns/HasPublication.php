<?php

namespace App\Models\Concerns;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Status terbit dari kolom published_at (dipakai Post, Album, Achievement): null = draft, masa depan =
 * terjadwal, sudah lewat = terbit. Tanpa cron — yang terjadwal tampil sendiri begitu waktunya lewat.
 */
trait HasPublication
{
    protected function initializeHasPublication(): void
    {
        $this->mergeCasts(['published_at' => 'datetime']);
    }

    /** Hanya yang tanggal terbitnya sudah lewat — draft dan terjadwal tidak tampil. */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /** Untuk filter status di tabel admin. */
    #[Scope]
    protected function wherePublicationStatus(Builder $query, PublicationStatus $status): void
    {
        match ($status) {
            PublicationStatus::Draft => $query->whereNull('published_at'),
            PublicationStatus::Scheduled => $query->where('published_at', '>', now()),
            PublicationStatus::Published => $query->published(),
        };
    }

    public function status(): PublicationStatus
    {
        return match (true) {
            $this->published_at === null => PublicationStatus::Draft,
            $this->published_at->isFuture() => PublicationStatus::Scheduled,
            default => PublicationStatus::Published,
        };
    }

    public function isPublished(): bool
    {
        return $this->status() === PublicationStatus::Published;
    }

    /**
     * Tanggal terbit untuk ditampilkan: zona waktu tampilan (WIB) dan bahasa Indonesia, mis. "12 Oktober 2026".
     *
     * @return Attribute<string|null, never>
     */
    protected function publishedDate(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->published_at
            ?->timezone(config('csirt.timezone'))
            ->locale('id')
            ->translatedFormat('j F Y'));
    }
}
