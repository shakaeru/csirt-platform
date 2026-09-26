<?php

namespace App\Models;

use Database\Factories\BoardPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'starts_on', 'ends_on', 'is_active'])]
class BoardPeriod extends Model
{
    /** @use HasFactory<BoardPeriodFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // Hanya satu periode aktif: mengaktifkan satu periode menonaktifkan yang lain.
        static::saved(function (BoardPeriod $period): void {
            if ($period->is_active) {
                static::query()
                    ->whereKeyNot($period->getKey())
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @return HasMany<BoardMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(BoardMember::class);
    }
}
