<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Reference data is switched off rather than deleted, so older records that
 * point at it stay intact while new forms stop offering it.
 */
trait BisaNonaktif
{
    public function initializeBisaNonaktif(): void
    {
        $this->mergeCasts(['is_active' => 'boolean']);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeAktif(Builder $query): void
    {
        $query->where($this->qualifyColumn('is_active'), true);
    }

    /**
     * Active rows, plus the one a form already holds so editing never drops it.
     *
     * @param  Builder<static>  $query
     */
    public function scopeAktifAtau(Builder $query, mixed $id): void
    {
        $query->where(fn (Builder $q) => $q->where($this->qualifyColumn('is_active'), true)
            ->when($id, fn (Builder $q) => $q->orWhere($this->qualifyColumn($this->getKeyName()), $id)));
    }
}
