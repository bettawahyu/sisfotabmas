<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One node of the science classification: rumpun, pohon or cabang ilmu.
 */
#[Fillable(['kode', 'nama', 'tingkat', 'induk_id', 'is_active'])]
class RumpunIlmu extends Model
{
    public const RUMPUN = 'rumpun';

    public const POHON = 'pohon';

    public const CABANG = 'cabang';

    protected $table = 'rumpun_ilmu';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<RumpunIlmu, $this>
     */
    public function induk(): BelongsTo
    {
        return $this->belongsTo(self::class, 'induk_id');
    }

    /**
     * @return HasMany<RumpunIlmu, $this>
     */
    public function turunan(): HasMany
    {
        return $this->hasMany(self::class, 'induk_id');
    }

    /**
     * @param  Builder<RumpunIlmu>  $query
     */
    public function scopeCabangAktif(Builder $query): void
    {
        $query->where('tingkat', self::CABANG)->where('is_active', true);
    }

    /**
     * The full path, for example "Ilmu Teknik › Teknik Elektro › Teknik Telekomunikasi".
     */
    public function jalur(): string
    {
        return collect([$this->induk?->induk?->nama, $this->induk?->nama, $this->nama])->filter()->implode(' › ');
    }
}
